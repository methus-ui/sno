<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmDailyIncentiveRule extends Model
{
    protected $fillable = [
        'title', 'delivery_count', 'bonus_amount', 'status', 'created_by',
    ];

    protected $casts = [
        'delivery_count' => 'integer',
        'bonus_amount' => 'float',
        'status' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
