<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\EmployeeBreak;
use App\Models\LeaveRequest;
use App\Models\Admin;
use App\Exports\AttendanceReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Brian2694\Toastr\Facades\Toastr;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // Only master admin can view all attendance
        $admin = auth('admin')->user();
        
        $employees = Admin::where('role_id', '!=', 1)->get();
        $totalEmployees = $employees->count();
        
        $query = Attendance::with('admin');
        
        // If not master admin, only show their own attendance
        if ($admin->role_id != 1) {
            $query->where('admin_id', $admin->id);
        }
        
        // Filter by employee (only for master admin)
        if ($admin->role_id == 1 && $request->has('employee_id') && $request->employee_id) {
            $query->where('admin_id', $request->employee_id);
        }
        
        // Filter by date range
        $today = Carbon::today()->format('Y-m-d');
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        
        $query->whereBetween('attendance_date', [$startDate, $endDate]);
        
        $attendances = $query->orderBy('attendance_date', 'desc')->paginate(15);
        
        // Calculate employee statistics for today (only for master admin)
        $presentEmployees = 0;
        $absentEmployees = 0;
        $onLeaveEmployees = 0;
        
        if ($admin->role_id == 1) {
            // Get today's attendance for all employees
            $todayAttendance = Attendance::where('attendance_date', $today)
                ->whereIn('admin_id', $employees->pluck('id'))
                ->get();
            
            $presentEmployees = $todayAttendance->where('status', 'present')->count();
            $absentEmployees = $totalEmployees - $presentEmployees;
            
            // You'll need to implement leave checking logic here
            // This is just a placeholder - adjust according to your leave system
            $onLeaveEmployees = 0; // Replace with actual leave count
        }
        
        // Get today's attendance for punch in/out buttons
        $todayAttendance = null;
        $canPunchIn = false;
        $canPunchOut = false;
        
        if ($admin->role_id != 1) {
            $todayAttendance = Attendance::where('admin_id', $admin->id)
                ->where('attendance_date', $today)
                ->first();
                
            $canPunchIn = !$todayAttendance || ($todayAttendance && $todayAttendance->punch_out);
            $canPunchOut = $todayAttendance && $todayAttendance->punch_in && !$todayAttendance->punch_out;
        }
        
        return view('admin-views.attendance.index', compact(
            'employees',
            'totalEmployees',
            'presentEmployees',
            'absentEmployees',
            'onLeaveEmployees',
            'attendances',
            'startDate',
            'endDate',
            'todayAttendance',
            'canPunchIn',
            'canPunchOut'
        ));
    }

    public function punchIn(Request $request)
    {
        $admin = auth('admin')->user();

        // Check if user is not master admin (role_id != 1)
        if ($admin->role_id == 1) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Master admin cannot punch in/out'], 403);
            }
            Toastr::error('Master admin cannot punch in/out');
            return redirect()->back();
        }

        $today = Carbon::now()->format('Y-m-d');

        // Check if already punched in today
        $attendance = Attendance::where('admin_id', $admin->id)
            ->where('attendance_date', $today)
            ->first();

        if ($attendance && $attendance->punch_in && !$attendance->punch_out) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'You have already punched in today'], 400);
            }
            Toastr::error('You have already punched in today');
            return redirect()->back();
        }

        $now = Carbon::now();
        $expectedEnd = $now->copy()->addMinutes(540); // 9hr work + 30min break

        // Create new attendance record
        Attendance::create([
            'admin_id' => $admin->id,
            'punch_in' => $now,
            'attendance_date' => $today,
            'status' => 'present',
            'expected_shift_end' => $expectedEnd,
            'allocated_break_minutes' => 30,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Punched in successfully at ' . $now->format('H:i:s')
            ]);
        }

        Toastr::success('Punched in successfully at ' . $now->format('H:i:s'));
        return redirect()->back();
    }

    public function punchOut(Request $request)
    {
        $admin = auth('admin')->user();

        // Check if user is not master admin (role_id != 1)
        if ($admin->role_id == 1) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Master admin cannot punch in/out'], 403);
            }
            Toastr::error('Master admin cannot punch in/out');
            return redirect()->back();
        }

        $today = Carbon::now()->format('Y-m-d');

        // Find today's attendance record
        $attendance = Attendance::where('admin_id', $admin->id)
            ->where('attendance_date', $today)
            ->whereNotNull('punch_in')
            ->whereNull('punch_out')
            ->first();

        if (!$attendance) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'No punch in record found for today'], 400);
            }
            Toastr::error('No punch in record found for today');
            return redirect()->back();
        }

        // End any active break first
        $activeBreak = EmployeeBreak::where('attendance_id', $attendance->id)->active()->first();
        if ($activeBreak) {
            $activeBreak->update(['break_end' => Carbon::now()]);
            $activeBreak->calculateDuration();
        }

        // Update punch out time
        $attendance->update([
            'punch_out' => Carbon::now(),
            'notes' => $request->notes
        ]);

        // Calculate all metrics
        $attendance->calculateTotalHours();
        $attendance->calculateTotalBreakMinutes();
        $attendance->calculateActualWorkHours();
        $attendance->checkShiftCompletion();

        $msg = 'Punched out successfully at ' . Carbon::now()->format('H:i:s');
        if ($attendance->early_departure) {
            $msg .= ' (Early departure - shift incomplete)';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'early_departure' => true
                ]);
            }
            Toastr::warning($msg);
        } else {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg
                ]);
            }
            Toastr::success($msg);
        }
        return redirect()->back();
    }

    public function show($id)
    {
        $attendance = Attendance::with(['admin', 'breaks'])->findOrFail($id);
        return view('admin-views.attendance.show', compact('attendance'));
    }

    public function attendanceReport()
    {
        // Only master admin can access this
        $admin = auth('admin')->user();
        if ($admin->role_id != 1) {
            abort(403, 'Unauthorized');
        }

        $employees = Admin::where('role_id', '!=', 1)->get();
        
        return view('admin-views.attendance.report', compact('employees'));
    }

    public function exportAttendanceReport(Request $request)
    {
        // Only master admin can access this
        $admin = auth('admin')->user();
        if ($admin->role_id != 1) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'employee_id' => 'nullable|exists:admins,id',
            'report_type' => 'required|in:weekly,monthly',
            'export_type' => 'required|in:excel,csv'
        ]);

        // Get date range based on report type
        if ($request->report_type == 'weekly') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
        } else {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }

        // Build query
        $query = Attendance::with('admin')
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($request->employee_id) {
            $query->where('admin_id', $request->employee_id);
        }

        $attendances = $query->get();
        $employee = $request->employee_id ? Admin::find($request->employee_id) : null;

        $data = [
            'attendances' => $attendances,
            'employee' => $employee,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'report_type' => $request->report_type
        ];

        $filename = 'AttendanceReport_' . $request->report_type . '_' . Carbon::now()->format('Y-m-d');
        
        if ($request->export_type == 'excel') {
            return Excel::download(new AttendanceReportExport($data), $filename . '.xlsx');
        }
        
        return Excel::download(new AttendanceReportExport($data), $filename . '.csv');
    }

    public function getTodayAttendance()
    {
        $admin = auth('admin')->user();

        if ($admin->role_id == 1) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $today = Carbon::now()->format('Y-m-d');
        $attendance = Attendance::where('admin_id', $admin->id)
            ->where('attendance_date', $today)
            ->first();

        // Check if face verification is required
        $faceRequired = $admin->role_id != 1;
        $faceRegistered = $admin->face_registered;

        return response()->json([
            'attendance' => $attendance,
            'can_punch_in' => !$attendance || ($attendance && $attendance->punch_out),
            'can_punch_out' => $attendance && $attendance->punch_in && !$attendance->punch_out,
            'face_required' => $faceRequired,
            'face_registered' => $faceRegistered,
            'face_data' => $faceRegistered ? $admin->face_data : null
        ]);
    }

    /**
     * Punch in with face verification
     */
    public function punchInWithFace(Request $request)
    {
        $request->validate([
            'face_verified' => 'required|boolean',
        ]);

        $admin = auth('admin')->user();

        // Check if user is not master admin
        if ($admin->role_id == 1) {
            return response()->json(['error' => translate('messages.master_admin_cannot_punch')], 403);
        }

        // Check if face is registered
        if (!$admin->face_registered) {
            return response()->json(['error' => translate('messages.face_not_registered')], 400);
        }

        // Verify face verification flag
        if (!$request->face_verified) {
            return response()->json(['error' => translate('messages.face_verification_required')], 400);
        }

        $today = Carbon::now()->format('Y-m-d');

        // Check if already punched in today
        $attendance = Attendance::where('admin_id', $admin->id)
            ->where('attendance_date', $today)
            ->first();

        if ($attendance && $attendance->punch_in && !$attendance->punch_out) {
            return response()->json(['error' => translate('messages.already_punched_in')], 400);
        }

        $now = Carbon::now();
        // Create new attendance record
        $attendance = Attendance::create([
            'admin_id' => $admin->id,
            'punch_in' => $now,
            'attendance_date' => $today,
            'status' => 'present',
            'punch_in_face_verified' => true,
            'expected_shift_end' => $now->copy()->addMinutes(540),
            'allocated_break_minutes' => 30,
        ]);

        return response()->json([
            'success' => true,
            'message' => translate('messages.punched_in_successfully'),
            'punch_in_time' => $attendance->punch_in->format('H:i:s')
        ]);
    }

    /**
     * Punch out with face verification
     */
    public function punchOutWithFace(Request $request)
    {
        $request->validate([
            'face_verified' => 'required|boolean',
            'notes' => 'nullable|string|max:500'
        ]);

        $admin = auth('admin')->user();

        // Check if user is not master admin
        if ($admin->role_id == 1) {
            return response()->json(['error' => translate('messages.master_admin_cannot_punch')], 403);
        }

        // Check if face is registered
        if (!$admin->face_registered) {
            return response()->json(['error' => translate('messages.face_not_registered')], 400);
        }

        // Verify face verification flag
        if (!$request->face_verified) {
            return response()->json(['error' => translate('messages.face_verification_required')], 400);
        }

        $today = Carbon::now()->format('Y-m-d');

        // Find today's attendance record
        $attendance = Attendance::where('admin_id', $admin->id)
            ->where('attendance_date', $today)
            ->whereNotNull('punch_in')
            ->whereNull('punch_out')
            ->first();

        if (!$attendance) {
            return response()->json(['error' => translate('messages.no_punch_in_record')], 400);
        }

        // Update punch out time
        $attendance->update([
            'punch_out' => Carbon::now(),
            'notes' => $request->notes,
            'punch_out_face_verified' => true
        ]);

        // Calculate total hours
        $attendance->calculateTotalHours();

        return response()->json([
            'success' => true,
            'message' => translate('messages.punched_out_successfully'),
            'punch_out_time' => $attendance->punch_out->format('H:i:s'),
            'total_hours' => $attendance->total_hours
        ]);
    }
}