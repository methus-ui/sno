<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BargainingStoreOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'bargaining_request_id',
        'store_id',
        'offer_type',
        'items_available',
        'items_missing',
        'fulfillment_percentage',
        'subtotal',
        'item_discount',
        'store_discount',
        'flash_sale_discount',
        'coupon_discount',
        'tax_amount',
        'delivery_charge',
        'packaging_charge',
        'total_amount',
        'special_discount',
        'vendor_notes',
        'estimated_delivery_time',
        'submitted_by_employee_id',
        'rank',
        'is_best_offer',
        'status',
        'missing_items_detail',
        'substitutes_suggested',
        'submitted_at',
        'accepted_at',
        'expired_at',
    ];

    protected $casts = [
        'fulfillment_percentage' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'item_discount' => 'decimal:2',
        'store_discount' => 'decimal:2',
        'flash_sale_discount' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'packaging_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'special_discount' => 'decimal:2',
        'is_best_offer' => 'boolean',
        'missing_items_detail' => 'array',
        'substitutes_suggested' => 'array',
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function bargainingRequest()
    {
        return $this->belongsTo(BargainingRequest::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function offerItems()
    {
        return $this->hasMany(BargainingOfferItem::class);
    }

    public function submittedByEmployee()
    {
        return $this->belongsTo(VendorEmployee::class, 'submitted_by_employee_id');
    }

    /**
     * Scopes
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeBestOffer($query)
    {
        return $query->where('is_best_offer', true);
    }

    public function scopeAutoCalculated($query)
    {
        return $query->where('offer_type', 'auto_calculated');
    }

    public function scopeVendorSubmitted($query)
    {
        return $query->where('offer_type', 'vendor_submitted');
    }

    public function scopeOrderedByRank($query)
    {
        return $query->orderBy('rank', 'asc');
    }

    /**
     * Helpers
     */
    public function getTotalDiscountAttribute()
    {
        return $this->item_discount + $this->store_discount + $this->flash_sale_discount + $this->coupon_discount + ($this->special_discount ?? 0);
    }

    public function getSavingsVsOriginalAttribute()
    {
        $request = $this->bargainingRequest;
        if (!$request) {
            return 0;
        }
        return $request->original_cart_value - $this->total_amount;
    }

    public function isFullFulfillment()
    {
        return $this->items_missing === 0 && $this->fulfillment_percentage >= 100;
    }

    public function isPartialFulfillment()
    {
        return $this->items_missing > 0 && $this->fulfillment_percentage > 0 && $this->fulfillment_percentage < 100;
    }

    public function hasVendorDiscount()
    {
        return $this->special_discount && $this->special_discount > 0;
    }

    public function isAutoOffer()
    {
        return $this->offer_type === 'auto_calculated';
    }

    public function isVendorOffer()
    {
        return $this->offer_type === 'vendor_submitted';
    }
}
