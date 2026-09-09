<?php

namespace App\Http\Controllers\Admin\Zone;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Models\ZoneDeliveryFeeSchedule;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZoneDeliveryFeeScheduleController extends Controller
{
    /**
     * Display list of delivery fee schedules for a zone.
     */
    public function index(int $zoneId): View|RedirectResponse
    {
        $zone = Zone::find($zoneId);

        if (!$zone) {
            Toastr::error(translate('messages.zone_not_found'));
            return redirect()->route('admin.business-settings.zone.home');
        }

        $schedules = ZoneDeliveryFeeSchedule::where('zone_id', $zoneId)
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        $dayNames = ZoneDeliveryFeeSchedule::DAY_NAMES;

        return view('admin-views.zone.delivery-fee-schedule.index', compact('zone', 'schedules', 'dayNames'));
    }

    /**
     * Store a new delivery fee schedule.
     */
    public function store(Request $request, int $zoneId): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'day' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'fee_percentage' => 'required|numeric|min:-100|max:500',
            'message' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0|max:255',
        ]);

        $zone = Zone::find($zoneId);

        if (!$zone) {
            Toastr::error(translate('messages.zone_not_found'));
            return redirect()->route('admin.business-settings.zone.home');
        }

        ZoneDeliveryFeeSchedule::create([
            'zone_id' => $zoneId,
            'title' => $request->title,
            'day' => $request->day,
            'start_time' => $request->start_time . ':00',
            'end_time' => $request->end_time . ':00',
            'fee_percentage' => $request->fee_percentage,
            'message' => $request->message,
            'status' => true,
            'priority' => $request->priority ?? 0,
        ]);

        Toastr::success(translate('messages.schedule_created_successfully'));
        return back();
    }

    /**
     * Update an existing delivery fee schedule.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'day' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'fee_percentage' => 'required|numeric|min:-100|max:500',
            'message' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0|max:255',
        ]);

        $schedule = ZoneDeliveryFeeSchedule::find($id);

        if (!$schedule) {
            Toastr::error(translate('messages.schedule_not_found'));
            return back();
        }

        $schedule->update([
            'title' => $request->title,
            'day' => $request->day,
            'start_time' => $request->start_time . ':00',
            'end_time' => $request->end_time . ':00',
            'fee_percentage' => $request->fee_percentage,
            'message' => $request->message,
            'priority' => $request->priority ?? 0,
        ]);

        Toastr::success(translate('messages.schedule_updated_successfully'));
        return back();
    }

    /**
     * Update schedule status (active/inactive).
     */
    public function updateStatus(int $id, int $status): RedirectResponse
    {
        $schedule = ZoneDeliveryFeeSchedule::find($id);

        if (!$schedule) {
            Toastr::error(translate('messages.schedule_not_found'));
            return back();
        }

        $schedule->update(['status' => $status]);

        Toastr::success(translate('messages.schedule_status_updated'));
        return back();
    }

    /**
     * Delete a delivery fee schedule.
     */
    public function destroy(int $id): RedirectResponse
    {
        $schedule = ZoneDeliveryFeeSchedule::find($id);

        if (!$schedule) {
            Toastr::error(translate('messages.schedule_not_found'));
            return back();
        }

        $schedule->delete();

        Toastr::success(translate('messages.schedule_deleted_successfully'));
        return back();
    }
}
