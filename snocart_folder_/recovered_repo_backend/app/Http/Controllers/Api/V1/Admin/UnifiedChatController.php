<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\UserInfo;
use App\Models\User;
use App\Models\Admin;
use App\CentralLogics\Helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Unified Chat Controller - Handles both employee and customer conversations
 * Provides API endpoints for React frontend
 */
class UnifiedChatController extends Controller
{
    /**
     * Get customer conversations for admin
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerConversations(Request $request)
    {
        $admin = $request->user();

        // Allow super admins (role_id = 1) or approved employees (status = 1)
        if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only approved admin employees can access chat.',
            ], 403);
        }

        // Get conversations where admin is participant
        $conversations = Conversation::with(['sender', 'receiver', 'last_message', 'assignedAdmin'])
            ->whereUserType('admin')
            ->when($request->search, function($query) use ($request) {
                $key = explode(' ', $request->search);
                $query->where(function($qu) use($key){
                    $qu->whereHas('sender',function($q) use($key){
                        foreach ($key as $value) {
                            $q->where('f_name', 'like', "%{$value}%")
                              ->orWhere('l_name', 'like', "%{$value}%")
                              ->orWhere('phone', 'like', "%{$value}%");
                        }
                    })->orWhereHas('receiver',function($q) use($key){
                        foreach ($key as $value) {
                            $q->where('f_name', 'like', "%{$value}%")
                              ->orWhere('l_name', 'like', "%{$value}%")
                              ->orWhere('phone', 'like', "%{$value}%");
                        }
                    });
                });
            })
            ->orderBy('last_message_time', 'DESC')
            ->paginate(20);

        // Get admin UserInfo once (outside map for efficiency)
        $senderUserInfo = UserInfo::where('admin_id', $admin->id)->first();

        // Format conversations for React frontend
        $formattedConversations = $conversations->map(function($conv) use ($admin, $senderUserInfo) {
            // Determine who the participant is (the other person in conversation)
            $isAdminSender = $conv->sender_id == $senderUserInfo?->id;
            $participant = $isAdminSender ? $conv->receiver : $conv->sender;

            // Skip conversations where participant is null (corrupted data)
            if (!$participant) {
                return null;
            }

            // Get actual User record if customer
            $customer = null;
            if ($participant->user_id) {
                $customer = User::find($participant->user_id);
            }

            return [
                'id' => $conv->id,
                'type' => 'customer',
                'participant' => [
                    'id' => $participant->user_id ?? $participant->id,
                    'name' => trim(($participant->f_name ?? '') . ' ' . ($participant->l_name ?? '')),
                    'email' => $participant->email ?? '',
                    'phone' => $participant->phone ?? '',
                    'image' => $participant->image_full_url ?? null,
                ],
                'last_message' => $conv->last_message ? [
                    'id' => $conv->last_message->id,
                    'text' => $conv->last_message->message ?? translate('Attachment'),
                    'created_at' => $conv->last_message->created_at->toIso8601String(),
                    'is_mine' => $conv->last_message->sender_id == $senderUserInfo?->id,
                ] : null,
                'unread_count' => $conv->unread_message_count ?? 0,
                'last_message_time' => $conv->last_message_time ?
                    Carbon::parse($conv->last_message_time)->toIso8601String() : null,
                'assigned_admin' => $conv->assignedAdmin ? [
                    'id' => $conv->assignedAdmin->id,
                    'name' => trim($conv->assignedAdmin->f_name . ' ' . $conv->assignedAdmin->l_name),
                ] : null,
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'conversations' => $formattedConversations,
            'pagination' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ]
        ]);
    }

    /**
     * Get messages in a customer conversation
     *
     * @param Request $request
     * @param int $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerMessages(Request $request, $conversationId)
    {
        $admin = $request->user();

        // Allow super admins (role_id = 1) or approved employees (status = 1)
        if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only approved admin employees can access chat.',
            ], 403);
        }

        $conversation = Conversation::with('assignedAdmin')->find($conversationId);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found',
            ], 404);
        }

        // Assign conversation to current admin if not already assigned and not super admin
        if (!$conversation->assigned_admin_id && ($admin->role->name ?? null) !== 'admin') {
            $conversation->assigned_admin_id = $admin->id;
            $conversation->assigned_at = now();
            $conversation->save();
        }

        // Get sender UserInfo
        $senderUserInfo = UserInfo::where('admin_id', $admin->id)->first();

        // Mark messages as read and reset unread count
        if ($conversation->last_message && $conversation->last_message->sender_id != $senderUserInfo?->id) {
            $conversation->unread_message_count = 0;
            $conversation->save();

            Message::where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $senderUserInfo?->id)
                ->update(['is_seen' => 1]);
        }

        // Get messages
        $messages = Message::where('conversation_id', $conversationId)
            ->with('sender')
            ->orderBy('created_at', 'ASC')
            ->paginate(50);

        // Format messages for React frontend
        $formattedMessages = $messages->map(function($msg) use ($senderUserInfo) {
            $files = [];
            if ($msg->file) {
                $fileArray = json_decode($msg->file, true) ?? [];
                foreach ($fileArray as $file) {
                    $fileData = is_array($file) ? $file : ['img' => $file, 'storage' => 'public'];
                    $files[] = [
                        'name' => $fileData['img'] ?? '',
                        'url' => Helpers::get_full_url('conversation', $fileData['img'] ?? '', $fileData['storage'] ?? 'public'),
                        'type' => 'image',
                        'size' => 0,
                    ];
                }
            }

            return [
                'id' => $msg->id,
                'conversation_id' => $msg->conversation_id,
                'text' => $msg->message ?? '',
                'files' => $files,
                'is_mine' => $msg->sender_id == $senderUserInfo?->id,
                'sender' => [
                    'id' => $msg->sender->id,
                    'name' => trim(($msg->sender->f_name ?? '') . ' ' . ($msg->sender->l_name ?? '')),
                    'image' => $msg->sender->image_full_url ?? null,
                ],
                'is_seen' => $msg->is_seen == 1,
                'created_at' => $msg->created_at->toIso8601String(),
            ];
        });

        // Get customer's recent orders
        $customerOrders = [];
        $customer = null;

        // Get customer from conversation
        if ($conversation->sender_type == 'customer') {
            $customerInfo = $conversation->sender;
        } else {
            $customerInfo = $conversation->receiver;
        }

        if ($customerInfo && $customerInfo->user_id) {
            $customer = User::find($customerInfo->user_id);

            if ($customer) {
                // Get customer's 5 most recent orders
                $orders = \App\Models\Order::where('user_id', $customer->id)
                    ->orderBy('created_at', 'DESC')
                    ->limit(5)
                    ->get(['id', 'order_status', 'order_amount', 'created_at', 'schedule_at']);

                $customerOrders = $orders->map(function($order) {
                    return [
                        'id' => $order->id,
                        'status' => $order->order_status,
                        'amount' => $order->order_amount,
                        'created_at' => Carbon::parse($order->created_at)->toIso8601String(),
                        'scheduled_at' => $order->schedule_at ?
                            Carbon::parse($order->schedule_at)->toIso8601String() : null,
                    ];
                });
            }
        }

        return response()->json([
            'success' => true,
            'messages' => $formattedMessages,
            'customer_orders' => $customerOrders,
            'customer_info' => $customer ? [
                'id' => $customer->id,
                'name' => trim($customer->f_name . ' ' . $customer->l_name),
                'email' => $customer->email,
                'phone' => $customer->phone,
                'image' => $customer->image_full_url ?? null,
            ] : null,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ]
        ]);
    }

    /**
     * Send message to customer
     *
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendCustomerMessage(Request $request, $userId)
    {
        $admin = $request->user();

        // Allow super admins (role_id = 1) or approved employees (status = 1)
        if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only approved admin employees can access chat.',
            ], 403);
        }

        // Validate with length limit
        $validator = Validator::make($request->all(), [
            'reply' => 'required_without:images|string|max:5000', // Max 5000 characters
            'images.*' => 'nullable|image|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Sanitize message content to prevent XSS
        $messageText = strip_tags($request->reply);
        $messageText = trim($messageText);

        // Handle file uploads
        $image_name = null;
        if ($request->hasFile('images')) {
            $image_name = [];
            foreach($request->file('images') as $img) {
                $name = Helpers::upload('conversation/', 'png', $img);
                $image_name[] = ['img' => $name, 'storage' => Helpers::getDisk()];
            }
        }

        // Get or create admin UserInfo
        $sender = UserInfo::where('admin_id', $admin->id)->first();
        if (!$sender) {
            $sender = UserInfo::create([
                'admin_id' => $admin->id,
                'f_name' => $admin->f_name,
                'l_name' => $admin->l_name,
                'phone' => $admin->phone,
                'email' => $admin->email,
                'image' => $admin->image,
            ]);
        }

        // Get customer User and UserInfo
        $customer = User::find($userId);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $receiver = UserInfo::where('user_id', $customer->id)->first();
        if (!$receiver) {
            $receiver = UserInfo::create([
                'user_id' => $customer->id,
                'f_name' => $customer->f_name,
                'l_name' => $customer->l_name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'image' => $customer->image,
            ]);
        }

        // Find existing conversation (customer may have sent to admin inbox with receiver_id=0)
        $conversation = Conversation::where(function($q) use ($sender, $receiver) {
                // Customer -> Admin conversation (receiver_id can be 0 or specific admin)
                $q->where(function($subQ) use ($receiver) {
                    $subQ->where('sender_id', $receiver->id)
                         ->where('sender_type', 'customer')
                         ->where('receiver_type', 'admin')
                         ->where(function($receiverQ) use ($receiver) {
                             $receiverQ->where('receiver_id', 0) // Admin inbox
                                       ->orWhere('receiver_id', $receiver->id); // Specific admin
                         });
                })
                // Admin -> Customer conversation (previous reply)
                ->orWhere(function($subQ) use ($sender, $receiver) {
                    $subQ->where('sender_type', 'admin')
                         ->where('receiver_id', $receiver->id)
                         ->where('receiver_type', 'customer');
                });
            })
            ->first();

        if (!$conversation) {
            // Create conversation with admin as sender, customer as receiver
            $conversation = Conversation::create([
                'sender_id' => $sender->id,
                'sender_type' => 'admin',
                'receiver_id' => $receiver->id,
                'receiver_type' => 'customer',
                'last_message_time' => Carbon::now(),
            ]);
        } else {
            // Update conversation if it was sent to general admin inbox (receiver_id = 0)
            // Assign it to the current admin who is replying
            if ($conversation->receiver_id == 0 && $conversation->receiver_type == 'admin') {
                $conversation->update([
                    'receiver_id' => $sender->id, // Assign to current admin
                ]);
            }
        }

        DB::beginTransaction();
        try {
            // Check if this is the first message from admin
            $previousMessagesCount = Message::where('conversation_id', $conversation->id)
                ->where('sender_id', $sender->id)
                ->count();

            // Add greeting for first message
            // Note: $messageText already sanitized above
            if ($previousMessagesCount == 0) {
                // Sanitize admin name to prevent XSS
                $agentName = strip_tags(trim($sender->f_name . ' ' . $sender->l_name));
                $greeting = "السلام علیکم (Assalamualaikum),\n\nI'm {$agentName} and I've been assigned to assist you with your matter. Please give me a moment to look into it.\n\n";
                $messageText = $greeting . $messageText;
            }

            // Create message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'message' => $messageText,
                'file' => $image_name ? json_encode($image_name, JSON_UNESCAPED_SLASHES) : null,
            ]);

            // Update conversation
            $conversation->increment('unread_message_count');
            $conversation->update([
                'last_message_id' => $message->id,
                'last_message_time' => Carbon::now(),
            ]);

            DB::commit();

            // Send push notification
            if ($customer->cm_firebase_token) {
                Helpers::send_push_notif_to_device($customer->cm_firebase_token, [
                    'title' => translate('messages.message_from_admin'),
                    'description' => $message->message ?? translate('attachment'),
                    'order_id' => '',
                    'image' => '',
                    'message' => json_encode($message),
                    'type' => 'message',
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'admin'
                ]);
            }

            // Format message for response
            $formattedMessage = [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'text' => $message->message,
                'files' => $message->file_full_url ?? [],
                'is_mine' => true,
                'sender' => [
                    'id' => $sender->id,
                    'name' => trim($sender->f_name . ' ' . $sender->l_name),
                    'image' => $sender->image_full_url ?? null,
                ],
                'is_seen' => false,
                'created_at' => $message->created_at->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'message' => $formattedMessage
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to send customer message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        }
    }
}
