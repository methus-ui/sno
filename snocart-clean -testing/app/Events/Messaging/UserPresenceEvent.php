<?php

namespace App\Events\Messaging;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

/**
 * UserPresenceEvent
 *
 * Broadcasts when a user goes online or offline.
 * Real-time presence indicators (green dot = online, gray = offline).
 *
 * Channel: private-{userType}-{userInfoId}
 * Event name: UserPresenceEvent
 */
class UserPresenceEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userInfoId;
    public string $userType;
    public string $status; // 'online' or 'offline'
    public Carbon $lastSeen;

    /**
     * Create a new event instance
     */
    public function __construct(
        int $userInfoId,
        string $userType,
        string $status,
        Carbon $lastSeen
    ) {
        $this->userInfoId = $userInfoId;
        $this->userType = $userType;
        $this->status = $status; // 'online' or 'offline'
        $this->lastSeen = $lastSeen;
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->userType . '-' . $this->userInfoId),
        ];
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'presence';
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'user_info_id' => $this->userInfoId,
            'user_type' => $this->userType,
            'status' => $this->status, // 'online' or 'offline'
            'last_seen' => $this->lastSeen->toIso8601String(),
            'last_seen_human' => $this->lastSeen->diffForHumans(),
            'timestamp' => now()->toIso8601String()
        ];
    }
}
