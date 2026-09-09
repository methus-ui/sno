<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaAbTest extends Model
{
    use HasFactory;

    protected $table = 'wa_ab_tests';

    protected $fillable = [
        'name',
        'campaign_id_a',
        'campaign_id_b',
        'split_ratio',
        'winning_metric',
        'status',
        'winner_campaign_id',
        'confidence_level',
        'test_duration_days',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'split_ratio' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get campaign A.
     */
    public function campaignA()
    {
        return $this->belongsTo(WaCampaign::class, 'campaign_id_a');
    }

    /**
     * Get campaign B.
     */
    public function campaignB()
    {
        return $this->belongsTo(WaCampaign::class, 'campaign_id_b');
    }

    /**
     * Get the winning campaign.
     */
    public function winner()
    {
        return $this->belongsTo(WaCampaign::class, 'winner_campaign_id');
    }

    /**
     * Get the creator.
     */
    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Scope for running tests.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope for completed tests.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if test is running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if test is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if test has a winner.
     */
    public function hasWinner(): bool
    {
        return !is_null($this->winner_campaign_id);
    }
}
