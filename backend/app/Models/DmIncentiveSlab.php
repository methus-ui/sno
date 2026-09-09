<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmIncentiveSlab extends Model
{
    protected $fillable = [
        'title', 'type', 'min_value', 'max_value', 'bonus_amount', 'status',
    ];

    protected $casts = [
        'min_value' => 'float',
        'max_value' => 'float',
        'bonus_amount' => 'float',
        'status' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
