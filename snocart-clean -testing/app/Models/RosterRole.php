<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RosterRole extends Model
{
    protected $fillable = [
        'name', 'description', 'color', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function shiftRosters(): HasMany
    {
        return $this->hasMany(ShiftRoster::class, 'roster_role_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
