<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmMinWageSetting extends Model
{
    protected $table = 'dm_minimum_wage_settings';

    protected $fillable = [
        'zone_id', 'dm_type', 'min_daily_amount', 'min_hours_required', 'status'
    ];

    protected $casts = [
        'min_daily_amount' => 'float',
        'min_hours_required' => 'float',
        'status' => 'boolean',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
