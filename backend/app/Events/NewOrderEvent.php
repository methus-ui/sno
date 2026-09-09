<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('admin-orders'), // For admin dashboard
            new Channel('orders-zone-' . $this->order->zone_id), // For delivery men in same zone
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.new';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'new_order',
            'order_id' => $this->order->id,
            'order' => [
                'id' => $this->order->id,
                'order_status' => $this->order->order_status,
                'module_id' => $this->order->module_id,
                'zone_id' => $this->order->zone_id,
                'customer_name' => $this->order->customer?->f_name . ' ' . $this->order->customer?->l_name,
                'store_name' => $this->order->store?->name,
                'order_amount' => $this->order->order_amount,
                'delivery_address' => $this->order->delivery_address,
                'created_at' => $this->order->created_at?->toIso8601String(),
            ],
        ];
    }
}
