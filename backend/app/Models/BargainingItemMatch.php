<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BargainingItemMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'bargaining_cart_item_id',
        'store_id',
        'matched_item_id',
        'matched_campaign_id',
        'matched_type',
        'match_method',
        'match_score',
        'base_price',
        'discounted_price',
        'discount_amount',
        'discount_percentage',
        'flash_sale_active',
        'flash_sale_price',
        'flash_sale_ends_at',
        'in_stock',
        'stock_quantity',
        'max_order_quantity',
        'store_tax',
        'store_discount',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
        'base_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'flash_sale_price' => 'decimal:2',
        'store_tax' => 'decimal:2',
        'store_discount' => 'decimal:2',
        'flash_sale_active' => 'boolean',
        'in_stock' => 'boolean',
        'flash_sale_ends_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function bargainingCartItem()
    {
        return $this->belongsTo(BargainingCartItem::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function matchedItem()
    {
        return $this->belongsTo(Item::class, 'matched_item_id');
    }

    public function matchedCampaign()
    {
        return $this->belongsTo(Campaign::class, 'matched_campaign_id');
    }

    /**
     * Scopes
     */
    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeExactMatch($query)
    {
        return $query->where('match_method', 'barcode_exact');
    }

    /**
     * Helpers
     */
    public function getFinalPriceAttribute()
    {
        if ($this->flash_sale_active && $this->flash_sale_price) {
            return $this->flash_sale_price;
        }
        return $this->discounted_price;
    }

    public function isExactMatch()
    {
        return $this->match_method === 'barcode_exact';
    }

    public function getSavingsAttribute()
    {
        return $this->base_price - $this->final_price;
    }
}
