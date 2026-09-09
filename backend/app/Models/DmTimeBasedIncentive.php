<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DmTimeBasedIncentive extends Model
{
    protected $fillable = [
        'title', 'time_from', 'time_to', 'days_of_week', 'bonus_type',
        'bonus_value', 'zone_id', 'module_id', 'status', 'created_by',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'bonus_value' => 'float',
        'status' => 'integer',
        'zone_id' => 'integer',
        'module_id' => 'integer',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function isActiveNow(): bool
    {
        if (!$this->status) return false;

        $now = Carbon::now();
        $currentDay = $now->dayOfWeek;

        if ($this->days_of_week && !in_array($currentDay, $this->days_of_week)) {
            return false;
        }

        $from = Carbon::parse($this->time_from);
        $to = Carbon::parse($this->time_to);

        return $now->between($from, $to);
    }

    public function calculateBonus($orderAmount): float
    {
        if ($this->bonus_type === 'percentage') {
            return round(($orderAmount * $this->bonus_value) / 100, 2);
        }
        return $this->bonus_value;
    }
}
