<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaCustomerJourney extends Model
{
    protected $table = 'wa_customer_journey';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'campaign_id',
        'event_type',
        'event_data',
        'event_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'event_at' => 'datetime',
    ];

    /**
     * Get the user this journey belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the campaign this journey is related to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WaCampaign::class, 'campaign_id');
    }

    /**
     * Scope: Campaign sent events
     */
    public function scopeCampaignSent($query)
    {
        return $query->where('event_type', 'campaign_sent');
    }

    /**
     * Scope: Message delivered events
     */
    public function scopeMessageDelivered($query)
    {
        return $query->where('event_type', 'message_delivered');
    }

    /**
     * Scope: Message read events
     */
    public function scopeMessageRead($query)
    {
        return $query->where('event_type', 'message_read');
    }

    /**
     * Scope: Order placed events
     */
    public function scopeOrderPlaced($query)
    {
        return $query->where('event_type', 'order_placed');
    }

    /**
     * Scope: Events for specific campaign
     */
    public function scopeForCampaign($query, $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope: Events for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
