<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficLog extends Model
{
    protected $fillable = [
        'date', 'unique_visitors', 'total_requests', 'customer_requests',
        'dm_requests', 'vendor_requests', 'app_opens', 'peak_hour',
        'top_endpoints', 'hourly_distribution',
    ];

    protected $casts = [
        'date' => 'date',
        'top_endpoints' => 'array',
        'hourly_distribution' => 'array',
    ];
}
