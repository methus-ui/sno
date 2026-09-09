<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NearbyOrdersEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deliveryManId;
    public $orders;

    /**
     * Create a new event instance.
     *
     * @param int $deliveryManId
     * @param array $orders Array of order data
     */
    public function __construct($deliveryManId, array $orders)
    {
        $this->deliveryManId = $deliveryManId;
        $this->orders = $orders;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('delivery-man.' . $this->deliveryManId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'orders.nearby';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'nearby_orders',
            'orders' => $this->orders,
        ];
    }
}
