<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\DmFuelIncentive;
use App\Models\Zone;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class FuelIncentiveController extends Controller
{
    public function index()
    {
        $incentives = DmFuelIncentive::with('zone')->latest()->paginate(25);
        $zones = Zone::all();
        return view('admin-views.payroll.fuel-incentives.index', compact('incentives', 'zones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'rate_per_km' => 'required|numeric|min:0',
        ]);

        DmFuelIncentive::create([
            'title' => $request->title,
            'rate_per_km' => $request->rate_per_km,
            'fuel_price_reference' => $request->fuel_price_reference,
            'status' => $request->has('status'),
            'zone_id' => $request->zone_id ?: null,
            'effective_from' => $request->effective_from,
            'effective_to' => $request->effective_to,
        ]);

        Toastr::success('Fuel incentive created.');
        return back();
    }

    public function update(Request $request, $id)
    {
        $incentive = DmFuelIncentive::findOrFail($id);
        $incentive->update([
            'title' => $request->title ?? $incentive->title,
            'rate_per_km' => $request->rate_per_km ?? $incentive->rate_per_km,
            'fuel_price_reference' => $request->fuel_price_reference,
            'status' => $request->has('status'),
            'zone_id' => $request->zone_id ?: null,
            'effective_from' => $request->effective_from,
            'effective_to' => $request->effective_to,
        ]);

        Toastr::success('Fuel incentive updated.');
        return back();
    }

    public function destroy($id)
    {
        DmFuelIncentive::findOrFail($id)->delete();
        Toastr::success('Fuel incentive deleted.');
        return back();
    }
}
