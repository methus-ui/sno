<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaCustomerSegment extends Model
{
    protected $table = 'wa_customer_segments';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'filters',
        'customer_count',
        'last_calculated_at',
        'created_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'last_calculated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get customers in this segment (many-to-many)
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wa_segment_customers', 'segment_id', 'user_id')
            ->withPivot('added_at');
    }

    /**
     * Get the admin who created this segment
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Scope: Predefined segments
     */
    public function scopePredefined($query)
    {
        return $query->where('type', 'predefined');
    }

    /**
     * Scope: Custom segments
     */
    public function scopeCustom($query)
    {
        return $query->where('type', 'custom');
    }

    /**
     * Check if segment needs refresh (older than 24 hours)
     */
    public function needsRefresh(): bool
    {
        if (!$this->last_calculated_at) {
            return true;
        }

        return $this->last_calculated_at->diffInHours(now()) >= 24;
    }
}
