<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmReferralBonus extends Model
{
    protected $fillable = [
        'referrer_id', 'referred_id', 'bonus_amount', 'status',
        'paid_at', 'condition_met'
    ];

    protected $casts = [
        'bonus_amount' => 'float',
        'condition_met' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function referrer()
    {
        return $this->belongsTo(DeliveryMan::class, 'referrer_id');
    }

    public function referred()
    {
        return $this->belongsTo(DeliveryMan::class, 'referred_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
