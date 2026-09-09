<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryManBarcodeSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_man_id',
        'order_id',
        'order_detail_id',
        'item_id',
        'submitted_barcode',
        'expected_barcode',
        'is_match',
        'submitted_at',
        'submission_type',
        'metadata'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'is_match' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Relationships
     */
    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
