<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmSosRequest extends Model
{
    protected $fillable = [
        'delivery_man_id', 'order_id', 'type', 'latitude', 'longitude',
        'status', 'resolved_by', 'resolved_at', 'notes'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'resolved_at' => 'datetime',
    ];

    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function resolvedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
