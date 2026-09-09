<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreBillLayout extends Model
{
    protected $fillable = [
        'store_id', 'field', 'position', 'line_ratio',
        'correction_from', 'correction_to', 'frequency',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'line_ratio' => 'float',
        'frequency' => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId)->orderByDesc('frequency');
    }

    public function scopeCorrections($query)
    {
        return $query->whereNotNull('correction_from')->whereNotNull('correction_to');
    }

    public function scopePositions($query)
    {
        return $query->whereNotNull('line_ratio');
    }
}
