<?php

namespace App\Events\Messaging;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MessageDeliveryStatusEvent
 *
 * Broadcasts when message delivery status changes.
 * Shows delivery receipts: sent ✓, delivered ✓✓, read 🔵
 *
 * Channel: private-message-{messageId}
 * Event name: MessageDeliveryStatusEvent
 */
class MessageDeliveryStatusEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $messageId;
    public string $status; // 'sent', 'delivered', 'read', 'failed'

    /**
     * Create a new event instance
     */
    public function __construct(Message $message, string $status)
    {
        $this->messageId = $message->id;
        $this->status = $status;
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
        return 'delivery-status';
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        // Get delivery status icon
        $icons = [
            'sent' => '✓',
            'delivered' => '✓✓',
            'read' => '🔵',
            'failed' => '❌'
        ];

        return [
            'message_id' => $this->messageId,
            'status' => $this->status,
            'icon' => $icons[$this->status] ?? '',
            'timestamp' => now()->toIso8601String()
        ];
    }
}
