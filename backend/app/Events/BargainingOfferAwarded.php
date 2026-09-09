<?php

namespace App\Events;

use App\Models\BargainingRequest;
use App\Models\BargainingStoreOffer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BargainingOfferAwarded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bargainingRequest;
    public $awardedOffer;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\BargainingRequest  $bargainingRequest
     * @param  \App\Models\BargainingStoreOffer  $awardedOffer
     * @return void
     */
    public function __construct(BargainingRequest $bargainingRequest, BargainingStoreOffer $awardedOffer)
    {
        $this->bargainingRequest = $bargainingRequest;
        $this->awardedOffer = $awardedOffer->load('store:id,name,logo');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $channels = [];

        // Broadcast to winning store's private channel
        $channels[] = new PrivateChannel('store.' . $this->awardedOffer->store_id . '.bargaining');

        // Also broadcast to customer (if they're still listening)
        if ($this->bargainingRequest->user_id) {
            $channels[] = new PrivateChannel('user.' . $this->bargainingRequest->user_id . '.bargaining');
        }

        return $channels;
    }

    /**
     * Get the event name for broadcasting
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'bargaining.offer.awarded';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'request_code' => $this->bargainingRequest->request_code,
            'awarded_store_id' => $this->awardedOffer->store_id,
            'awarded_store_name' => $this->awardedOffer->store->name,
            'offer_id' => $this->awardedOffer->id,
            'offer_type' => $this->awardedOffer->offer_type,
            'total_amount' => (float) $this->awardedOffer->total_amount,
            'total_savings' => (float) $this->bargainingRequest->total_savings,
            'items_count' => $this->awardedOffer->items_available,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Determine if this event should broadcast.
     *
     * @return bool
     */
    public function shouldBroadcast()
    {
        return config('bargaining.enabled', true)
            && config('bargaining.notifications.vendor.offer_awarded', true);
    }
}
