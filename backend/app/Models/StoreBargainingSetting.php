<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreBargainingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'bargaining_enabled',
        'auto_participate',
        'manual_bidding_enabled',
        'notify_on_new_request',
        'notify_on_award',
        'notification_channel',
        'auto_discount_percentage',
        'min_order_value_for_discount',
        'max_discount_amount',
        'always_match_lowest_price',
        'price_match_buffer',
        'max_concurrent_bargains',
        'min_cart_value',
        'max_cart_items',
        'bidding_start_time',
        'bidding_end_time',
    ];

    protected $casts = [
        'bargaining_enabled' => 'boolean',
        'auto_participate' => 'boolean',
        'manual_bidding_enabled' => 'boolean',
        'notify_on_new_request' => 'boolean',
        'notify_on_award' => 'boolean',
        'auto_discount_percentage' => 'decimal:2',
        'min_order_value_for_discount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'always_match_lowest_price' => 'boolean',
        'price_match_buffer' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Scopes
     */
    public function scopeEnabled($query)
    {
        return $query->where('bargaining_enabled', true);
    }

    public function scopeAutoParticipating($query)
    {
        return $query->where('auto_participate', true);
    }

    public function scopeManualBiddingEnabled($query)
    {
        return $query->where('manual_bidding_enabled', true);
    }

    /**
     * Helpers
     */
    public function canAutoParticipate()
    {
        return $this->bargaining_enabled && $this->auto_participate;
    }

    public function canManualBid()
    {
        return $this->bargaining_enabled && $this->manual_bidding_enabled;
    }

    public function isWithinBiddingHours()
    {
        if (!$this->bidding_start_time || !$this->bidding_end_time) {
            return true; // No restrictions
        }

        $now = now()->format('H:i:s');
        return $now >= $this->bidding_start_time && $now <= $this->bidding_end_time;
    }

    public function getAutoDiscountAmount($orderValue)
    {
        if (!$this->auto_discount_percentage || $orderValue < $this->min_order_value_for_discount) {
            return 0;
        }

        $discount = ($orderValue * $this->auto_discount_percentage) / 100;

        if ($this->max_discount_amount) {
            $discount = min($discount, $this->max_discount_amount);
        }

        return round($discount, 2);
    }

    /**
     * Boot method - create default settings for new stores
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-create default settings when store is created
        Store::created(function ($store) {
            self::firstOrCreate(
                ['store_id' => $store->id],
                [
                    'bargaining_enabled' => true,
                    'auto_participate' => true,
                    'manual_bidding_enabled' => false,
                    'notify_on_award' => true,
                    'notification_channel' => 'push',
                ]
            );
        });
    }
}
