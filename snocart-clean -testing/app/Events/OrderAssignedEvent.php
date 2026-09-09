<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAssignedEvent implements ShouldBroadcast
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

        // Broadcast to the delivery man's channel
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
        return 'order.assigned';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Handle created_at - could be string or Carbon instance
        $createdAt = $this->order->created_at;
        if (is_string($createdAt)) {
            $createdAt = \Carbon\Carbon::parse($createdAt)->toIso8601String();
        } elseif ($createdAt instanceof \Carbon\Carbon) {
            $createdAt = $createdAt->toIso8601String();
        }

        return [
            'type' => 'order_assigned',
            'order_id' => $this->order->id,
            'delivery_man_id' => $this->deliveryManId,
            'order' => [
                'id' => $this->order->id,
                'order_status' => $this->order->order_status,
                'module_id' => $this->order->module_id,
                'zone_id' => $this->order->zone_id,
                'customer_name' => $this->order->customer?->f_name . ' ' . $this->order->customer?->l_name,
                'store_name' => $this->order->store?->name,
                'order_amount' => $this->order->order_amount,
                'delivery_address' => $this->order->delivery_address,
                'created_at' => $createdAt,
            ],
        ];
    }
}
