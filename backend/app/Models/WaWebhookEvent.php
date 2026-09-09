<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaWebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'wa_webhook_events';

    protected $fillable = [
        'campaign_id',
        'recipient_id',
        'event_type',
        'whatsapp_message_id',
        'phone',
        'event_timestamp',
        'raw_payload',
        'error_code',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'event_timestamp' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the campaign that owns the webhook event.
     */
    public function campaign()
    {
        return $this->belongsTo(WaCampaign::class, 'campaign_id');
    }

    /**
     * Get the recipient that owns the webhook event.
     */
    public function recipient()
    {
        return $this->belongsTo(WaCampaignRecipient::class, 'recipient_id');
    }

    /**
     * Scope for unprocessed events.
     */
    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    /**
     * Scope for specific event type.
     */
    public function scopeEventType($query, $type)
    {
        return $query->where('event_type', $type);
    }
}
