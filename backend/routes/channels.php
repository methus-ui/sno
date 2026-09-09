<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Conversation Channels
|--------------------------------------------------------------------------
|
| Private channel for specific conversations. Users can only access
| conversations they are part of. Admins can access all conversations.
|
*/

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // For admin users - admins can access all conversations
    if (auth('admin')->check()) {
        return ['id' => auth('admin')->id(), 'name' => auth('admin')->user()->f_name];
    }

    // For regular users - check if they are part of the conversation
    if ($user) {
        $conversation = \App\Models\Conversation::find($conversationId);
        if (!$conversation) {
            return false;
        }

        $userInfo = \App\Models\UserInfo::where('user_id', $user->id)->first();
        if ($userInfo && ($conversation->sender_id == $userInfo->id || $conversation->receiver_id == $userInfo->id)) {
            return ['id' => $user->id, 'name' => $user->f_name];
        }
    }

    return false;
});

/*
|--------------------------------------------------------------------------
| Admin Notifications Channel
|--------------------------------------------------------------------------
|
| Public channel for admin notifications. Only authenticated admins
| can subscribe to this channel for real-time notifications.
|
*/

Broadcast::channel('admin-notifications', function () {
    return auth('admin')->check();
});
