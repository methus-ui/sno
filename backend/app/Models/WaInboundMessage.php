<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaInboundMessage extends Model
{
    use HasFactory;

    protected $table = 'wa_inbound_messages';

    protected $fillable = [
        'user_id',
        'phone',
        'message_text',
        'media_url',
        'media_type',
        'whatsapp_message_id',
        'campaign_id',
        'sentiment',
        'tags',
        'assigned_to',
        'status',
        'received_at',
        'read_at',
        'replied_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'received_at' => 'datetime',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    /**
     * Get the customer who sent the message.
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the campaign this message is replying to.
     */
    public function campaign()
    {
        return $this->belongsTo(WaCampaign::class, 'campaign_id');
    }

    /**
     * Get the admin assigned to this message.
     */
    public function assignedAdmin()
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    /**
     * Scope for unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    /**
     * Scope for messages with specific sentiment.
     */
    public function scopeSentiment($query, $sentiment)
    {
        return $query->where('sentiment', $sentiment);
    }

    /**
     * Scope for assigned messages.
     */
    public function scopeAssigned($query)
    {
        return $query->whereNotNull('assigned_to');
    }

    /**
     * Scope for unassigned messages.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Mark as read.
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Mark as replied.
     */
    public function markAsReplied(): bool
    {
        return $this->update([
            'status' => 'replied',
            'replied_at' => now(),
        ]);
    }

    /**
     * Check if message has media.
     */
    public function hasMedia(): bool
    {
        return !empty($this->media_url);
    }
}
