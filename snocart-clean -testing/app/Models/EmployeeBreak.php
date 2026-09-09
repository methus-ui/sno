<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EmployeeBreak extends Model
{
    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id', 'break_start', 'break_end', 'duration_minutes', 'notes',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('break_start')->whereNull('break_end');
    }

    public function calculateDuration()
    {
        if ($this->break_start && $this->break_end) {
            $this->duration_minutes = Carbon::parse($this->break_start)->diffInMinutes(Carbon::parse($this->break_end));
            $this->save();
        }
    }
}
