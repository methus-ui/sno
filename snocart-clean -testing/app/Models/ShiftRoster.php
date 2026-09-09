<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\RosterRole;

class ShiftRoster extends Model
{
    protected $fillable = [
        'admin_id', 'day_of_week', 'shift_start', 'shift_end',
        'is_off_day', 'week_start_date', 'shift_template_id', 'created_by',
        'roster_role_id',
    ];

    protected $casts = [
        'is_off_day' => 'boolean',
        'week_start_date' => 'date',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function rosterRole(): BelongsTo
    {
        return $this->belongsTo(RosterRole::class, 'roster_role_id');
    }

    public function scopeForWeek($query, $weekStartDate)
    {
        return $query->where('week_start_date', $weekStartDate);
    }
}
