<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmZoneNotificationLog extends Model
{
    protected $fillable = [
        'zone_id', 'order_id', 'notification_type',
        'dms_notified_count', 'notification_payload', 'sent_at',
    ];

    protected $casts = [
        'zone_id' => 'integer',
        'order_id' => 'integer',
        'dms_notified_count' => 'integer',
        'notification_payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
