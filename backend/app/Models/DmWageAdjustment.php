<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmWageAdjustment extends Model
{
    protected $fillable = [
        'delivery_man_id', 'date', 'total_earned', 'min_guaranteed',
        'adjustment_amount', 'hours_logged', 'status'
    ];

    protected $casts = [
        'date' => 'date',
        'total_earned' => 'float',
        'min_guaranteed' => 'float',
        'adjustment_amount' => 'float',
        'hours_logged' => 'float',
    ];

    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
