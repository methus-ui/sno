<?php

namespace App\Events\Messaging;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * NewMessageEvent
 *
 * Broadcasts when a new message is sent in a conversation.
 * Real-time message delivery to conversation participants.
 *
 * Channel: private-conversation-{conversationId}
 * Event name: NewMessageEvent
 */
class NewMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;
    public Conversation $conversation;

    /**
     * Create a new event instance
     */
    public function __construct(Message $message, Conversation $conversation)
    {
        $this->message = $message;
        $this->conversation = $conversation;
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation-' . $this->conversation->id),
        ];
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'new-message';
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'message' => $this->message->message,
                'file' => $this->message->file,
                'order_id' => $this->message->order_id,
                'is_system_message' => $this->message->is_system_message ?? false,
                'created_at' => $this->message->created_at->toIso8601String(),
                'created_at_human' => $this->message->created_at->diffForHumans(),
            ],
            'conversation' => [
                'id' => $this->conversation->id,
                'unread_count' => $this->conversation->unread_message_count,
            ],
            'timestamp' => now()->toIso8601String()
        ];
    }
}
