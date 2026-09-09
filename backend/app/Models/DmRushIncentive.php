<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmRushIncentive extends Model
{
    protected $fillable = [
        'title', 'trigger_type', 'min_threshold', 'max_threshold', 'zone_id',
        'bonus_type', 'bonus_value', 'duration_minutes', 'status', 'created_by',
    ];

    protected $casts = [
        'min_threshold' => 'integer',
        'max_threshold' => 'integer',
        'bonus_value' => 'float',
        'duration_minutes' => 'integer',
        'status' => 'integer',
        'zone_id' => 'integer',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function activations()
    {
        return $this->hasMany(DmRushActivation::class, 'rush_incentive_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function calculateBonus($orderAmount): float
    {
        if ($this->bonus_type === 'percentage') {
            return round(($orderAmount * $this->bonus_value) / 100, 2);
        }
        return $this->bonus_value;
    }
}
