<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmFuelIncentive extends Model
{
    protected $fillable = [
        'title', 'rate_per_km', 'fuel_price_reference', 'status',
        'zone_id', 'effective_from', 'effective_to'
    ];

    protected $casts = [
        'rate_per_km' => 'float',
        'fuel_price_reference' => 'float',
        'status' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true)
            ->where(function ($q) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            });
    }
}
