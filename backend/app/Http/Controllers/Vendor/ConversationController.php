<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Models\Conversation;
use App\Models\UserInfo;
use App\Models\Message;
use App\Models\User;
use App\Models\DeliveryMan;
use App\Models\MessageTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function list(Request $request)
    {
        $vendor = Helpers::get_vendor_data();
        $vendor = UserInfo::where('vendor_id',$vendor->id)->first();
        if($vendor){
            $conversations = Conversation::with(['sender','receiver', 'last_message'])->WhereUser($vendor->id);
            if($request->query('key')) {
                $key = explode(' ', $request->get('key'));
                $conversations = $conversations->where(function($qu)use($key){
                    $qu->whereHas('sender',function($query)use($key){
                        foreach ($key as $value) {
                            $query->where('f_name', 'like', "%{$value}%")
                            ->orWhere('l_name', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%");
                        }
                    })
                    ->orWhereHas('receiver',function($query1)use($key){
                        foreach ($key as $value) {
                            $query1->where('f_name', 'like', "%{$value}%")
                            ->orWhere('l_name', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%");
                        }
                    });
                });
            }
            $conversations = $conversations->orderBy('last_message_time', 'DESC')
            ->latest()
            ->paginate(8);
        }else{
            $conversations = [];
        }


        if ($request->ajax()) {
            // dd($conversations);

            $view = view('vendor-views.messages.data',compact('conversations'))->render();
            return response()->json(['html'=>$view]);
        }

        return view('vendor-views.messages.index', compact('conversations'));
    }

    public function view($conversation_id,$user_id)
    {
        $conversation = Conversation::find($conversation_id);
        $lastmessage = $conversation->last_message;
        if($lastmessage && $lastmessage->sender_id == $user_id ) {
            $conversation->unread_message_count = 0;
            $conversation->save();
        }
        Message::where(['conversation_id' => $conversation->id])->where('sender_id',$user_id)->update(['is_seen' => 1]);
        $convs = Message::where(['conversation_id' => $conversation_id])->get();
        // Message::where(['conversation_id' => $conversation_id])->update(['is_seen' => 1]);
        $conversation= Conversation::find($conversation_id);
        $receiver = $conversation->receiver;
        $sender = $conversation->sender;
        $vendor = Helpers::get_vendor_data();
        $vendor = UserInfo::where('vendor_id',$vendor->id)->first();

        if($receiver->user_id){
            $user = User::find($receiver->user_id);
            $user_type = 'user';
        }elseif($receiver->deliveryman_id){
            $user = DeliveryMan::find($receiver->deliveryman_id);
            $user_type = 'delivery_man';
        }elseif($sender->user_id){
            $user = User::find($sender->user_id);
            $user_type = 'user';
        }else{
            $user = DeliveryMan::find($sender->deliveryman_id);
            $user_type = 'delivery_man';
        }

        return response()->json([
            'view' => view('vendor-views.messages.partials._conversations', compact('convs', 'user', 'receiver','sender','user_type','vendor'))->render()
        ]);
    }

    public function store(Request $request, $user_id, $user_type)
    {
        if ($request->has('images')) {
            $image_name=[];
            foreach($request->images as $key=>$img)
            {
                $name = Helpers::upload('conversation/', 'png', $img);
                array_push($image_name,['img'=>$name, 'storage'=> Helpers::getDisk()]);
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

        $vendor = Helpers::get_vendor_data();
        $sender = UserInfo::where('vendor_id',$vendor->id)->first();
        if(!$sender){
            $sender = new UserInfo();
            $sender->vendor_id = $vendor->id;
            $sender->f_name = $vendor->stores[0]->name;
            $sender->l_name = '';
            $sender->phone = $vendor->phone;
            $sender->email = $vendor->email;
            $sender->image = $vendor->stores[0]->logo;
            $sender->save();
        }

        $fcm_token = null;

        if($user_type == 'user'){
            $user = User::find($user_id);
            if(!$user) {
                return response()->json(['errors' => [['code' => 'user', 'message' => 'User not found.']]]);
            }
            $fcm_token=$user->cm_firebase_token;
            $receiver = UserInfo::where('user_id', $user->id)->first();
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

        }elseif($user_type == 'delivery_man'){
            $dm = DeliveryMan::find($user_id);
            $fcm_token=$dm->fcm_token;
            $receiver = UserInfo::where('deliveryman_id', $dm->id)->first();
            if(!$receiver){
                $receiver = new UserInfo();
                $receiver->deliveryman_id = $dm->id;
                $receiver->f_name = $dm->f_name;
                $receiver->l_name = $dm->l_name;
                $receiver->phone = $dm->phone;
                $receiver->email = $dm->email;
                $receiver->image = $dm->image;
                $receiver->save();
            }
            $user = DeliveryMan::find($user_id);
        }



        // Use database transaction with locking to prevent race conditions
        DB::beginTransaction();
        try {
            $conversation = Conversation::WhereConversation($sender->id,$receiver->id)
                ->lockForUpdate()
                ->first();

            if(!$conversation){
                $conversation = new Conversation;
                $conversation->sender_id = $sender->id;
                $conversation->sender_type = 'vendor';
                $conversation->receiver_id = $receiver->id;
                $conversation->receiver_type = $user_type;
                $conversation->last_message_time = Carbon::now()->toDateTimeString();
                $conversation->save();

                $conversation= Conversation::find($conversation->id);
            }

            $message = new Message();
            $message->conversation_id = $conversation->id;
            $message->sender_id = $sender->id;
            $message->message = $request->reply;
            if($image_name && count($image_name)>0){
                $message->file = json_encode($image_name, JSON_UNESCAPED_SLASHES);
            }

            if($message->save()) {
                $conversation->unread_message_count = $conversation->unread_message_count ? $conversation->unread_message_count + 1 : 1;
                $conversation->last_message_id = $message->id;
                $conversation->last_message_time = Carbon::now()->toDateTimeString();
                $conversation->save();

                DB::commit();

                if(isset($fcm_token)) {
                    $data = [
                        'title' => translate('messages.message_from')." ".$sender->f_name,
                        'description' => $message->message ?? translate('attachment'),
                        'order_id' => '',
                        'image' => '',
                        'message' => json_encode($message),
                        'type' => 'message',
                        'conversation_id' => $conversation->id,
                        'sender_type' => 'vendor'
                    ];
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }
            } else {
                DB::rollBack();
                return response()->json(['errors' => [['code' => 'message', 'message' => 'Failed to save message.']]]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            info('Vendor message send error: ' . $e->getMessage());
            return response()->json(['errors' => [['code' => 'message', 'message' => 'Failed to send message.']]]);
        }
        $vendor = UserInfo::where('vendor_id',$vendor->id)->first();
        $convs = Message::where(['conversation_id' => $conversation->id])->get();
        return response()->json([
            'view' => view('vendor-views.messages.partials._conversations', compact('convs', 'user', 'receiver','user_type','vendor'))->render()
        ]);
    }

    /**
     * Check for new messages (polling endpoint)
     * Used for real-time updates in vendor panel
     */
    public function checkNewMessages(Request $request)
    {
        try {
            $vendor = Helpers::get_vendor_data();
            $vendorInfo = UserInfo::where('vendor_id', $vendor->id)->first();

            if (!$vendorInfo) {
                return response()->json([
                    'new_messages' => 0,
                    'messages' => [],
                    'current_time' => now()->timestamp
                ]);
            }

            $query = Conversation::with(['sender', 'receiver', 'last_message'])
                ->WhereUser($vendorInfo->id)
                ->where('unread_message_count', '>', 0);

            if ($request->has('last_checked')) {
                $query->where('last_message_time', '>', Carbon::createFromTimestamp($request->last_checked));
            }

            $conversations = $query->orderBy('last_message_time', 'DESC')->get();
            $unreadCount = $conversations->sum('unread_message_count');

            // Get the latest message details for popup
            $latestMessages = [];
            foreach ($conversations as $conv) {
                $user = $conv->sender_id == $vendorInfo->id ? $conv->receiver : $conv->sender;
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
                'success' => true,
                'new_messages' => $unreadCount,
                'messages' => $latestMessages,
                'current_time' => now()->timestamp
            ]);

        } catch (\Exception $e) {
            \Log::error('Vendor check new messages error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'new_messages' => 0,
                'messages' => [],
                'current_time' => now()->timestamp
            ]);
        }
    }

    /**
     * ========================================
     * MESSAGE TEMPLATE MANAGEMENT
     * ========================================
     */

    /**
     * Get all active templates
     * Route: GET /store-panel/message/templates
     */
    public function getTemplates()
    {
        $templates = MessageTemplate::active()->orderBy('title')->get();
        return response()->json(['templates' => $templates]);
    }

    /**
     * Create new template
     * Route: POST /store-panel/message/templates
     */
    public function storeTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vendor = Helpers::get_vendor_data();

        $template = MessageTemplate::create([
            'title' => $request->title,
            'content' => $request->content,
            'created_by' => $vendor->id,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'template' => $template
        ]);
    }

    /**
     * Update template
     * Route: PUT /store-panel/message/templates/{id}
     */
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

    /**
     * Delete template
     * Route: DELETE /store-panel/message/templates/{id}
     */
    public function deleteTemplate($id)
    {
        $template = MessageTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
    }
}
