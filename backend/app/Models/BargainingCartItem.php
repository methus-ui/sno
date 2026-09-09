<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BargainingCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bargaining_request_id',
        'original_item_id',
        'original_campaign_id',
        'item_type',
        'item_name',
        'item_barcode',
        'category_id',
        'sub_category_id',
        'item_description',
        'quantity',
        'original_price',
        'original_discount',
        'variations',
        'add_ons',
        'match_method',
        'total_matches_found',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'original_discount' => 'decimal:2',
        'variations' => 'array',
        'add_ons' => 'array',
    ];

    /**
     * Relationships
     */
    public function bargainingRequest()
    {
        return $this->belongsTo(BargainingRequest::class);
    }

    public function originalItem()
    {
        return $this->belongsTo(Item::class, 'original_item_id');
    }

    public function originalCampaign()
    {
        return $this->belongsTo(Campaign::class, 'original_campaign_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function matches()
    {
        return $this->hasMany(BargainingItemMatch::class);
    }

    public function offerItems()
    {
        return $this->hasMany(BargainingOfferItem::class);
    }

    /**
     * Helpers
     */
    public function hasBarcode()
    {
        return !empty($this->item_barcode);
    }

    public function getMatchQualityAttribute()
    {
        if ($this->total_matches_found === 0) {
            return 'no_match';
        } elseif ($this->match_method === 'barcode') {
            return 'exact';
        } elseif ($this->match_method === 'fuzzy') {
            return 'similar';
        }
        return 'unknown';
    }

    public function getLineSubtotalAttribute()
    {
        return $this->quantity * $this->original_price;
    }
}
