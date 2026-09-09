<?php

namespace App\Http\Controllers\Admin\DeliveryMan;

use App\Models\DeliveryMan;
use App\Models\DmShiftBooking;
use App\Models\DmShiftSwapRequest;
use App\Models\ShiftTemplate;
use App\Services\DmShiftBookingService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ShiftManagementController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $bookings = DmShiftBooking::where('date', $date)
            ->with(['deliveryMan', 'shiftTemplate'])
            ->whereIn('status', ['active', 'scheduled', 'self_booked', 'completed'])
            ->orderBy('created_at')
            ->paginate(50);
        $templates = ShiftTemplate::where('is_active', 1)->get();
        $deliveryMen = DeliveryMan::withoutGlobalScopes()
            ->where('status', 1)
            ->where('application_status', 'approved')
            ->select('id', 'f_name', 'l_name', 'zone_id')
            ->get();

        return view('admin-views.delivery-man.shifts.index', compact('bookings', 'templates', 'deliveryMen', 'date'));
    }

    public function assignShift(Request $request)
    {
        $request->validate([
            'delivery_man_id' => 'required|exists:delivery_men,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'date' => 'required|date',
        ]);

        $service = new DmShiftBookingService();
        $service->bookShift(
            $request->delivery_man_id,
            $request->shift_template_id,
            $request->date,
            auth('admin')->id()
        );

        return back()->with('success', 'Shift assigned successfully.');
    }

    public function bulkAssign(Request $request)
    {
        $request->validate([
            'delivery_man_ids' => 'required|array',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'date' => 'required|date',
        ]);

        $service = new DmShiftBookingService();
        $count = 0;
        $failures = [];
        foreach ($request->delivery_man_ids as $dmId) {
            try {
                $service->bookShift($dmId, $request->shift_template_id, $request->date, auth('admin')->id());
                $count++;
            } catch (\Exception $e) {
                $failures[] = "DM #{$dmId}: " . $e->getMessage();
            }
        }

        if ($count > 0) {
            session()->flash('success', "Assigned shifts to {$count} delivery man(men).");
        }
        if (!empty($failures)) {
            session()->flash('warning', 'Some assignments failed: ' . implode(' | ', $failures));
        }

        return back();
    }

    public function shiftSwapRequests()
    {
        $requests = DmShiftSwapRequest::with(['requester', 'target', 'originalBooking.shiftTemplate'])
            ->latest()
            ->paginate(25);
        return view('admin-views.delivery-man.shifts.swap-requests', compact('requests'));
    }

    public function approveSwap($id)
    {
        // Note: Shift swap functionality needs to be reimplemented for the new booking system
        // For now, this is a placeholder
        return back()->with('error', 'Shift swap functionality is being updated. Please contact support.');
    }

    public function rejectSwap($id)
    {
        // Note: Shift swap functionality needs to be reimplemented for the new booking system
        // For now, this is a placeholder
        return back()->with('error', 'Shift swap functionality is being updated. Please contact support.');
    }
}
