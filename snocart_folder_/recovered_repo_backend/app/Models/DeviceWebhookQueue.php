<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DeviceWebhookQueue extends Model
{
    protected $table = 'device_webhook_queue';

    protected $fillable = [
        'device_id',
        'order_id',
        'webhook_url',
        'payload',
        'attempts',
        'last_attempt_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * Check if webhook should be retried
     *
     * @return bool
     */
    public function shouldRetry(): bool
    {
        $maxAttempts = config('soundbox.webhook.retry_attempts', 3);
        return $this->status === self::STATUS_PENDING && $this->attempts < $maxAttempts;
    }

    /**
     * Increment attempt counter
     *
     * @return bool
     */
    public function incrementAttempts(): bool
    {
        return $this->update([
            'attempts' => $this->attempts + 1,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Mark as success
     *
     * @return bool
     */
    public function markAsSuccess(): bool
    {
        return $this->update([
            'status' => self::STATUS_SUCCESS,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     *
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Check if max attempts reached
     *
     * @return bool
     */
    public function maxAttemptsReached(): bool
    {
        $maxAttempts = config('soundbox.webhook.retry_attempts', 3);
        return $this->attempts >= $maxAttempts;
    }

    /**
     * Relationship: Device
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(VendorSoundDevice::class, 'device_id');
    }

    /**
     * Relationship: Order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Scope: Pending webhooks
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Failed webhooks
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Webhooks needing retry
     */
    public function scopeNeedingRetry($query)
    {
        $maxAttempts = config('soundbox.webhook.retry_attempts', 3);
        $retryDelay = config('soundbox.webhook.retry_delay', 30); // seconds

        return $query->where('status', self::STATUS_PENDING)
            ->where('attempts', '<', $maxAttempts)
            ->where(function ($q) use ($retryDelay) {
                $q->whereNull('last_attempt_at')
                    ->orWhere('last_attempt_at', '<=', now()->subSeconds($retryDelay));
            });
    }

    /**
     * Scope: Webhooks for specific device
     */
    public function scopeForDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Accessor: Can retry
     */
    public function getCanRetryAttribute(): bool
    {
        return $this->shouldRetry();
    }
}
