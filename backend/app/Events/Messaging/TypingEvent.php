<?php

namespace App\Events\Messaging;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * TypingEvent
 *
 * Broadcasts when a user starts or stops typing in a conversation.
 * Real-time typing indicators for better user experience.
 *
 * Channel: private-conversation-{conversationId}
 * Event name: TypingEvent
 */
class TypingEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $conversationId;
    public int $userInfoId;
    public string $userType;
    public string $action; // 'start' or 'stop'

    /**
     * Create a new event instance
     */
    public function __construct(
        int $conversationId,
        int $userInfoId,
        string $userType,
        string $action
    ) {
        $this->conversationId = $conversationId;
        $this->userInfoId = $userInfoId;
        $this->userType = $userType;
        $this->action = $action; // 'start' or 'stop'
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation-' . $this->conversationId),
        ];
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'typing';
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_info_id' => $this->userInfoId,
            'user_type' => $this->userType,
            'action' => $this->action, // 'start' or 'stop'
            'timestamp' => now()->toIso8601String()
        ];
    }
}
