<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCancelledEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $deliveryManId;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->deliveryManId = $order->delivery_man_id;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('admin-orders'), // For admin dashboard
        ];

        // Broadcast to the delivery man's channel if was assigned
        if ($this->deliveryManId) {
            $channels[] = new Channel('delivery-man.' . $this->deliveryManId);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.cancelled';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'order_cancelled',
            'order_id' => $this->order->id,
            'delivery_man_id' => $this->deliveryManId,
            'cancellation_reason' => $this->order->cancellation_reason,
            'order' => [
                'id' => $this->order->id,
                'order_status' => $this->order->order_status,
                'module_id' => $this->order->module_id,
                'zone_id' => $this->order->zone_id,
                'updated_at' => $this->order->updated_at?->toIso8601String(),
            ],
        ];
    }
}
