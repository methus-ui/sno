<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmInstantWithdrawal extends Model
{
    protected $fillable = [
        'delivery_man_id', 'amount', 'status', 'method',
        'account_details', 'requested_at', 'processed_at', 'transaction_ref'
    ];

    protected $casts = [
        'amount' => 'float',
        'account_details' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
