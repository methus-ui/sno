<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BargainingOfferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bargaining_store_offer_id',
        'bargaining_cart_item_id',
        'matched_item_id',
        'matched_campaign_id',
        'matched_type',
        'is_available',
        'substitute_suggested',
        'substitute_item_id',
        'substitute_notes',
        'quantity',
        'unit_price',
        'discount_per_unit',
        'final_price_per_unit',
        'line_total',
        'flash_sale_applied',
        'flash_sale_savings',
        'variation_prices',
        'addon_prices',
        'variation_total',
        'addon_total',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'substitute_suggested' => 'boolean',
        'flash_sale_applied' => 'boolean',
        'unit_price' => 'decimal:2',
        'discount_per_unit' => 'decimal:2',
        'final_price_per_unit' => 'decimal:2',
        'line_total' => 'decimal:2',
        'flash_sale_savings' => 'decimal:2',
        'variation_total' => 'decimal:2',
        'addon_total' => 'decimal:2',
        'variation_prices' => 'array',
        'addon_prices' => 'array',
    ];

    /**
     * Relationships
     */
    public function bargainingStoreOffer()
    {
        return $this->belongsTo(BargainingStoreOffer::class);
    }

    public function bargainingCartItem()
    {
        return $this->belongsTo(BargainingCartItem::class);
    }

    public function matchedItem()
    {
        return $this->belongsTo(Item::class, 'matched_item_id');
    }

    public function matchedCampaign()
    {
        return $this->belongsTo(Campaign::class, 'matched_campaign_id');
    }

    public function substituteItem()
    {
        return $this->belongsTo(Item::class, 'substitute_item_id');
    }

    /**
     * Scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeUnavailable($query)
    {
        return $query->where('is_available', false);
    }

    public function scopeWithSubstitutes($query)
    {
        return $query->where('substitute_suggested', true);
    }

    /**
     * Helpers
     */
    public function getTotalPriceAttribute()
    {
        return $this->line_total + $this->variation_total + $this->addon_total;
    }

    public function getSavingsPerUnitAttribute()
    {
        return $this->unit_price - $this->final_price_per_unit;
    }

    public function getTotalSavingsAttribute()
    {
        return ($this->unit_price - $this->final_price_per_unit) * $this->quantity + $this->flash_sale_savings;
    }

    public function hasSubstitute()
    {
        return $this->substitute_suggested && $this->substitute_item_id;
    }
}
