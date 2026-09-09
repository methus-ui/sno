<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class DeliveryManLocationUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $deliveryManId;
    public $location;

    /**
     * Create a new event instance.
     *
     * @param int $deliveryManId
     * @param array $location Contains: longitude, latitude, speed, accuracy, heading, timestamp
     */
    public function __construct($deliveryManId, array $location)
    {
        $this->deliveryManId = $deliveryManId;
        $this->location      = $location;
    }

    public function broadcastOn()
    {
        return [
            new Channel('delivery-man.' . $this->deliveryManId),
        ];
    }

    public function broadcastAs()
    {
        return 'location.updated';
    }

    public function broadcastWith()
    {
        return [
            'delivery_man_id' => $this->deliveryManId,
            'location' => $this->location,
        ];
    }
}
