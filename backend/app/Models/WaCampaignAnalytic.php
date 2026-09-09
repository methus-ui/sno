<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaCampaignAnalytic extends Model
{
    protected $table = 'wa_campaign_analytics';
    protected $primaryKey = 'campaign_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'read_count',
        'failed_count',
        'delivery_rate',
        'read_rate',
        'orders_placed_24h',
        'orders_placed_7d',
        'orders_placed_30d',
        'revenue_generated_24h',
        'revenue_generated_7d',
        'revenue_generated_30d',
        'conversion_rate_24h',
        'conversion_rate_7d',
        'avg_order_value',
        'roi',
        'processing_time_seconds',
        'calculated_at',
    ];

    protected $casts = [
        'delivery_rate' => 'decimal:2',
        'read_rate' => 'decimal:2',
        'revenue_generated_24h' => 'decimal:2',
        'revenue_generated_7d' => 'decimal:2',
        'revenue_generated_30d' => 'decimal:2',
        'conversion_rate_24h' => 'decimal:2',
        'conversion_rate_7d' => 'decimal:2',
        'avg_order_value' => 'decimal:2',
        'roi' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the campaign these analytics belong to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WaCampaign::class, 'campaign_id');
    }

    /**
     * Get delivery success rate percentage
     */
    public function getDeliverySuccessRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return round(($this->delivered_count / $this->sent_count) * 100, 2);
    }

    /**
     * Get engagement rate (read/delivered) percentage
     */
    public function getEngagementRateAttribute(): float
    {
        if ($this->delivered_count === 0) {
            return 0;
        }

        return round(($this->read_count / $this->delivered_count) * 100, 2);
    }

    /**
     * Get overall conversion rate percentage
     */
    public function getOverallConversionRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round(($this->orders_placed_30d / $this->total_recipients) * 100, 2);
    }
}
