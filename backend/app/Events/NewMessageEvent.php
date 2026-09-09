<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversationId;
    public $senderType;
    public $senderName;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, string $senderType, string $senderName)
    {
        $this->message = $message;
        $this->conversationId = $message->conversation_id;
        $this->senderType = $senderType;
        $this->senderName = $senderName;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversationId),
            new Channel('admin-messages'),
            new Channel('admin-notifications'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'new-message';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->conversationId,
            'message' => $this->message->message,
            'sender_type' => $this->senderType,
            'sender_name' => $this->senderName,
            'sender_id' => $this->message->sender_id,
            'file' => $this->message->file_full_url,
            'is_auto_reply' => $this->message->is_auto_reply ?? false,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
