<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherSurgeRule extends Model
{
    protected $fillable = [
        'zone_id', 'weather_condition', 'surge_percentage',
        'min_temp', 'max_temp', 'is_enabled', 'message',
    ];

    protected $casts = [
        'zone_id' => 'integer',
        'surge_percentage' => 'float',
        'min_temp' => 'float',
        'max_temp' => 'float',
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
