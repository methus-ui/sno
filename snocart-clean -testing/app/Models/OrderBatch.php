<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderBatch extends Model
{
    protected $fillable = [
        'batch_code', 'delivery_man_id', 'zone_id', 'status', 'total_orders'
    ];

    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'batch_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['assigned', 'in_progress']);
    }
}
