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

class NewBargainingOffer implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bargainingRequest;
    public $offer;
    public $isNewBestOffer;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\BargainingRequest  $bargainingRequest
     * @param  \App\Models\BargainingStoreOffer  $offer
     * @param  bool  $isNewBestOffer
     * @return void
     */
    public function __construct(BargainingRequest $bargainingRequest, BargainingStoreOffer $offer, $isNewBestOffer = false)
    {
        $this->bargainingRequest = $bargainingRequest;
        $this->offer = $offer->load('store:id,name,logo,rating');
        $this->isNewBestOffer = $isNewBestOffer;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Broadcast to customer's private channel
        if ($this->bargainingRequest->user_id) {
            return new PrivateChannel('user.' . $this->bargainingRequest->user_id . '.bargaining');
        }

        // For guest users, use public channel
        if ($this->bargainingRequest->guest_id) {
            return new Channel('guest.' . $this->bargainingRequest->guest_id . '.bargaining');
        }

        return [];
    }

    /**
     * Get the event name for broadcasting
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'bargaining.offer.new';
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
            'offer' => [
                'offer_id' => $this->offer->id,
                'store_id' => $this->offer->store_id,
                'store_name' => $this->offer->store->name,
                'store_logo' => $this->offer->store->logo_full_url,
                'store_rating' => (float) ($this->offer->store->rating ?? 0),
                'offer_type' => $this->offer->offer_type,
                'is_vendor_offer' => $this->offer->isVendorOffer(),
                'total_amount' => (float) $this->offer->total_amount,
                'items_available' => $this->offer->items_available,
                'items_missing' => $this->offer->items_missing,
                'fulfillment_percentage' => (float) $this->offer->fulfillment_percentage,
                'rank' => $this->offer->rank,
                'is_best_offer' => $this->offer->is_best_offer,
                'savings' => (float) ($this->bargainingRequest->original_cart_value - $this->offer->total_amount),
                'special_discount' => (float) ($this->offer->special_discount ?? 0),
                'vendor_notes' => $this->offer->vendor_notes,
            ],
            'is_new_best_offer' => $this->isNewBestOffer,
            'total_offers' => $this->bargainingRequest->storeOffers()->count(),
            'time_remaining' => $this->bargainingRequest->time_remaining,
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
        return config('bargaining.enabled', true);
    }
}
