<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Scopes\ZoneScope;

class ProvideDMEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_man_id', 'method', 'ref', 'amount',
        'incentive_type', 'incentive_rule_id', 'order_id', 'metadata',
    ];

    protected $casts = [
        'amount' => 'float',
        'metadata' => 'array',
    ];

    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    protected static function booted()
    {
        static::addGlobalScope(new ZoneScope);
    }
}
