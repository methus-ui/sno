<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemPickupUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderId;
    public $orderDetailId;
    public $isPickedUp;
    public $pickedUpAt;

    /**
     * Create a new event instance.
     */
    public function __construct($orderId, $orderDetailId, $isPickedUp, $pickedUpAt = null)
    {
        $this->orderId = $orderId;
        $this->orderDetailId = $orderDetailId;
        $this->isPickedUp = $isPickedUp;
        $this->pickedUpAt = $pickedUpAt;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('order.' . $this->orderId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'item.pickup.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'order_detail_id' => $this->orderDetailId,
            'is_picked_up' => $this->isPickedUp,
            'picked_up_at' => $this->pickedUpAt ? $this->pickedUpAt->format('h:i A') : null,
        ];
    }
}
