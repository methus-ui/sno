<?php

namespace App\Http\Controllers\Admin\DeliveryMan;

use App\Http\Controllers\Controller;
use App\Models\DmAutoAssignmentSetting;
use App\Models\Zone;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class AutoAssignSettingsController extends Controller
{
    public function index()
    {
        $settings = DmAutoAssignmentSetting::with('zone')->get();
        $zones = Zone::all();
        return view('admin-views.delivery-man.auto-assign.index', compact('settings', 'zones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'max_radius_km' => 'required|numeric|min:0.5|max:50',
            'fallback_to_manual_after_seconds' => 'required|integer|min:30|max:600',
            'max_orders_per_dm' => 'required|integer|min:1|max:10',
        ]);

        DmAutoAssignmentSetting::updateOrCreate(
            ['zone_id' => $request->zone_id ?: null],
            [
                'is_enabled' => $request->has('is_enabled'),
                'max_radius_km' => $request->max_radius_km,
                'priority_factors' => [
                    'distance_weight' => (float) ($request->distance_weight ?? 0.4),
                    'rating_weight' => (float) ($request->rating_weight ?? 0.25),
                    'acceptance_rate_weight' => (float) ($request->acceptance_rate_weight ?? 0.2),
                    'tier_weight' => (float) ($request->tier_weight ?? 0.15),
                ],
                'fallback_to_manual_after_seconds' => $request->fallback_to_manual_after_seconds,
                'max_orders_per_dm' => $request->max_orders_per_dm,
            ]
        );

        Toastr::success('Auto-assignment settings saved.');
        return back();
    }

    public function destroy($id)
    {
        DmAutoAssignmentSetting::findOrFail($id)->delete();
        Toastr::success('Setting deleted.');
        return back();
    }
}
