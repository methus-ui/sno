<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaCampaignRecipient extends Model
{
    protected $table = 'wa_campaign_recipients';

    protected $fillable = [
        'campaign_id',
        'user_id',
        'phone',
        'status',
        'whatsapp_message_id',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_reason',
        'retry_count',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the campaign this recipient belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WaCampaign::class, 'campaign_id');
    }

    /**
     * Get the user this recipient represents
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope: Sent messages
     */
    public function scopeSent($query)
    {
        return $query->whereIn('status', ['sent', 'delivered', 'read']);
    }

    /**
     * Scope: Delivered messages
     */
    public function scopeDelivered($query)
    {
        return $query->whereIn('status', ['delivered', 'read']);
    }

    /**
     * Scope: Read messages
     */
    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    /**
     * Scope: Failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
