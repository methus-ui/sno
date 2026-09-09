<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\EmployeeBreak;
use Carbon\Carbon;

class BreakController extends Controller
{
    public function startBreak(Request $request)
    {
        $admin = auth('admin')->user();

        if ($admin->role_id == 1) {
            return response()->json(['error' => translate('messages.master_admin_cannot_punch')], 403);
        }

        $today = Carbon::now()->format('Y-m-d');
        $attendance = Attendance::where('admin_id', $admin->id)
            ->where('attendance_date', $today)
            ->whereNotNull('punch_in')
            ->whereNull('punch_out')
            ->first();

        if (!$attendance) {
            return response()->json(['error' => translate('messages.no_active_attendance')], 400);
        }

        // Check if already on break
        $activeBreak = EmployeeBreak::where('attendance_id', $attendance->id)->active()->first();
        if ($activeBreak) {
            return response()->json(['error' => translate('messages.already_on_break')], 400);
        }

        $break = EmployeeBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => translate('messages.break_started'),
            'break_id' => $break->id,
            'break_start' => $break->break_start->format('H:i:s'),
        ]);
    }

    public function endBreak(Request $request)
    {
        $admin = auth('admin')->user();

        if ($admin->role_id == 1) {
            return response()->json(['error' => translate('messages.master_admin_cannot_punch')], 403);
        }

        $today = Carbon::now()->format('Y-m-d');
        $attendance = Attendance::where('admin_id', $admin->id)
            ->where('attendance_date', $today)
            ->whereNotNull('punch_in')
            ->whereNull('punch_out')
            ->first();

        if (!$attendance) {
            return response()->json(['error' => translate('messages.no_active_attendance')], 400);
        }

        $activeBreak = EmployeeBreak::where('attendance_id', $attendance->id)->active()->first();
        if (!$activeBreak) {
            return response()->json(['error' => translate('messages.no_active_break')], 400);
        }

        $activeBreak->update([
            'break_end' => Carbon::now(),
            'notes' => $request->notes,
        ]);
        $activeBreak->calculateDuration();

        // Recalculate attendance break totals and expected shift end
        $attendance->calculateTotalBreakMinutes();
        $attendance->calculateExpectedShiftEnd();

        return response()->json([
            'success' => true,
            'message' => translate('messages.break_ended'),
            'duration_minutes' => $activeBreak->duration_minutes,
            'total_break_minutes' => $attendance->total_break_minutes,
            'extra_break_minutes' => $attendance->extra_break_minutes,
            'expected_shift_end' => $attendance->expected_shift_end ? $attendance->expected_shift_end->format('h:i A') : null,
        ]);
    }

    public function getBreakStatus(Request $request)
    {
        $admin = auth('admin')->user();

        if ($admin->role_id == 1) {
            return response()->json(['error' => translate('messages.master_admin_cannot_punch')], 403);
        }

        $today = Carbon::now()->format('Y-m-d');
        $attendance = Attendance::where('admin_id', $admin->id)
            ->where('attendance_date', $today)
            ->whereNotNull('punch_in')
            ->whereNull('punch_out')
            ->with('breaks')
            ->first();

        if (!$attendance) {
            return response()->json(['on_break' => false, 'has_attendance' => false]);
        }

        $activeBreak = $attendance->breaks()->active()->first();

        return response()->json([
            'has_attendance' => true,
            'on_break' => $activeBreak !== null,
            'active_break' => $activeBreak ? [
                'id' => $activeBreak->id,
                'break_start' => $activeBreak->break_start->format('H:i:s'),
            ] : null,
            'total_break_minutes' => $attendance->total_break_minutes,
            'extra_break_minutes' => $attendance->extra_break_minutes,
            'allocated_break_minutes' => $attendance->allocated_break_minutes,
            'expected_shift_end' => $attendance->expected_shift_end ? $attendance->expected_shift_end->format('h:i A') : null,
            'breaks' => $attendance->breaks->map(function ($b) {
                return [
                    'break_start' => $b->break_start->format('H:i:s'),
                    'break_end' => $b->break_end ? $b->break_end->format('H:i:s') : null,
                    'duration_minutes' => $b->duration_minutes,
                ];
            }),
        ]);
    }
}
