<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\DeliveryMan;
use App\Models\UserInfo;
use App\Models\Message;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Admin;
use App\Events\NewMessageEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Entities\Trips;

class ConversationController extends Controller
{
   public function messages_store(Request $request)
    {
        // Validate and sanitize input to prevent XSS
        $validator = \Validator::make($request->all(), [
            'message' => 'required_without:image|string|max:5000', // Max 5000 characters
            'receiver_type' => 'required|in:admin,vendor,delivery_man',
            'receiver_id' => 'nullable|integer',
            'order_id' => 'nullable|integer',
            'image.*' => 'nullable|image|max:10240', // 10MB max per image
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // Sanitize message content to prevent XSS
        if ($request->has('message')) {
            $request->merge(['message' => strip_tags(trim($request->message))]);
        }

        if ($request->has('image')) {
            $image_name = [];
            foreach($request->file('image') as $key => $img) {
                $name = Helpers::upload('conversation/', 'png', $img);
                $image_name[] = ['img' => $name, 'storage' => Helpers::getDisk()];
            }
        } else {
            $image_name = null;
        }

        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;
        $fcm_token = null;
        $fcm_token_web = null;

        $sender = UserInfo::where('user_id', $request->user()->id)->first();
        if(!$sender) {
            $sender = new UserInfo();
            $sender->user_id = $request->user()->id;
            $sender->f_name = $request->user()->f_name;
            $sender->l_name = $request->user()->l_name;
            $sender->phone = $request->user()->phone;
            $sender->email = $request->user()->email;
            $sender->image = $request->user()->image;
            $sender->save();
        }

        if($request->conversation_id) {
            $conversation = Conversation::find($request->conversation_id);

            if($conversation->sender_id == $sender->id) {
                $receiver_id = $conversation->receiver_id;
                $receiver = UserInfo::find($receiver_id);
                if($receiver->vendor_id) {
                    $vendor = Vendor::find($receiver->vendor_id);
                    $fcm_token = $vendor->firebase_token;
                    $fcm_token_web = "store_panel_{$vendor->stores[0]->id}_message";
                } elseif($receiver->deliveryman_id) {
                    $delivery_man = DeliveryMan::find($receiver->deliveryman_id);
                    $fcm_token = $delivery_man->fcm_token;
                } elseif($receiver->admin_id) {
                    $receiver_id = 0;
                }
            } else {
                $receiver_id = $conversation->sender_id;
                $receiver = UserInfo::find($receiver_id);
                if($receiver->vendor_id) {
                    $vendor = Vendor::find($receiver->vendor_id);
                    $fcm_token = $vendor->firebase_token;
                    $fcm_token_web = "store_panel_{$vendor->stores[0]->id}_message";
                } elseif($receiver->deliveryman_id) {
                    $delivery_man = DeliveryMan::find($receiver->deliveryman_id);
                    $fcm_token = $delivery_man->fcm_token;
                } elseif($receiver->admin_id) {
                    $receiver_id = 0;
                }
            }
        } else {
            if($request->receiver_type == 'admin') {
                $receiver_id = 0;
            } else if($request->receiver_type == 'vendor') {
                $receiver = UserInfo::where('vendor_id', $request->receiver_id)->first();
                $vendor = Vendor::find($request->receiver_id);
                if(!$receiver) {
                    $receiver = new UserInfo();
                    $receiver->vendor_id = $vendor->id;
                    $receiver->f_name = $vendor->stores[0]->name;
                    $receiver->l_name = '';
                    $receiver->phone = $vendor->phone;
                    $receiver->email = $vendor->email;
                    $receiver->image = $vendor->stores[0]->logo;
                    $receiver->save();
                }

                $receiver_id = $receiver->id;
                $fcm_token = $vendor->firebase_token;
                $fcm_token_web = "store_panel_{$vendor->stores[0]->id}_message";

            } else if($request->receiver_type == 'delivery_man') {
                $receiver = UserInfo::where('deliveryman_id', $request->receiver_id)->first();
                $delivery_man = DeliveryMan::find($request->receiver_id);

                if(!$receiver) {
                    $receiver = new UserInfo();
                    $receiver->deliveryman_id = $delivery_man->id;
                    $receiver->f_name = $delivery_man->f_name;
                    $receiver->l_name = $delivery_man->l_name;
                    $receiver->phone = $delivery_man->phone;
                    $receiver->email = $delivery_man->email;
                    $receiver->image = $delivery_man->image;
                    $receiver->save();
                }

                $receiver_id = $receiver->id;
                $fcm_token = $delivery_man->fcm_token;
            }

            // Enhanced conversation lookup to handle admin replies
            if($request->receiver_type == 'admin') {
                // When customer sends to admin, check for:
                // 1. Customer -> Admin (receiver_id = 0 OR specific admin ID)
                // 2. Admin -> Customer (admin replied, conversation reversed)
                $conversation = Conversation::where(function($q) use ($sender, $receiver_id) {
                    // Customer as sender
                    $q->where(function($subQ) use ($sender, $receiver_id) {
                        $subQ->where('sender_id', $sender->id)
                             ->where('sender_type', 'customer')
                             ->where('receiver_type', 'admin')
                             ->where(function($receiverQ) {
                                 $receiverQ->where('receiver_id', 0) // Admin inbox
                                           ->orWhere('receiver_id', '>', 0); // Specific admin
                             });
                    })
                    // Admin as sender (when admin replied, conversation direction may have flipped)
                    ->orWhere(function($subQ) use ($sender) {
                        $subQ->where('receiver_id', $sender->id)
                             ->where('receiver_type', 'customer')
                             ->where('sender_type', 'admin');
                    });
                })->first();
            } else {
                // For vendor/delivery_man, use standard lookup
                $conversation = Conversation::WhereConversation($sender->id, $receiver_id)->first();
            }
        }

        // Use database transaction with locking to prevent duplicate conversations
        if(!$conversation) {
            DB::beginTransaction();
            try {
                // Double-check inside transaction to prevent race conditions
                if($request->receiver_type == 'admin') {
                    $conversation = Conversation::where(function($q) use ($sender, $receiver_id) {
                        $q->where(function($subQ) use ($sender, $receiver_id) {
                            $subQ->where('sender_id', $sender->id)
                                 ->where('sender_type', 'customer')
                                 ->where('receiver_type', 'admin')
                                 ->where(function($receiverQ) {
                                     $receiverQ->where('receiver_id', 0)
                                               ->orWhere('receiver_id', '>', 0);
                                 });
                        })->orWhere(function($subQ) use ($sender) {
                            $subQ->where('receiver_id', $sender->id)
                                 ->where('receiver_type', 'customer')
                                 ->where('sender_type', 'admin');
                        });
                    })->lockForUpdate()->first();
                } else {
                    $conversation = Conversation::WhereConversation($sender->id, $receiver_id)
                        ->lockForUpdate()
                        ->first();
                }

                if(!$conversation) {
                    $conversation = new Conversation;
                    $conversation->sender_id = $sender->id;
                    $conversation->sender_type = 'customer';
                    $conversation->receiver_id = $receiver_id;
                    $conversation->receiver_type = $request->receiver_type;
                    $conversation->unread_message_count = 0;
                    $conversation->last_message_time = Carbon::now()->toDateTimeString();
                    $conversation->auto_response_sent = false;
                    $conversation->save();
                }

                DB::commit();
                $conversation = Conversation::find($conversation->id);
            } catch (\Exception $e) {
                DB::rollBack();
                info('Conversation creation error: ' . $e->getMessage());
                throw $e;
            }
        }

        $message = new Message();
        $message->conversation_id = $conversation->id;
        $message->sender_id = $sender->id;
        $message->message = $request->message;
        $message->order_id = $request?->order_id ?? null;

        if($image_name && count($image_name) > 0) {
            $message->file = json_encode($image_name, JSON_UNESCAPED_SLASHES);
        }
        
        try {
            $message->save();
            $conversation->unread_message_count = $conversation->unread_message_count ? $conversation->unread_message_count + 1 : 1;
            $conversation->last_message_id = $message->id;
            $conversation->last_message_time = Carbon::now()->toDateTimeString();
            $conversation->save();

            // Send auto-response if it's the first message to admin and not sent before
            if($request->receiver_type == 'admin' || $receiver_id == 0) {
                // Check if this is truly the first message (no admin has replied yet)
                // Don't send auto-response if admin has already replied to this customer
                $adminHasReplied = Message::where('conversation_id', $conversation->id)
                    ->whereHas('sender', function($q) {
                        $q->where('admin_id', '>', 0);
                    })
                    ->exists();

                if(!$conversation->auto_response_sent && !$adminHasReplied) {
                    $this->sendAutoResponse($conversation, $sender);
                    $conversation->auto_response_sent = true;
                    $conversation->save();
                }

                // Broadcast new message event for real-time updates
                try {
                    broadcast(new NewMessageEvent($message, 'customer', $sender->f_name . ' ' . $sender->l_name))->toOthers();
                } catch (\Exception $e) {
                    info('Broadcast error: ' . $e->getMessage());
                }

                $data = [
                    'title' => translate('messages.message'),
                    'description' => translate('messages.message_description'),
                    'order_id' => '',
                    'image' => '',
                    'message' => json_encode($message),
                    'type' => 'message'
                ];
                Helpers::send_push_notif_to_topic($data, 'admin_message', 'message');
                
            } else if($request->receiver_type == 'vendor' || $request->receiver_type == 'delivery_man') {
                $data = [
                    'title' => translate('messages.message'),
                    'description' => translate('messages.message_description'),
                    'order_id' => '',
                    'image' => '',
                    'message' => json_encode($message),
                    'type' => 'message',
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'user'
                ];
                if(!empty($fcm_token)) {
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }
                if($fcm_token_web) {
                    Helpers::send_push_notif_to_topic($data, $fcm_token_web, 'message');
                }
            }

        } catch (\Exception $e) {
            info($e->getMessage());
        }

        $messages = Message::where(['conversation_id' => $conversation->id])->with('order')->latest()->paginate($limit, ['*'], 'page', $offset);
        $messages->getCollection()->transform(function ($message) {
            if ($message->order) {
                $message->order->delivery_address = gettype($message->order->delivery_address) == 'string' ? json_decode($message->order->delivery_address, true) : $message->order->delivery_address;
                $message->order->id = (int) $message->order->id;
                $message->order->order_amount = (float) $message->order->order_amount;
                $message->order->details_count = (int) $message->order->details_count;
            }

            return $message;
        });

        $conv = Conversation::with('sender', 'receiver', 'last_message')->find($conversation->id);

        // Order validation logic remains the same...
        if($conv->sender_type == 'vendor' && $conversation->sender) {
            $vd = Vendor::find($conv->sender->vendor_id);
            if($vd?->store?->module_type == 'rental' && addon_published_status('Rental')) {
                $order = Trips::where('user_id', $request->user()->id)->where('provider_id', $vd->store->id)->whereIn('trip_status', ['pending','confirmed','ongoing','completed'])->where('payment_status' ,'unpaid')->count();
            } else {
                $order = Order::where('user_id', $request->user()->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
            }
        } else if($conv->receiver_type == 'vendor' && $conversation->receiver) {
            $vd = Vendor::find($conv->receiver->vendor_id);

            if($vd?->store?->module_type == 'rental' && addon_published_status('Rental')) {
                $order = Trips::where('user_id', $request->user()->id)->where('provider_id', $vd->store->id)->whereIn('trip_status', ['pending','confirmed','ongoing','completed'])->where('payment_status' ,'unpaid')->count();
            } else {
                $order = Order::where('user_id', $request->user()->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
            }
        } else if($conv->sender_type == 'delivery_man' && $conversation->sender) {
            $user2 = DeliveryMan::find($conv->sender->deliveryman_id);
            $order = Order::where('user_id', $request->user()->id)->where('delivery_man_id', $user2->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
        } else if($conv->receiver_type == 'delivery_man' && $conversation->receiver) {
            $user2 = DeliveryMan::find($conv->receiver->deliveryman_id);
            $order = Order::where('user_id', $request->user()->id)->where('delivery_man_id', $user2->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
        } else {
            $order = 1;
        }

        $data = [
            'total_size' => intval($messages->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'status' => ($order > 0) ? true : false,
            'message' => 'successfully sent!',
            'messages' => $messages->items(),
            'conversation' => $conv,
        ];
        return response()->json($data, 200);
    }

    private function sendAutoResponse($conversation, $sender)
    {
        try {
            // Create auto-response from admin
            $adminUser = UserInfo::where('admin_id', 1)->first(); // Assuming admin ID 1 or get super admin
            if (!$adminUser) {
                $admin = Admin::first();
                $adminUser = new UserInfo();
                $adminUser->admin_id = $admin->id;
                $adminUser->f_name = $admin->f_name;
                $adminUser->l_name = $admin->l_name;
                $adminUser->phone = $admin->phone;
                $adminUser->email = $admin->email;
                $adminUser->image = $admin->image;
                $adminUser->save();
            }

            $autoMessage = new Message();
            $autoMessage->conversation_id = $conversation->id;
            $autoMessage->sender_id = $adminUser->id;
            $autoMessage->message = "السلام علیکم (Assalamualaikum) 👋\n\nPlease wait while we connect you to an available representative.\n\n⏱️ Expected wait time: 10-30 minutes\n\nIn the meantime, please share your problems or questions here and we'll assist you as soon as possible.";
            $autoMessage->save();

            // Update conversation
            $conversation->last_message_id = $autoMessage->id;
            $conversation->last_message_time = Carbon::now()->toDateTimeString();
            $conversation->save();

            // Send notification to customer
            $user = User::find($sender->user_id);
            if ($user && $user->cm_firebase_token) {
                $data = [
                    'title' => translate('messages.message_from_admin'),
                    'description' => $autoMessage->message,
                    'order_id' => '',
                    'image' => '',
                    'message' => json_encode($autoMessage),
                    'type' => 'message',
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'admin'
                ];
                Helpers::send_push_notif_to_device($user->cm_firebase_token, $data);
            }
        } catch (\Exception $e) {
            info('Auto-response error: ' . $e->getMessage());
        }
    }


    public function chat_image(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if ($request->has('image')) {
            $image_name = Helpers::upload('conversation/', 'png', $request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        $url = asset('storage/app/public/conversation') . '/' . $image_name;

        return response()->json(['image_url' => $url], 200);
    }


    public function conversations(Request $request)
    {
        $limit = $request['limit']??10;
        $offset = $request['offset']??1;

        $sender = UserInfo::where('user_id', $request?->user()?->id)->first();
        if(!$sender){
            $sender = new UserInfo();
            $sender->user_id = $request?->user()?->id;
            $sender->f_name = $request?->user()?->f_name;
            $sender->l_name = $request?->user()?->l_name;
            $sender->phone = $request?->user()?->phone;
            $sender->email = $request?->user()?->email;
            $sender->image = $request?->user()?->image;
            $sender->save();
        }

        $conversations = Conversation::with('sender','receiver','last_message')
        ->where(function($q) use($sender){
                    $q->where(['sender_id' => $sender->id])->orWhere(['receiver_id' => $sender->id]);
                })
        ->when(isset($request->type) , function($query) use($request) {
            $query->where(function($q) use($request){
                $q->where('receiver_type', $request->type)->where('sender_type','customer')
                    ->orWhere(function($q) use($request){
                        $q->where('sender_type', $request->type)->where('receiver_type','customer');
                });
            });
        })
        ->orderBy('last_message_time', 'DESC')->paginate($limit, ['*'], 'page', $offset);

        $data =  [
            'type'=>$request->type ?? null,
            'total_size' => intval($conversations->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'conversations' => $conversations->items()
        ];
        return response()->json($data, 200);
    }

    public function search_conversations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $key = explode(' ', $request['name']);

        $limit = $request['limit']??10;
        $offset = $request['offset']??1;

        $sender = UserInfo::where('user_id', $request->user()->id)->first();
        if(!$sender){
            $sender = new UserInfo();
            $sender->user_id = $request->user()->id;
            $sender->f_name = $request->user()->f_name;
            $sender->l_name = $request->user()->l_name;
            $sender->phone = $request->user()->phone;
            $sender->email = $request->user()->email;
            $sender->image = $request->user()->image;
            $sender->save();
        }

        $conversations = Conversation::with('sender','receiver','last_message')->WhereUser($sender->id)->where(function($qu)use($key){
                    $qu->whereHas('sender',function($query)use($key){
                    foreach ($key as $value) {
                        $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                    }
                })
                ->orWhereHas('receiver',function($query1)use($key){
                    foreach ($key as $value) {
                        $query1->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                    }
                });
            });

        $conversations = $conversations->orderBy('last_message_time', 'DESC')->paginate($limit, ['*'], 'page', $offset);

        $data =  [
            'total_size' => intval($conversations->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'conversations' => $conversations->items()
        ];
        return response()->json($data, 200);
    }

    public function messages(Request $request)
    {
        $limit = $request['limit']??10;
        $offset = $request['offset']??1;

        $user = UserInfo::where('user_id', $request->user()->id)->first();
        if(!$user){
            $user = new UserInfo();
            $user->user_id = $request->user()->id;
            $user->f_name = $request->user()->f_name;
            $user->l_name = $request->user()->l_name;
            $user->phone = $request->user()->phone;
            $user->email = $request->user()->email;
            $user->image = $request->user()->image;
            $user->save();
        }

        $conversation = null;
        if($request->conversation_id){
            $conversation = Conversation::with(['sender','receiver','last_message'])->find($request->conversation_id);
        }else if($request->has('admin_id')){
            // Enhanced lookup for admin conversations to handle both directions
            $conversation = Conversation::with(['sender','receiver','last_message'])
                ->where(function($q) use ($user) {
                    // Customer -> Admin (receiver_id = 0 OR specific admin ID)
                    $q->where(function($subQ) use ($user) {
                        $subQ->where('sender_id', $user->id)
                             ->where('sender_type', 'customer')
                             ->where('receiver_type', 'admin')
                             ->where(function($receiverQ) {
                                 $receiverQ->where('receiver_id', 0)
                                           ->orWhere('receiver_id', '>', 0);
                             });
                    })
                    // Admin -> Customer (when admin replied)
                    ->orWhere(function($subQ) use ($user) {
                        $subQ->where('receiver_id', $user->id)
                             ->where('receiver_type', 'customer')
                             ->where('sender_type', 'admin');
                    });
                })->first();
            $order=0;
        }else if($request->vendor_id){
            $vendor = UserInfo::where('vendor_id', $request->vendor_id)->first();
            if(!$vendor){
                $vd = Vendor::find($request->vendor_id);
                $vendor = new UserInfo();
                $vendor->vendor_id = $vd->id;
                $vendor->f_name = $vd->stores[0]->name;
                $vendor->l_name = '';
                $vendor->phone = $vd->phone;
                $vendor->email = $vd->email;
                $vendor->image = $vd->stores[0]->logo;
                $vendor->save();
            }
            $conversation = Conversation::with(['sender','receiver','last_message'])->WhereConversation($user->id,$vendor->id)->first();
        }else if($request->delivery_man_id){
            $dm = UserInfo::where('deliveryman_id', $request->delivery_man_id)->first();
            if(!$dm){
                $user2 = DeliveryMan::find($request->delivery_man_id);
                $dm = new UserInfo();
                $dm->deliveryman_id = $user2->id;
                $dm->f_name = $user2->f_name;
                $dm->l_name = $user2->l_name;
                $dm->phone = $user2->phone;
                $dm->email = $user2->email;
                $dm->image = $user2->image;
                $dm->save();
            }
            $conversation = Conversation::with(['sender','receiver','last_message'])->WhereConversation($user->id,$dm->id)->first();
        }

        if(isset($conversation)){
            if($conversation->sender_type == 'vendor' && $conversation->sender){
                $vd = Vendor::find($conversation->sender->vendor_id);
                if($vd?->store?->module_type == 'rental' && addon_published_status('Rental')){
                    $order = Trips::where('user_id',$request->user()->id)->where('provider_id', $vd->store->id)->whereIn('trip_status',['pending','confirmed','ongoing','completed'])->where('payment_status' ,'unpaid')->count();
                } else{
                    $order = Order::where('user_id',$request->user()->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
                }

            }else if($conversation->receiver_type == 'vendor' && $conversation->receiver){
                $vd = Vendor::find($conversation->receiver->vendor_id);
                if($vd?->store?->module_type == 'rental' && addon_published_status('Rental')){
                    $order = Trips::where('user_id',$request->user()->id)->where('provider_id', $vd->store->id)->whereIn('trip_status',['pending','confirmed','ongoing','completed'])->where('payment_status' ,'unpaid')->count();
                } else{
                    $order = Order::where('user_id',$request->user()->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
                }
            }else if($conversation->sender_type == 'delivery_man' && $conversation->sender){
                $user2 = DeliveryMan::find($conversation->sender->deliveryman_id);
                $order = Order::where('user_id',$user->user_id)->where('delivery_man_id', $user2->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
            }else if($conversation->receiver_type == 'delivery_man' && $conversation->receiver){
                $user2 = DeliveryMan::find($conversation->receiver->deliveryman_id);
                $order = Order::where('user_id',$user->user_id)->where('delivery_man_id', $user2->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
            }
            else{
                $order=1;
            }

            $lastmessage = $conversation->last_message;
            if($lastmessage && $lastmessage->sender_id != $user->id ) {
                $conversation->unread_message_count = 0;
                $conversation->save();
            }
            Message::where(['conversation_id' => $conversation->id])->where('sender_id','!=',$user->id)->update(['is_seen' => 1]);
            $messages = Message::where(['conversation_id' => $conversation->id])->with('order')->latest()->paginate($limit, ['*'], 'page', $offset);
            $messages->getCollection()->transform(function ($message) {
                if ($message->order) {
                    $message->order->delivery_address = gettype($message->order->delivery_address) == 'string' ? json_decode($message->order->delivery_address,true): $message->order->delivery_address;
                    $message->order->id = (int) $message->order->id;
                    $message->order->order_amount = (float) $message->order->order_amount;
                    $message->order->details_count = (int) $message->order->details_count;
                    }

                return $message;
            });
        }else{
            $messages =[];
            $order=0;
        }


        $data =  [
            'total_size' => $messages? intval($messages->total()):0,
            'limit' => intval($limit),
            'offset' => intval($offset),
            'status' => ($order > 0)?true:false,
            'messages' => $messages? $messages->items():[],
            'conversation' => $conversation
        ];
        return response()->json($data, 200);
    }

    public function dm_messages_store(Request $request)
    {

        if ($request->has('image')) {
            $image_name=[];
            foreach($request->file('image') as $key=>$img)
            {

                $name = Helpers::upload('conversation/', 'png', $img);
                array_push($image_name,['img'=>$name, 'storage'=> Helpers::getDisk()]);
            }
        } else {
            $image_name = null;
        }

        $limit = $request['limit']??10;
        $offset = $request['offset']??1;
        $fcm_token = null;
        $fcm_token_web = null;

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $sender = UserInfo::where('deliveryman_id', $dm->id)->first();
        if(!$sender){
            $sender = new UserInfo();
            $sender->deliveryman_id = $dm->id;
            $sender->f_name = $dm->f_name;
            $sender->l_name = $dm->l_name;
            $sender->phone = $dm->phone;
            $sender->email = $dm->email;
            $sender->image = $dm->image;
            $sender->save();
        }

        if($request->conversation_id){
            $conversation = Conversation::find($request->conversation_id);

            if($conversation->sender_id == $sender->id){
                $receiver_id = $conversation->receiver_id;
                $receiver = UserInfo::find($receiver_id);
                if($receiver->vendor_id){
                    $vendor = Vendor::find($receiver->vendor_id);
                    $fcm_token=$vendor->firebase_token;
                    $fcm_token_web = "store_panel_{$vendor->stores[0]->id}_message";
                }elseif($receiver->user_id){
                    $user = User::find($receiver->user_id);
                    $fcm_token=$user->cm_firebase_token;
                }
            }else{
                $receiver_id =$conversation->sender_id;
                $receiver = UserInfo::find($receiver_id);
                if($receiver->vendor_id){
                    $vendor = Vendor::find($receiver->vendor_id);
                    $fcm_token=$vendor->firebase_token;
                    $fcm_token_web = "store_panel_{$vendor->stores[0]->id}_message";
                }elseif($receiver->user_id){
                    $user = User::find($receiver->user_id);
                    $fcm_token=$user->cm_firebase_token;
                }
            }
        }else{
            if($request->receiver_type == 'vendor'){
                $receiver = UserInfo::where('vendor_id',$request->receiver_id)->first();
                $vendor = Vendor::find($request->receiver_id);

                if(!$receiver){
                    $receiver = new UserInfo();
                    $receiver->vendor_id = $vendor->id;
                    $receiver->f_name = $vendor->stores[0]->name;
                    $receiver->l_name = '';
                    $receiver->phone = $vendor->phone;
                    $receiver->email = $vendor->email;
                    $receiver->image = $vendor->stores[0]->logo;
                    $receiver->save();
                }
                $receiver_id = $receiver->id;
                $fcm_token=$vendor->firebase_token;
                $fcm_token_web = "store_panel_{$vendor->stores[0]->id}_message";
            }else if($request->receiver_type == 'customer'){
                $receiver = UserInfo::where('user_id',$request->receiver_id)->first();
                $user = User::find($request->receiver_id);
                // dd($user);

                if(!$receiver){
                    $receiver = new UserInfo();
                    $receiver->user_id = $user->id;
                    $receiver->f_name = $user->f_name;
                    $receiver->l_name = $user->l_name;
                    $receiver->phone = $user->phone;
                    $receiver->email = $user->email;
                    $receiver->image = $user->image;
                    $receiver->save();
                }
                $receiver_id = $receiver->id;
                $fcm_token=$user->cm_firebase_token;
            }
        }

        $conversation = Conversation::WhereConversation($sender->id,$receiver_id)->first();

        // Use database transaction with locking to prevent duplicate conversations
        if(!$conversation){
            DB::beginTransaction();
            try {
                // Double-check inside transaction to prevent race conditions
                $conversation = Conversation::WhereConversation($sender->id, $receiver_id)
                    ->lockForUpdate()
                    ->first();

                if(!$conversation){
                    $conversation = new Conversation;
                    $conversation->sender_id = $sender->id;
                    $conversation->sender_type = 'delivery_man';
                    $conversation->receiver_id = $receiver->id;
                    $conversation->receiver_type = $request->receiver_type;
                    $conversation->unread_message_count = 0;
                    $conversation->last_message_time = Carbon::now()->toDateTimeString();
                    $conversation->save();
                }

                DB::commit();
                $conversation = Conversation::find($conversation->id);
            } catch (\Exception $e) {
                DB::rollBack();
                info('DM Conversation creation error: ' . $e->getMessage());
                throw $e;
            }
        }


        $message = new Message();
        $message->conversation_id = $conversation->id;
        $message->sender_id = $sender->id;
        $message->message = $request->message;
        if($image_name && count($image_name)>0){
            $message->file = json_encode($image_name, JSON_UNESCAPED_SLASHES);
        }
        try {
            if($message->save()) {
                $conversation->unread_message_count = $conversation->unread_message_count ? $conversation->unread_message_count + 1 : 1;
                $conversation->last_message_id = $message->id;
                $conversation->last_message_time = Carbon::now()->toDateTimeString();
                $conversation->save();

                if(isset($fcm_token)) {
                    $data = [
                        'title' => translate('messages.message_from')." ".$sender->f_name,
                        'description' => $message->message ?? translate('attachment'),
                        'order_id' => '',
                        'image' => '',
                        'message' => json_encode($message),
                        'type' => 'message',
                        'conversation_id' => $conversation->id,
                        'sender_type' => 'delivery_man'
                    ];
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                    if($fcm_token_web) {
                        Helpers::send_push_notif_to_topic($data, $fcm_token_web, 'message');
                    }
                }
            }
        } catch (\Exception $e) {
            info($e->getMessage());
        }

        $messages = Message::where(['conversation_id' => $conversation->id])->latest()->paginate($limit, ['*'], 'page', $offset);

        $conv = Conversation::with('sender','receiver','last_message')->find($conversation->id);

        if($conv->sender_type == 'vendor' && $conversation->sender){
            $vd = Vendor::find($conv->sender->vendor_id);
            $order = Order::where('delivery_man_id',$dm->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
        }else if($conv->receiver_type == 'vendor' && $conversation->receiver){
            $vd = Vendor::find($conv->receiver->vendor_id);
            $order = Order::where('delivery_man_id',$dm->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
        }else if($conv->sender_type == 'customer' && $conversation->sender){
            $user = User::find($conv->sender->user_id);
            $order = Order::where('delivery_man_id',$dm->id)->where('user_id', $user->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
        }else if($conv->receiver_type == 'customer' && $conversation->receiver){
            $user = User::find($conv->receiver->user_id);
            $order = Order::where('delivery_man_id',$dm->id)->where('user_id', $user->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
        }
        else{
            $order=0;
        }


        $data =  [
            'total_size' => intval($messages->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'status' => ($order>0)?true:false,
            'message' => 'successfully sent!',
            'messages' => $messages->items(),
            'conversation' => $conv,
        ];
        return response()->json($data, 200);
    }

    public function dm_conversations(Request $request)
    {
        $limit = $request['limit']??10;
        $offset = $request['offset']??1;

        $delivery_man = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $sender = UserInfo::where('deliveryman_id', $delivery_man->id)->first();
        if(!$sender){
            $sender = new UserInfo();
            $sender->deliveryman_id = $delivery_man->id;
            $sender->f_name = $delivery_man->f_name;
            $sender->l_name = $delivery_man->l_name;
            $sender->phone = $delivery_man->phone;
            $sender->email = $delivery_man->email;
            $sender->image = $delivery_man->image;
            $sender->save();
        }


        $conversations = Conversation::with('sender','receiver','last_message')->where(['sender_id' => $sender->id])->orWhere(['receiver_id' => $sender->id])->orderBy('last_message_time', 'DESC')->paginate($limit, ['*'], 'page', $offset);


        $data =  [
            'total_size' => intval($conversations->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'conversation' => $conversations->items()
        ];

        return response()->json($data, 200);
    }

    public function dm_search_conversations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $key = explode(' ', $request['name']);

        $limit = $request['limit']??10;
        $offset = $request['offset']??1;

        $delivery_man = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $sender = UserInfo::where('deliveryman_id', $delivery_man->id)->first();
        if(!$sender){
            $sender = new UserInfo();
            $sender->deliveryman_id = $delivery_man->id;
            $sender->f_name = $delivery_man->f_name;
            $sender->l_name = $delivery_man->l_name;
            $sender->phone = $delivery_man->phone;
            $sender->email = $delivery_man->email;
            $sender->image = $delivery_man->image;
            $sender->save();
        }

        $conversations = Conversation::with('sender','receiver','last_message')->WhereUser($sender->id)->where(function($qu)use($key){
                    $qu->whereHas('sender',function($query)use($key){
                    foreach ($key as $value) {
                        $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                    }
                })
                ->orWhereHas('receiver',function($query1)use($key){
                    foreach ($key as $value) {
                        $query1->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                    }
                });
            });

        $conversations = $conversations->orderBy('last_message_time', 'DESC')->paginate($limit, ['*'], 'page', $offset);

        $data =  [
            'total_size' => intval($conversations->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'conversation' => $conversations->items()
        ];
        return response()->json($data, 200);
    }


    public function dm_messages(Request $request)
    {
        $limit = $request['limit']??10;
        $offset = $request['offset']??1;

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        $delivery_man = UserInfo::where('deliveryman_id',$dm->id)->first();

        if(!$delivery_man){
            $delivery_man = new UserInfo();
            $delivery_man->deliveryman_id = $dm->id;
            $delivery_man->f_name = $dm->f_name;
            $delivery_man->l_name = $dm->l_name;
            $delivery_man->phone = $dm->phone;
            $delivery_man->email = $dm->email;
            $delivery_man->image = $dm->image;
            $delivery_man->save();
        }

        if($request->conversation_id){
            $conversation = Conversation::with(['sender','receiver','last_message'])->find($request->conversation_id);
        }else if($request->vendor_id){
            $vendor = UserInfo::where('vendor_id', $request->vendor_id)->first();
            if(!$vendor){
                $user = Vendor::find($request->vendor_id);
                $vendor = new UserInfo();
                $vendor->vendor_id = $user->id;
                $vendor->f_name = $user->stores[0]->name;
                $vendor->l_name = '';
                $vendor->phone = $user->phone;
                $vendor->email = $user->email;
                $vendor->image = $user->image;
                $vendor->save();
            }
            $conversation = Conversation::with(['sender','receiver','last_message'])->WhereConversation($delivery_man->id,$vendor->id)->first();

        }else if($request->user_id){
            $user = UserInfo::where('user_id', $request->user_id)->first();
            if(!$user){
                $customer = User::find($request->user_id);
                $user = new UserInfo();
                $user->user_id = $customer->id;
                $user->f_name = $customer->f_name;
                $user->l_name = $customer->l_name;
                $user->phone = $customer->phone;
                $user->email = $customer->email;
                $user->image = $customer->image;
                $user->save();
            }
            $conversation = Conversation::with(['sender','receiver','last_message'])->WhereConversation($delivery_man->id,$user->id)->first();
        }

        if($conversation){

            if($conversation->sender_type == 'vendor' && $conversation->sender){
                $vd = Vendor::find($conversation->sender->vendor_id);
                $order = Order::where('delivery_man_id',$dm->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
            }else if($conversation->receiver_type == 'vendor' && $conversation->receiver){
                $vd = Vendor::find($conversation->receiver->vendor_id);
                $order = Order::where('delivery_man_id',$dm->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
            }else if($conversation->sender_type == 'customer' && $conversation->sender){
                $user = User::find($conversation->sender->user_id);
                $order = Order::where('delivery_man_id',$dm->id)->where('user_id', $user->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
            }else if($conversation->receiver_type == 'customer' && $conversation->receiver){
                $user = User::find($conversation->receiver->user_id);
                $order = Order::where('delivery_man_id',$dm->id)->where('user_id', $user->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count();
            }
            else{
                $order=0;
            }


            $lastmessage = $conversation->last_message;
            if($lastmessage && $lastmessage->sender_id != $delivery_man->id ) {
                $conversation->unread_message_count = 0;
                $conversation->save();
            }

            Message::where(['conversation_id' => $conversation->id])->where('sender_id','!=',$delivery_man->id)->update(['is_seen' => 1]);
            $messages = Message::where(['conversation_id' => $conversation->id])->latest()->paginate($limit, ['*'], 'page', $offset);
        }else{
            $messages =[];
            $order=0;
        }

        $data =  [
            'total_size' => $messages? intval($messages->total()):0,
            'limit' => intval($limit),
            'offset' => intval($offset),
            'status' => ($order>0)?true:false,
            'messages' => $messages? $messages->items():[],
            'conversation' => $conversation
        ];
        return response()->json($data, 200);
    }
}
