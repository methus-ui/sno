<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmPerformanceTier extends Model
{
    protected $fillable = [
        'name', 'rank_order', 'icon', 'color', 'min_rating', 'min_deliveries',
        'min_acceptance_rate', 'bonus_per_delivery', 'priority_boost', 'status',
    ];

    protected $casts = [
        'rank_order' => 'integer',
        'min_rating' => 'float',
        'min_deliveries' => 'integer',
        'min_acceptance_rate' => 'integer',
        'bonus_per_delivery' => 'float',
        'priority_boost' => 'integer',
        'status' => 'integer',
    ];

    public function deliveryMen()
    {
        return $this->hasMany(DeliveryMan::class, 'current_tier_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('rank_order', 'asc');
    }
}
