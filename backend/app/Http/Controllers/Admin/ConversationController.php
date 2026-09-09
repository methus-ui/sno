<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Models\Conversation;
use App\Models\UserInfo;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function list(Request $request)
    {
        $conversations = Conversation::with(['sender', 'receiver', 'last_message', 'assignedAdmin'])->WhereUserType('admin');
        
        if($request->query('key')) {
            $key = explode(' ', $request->get('key'));
            $conversations = $conversations->where(function($qu) use($key){
                $qu->whereHas('sender',function($query) use($key){
                    foreach ($key as $value) {
                        $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%")->orWhere('phone', 'like', "%{$value}%");
                    }
                })
                ->orWhereHas('receiver',function($query1) use($key){
                    foreach ($key as $value) {
                        $query1->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%")->orWhere('phone', 'like', "%{$value}%");
                    }
                });
            });
        }
        
        $conversations = $conversations->orderBy('last_message_time', 'DESC')->paginate(8);

        if ($request->ajax()) {
            $view = view('admin-views.messages.data', compact('conversations'))->render();
            return response()->json(['html' => $view]);
        }

        return view('admin-views.messages.index', compact('conversations'));
    }
    
    public function checkNewMessages(Request $request)
    {
        $query = Conversation::with(['sender', 'receiver', 'last_message'])
            ->whereUserType('admin')
            ->where('unread_message_count', '>', 0);

        if ($request->has('last_checked')) {
            $query->where('last_message_time', '>', Carbon::createFromTimestamp($request->last_checked));
        }

        $conversations = $query->orderBy('last_message_time', 'DESC')->get();
        $unreadCount = $conversations->count();

        // Get the latest message details for popup
        $latestMessages = [];
        foreach ($conversations as $conv) {
            $user = $conv->sender_type == 'admin' ? $conv->receiver : $conv->sender;
            if ($user && $conv->last_message) {
                $latestMessages[] = [
                    'conversation_id' => $conv->id,
                    'sender_id' => $user->id,
                    'sender_name' => ($user->f_name ?? '') . ' ' . ($user->l_name ?? ''),
                    'sender_image' => $user->image_full_url ?? null,
                    'message' => $conv->last_message->message ?? translate('Attachment'),
                    'time' => $conv->last_message->created_at->diffForHumans(),
                ];
            }
        }

        return response()->json([
            'new_messages' => $unreadCount,
            'messages' => $latestMessages,
            'current_time' => now()->timestamp
        ]);
    }

    public function view($conversation_id, $user_id)
    {
        $conversation = Conversation::with('assignedAdmin')->find($conversation_id);
        $lastmessage = $conversation->last_message;
        
        // Assign conversation to current admin if not already assigned and not super admin
        $currentAdmin = auth('admin')->user();
        if (!$conversation->assigned_admin_id && $currentAdmin->role->name !== 'admin') {
            $conversation->assigned_admin_id = $currentAdmin->id;
            $conversation->assigned_at = now();
            $conversation->save();
        }
        
        if($lastmessage && $lastmessage->sender_id == $user_id) {
            $conversation->unread_message_count = 0;
            $conversation->save();
        }
        
        Message::where(['conversation_id' => $conversation->id])->where('sender_id', $user_id)->update(['is_seen' => 1]);
        $convs = Message::where(['conversation_id' => $conversation_id])->get();
        $receiver = UserInfo::find($user_id);
        $user = $receiver;
        
        // Get templates for dropdown
        $templates = MessageTemplate::active()->get();
        
        return response()->json([
            'view' => view('admin-views.messages.partials._conversations', compact('convs', 'user', 'receiver', 'conversation', 'templates'))->render()
        ]);
    }

    public function store(Request $request, $user_id)
    {
        if ($request->has('images')) {
            $image_name = [];
            foreach($request->images as $key => $img) {
                $name = Helpers::upload('conversation/', 'png', $img);
                array_push($image_name, ['img' => $name, 'storage' => Helpers::getDisk()]);
            }
        } else {
            $image_name = null;
            $validator = Validator::make($request->all(), [
                'reply' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => Helpers::error_processor($validator)]);
            }
        }

        $admin = Admin::find(auth('admin')->id());
        $sender = UserInfo::where('admin_id', $admin->id)->first();

        if(!$sender) {
            $sender = new UserInfo();
            $sender->admin_id = $admin->id;
            $sender->f_name = $admin->f_name;
            $sender->l_name = $admin->l_name;
            $sender->phone = $admin->phone;
            $sender->email = $admin->email;
            $sender->image = $admin->image;
            $sender->save();
        }

        // Validate sender was created/retrieved successfully
        if (!$sender || !$sender->id) {
            return response()->json([
                'errors' => [['code' => 'sender', 'message' => 'Failed to create or retrieve admin user info.']]
            ], 500);
        }

        $actualUser = User::find($user_id);
        if(!$actualUser) {
            return response()->json(['errors' => [['code' => 'user', 'message' => 'User not found.']]]);
        }
        $fcm_token = $actualUser->cm_firebase_token;
        $receiver = UserInfo::where('user_id', $actualUser->id)->first();

        if(!$receiver) {
            $receiver = new UserInfo();
            $receiver->user_id = $actualUser->id;
            $receiver->f_name = $actualUser->f_name;
            $receiver->l_name = $actualUser->l_name;
            $receiver->phone = $actualUser->phone;
            $receiver->email = $actualUser->email;
            $receiver->image = $actualUser->image;
            $receiver->save();
        }
        $user = $receiver;

        $conversation = Conversation::whereConversation($receiver->id, $sender->id)->first();

        // Verify conversation belongs to correct users
        if ($conversation) {
            $isValidConversation = (
                ($conversation->sender_id == $sender->id && $conversation->receiver_id == $receiver->id) ||
                ($conversation->sender_id == $receiver->id && $conversation->receiver_id == $sender->id)
            );

            if (!$isValidConversation) {
                // Wrong conversation returned, create new one
                $conversation = null;
            }
        }

        if(!$conversation) {
            $conversation = new Conversation;
            $conversation->sender_id = $sender->id;
            $conversation->sender_type = 'admin';
            $conversation->receiver_id = $receiver->id;
            $conversation->receiver_type = 'user';
            $conversation->last_message_time = Carbon::now()->toDateTimeString();
            $conversation->save();
            $conversation = Conversation::find($conversation->id);
        }

        DB::beginTransaction();
        try {
            $message = new Message();
        $message->conversation_id = $conversation->id;
        $message->sender_id = $sender->id;

        // Check if this is the first message from admin in this conversation
        $previousMessagesCount = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $sender->id)
            ->count();

        // Add greeting with agent name for first message
        $messageText = $request->reply;
        if ($previousMessagesCount == 0) {
            $agentName = trim($sender->f_name . ' ' . $sender->l_name);
            $greeting = "السلام علیکم (Assalamualaikum),\n\nYour support agent *{$agentName}* has been assigned to assist you.\n\n";
            $messageText = $greeting . $messageText;
        }

        $message->message = $messageText;
        
        if($image_name && count($image_name) > 0) {
            $message->file = json_encode($image_name, JSON_UNESCAPED_SLASHES);
        }
        
            if($message->save()) {
                $conversation->unread_message_count = $conversation->unread_message_count ? $conversation->unread_message_count + 1 : 1;
                $conversation->last_message_id = $message->id;
                $conversation->last_message_time = Carbon::now()->toDateTimeString();
                $conversation->save();

                DB::commit();

                $data = [
                    'title' => translate('messages.message_from_admin'),
                    'description' => $message->message ?? translate('attachment'),
                    'order_id' => '',
                    'image' => '',
                    'message' => json_encode($message),
                    'type' => 'message',
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'admin'
                ];
                if(!empty($fcm_token)) {
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'errors' => [['code' => 'message', 'message' => 'Failed to save message.']]
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return response()->json([
                'errors' => [['code' => 'message', 'message' => 'Failed to send message.']]
            ], 500);
        }

        $convs = Message::where(['conversation_id' => $conversation->id])->get();
        $templates = MessageTemplate::active()->get();
        
        return response()->json([
            'view' => view('admin-views.messages.partials._conversations', compact('convs', 'user', 'receiver', 'conversation', 'templates'))->render()
        ]);
    }

    // Template management methods
    public function getTemplates()
    {
        // Sort by most used first, then alphabetically
        $templates = MessageTemplate::active()
            ->orderBy('usage_count', 'DESC')
            ->orderBy('title', 'ASC')
            ->get();
        return response()->json(['templates' => $templates]);
    }

    public function storeTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template = MessageTemplate::create([
            'title' => $request->title,
            'content' => $request->content,
            'created_by' => auth('admin')->id(),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'template' => $template
        ]);
    }

    public function updateTemplate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template = MessageTemplate::findOrFail($id);
        $template->update([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully',
            'template' => $template
        ]);
    }

    public function deleteTemplate($id)
    {
        $template = MessageTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
    }

    public function trackTemplateUsage($id)
    {
        $admin = auth('sanctum')->user();

        // Verify admin is authenticated
        if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Find template and verify it's active
        $template = MessageTemplate::where('id', $id)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found or inactive'
            ], 404);
        }

        // Additional authorization: Only track if user can access this template
        // (for now, all active templates are accessible to all admins)
        // Future: Add template ownership/permissions

        // Increment usage count and update last used timestamp
        $template->increment('usage_count');
        $template->update(['last_used_at' => now()]);

        return response()->json([
            'success' => true,
            'usage_count' => $template->usage_count
        ]);
    }

    /**
     * ========================================
     * NEXT.JS ADMIN PANEL API ENDPOINTS
     * ========================================
     */

    /**
     * Get all customer conversations for Next.js admin panel
     * Route: GET /admin/chat/customer-conversations
     */
    public function customer_conversations(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            $search = $request->get('search');

            // Get conversations where admin is participant
            $query = Conversation::with([
                    'sender' => function($q) {
                        $q->with('user'); // Load actual User model for customer data
                    },
                    'receiver' => function($q) {
                        $q->with('user');
                    },
                    'last_message'
                ])
                ->where(function($q) {
                    // Admin conversations (receiver_type = 'admin' OR sender_type = 'admin')
                    $q->where('receiver_type', 'admin')
                      ->orWhere('sender_type', 'admin');
                })
                ->where(function($q) {
                    // Must involve a customer
                    $q->where('sender_type', 'customer')
                      ->orWhere('receiver_type', 'customer');
                });

            // Search by customer name/phone
            if ($search) {
                $query->whereHas('sender.user', function($q) use ($search) {
                    $q->where('f_name', 'LIKE', "%{$search}%")
                      ->orWhere('l_name', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%");
                })->orWhereHas('receiver.user', function($q) use ($search) {
                    $q->where('f_name', 'LIKE', "%{$search}%")
                      ->orWhere('l_name', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            }

            $conversations = $query->orderBy('last_message_time', 'DESC')
                ->paginate($perPage, ['*'], 'page', $page);

            // Transform to Next.js expected format
            $transformedConversations = $conversations->getCollection()->map(function($conv) {
                // Get customer info (either sender or receiver)
                $customer = $conv->sender_type === 'customer' ? $conv->sender : $conv->receiver;
                $customerUser = $customer?->user;

                return [
                    'id' => $conv->id,
                    'type' => 'customer',
                    'participant' => [
                        'id' => $customerUser?->id ?? 0,
                        'name' => ($customerUser?->f_name ?? '') . ' ' . ($customerUser?->l_name ?? ''),
                        'email' => $customerUser?->email ?? '',
                        'image' => $customerUser?->image_full_url ?? asset('public/assets/admin/img/default-avatar.png'),
                        'phone' => $customerUser?->phone ?? '',
                    ],
                    'last_message' => $conv->last_message ? [
                        'id' => $conv->last_message->id,
                        'text' => $conv->last_message->message ?? '',
                        'created_at' => $conv->last_message->created_at->toISOString(),
                        'is_mine' => $conv->last_message->sender_id != $customer?->id, // Is admin's message
                    ] : null,
                    'unread_count' => $conv->unread_message_count ?? 0,
                    'last_message_time' => $conv->last_message_time ? Carbon::parse($conv->last_message_time)->toISOString() : null,
                    'assigned_admin' => null, // Future feature
                ];
            });

            return response()->json([
                'success' => true,
                'conversations' => $transformedConversations,
                'pagination' => [
                    'current_page' => $conversations->currentPage(),
                    'last_page' => $conversations->lastPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Customer conversations error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get messages for a customer conversation (Next.js format)
     * Route: GET /admin/chat/customer-messages/{conversation_id}
     */
    public function customer_messages($conversation_id, Request $request)
    {
        try {
            $perPage = $request->get('per_page', 50);
            $page = $request->get('page', 1);

            $conversation = Conversation::with(['sender', 'receiver'])->find($conversation_id);

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            // Verify this is a customer-admin conversation
            if (!($conversation->sender_type === 'customer' || $conversation->receiver_type === 'customer')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not a customer conversation',
                ], 403);
            }

            // Get messages
            $messages = Message::where('conversation_id', $conversation_id)
                ->with(['sender.user', 'sender.admin'])
                ->orderBy('created_at', 'ASC')
                ->paginate($perPage, ['*'], 'page', $page);

            // Get customer info
            $customer = $conversation->sender_type === 'customer' ? $conversation->sender : $conversation->receiver;

            // Transform messages
            $transformedMessages = $messages->getCollection()->map(function($msg) use ($customer) {
                $sender = $msg->sender;
                $isCustomer = $sender->id === $customer->id;

                // Parse files if present
                $files = [];
                if ($msg->file) {
                    $fileData = json_decode($msg->file, true);
                    if (is_array($fileData)) {
                        foreach ($fileData as $file) {
                            $files[] = [
                                'name' => $file['img'] ?? '',
                                'url' => asset('storage/app/public/conversation/' . ($file['img'] ?? '')),
                                'type' => 'image',
                                'size' => 0,
                            ];
                        }
                    }
                }

                return [
                    'id' => $msg->id,
                    'conversation_id' => $msg->conversation_id,
                    'text' => $msg->message ?? '',
                    'files' => $files,
                    'is_mine' => !$isCustomer, // Admin's messages
                    'sender' => [
                        'id' => $sender->id,
                        'name' => $isCustomer
                            ? ($sender->user?->f_name ?? '') . ' ' . ($sender->user?->l_name ?? '')
                            : ($sender->admin?->f_name ?? 'Admin') . ' ' . ($sender->admin?->l_name ?? ''),
                        'image' => $isCustomer
                            ? ($sender->user?->image_full_url ?? asset('public/assets/admin/img/default-avatar.png'))
                            : ($sender->admin?->image_full_url ?? asset('public/assets/admin/img/default-avatar.png')),
                    ],
                    'is_seen' => (bool)$msg->is_seen,
                    'created_at' => $msg->created_at->toISOString(),
                ];
            });

            // Mark all customer messages as seen
            Message::where('conversation_id', $conversation_id)
                ->where('sender_id', $customer->id)
                ->where('is_seen', 0)
                ->update(['is_seen' => 1]);

            // Reset unread count
            $conversation->update(['unread_message_count' => 0]);

            return response()->json([
                'success' => true,
                'messages' => $transformedMessages,
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Customer messages error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load messages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send message to customer (Next.js format)
     * Route: POST /admin/chat/send-customer-message/{user_id}
     */
    public function send_customer_message(Request $request, $user_id)
    {
        $validator = Validator::make($request->all(), [
            'reply' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get customer UserInfo
            $customerInfo = UserInfo::where('user_id', $user_id)->first();

            if (!$customerInfo) {
                // Create customer UserInfo if doesn't exist
                $customer = User::find($user_id);
                if (!$customer) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Customer not found',
                    ], 404);
                }

                $customerInfo = new UserInfo();
                $customerInfo->user_id = $customer->id;
                $customerInfo->f_name = $customer->f_name;
                $customerInfo->l_name = $customer->l_name;
                $customerInfo->phone = $customer->phone;
                $customerInfo->email = $customer->email;
                $customerInfo->image = $customer->image;
                $customerInfo->save();
            }

            // Get or create admin UserInfo
            $admin = auth('admin')->user();
            $adminInfo = UserInfo::where('admin_id', $admin->id)->first();

            if (!$adminInfo) {
                $adminInfo = new UserInfo();
                $adminInfo->admin_id = $admin->id;
                $adminInfo->f_name = $admin->f_name;
                $adminInfo->l_name = $admin->l_name;
                $adminInfo->phone = $admin->phone;
                $adminInfo->email = $admin->email;
                $adminInfo->image = $admin->image;
                $adminInfo->save();
            }

            // Find or create conversation
            $conversation = Conversation::WhereConversation($adminInfo->id, $customerInfo->id)
                ->lockForUpdate()
                ->first();

            if (!$conversation) {
                $conversation = new Conversation();
                $conversation->sender_id = $adminInfo->id;
                $conversation->sender_type = 'admin';
                $conversation->receiver_id = $customerInfo->id;
                $conversation->receiver_type = 'customer';
                $conversation->unread_message_count = 0;
                $conversation->last_message_time = Carbon::now()->toDateTimeString();
                $conversation->auto_response_sent = true; // Admin already responding
                $conversation->save();
            }

            // Handle image uploads
            $image_name = null;
            if ($request->hasFile('images')) {
                $images = [];
                foreach ($request->file('images') as $img) {
                    $name = Helpers::upload('conversation/', 'png', $img);
                    $images[] = ['img' => $name, 'storage' => Helpers::getDisk()];
                }
                $image_name = json_encode($images, JSON_UNESCAPED_SLASHES);
            }

            // Create message
            $message = new Message();
            $message->conversation_id = $conversation->id;
            $message->sender_id = $adminInfo->id;
            $message->message = $request->reply;
            $message->file = $image_name;
            $message->save();

            // Update conversation
            $conversation->last_message_id = $message->id;
            $conversation->last_message_time = Carbon::now()->toDateTimeString();
            $conversation->unread_message_count = ($conversation->unread_message_count ?? 0) + 1;
            $conversation->save();

            // Send FCM notification to customer
            $customer = User::find($user_id);
            if ($customer && $customer->cm_firebase_token) {
                $data = [
                    'title' => translate('messages.message_from_admin'),
                    'description' => $request->reply,
                    'order_id' => '',
                    'image' => '',
                    'message' => json_encode($message),
                    'type' => 'message',
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'admin',
                ];
                Helpers::send_push_notif_to_device($customer->cm_firebase_token, $data);
            }

            DB::commit();

            // Return transformed message
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'text' => $message->message,
                    'files' => $image_name ? json_decode($image_name, true) : [],
                    'is_mine' => true,
                    'sender' => [
                        'id' => $adminInfo->id,
                        'name' => $admin->f_name . ' ' . $admin->l_name,
                        'image' => $admin->image_full_url ?? asset('public/assets/admin/img/default-avatar.png'),
                    ],
                    'is_seen' => false,
                    'created_at' => $message->created_at->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Send customer message error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check for new customer messages (polling endpoint for Next.js)
     * Route: GET /admin/chat/check-new-messages
     */
    public function check_new_customer_messages(Request $request)
    {
        try {
            $lastChecked = $request->get('last_checked');

            $query = Conversation::with(['sender.user', 'receiver.user', 'last_message'])
                ->where(function($q) {
                    $q->where('receiver_type', 'admin')->orWhere('sender_type', 'admin');
                })
                ->where(function($q) {
                    $q->where('sender_type', 'customer')->orWhere('receiver_type', 'customer');
                })
                ->where('unread_message_count', '>', 0);

            if ($lastChecked) {
                $query->where('last_message_time', '>', Carbon::createFromTimestamp($lastChecked));
            }

            $conversations = $query->orderBy('last_message_time', 'DESC')->get();

            $transformedConversations = $conversations->map(function($conv) {
                $customer = $conv->sender_type === 'customer' ? $conv->sender : $conv->receiver;
                $customerUser = $customer?->user;

                return [
                    'id' => $conv->id,
                    'type' => 'customer',
                    'participant' => [
                        'id' => $customerUser?->id ?? 0,
                        'name' => ($customerUser?->f_name ?? '') . ' ' . ($customerUser?->l_name ?? ''),
                    ],
                    'unread_count' => $conv->unread_message_count,
                    'last_message_time' => $conv->last_message_time,
                ];
            });

            return response()->json([
                'success' => true,
                'new_messages' => $conversations->sum('unread_message_count'),
                'conversations' => $transformedConversations,
                'current_time' => time(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Check new customer messages error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check new messages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
