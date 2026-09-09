<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'admin_id',
        'punch_in',
        'punch_out',
        'attendance_date',
        'total_hours',
        'status',
        'notes',
        'total_break_minutes',
        'allocated_break_minutes',
        'extra_break_minutes',
        'expected_shift_end',
        'shift_completed',
        'early_departure',
        'actual_work_hours',
    ];

    protected $casts = [
        'punch_in' => 'datetime',
        'punch_out' => 'datetime',
        'attendance_date' => 'date',
        'total_hours' => 'decimal:2',
        'expected_shift_end' => 'datetime',
        'shift_completed' => 'boolean',
        'early_departure' => 'boolean',
        'actual_work_hours' => 'decimal:2',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(EmployeeBreak::class);
    }

    public function calculateTotalHours()
    {
        if ($this->punch_in && $this->punch_out) {
            $punchIn = Carbon::parse($this->punch_in);
            $punchOut = Carbon::parse($this->punch_out);
            $totalMinutes = $punchOut->diffInMinutes($punchIn);
            $this->total_hours = round($totalMinutes / 60, 2);
            $this->save();
        }
    }

    public function calculateTotalBreakMinutes()
    {
        $total = $this->breaks()->whereNotNull('duration_minutes')->sum('duration_minutes');
        $this->total_break_minutes = $total;
        $this->extra_break_minutes = max(0, $total - $this->allocated_break_minutes);
        $this->save();
        return $total;
    }

    public function calculateExpectedShiftEnd()
    {
        if ($this->punch_in) {
            // Total shift = 9hr (540 min) including 30min break. Extra breaks extend shift.
            $totalMinutes = 540 + $this->extra_break_minutes;
            $this->expected_shift_end = Carbon::parse($this->punch_in)->addMinutes($totalMinutes);
            $this->save();
        }
    }

    public function calculateActualWorkHours()
    {
        if ($this->punch_in && $this->punch_out) {
            $punchIn = Carbon::parse($this->punch_in);
            $punchOut = Carbon::parse($this->punch_out);
            $totalMinutes = $punchOut->diffInMinutes($punchIn);
            $workMinutes = $totalMinutes - $this->total_break_minutes;
            $this->actual_work_hours = round($workMinutes / 60, 2);
            $this->save();
        }
    }

    public function checkShiftCompletion()
    {
        if ($this->actual_work_hours !== null) {
            // 510 minutes = 8.5 hours (9hr shift minus 30min break)
            $this->shift_completed = ($this->actual_work_hours * 60) >= 510;
            $this->early_departure = !$this->shift_completed && $this->punch_out && $this->expected_shift_end
                && Carbon::parse($this->punch_out)->lt(Carbon::parse($this->expected_shift_end));
            $this->save();
        }
    }

    public function scopeToday($query)
    {
        return $query->whereDate('attendance_date', today());
    }

    public function scopeWeekly($query)
    {
        return $query->whereBetween('attendance_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeMonthly($query)
    {
        return $query->whereBetween('attendance_date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }
}
