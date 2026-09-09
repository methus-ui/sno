<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MessageDeliveryStatus Model
 *
 * Tracks message delivery status with retry logic.
 * Supports multi-channel delivery (Pusher, FCM, Database).
 *
 * Statuses:
 * - pending: Waiting to be delivered
 * - sent: Successfully sent to delivery channel
 * - delivered: Confirmed delivered to recipient device
 * - read: Recipient has read the message
 * - failed: All delivery attempts failed
 *
 * @property int $id
 * @property int $message_id
 * @property string $status
 * @property string|null $delivery_channel
 * @property int $retry_count
 * @property \Carbon\Carbon|null $last_retry_at
 * @property string|null $error_message
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MessageDeliveryStatus extends Model
{
    use HasFactory;

    /**
     * The table associated with the model
     */
    protected $table = 'message_delivery_status';

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'message_id',
        'status',
        'delivery_channel',
        'retry_count',
        'last_retry_at',
        'error_message',
        'metadata',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'last_retry_at' => 'datetime',
        'metadata' => 'array',
        'retry_count' => 'integer',
    ];

    /**
     * Get the message that this delivery status belongs to
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Check if delivery is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if delivery was sent
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if delivery was delivered
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if message was read
     */
    public function isRead(): bool
    {
        return $this->status === 'read';
    }

    /**
     * Check if delivery failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if max retries reached
     */
    public function hasMaxRetriesReached(): bool
    {
        return $this->retry_count >= 3; // Max retries from MessageDeliveryService::MAX_RETRIES
    }

    /**
     * Get delivery status icon for display
     */
    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'sent' => '✓',
            'delivered' => '✓✓',
            'read' => '🔵',
            'failed' => '❌',
            default => '⏳',
        };
    }

    /**
     * Get human-readable status
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'sent' => 'Sent',
            'delivered' => 'Delivered',
            'read' => 'Read',
            'failed' => 'Failed',
            default => 'Unknown',
        };
    }

    /**
     * Scope to get pending deliveries
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get failed deliveries
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get deliveries eligible for retry
     */
    public function scopeEligibleForRetry($query)
    {
        return $query->where('status', 'failed')
            ->where('retry_count', '<', 3)
            ->where(function($q) {
                $q->whereNull('last_retry_at')
                    ->orWhere('last_retry_at', '<=', now()->subSeconds(5));
            });
    }
}
