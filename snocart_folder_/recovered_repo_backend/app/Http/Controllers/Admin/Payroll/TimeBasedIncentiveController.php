<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Models\DmTimeBasedIncentive;
use App\Models\Zone;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TimeBasedIncentiveController extends Controller
{
    public function index()
    {
        $incentives = DmTimeBasedIncentive::latest()->paginate(25);
        $zones = Zone::all();
        return view('admin-views.payroll.time-incentives.index', compact('incentives', 'zones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'time_from' => 'required',
            'time_to' => 'required',
            'bonus_type' => 'required|in:fixed,percentage',
            'bonus_value' => 'required|numeric|min:0',
        ]);

        DmTimeBasedIncentive::create([
            'title' => $request->title,
            'time_from' => $request->time_from,
            'time_to' => $request->time_to,
            'days_of_week' => $request->days_of_week ? json_decode($request->days_of_week) : null,
            'bonus_type' => $request->bonus_type,
            'bonus_value' => $request->bonus_value,
            'zone_id' => $request->zone_id,
            'module_id' => $request->module_id,
            'status' => 1,
            'created_by' => auth('admin')->id(),
        ]);

        return back()->with('success', 'Time-based incentive created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'time_from' => 'required',
            'time_to' => 'required',
            'bonus_type' => 'required|in:fixed,percentage',
            'bonus_value' => 'required|numeric|min:0',
        ]);

        $incentive = DmTimeBasedIncentive::findOrFail($id);
        $incentive->update([
            'title' => $request->title,
            'time_from' => $request->time_from,
            'time_to' => $request->time_to,
            'days_of_week' => $request->days_of_week ? json_decode($request->days_of_week) : null,
            'bonus_type' => $request->bonus_type,
            'bonus_value' => $request->bonus_value,
            'zone_id' => $request->zone_id,
            'module_id' => $request->module_id,
        ]);

        return back()->with('success', 'Time-based incentive updated.');
    }

    public function delete($id)
    {
        DmTimeBasedIncentive::findOrFail($id)->delete();
        return back()->with('success', 'Time-based incentive deleted.');
    }

    public function toggleStatus($id)
    {
        $incentive = DmTimeBasedIncentive::findOrFail($id);
        $incentive->update(['status' => !$incentive->status]);
        return back()->with('success', 'Status updated.');
    }
}
