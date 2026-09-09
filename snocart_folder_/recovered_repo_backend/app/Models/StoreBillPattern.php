<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreBillPattern extends Model
{
    protected $fillable = ['store_id', 'field', 'pattern', 'frequency'];

    protected $casts = [
        'store_id' => 'integer',
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
}
