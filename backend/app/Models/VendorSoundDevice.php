<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class VendorSoundDevice extends Model
{
    protected $table = 'vendor_sound_devices';

    protected $fillable = [
        'vendor_id',
        'store_id',
        'device_id',
        'device_name',
        'webhook_url',
        'api_key',
        'api_key_hash',
        'is_active',
        'last_ping_at',
        'wifi_ssid',
        'local_ip',
        'firmware_version',
        'settings',
        'paired_at',
        'paired_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'last_ping_at' => 'datetime',
        'paired_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'api_key_hash',
    ];

    /**
     * Generate a secure API key for device authentication
     *
     * @return string
     */
    public static function generateApiKey(): string
    {
        return 'SK_' . Str::random(48);
    }

    /**
     * Hash API key for storage
     *
     * @param string $apiKey
     * @return string
     */
    public static function hashApiKey(string $apiKey): string
    {
        return hash('sha256', $apiKey);
    }

    /**
     * Check if device is online (pinged within threshold)
     *
     * @return bool
     */
    public function isOnline(): bool
    {
        if (!$this->last_ping_at || !$this->is_active) {
            return false;
        }

        $offlineThreshold = config('soundbox.device.offline_threshold', 300); // seconds
        return $this->last_ping_at->diffInSeconds(now()) < $offlineThreshold;
    }

    /**
     * Update last ping timestamp
     *
     * @return bool
     */
    public function ping(): bool
    {
        return $this->update(['last_ping_at' => now()]);
    }

    /**
     * Get device settings with defaults
     *
     * @return array
     */
    public function getSettingsWithDefaults(): array
    {
        $defaults = [
            'volume' => 80,
            'language' => 'en',
            'auto_accept' => false,
            'auto_accept_timeout' => 300,
            'announcement_voice' => 'female',
            'play_sound' => true,
        ];

        return array_merge($defaults, $this->settings ?? []);
    }

    /**
     * Relationship: Vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Relationship: Store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Relationship: Paired By Employee
     */
    public function pairedBy(): BelongsTo
    {
        return $this->belongsTo(VendorEmployee::class, 'paired_by');
    }

    /**
     * Relationship: Webhook Queue
     */
    public function webhookQueue()
    {
        return $this->hasMany(DeviceWebhookQueue::class, 'device_id');
    }

    /**
     * Scope: Active devices only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Online devices (pinged recently)
     */
    public function scopeOnline($query)
    {
        $offlineThreshold = config('soundbox.device.offline_threshold', 300);
        return $query->where('is_active', true)
            ->where('last_ping_at', '>=', now()->subSeconds($offlineThreshold));
    }

    /**
     * Scope: Devices for specific store
     */
    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Accessor: Online status
     */
    public function getIsOnlineAttribute(): bool
    {
        return $this->isOnline();
    }

    /**
     * Accessor: Uptime in seconds (since last ping)
     */
    public function getUptimeAttribute(): ?int
    {
        return $this->last_ping_at ? now()->diffInSeconds($this->last_ping_at) : null;
    }
}
