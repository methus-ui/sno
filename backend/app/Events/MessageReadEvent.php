<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReadEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $messageIds;
    public $readBy;
    public $readByType;
    public $readAt;

    /**
     * Create a new event instance.
     */
    public function __construct(int $conversationId, array $messageIds, int $readBy, string $readByType)
    {
        $this->conversationId = $conversationId;
        $this->messageIds = $messageIds;
        $this->readBy = $readBy;
        $this->readByType = $readByType;
        $this->readAt = now()->toIso8601String();
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
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_ids' => $this->messageIds,
            'read_by' => $this->readBy,
            'read_by_type' => $this->readByType,
            'read_at' => $this->readAt,
        ];
    }
}
