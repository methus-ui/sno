<?php

namespace App\Events;

use App\Models\BargainingRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BargainingStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bargainingRequest;
    public $oldStatus;
    public $newStatus;
    public $metadata;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\BargainingRequest  $bargainingRequest
     * @param  string  $oldStatus
     * @param  string  $newStatus
     * @param  array  $metadata
     * @return void
     */
    public function __construct(BargainingRequest $bargainingRequest, $oldStatus, $newStatus, $metadata = [])
    {
        $this->bargainingRequest = $bargainingRequest;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->metadata = $metadata;
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

        // For guest users, use guest ID channel
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
        return 'bargaining.status.changed';
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
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'total_stores_matched' => $this->bargainingRequest->total_stores_matched,
            'total_offers_received' => $this->bargainingRequest->total_offers_received,
            'time_remaining' => $this->bargainingRequest->time_remaining,
            'expires_at' => $this->bargainingRequest->expires_at?->toIso8601String(),
            'metadata' => $this->metadata,
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
        // Only broadcast if bargaining is enabled
        return config('bargaining.enabled', true);
    }
}
