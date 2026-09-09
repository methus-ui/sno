<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandSurgeLevel extends Model
{
    protected $fillable = [
        'zone_id', 'min_pending_orders', 'max_available_dms',
        'surge_percentage', 'is_enabled', 'message',
    ];

    protected $casts = [
        'zone_id' => 'integer',
        'min_pending_orders' => 'integer',
        'max_available_dms' => 'integer',
        'surge_percentage' => 'float',
        'is_enabled' => 'boolean',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForZone($query, $zoneId)
    {
        return $query->where(function ($q) use ($zoneId) {
            $q->where('zone_id', $zoneId)->orWhereNull('zone_id');
        });
    }
}
