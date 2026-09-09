<?php

namespace App\Events;

use App\Models\BargainingRequest;
use App\Models\Store;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BargainingRequestAvailable implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bargainingRequest;
    public $storeIds;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\BargainingRequest  $bargainingRequest
     * @param  array  $storeIds
     * @return void
     */
    public function __construct(BargainingRequest $bargainingRequest, array $storeIds = [])
    {
        $this->bargainingRequest = $bargainingRequest;
        $this->storeIds = $storeIds;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Broadcast to zone-specific vendor channel
        // All vendors in this zone will receive the notification
        return new Channel('zone.' . $this->bargainingRequest->zone_id . '.bargaining.requests');
    }

    /**
     * Get the event name for broadcasting
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'bargaining.request.available';
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
            'mode' => $this->bargainingRequest->mode,
            'total_items' => $this->bargainingRequest->total_cart_items,
            'estimated_value' => (float) $this->bargainingRequest->original_cart_value,
            'zone_id' => $this->bargainingRequest->zone_id,
            'module_id' => $this->bargainingRequest->module_id,
            'stores_matched' => $this->bargainingRequest->total_stores_matched,
            'eligible_store_ids' => $this->storeIds, // Stores that can bid on this request
            'time_remaining' => $this->bargainingRequest->time_remaining,
            'expires_at' => $this->bargainingRequest->expires_at?->toIso8601String(),
            'customer_budget' => $this->bargainingRequest->customer_budget ? (float) $this->bargainingRequest->customer_budget : null,
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
        // Only broadcast for wait mode (manual bidding)
        return config('bargaining.enabled', true)
            && $this->bargainingRequest->mode === 'wait'
            && config('bargaining.notifications.vendor.new_request_available', true);
    }
}
