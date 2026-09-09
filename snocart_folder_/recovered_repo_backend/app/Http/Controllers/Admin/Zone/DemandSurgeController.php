<?php

namespace App\Http\Controllers\Admin\Zone;

use App\Http\Controllers\Controller;
use App\Models\DemandSurgeLevel;
use App\Models\Zone;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DemandSurgeController extends Controller
{
    public function index(int $zoneId): View|RedirectResponse
    {
        $zone = Zone::find($zoneId);
        if (!$zone) {
            Toastr::error(translate('messages.zone_not_found'));
            return redirect()->route('admin.business-settings.zone.home');
        }

        $levels = DemandSurgeLevel::where('zone_id', $zoneId)
            ->orWhereNull('zone_id')
            ->orderBy('min_pending_orders')
            ->get();

        return view('admin-views.zone.demand-surge.index', compact('zone', 'levels'));
    }

    public function store(Request $request, int $zoneId): RedirectResponse
    {
        $request->validate([
            'min_pending_orders' => 'required|integer|min:1',
            'max_available_dms' => 'required|integer|min:0',
            'surge_percentage' => 'required|numeric|min:0|max:500',
            'message' => 'nullable|string|max:255',
        ]);

        DemandSurgeLevel::create([
            'zone_id' => $zoneId,
            'min_pending_orders' => $request->min_pending_orders,
            'max_available_dms' => $request->max_available_dms,
            'surge_percentage' => $request->surge_percentage,
            'is_enabled' => true,
            'message' => $request->message,
        ]);

        Toastr::success(translate('messages.demand_surge_level_created'));
        return back();
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'min_pending_orders' => 'required|integer|min:1',
            'max_available_dms' => 'required|integer|min:0',
            'surge_percentage' => 'required|numeric|min:0|max:500',
            'message' => 'nullable|string|max:255',
        ]);

        DemandSurgeLevel::findOrFail($id)->update([
            'min_pending_orders' => $request->min_pending_orders,
            'max_available_dms' => $request->max_available_dms,
            'surge_percentage' => $request->surge_percentage,
            'message' => $request->message,
        ]);

        Toastr::success(translate('messages.demand_surge_level_updated'));
        return back();
    }

    public function updateStatus(int $id, int $status): RedirectResponse
    {
        DemandSurgeLevel::findOrFail($id)->update(['is_enabled' => $status]);
        Toastr::success(translate('messages.status_updated'));
        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        DemandSurgeLevel::findOrFail($id)->delete();
        Toastr::success(translate('messages.demand_surge_level_deleted'));
        return back();
    }
}
