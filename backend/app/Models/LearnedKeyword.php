<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearnedKeyword extends Model
{
    protected $fillable = [
        'keyword',
        'frequency',
        'status',
        'category',
        'last_seen_at'
    ];

    protected $casts = [
        'last_seen_at' => 'datetime'
    ];

    /**
     * Scope to get pending keywords.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved keywords.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get high frequency keywords.
     */
    public function scopeHighFrequency($query, $min = 5)
    {
        return $query->where('frequency', '>=', $min);
    }
}
