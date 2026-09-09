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
 * MessageReactionEvent
 *
 * Broadcasts when a user adds, updates, or removes a reaction on a message.
 * Real-time reaction updates (thumbs up, heart, laugh, etc.).
 *
 * Channel: private-message-{messageId}
 * Event name: MessageReactionEvent
 */
class MessageReactionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $messageId;
    public int $userInfoId;
    public string $reactionType;
    public string $action; // 'added', 'updated', 'removed'

    /**
     * Create a new event instance
     */
    public function __construct(
        int $messageId,
        int $userInfoId,
        string $reactionType,
        string $action
    ) {
        $this->messageId = $messageId;
        $this->userInfoId = $userInfoId;
        $this->reactionType = $reactionType;
        $this->action = $action; // 'added', 'updated', 'removed'
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('message-' . $this->messageId),
        ];
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'reaction';
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        // Get emoji for reaction type
        $emojis = [
            'thumbs_up' => '👍',
            'heart' => '❤️',
            'laugh' => '😂',
            'sad' => '😢',
            'angry' => '😠',
            'wow' => '😮'
        ];

        return [
            'message_id' => $this->messageId,
            'user_info_id' => $this->userInfoId,
            'reaction_type' => $this->reactionType,
            'emoji' => $emojis[$this->reactionType] ?? '❓',
            'action' => $this->action, // 'added', 'updated', 'removed'
            'timestamp' => now()->toIso8601String()
        ];
    }
}
