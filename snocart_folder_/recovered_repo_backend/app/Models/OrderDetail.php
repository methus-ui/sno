<?php

namespace App\Models;

use App\Traits\ReportFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderDetail extends Model
{
    use HasFactory , ReportFilter;

    protected $casts = [
        'price' => 'float',
        'discount_on_item' => 'float',
        'total_add_on_price' => 'float',
        'tax_amount' => 'float',
        'item_id'=> 'integer',
        'order_id'=> 'integer',
        'quantity'=>'integer',
        'item_campaign_id'=>'integer',
        'is_unavailable' => 'boolean',
        'marked_unavailable_at' => 'datetime',
        'requested_mrp' => 'float',
        'is_outside_purchase' => 'boolean',
        'outside_purchase_cost' => 'float',
        'outside_purchase_store_id' => 'integer',
        'outside_purchase_approved_by' => 'integer',
        'outside_purchase_approved_at' => 'datetime',
        'is_picked_up' => 'boolean',
        'picked_up_at' => 'datetime',
    ];

    protected $primaryKey   = 'id';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function vendor()
    {
        return $this->order->store();
    }
    public function item()
    {
        return $this->belongsTo(Item::class,'item_id');
    }
    public function campaign()
    {
        return $this->belongsTo(ItemCampaign::class, 'item_campaign_id');
    }
    public function outsidePurchaseStore()
    {
        return $this->belongsTo(Store::class, 'outside_purchase_store_id');
    }

    protected static function boot(){
        parent::boot();
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->Has('order');
        });
    }
}
