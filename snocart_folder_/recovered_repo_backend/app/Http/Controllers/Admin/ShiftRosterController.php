<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShiftRoster;
use App\Models\ShiftTemplate;
use App\Models\RosterRole;
use App\Models\Admin;
use Carbon\Carbon;
use Brian2694\Toastr\Facades\Toastr;

class ShiftRosterController extends Controller
{
    public function index(Request $request)
    {
        $admin = auth('admin')->user();
        if ($admin->role_id != 1) {
            return redirect()->route('admin.shift-roster.my-shift');
        }

        $weekStart = $request->week_start
            ? Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $employees = Admin::where('role_id', '!=', 1)->get();
        $templates = ShiftTemplate::active()->get();
        $rosterRoles = RosterRole::active()->get();

        $rosters = ShiftRoster::forWeek($weekStart->format('Y-m-d'))
            ->with('admin', 'template', 'rosterRole')
            ->get()
            ->groupBy('admin_id');

        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return view('admin-views.shift-roster.index', compact(
            'weekStart', 'employees', 'templates', 'rosters', 'days', 'rosterRoles'
        ));
    }

    public function assign(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'week_start_date' => 'required|date',
            'is_off_day' => 'nullable|boolean',
        ]);

        $isOffDay = $request->is_off_day ?? false;

        if (!$isOffDay) {
            $request->validate([
                'shift_start' => 'required',
                'shift_end' => 'required',
            ]);

            $start = Carbon::parse($request->shift_start);
            $end = Carbon::parse($request->shift_end);
            $minutes = $start->diffInMinutes($end);

            // 9.5 hours = 540 minutes
            if ($minutes != 540) {
                Toastr::error(translate('messages.shift_must_be_9_hours_including_break'));
                return back();
            }
        }

        ShiftRoster::updateOrCreate(
            [
                'admin_id' => $request->admin_id,
                'day_of_week' => $request->day_of_week,
                'week_start_date' => $request->week_start_date,
            ],
            [
                'shift_start' => $isOffDay ? null : $request->shift_start,
                'shift_end' => $isOffDay ? null : $request->shift_end,
                'is_off_day' => $isOffDay,
                'shift_template_id' => $request->shift_template_id,
                'roster_role_id' => $request->roster_role_id,
                'created_by' => auth('admin')->id(),
            ]
        );

        Toastr::success(translate('messages.shift_assigned_successfully'));
        return back();
    }

    public function bulkAssign(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'week_start_date' => 'required|date',
            'off_days' => 'nullable|array',
            'off_days.*' => 'integer|min:0|max:6',
        ]);

        $template = ShiftTemplate::findOrFail($request->shift_template_id);
        $offDays = $request->off_days ?? [];

        for ($day = 0; $day <= 6; $day++) {
            $isOff = in_array($day, $offDays);

            ShiftRoster::updateOrCreate(
                [
                    'admin_id' => $request->admin_id,
                    'day_of_week' => $day,
                    'week_start_date' => $request->week_start_date,
                ],
                [
                    'shift_start' => $isOff ? null : $template->start_time,
                    'shift_end' => $isOff ? null : $template->end_time,
                    'is_off_day' => $isOff,
                    'shift_template_id' => $isOff ? null : $template->id,
                    'roster_role_id' => $isOff ? null : $request->roster_role_id,
                    'created_by' => auth('admin')->id(),
                ]
            );
        }

        Toastr::success(translate('messages.shifts_assigned_successfully'));
        return back();
    }

    public function copyWeek(Request $request)
    {
        $request->validate([
            'from_week' => 'required|date',
            'to_week' => 'required|date',
        ]);

        $sourceRosters = ShiftRoster::forWeek($request->from_week)->get();

        if ($sourceRosters->isEmpty()) {
            Toastr::error(translate('messages.no_roster_found_for_source_week'));
            return back();
        }

        foreach ($sourceRosters as $roster) {
            ShiftRoster::updateOrCreate(
                [
                    'admin_id' => $roster->admin_id,
                    'day_of_week' => $roster->day_of_week,
                    'week_start_date' => $request->to_week,
                ],
                [
                    'shift_start' => $roster->shift_start,
                    'shift_end' => $roster->shift_end,
                    'is_off_day' => $roster->is_off_day,
                    'shift_template_id' => $roster->shift_template_id,
                    'created_by' => auth('admin')->id(),
                ]
            );
        }

        Toastr::success(translate('messages.week_copied_successfully'));
        return redirect()->route('admin.shift-roster.index', ['week_start' => $request->to_week]);
    }

    public function templates()
    {
        $admin = auth('admin')->user();
        if ($admin->role_id != 1) {
            Toastr::error(translate('messages.access_denied'));
            return back();
        }

        $templates = ShiftTemplate::latest()->paginate(15);
        return view('admin-views.shift-roster.templates', compact('templates'));
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);
        if ($start->diffInMinutes($end) != 540) {
            Toastr::error(translate('messages.shift_must_be_9_hours_including_break'));
            return back();
        }

        ShiftTemplate::create([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'created_by' => auth('admin')->id(),
        ]);

        Toastr::success(translate('messages.template_created_successfully'));
        return back();
    }

    public function deleteTemplate($id)
    {
        ShiftTemplate::findOrFail($id)->delete();
        Toastr::success(translate('messages.template_deleted_successfully'));
        return back();
    }

    public function myShift(Request $request)
    {
        $admin = auth('admin')->user();
        $weekStart = $request->week_start
            ? Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $rosters = ShiftRoster::where('admin_id', $admin->id)
            ->forWeek($weekStart->format('Y-m-d'))
            ->with('template')
            ->get()
            ->keyBy('day_of_week');

        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return view('admin-views.shift-roster.my-shift', compact('weekStart', 'rosters', 'days'));
    }

    public function autoGenerateNextWeek(Request $request)
    {
        $currentWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $nextWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek()->format('Y-m-d');

        // Check if next week already has rosters
        $existingCount = ShiftRoster::forWeek($nextWeekStart)->count();
        if ($existingCount > 0) {
            Toastr::warning(translate('messages.next_week_roster_already_exists'));
            return back();
        }

        $sourceRosters = ShiftRoster::forWeek($currentWeekStart)->get();

        if ($sourceRosters->isEmpty()) {
            Toastr::error(translate('messages.no_roster_for_current_week'));
            return back();
        }

        foreach ($sourceRosters as $roster) {
            ShiftRoster::create([
                'admin_id' => $roster->admin_id,
                'day_of_week' => $roster->day_of_week,
                'week_start_date' => $nextWeekStart,
                'shift_start' => $roster->shift_start,
                'shift_end' => $roster->shift_end,
                'is_off_day' => $roster->is_off_day,
                'shift_template_id' => $roster->shift_template_id,
                'roster_role_id' => $roster->roster_role_id,
                'created_by' => auth('admin')->id(),
            ]);
        }

        Toastr::success(translate('messages.next_week_roster_generated'));
        return redirect()->route('admin.shift-roster.index', ['week_start' => $nextWeekStart]);
    }
}
