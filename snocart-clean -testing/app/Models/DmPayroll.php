<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmPayroll extends Model
{
    protected $fillable = [
        'delivery_man_id', 'salary_amount', 'incentive_amount', 'incentive_breakdown',
        'total_deliveries', 'avg_delivery_time', 'total_amount', 'deductions',
        'net_payable', 'period_from', 'period_to', 'status', 'paid_at',
        'paid_method', 'paid_ref', 'note', 'created_by',
    ];

    protected $casts = [
        'salary_amount' => 'float',
        'incentive_amount' => 'float',
        'incentive_breakdown' => 'array',
        'total_deliveries' => 'integer',
        'avg_delivery_time' => 'float',
        'total_amount' => 'float',
        'deductions' => 'float',
        'net_payable' => 'float',
        'period_from' => 'date',
        'period_to' => 'date',
        'paid_at' => 'datetime',
    ];

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function scopeGenerated($query)
    {
        return $query->where('status', 'generated');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForPeriod($query, $from, $to)
    {
        return $query->where('period_from', $from)->where('period_to', $to);
    }
}
