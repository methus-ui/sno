<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmAutoAssignmentSetting extends Model
{
    protected $fillable = [
        'zone_id', 'is_enabled', 'max_radius_km', 'priority_factors',
        'fallback_to_manual_after_seconds', 'max_orders_per_dm'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'max_radius_km' => 'float',
        'priority_factors' => 'array',
        'max_orders_per_dm' => 'integer',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function getWeights()
    {
        return $this->priority_factors ?? [
            'distance_weight' => 0.4,
            'rating_weight' => 0.25,
            'acceptance_rate_weight' => 0.2,
            'tier_weight' => 0.15,
        ];
    }
}
