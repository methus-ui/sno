<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmLeaderboard extends Model
{
    protected $fillable = [
        'delivery_man_id', 'period_type', 'period_start', 'period_end',
        'deliveries_completed', 'total_earnings', 'avg_rating',
        'acceptance_rate', 'rank_position', 'zone_id',
    ];

    protected $casts = [
        'delivery_man_id' => 'integer',
        'deliveries_completed' => 'integer',
        'total_earnings' => 'float',
        'avg_rating' => 'float',
        'acceptance_rate' => 'float',
        'rank_position' => 'integer',
        'zone_id' => 'integer',
    ];

    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
