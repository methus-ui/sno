<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'keywords',
        'category_id',
        'priority',
        'is_active',
        'hit_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'hit_count' => 'integer',
        'category_id' => 'integer',
    ];

    /**
     * Scope a query to only include active FAQs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Scope a query to order by most used.
     */
    public function scopePopular($query)
    {
        return $query->orderBy('hit_count', 'desc');
    }

    /**
     * Get keywords as array.
     */
    public function getKeywordsArrayAttribute(): array
    {
        if (empty($this->keywords)) {
            return [];
        }
        return array_map('trim', explode(',', strtolower($this->keywords)));
    }

    /**
     * Increment hit count.
     */
    public function recordHit(): void
    {
        $this->increment('hit_count');
    }
}
