<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DmRushActivation extends Model
{
    protected $fillable = [
        'rush_incentive_id', 'zone_id', 'started_at', 'ended_at',
        'pending_orders_count', 'dm_notified',
    ];

    protected $casts = [
        'rush_incentive_id' => 'integer',
        'zone_id' => 'integer',
        'pending_orders_count' => 'integer',
        'dm_notified' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function rushIncentive()
    {
        return $this->belongsTo(DmRushIncentive::class, 'rush_incentive_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function isActive(): bool
    {
        return is_null($this->ended_at);
    }

    public function scopeCurrentlyActive($query)
    {
        return $query->whereNull('ended_at');
    }

    public function endSurge(): void
    {
        $this->update(['ended_at' => now()]);
    }
}
