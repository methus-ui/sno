<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaCampaign extends Model
{
    protected $table = 'wa_campaigns';

    protected $fillable = [
        'name',
        'image_id',
        'caption',
        'audience',
        'segment_ids',
        'total',
        'sent',
        'failed',
        'started_at',
        'finished_at',
        'scheduled_at',
        'status',
        'created_by',
        'processing_time_seconds',
    ];

    protected $casts = [
        'segment_ids' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get recipients for this campaign
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(WaCampaignRecipient::class, 'campaign_id');
    }

    /**
     * Get analytics for this campaign
     */
    public function analytics(): HasOne
    {
        return $this->hasOne(WaCampaignAnalytic::class, 'campaign_id');
    }

    /**
     * Get journey events for this campaign
     */
    public function journeyEvents(): HasMany
    {
        return $this->hasMany(WaCustomerJourney::class, 'campaign_id');
    }

    /**
     * Get the admin who created this campaign
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Scope: Running campaigns
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: Completed campaigns
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Failed campaigns
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Scheduled campaigns
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'draft')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Accessor: sent_count (alias for sent column)
     */
    public function getSentCountAttribute()
    {
        return $this->attributes['sent'] ?? 0;
    }

    /**
     * Accessor: total_recipients (alias for total column)
     */
    public function getTotalRecipientsAttribute()
    {
        return $this->attributes['total'] ?? 0;
    }

    /**
     * Accessor: failed_count (alias for failed column)
     */
    public function getFailedCountAttribute()
    {
        return $this->attributes['failed'] ?? 0;
    }

    /**
     * Accessor: delivered_count (from analytics)
     */
    public function getDeliveredCountAttribute()
    {
        return $this->analytics->delivered_count ?? 0;
    }

    /**
     * Accessor: read_count (from analytics)
     */
    public function getReadCountAttribute()
    {
        return $this->analytics->read_count ?? 0;
    }
}
