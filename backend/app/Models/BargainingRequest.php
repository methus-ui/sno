<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BargainingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_code',
        'user_id',
        'guest_id',
        'zone_id',
        'module_id',
        'original_cart_snapshot',
        'original_cart_value',
        'total_cart_items',
        'delivery_address',
        'latitude',
        'longitude',
        'mode',
        'customer_budget',
        'status',
        'total_stores_matched',
        'total_offers_received',
        'awarded_store_id',
        'accepted_offer_id',
        'final_price',
        'total_savings',
        'expires_at',
        'awarded_at',
        'accepted_at',
        'cancelled_at',
    ];

    protected $casts = [
        'original_cart_snapshot' => 'array',
        'delivery_address' => 'array',
        'original_cart_value' => 'decimal:2',
        'customer_budget' => 'decimal:2',
        'final_price' => 'decimal:2',
        'total_savings' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'expires_at' => 'datetime',
        'awarded_at' => 'datetime',
        'accepted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Boot method - generate request code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->request_code)) {
                $model->request_code = 'BR-' . strtoupper(Str::random(6));
            }
        });
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function cartItems()
    {
        return $this->hasMany(BargainingCartItem::class);
    }

    public function storeOffers()
    {
        return $this->hasMany(BargainingStoreOffer::class);
    }

    public function awardedStore()
    {
        return $this->belongsTo(Store::class, 'awarded_store_id');
    }

    public function acceptedOffer()
    {
        return $this->belongsTo(BargainingStoreOffer::class, 'accepted_offer_id');
    }

    public function bestOffer()
    {
        return $this->hasOne(BargainingStoreOffer::class)
            ->where('is_best_offer', true)
            ->where('status', 'submitted');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['initiated', 'matching', 'matching_completed', 'offers_received']);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled', 'expired']);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForGuest($query, $guestId)
    {
        return $query->where('guest_id', $guestId);
    }

    /**
     * Helpers
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canAcceptOffers()
    {
        return in_array($this->status, ['offers_received', 'matching_completed'])
            && !$this->isExpired();
    }

    public function canCancel()
    {
        return in_array($this->status, ['initiated', 'matching', 'matching_completed', 'offers_received']);
    }

    public function getTimeRemainingAttribute()
    {
        if (!$this->expires_at) {
            return 0;
        }

        $seconds = now()->diffInSeconds($this->expires_at, false);
        return max(0, $seconds);
    }
}
