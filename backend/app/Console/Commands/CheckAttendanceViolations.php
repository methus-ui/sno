<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Carbon\Carbon;

class CheckAttendanceViolations extends Command
{
    protected $signature = 'attendance:check-violations';
    protected $description = 'Flag incomplete shifts and early departures from the previous day';

    public function handle()
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        // Find attendances from yesterday that are missing punch_out
        $noPunchOut = Attendance::where('attendance_date', $yesterday)
            ->whereNotNull('punch_in')
            ->whereNull('punch_out')
            ->get();

        foreach ($noPunchOut as $attendance) {
            $attendance->update([
                'shift_completed' => false,
                'notes' => trim(($attendance->notes ?? '') . ' [Auto-flagged: missing punch out]'),
            ]);
        }

        // Recalculate any that have punch_out but haven't been checked
        $completed = Attendance::where('attendance_date', $yesterday)
            ->whereNotNull('punch_in')
            ->whereNotNull('punch_out')
            ->where('shift_completed', false)
            ->whereNull('actual_work_hours')
            ->get();

        foreach ($completed as $attendance) {
            $attendance->calculateTotalBreakMinutes();
            $attendance->calculateActualWorkHours();
            $attendance->checkShiftCompletion();
        }

        $this->info("Checked {$noPunchOut->count()} missing punch-outs, {$completed->count()} incomplete calculations for {$yesterday}.");
        return 0;
    }
}
