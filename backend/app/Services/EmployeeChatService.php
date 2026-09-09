<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\UserInfo;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeChatService
{
    /**
     * Create or get UserInfo record for admin employee
     *
     * @param Admin $admin
     * @return UserInfo
     */
    public function createOrGetUserInfo(Admin $admin): UserInfo
    {
        return UserInfo::firstOrCreate(
            ['admin_id' => $admin->id],
            [
                'f_name' => $admin->f_name,
                'l_name' => $admin->l_name,
                'phone' => $admin->phone,
                'email' => $admin->email,
                'image' => $admin->image,
            ]
        );
    }

    /**
     * Find or create conversation between two employees
     *
     * @param int $senderId UserInfo ID
     * @param int $receiverId UserInfo ID
     * @return Conversation
     */
    public function findOrCreateConversation(int $senderId, int $receiverId): Conversation
    {
        // Check if conversation already exists (bidirectional)
        $conversation = Conversation::where(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $senderId)
                  ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $receiverId)
                  ->where('receiver_id', $senderId);
        })
        ->where('sender_type', 'employee')
        ->where('receiver_type', 'employee')
        ->first();

        if ($conversation) {
            return $conversation;
        }

        // Create new conversation
        return Conversation::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'sender_type' => 'employee',
            'receiver_type' => 'employee',
            'last_message_time' => now(),
            'unread_message_count' => 0,
        ]);
    }

    /**
     * Get all conversations for a user (employee type only)
     *
     * @param int $userInfoId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getConversations(int $userInfoId, int $perPage = 20)
    {
        return Conversation::with(['sender', 'receiver', 'last_message'])
            ->where(function ($query) use ($userInfoId) {
                $query->where('sender_id', $userInfoId)
                      ->orWhere('receiver_id', $userInfoId);
            })
            ->where('sender_type', 'employee')
            ->where('receiver_type', 'employee')
            ->orderBy('last_message_time', 'DESC')
            ->paginate($perPage);
    }

    /**
     * Get messages in a conversation
     *
     * @param int $conversationId
     * @param int $userInfoId - For authorization check
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|null
     */
    public function getMessages(int $conversationId, int $userInfoId, int $perPage = 50)
    {
        // Verify user has access to this conversation
        $conversation = Conversation::where('id', $conversationId)
            ->where(function ($query) use ($userInfoId) {
                $query->where('sender_id', $userInfoId)
                      ->orWhere('receiver_id', $userInfoId);
            })
            ->first();

        if (!$conversation) {
            return null;
        }

        return Message::where('conversation_id', $conversationId)
            ->with('sender')
            ->orderBy('created_at', 'ASC')
            ->paginate($perPage);
    }

    /**
     * Send a message with file support
     *
     * @param int $senderId UserInfo ID
     * @param int $receiverId UserInfo ID
     * @param string|null $messageText
     * @param array $files
     * @return Message
     */
    public function sendMessage(int $senderId, int $receiverId, ?string $messageText, array $files = []): Message
    {
        return DB::transaction(function () use ($senderId, $receiverId, $messageText, $files) {
            // Find or create conversation
            $conversation = $this->findOrCreateConversation($senderId, $receiverId);

            // Create message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'message' => $messageText,
                'file' => !empty($files) ? json_encode($files) : null,
                'is_seen' => 0,
            ]);

            // Update conversation
            $conversation->last_message_id = $message->id;
            $conversation->last_message_time = now();
            $conversation->unread_message_count = ($conversation->unread_message_count ?? 0) + 1;
            $conversation->save();

            Log::info('Employee message sent', [
                'message_id' => $message->id,
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
            ]);

            return $message->load('sender');
        });
    }

    /**
     * Mark messages as read
     *
     * @param int $conversationId
     * @param int $userInfoId - User marking as read
     * @return bool
     */
    public function markAsRead(int $conversationId, int $userInfoId): bool
    {
        return DB::transaction(function () use ($conversationId, $userInfoId) {
            // Get conversation
            $conversation = Conversation::where('id', $conversationId)
                ->where(function ($query) use ($userInfoId) {
                    $query->where('sender_id', $userInfoId)
                          ->orWhere('receiver_id', $userInfoId);
                })
                ->first();

            if (!$conversation) {
                return false;
            }

            // Mark all messages as read where user is NOT the sender
            Message::where('conversation_id', $conversationId)
                ->where('sender_id', '!=', $userInfoId)
                ->where('is_seen', 0)
                ->update(['is_seen' => 1]);

            // Reset unread count
            $conversation->update(['unread_message_count' => 0]);

            return true;
        });
    }

    /**
     * Get all available employees for chat
     *
     * @param int $excludeUserInfoId - Exclude current user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableEmployees(int $excludeUserInfoId)
    {
        // Get all admin employees - approved (status = 1) OR super admins (role_id = 1)
        $admins = Admin::where(function($query) {
            $query->where('status', 1)
                  ->orWhere('role_id', 1);
        })->get();

        // Get or create UserInfo for each admin
        $employees = [];
        foreach ($admins as $admin) {
            $userInfo = $this->createOrGetUserInfo($admin);

            if ($userInfo->id !== $excludeUserInfoId) {
                $employees[] = [
                    'id' => $userInfo->id,
                    'admin_id' => $admin->id,
                    'name' => trim($admin->f_name . ' ' . $admin->l_name),
                    'email' => $admin->email,
                    'phone' => $admin->phone,
                    'image' => $userInfo->image_full_url,
                    'role' => $admin->role?->name ?? 'Employee',
                ];
            }
        }

        return collect($employees);
    }

    /**
     * Poll for new messages since timestamp
     *
     * @param int $userInfoId
     * @param int $sinceTimestamp Unix timestamp
     * @return array
     */
    public function pollNewMessages(int $userInfoId, int $sinceTimestamp): array
    {
        $since = date('Y-m-d H:i:s', $sinceTimestamp);

        // Get conversations where user is participant
        $conversationIds = Conversation::where(function ($query) use ($userInfoId) {
            $query->where('sender_id', $userInfoId)
                  ->orWhere('receiver_id', $userInfoId);
        })
        ->where('sender_type', 'employee')
        ->where('receiver_type', 'employee')
        ->pluck('id');

        // Get new messages in those conversations
        $messages = Message::whereIn('conversation_id', $conversationIds)
            ->where('created_at', '>', $since)
            ->where('sender_id', '!=', $userInfoId) // Don't return own messages
            ->with('sender')
            ->orderBy('created_at', 'ASC')
            ->get();

        return [
            'messages' => $messages,
            'timestamp' => time(),
        ];
    }
}
