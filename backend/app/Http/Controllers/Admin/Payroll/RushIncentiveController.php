<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Models\DmRushIncentive;
use App\Models\DmRushActivation;
use App\Models\Zone;
use App\Services\DmRushMonitorService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RushIncentiveController extends Controller
{
    public function index()
    {
        $incentives = DmRushIncentive::latest()->paginate(25);
        $zones = Zone::all();
        return view('admin-views.payroll.rush-incentives.index', compact('incentives', 'zones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'trigger_type' => 'required|in:order_count,pending_time',
            'min_threshold' => 'required|integer|min:1',
            'bonus_type' => 'required|in:fixed,percentage',
            'bonus_value' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:1',
        ]);

        DmRushIncentive::create([
            'title' => $request->title,
            'trigger_type' => $request->trigger_type,
            'min_threshold' => $request->min_threshold,
            'max_threshold' => $request->max_threshold,
            'zone_id' => $request->zone_id,
            'bonus_type' => $request->bonus_type,
            'bonus_value' => $request->bonus_value,
            'duration_minutes' => $request->duration_minutes,
            'status' => 1,
            'created_by' => auth('admin')->id(),
        ]);

        return back()->with('success', 'Rush incentive created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'trigger_type' => 'required|in:order_count,pending_time',
            'min_threshold' => 'required|integer|min:1',
            'bonus_type' => 'required|in:fixed,percentage',
            'bonus_value' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:1',
        ]);

        $incentive = DmRushIncentive::findOrFail($id);
        $incentive->update($request->only([
            'title', 'trigger_type', 'min_threshold', 'max_threshold',
            'zone_id', 'bonus_type', 'bonus_value', 'duration_minutes',
        ]));

        return back()->with('success', 'Rush incentive updated.');
    }

    public function delete($id)
    {
        DmRushIncentive::findOrFail($id)->delete();
        return back()->with('success', 'Rush incentive deleted.');
    }

    public function toggleStatus($id)
    {
        $incentive = DmRushIncentive::findOrFail($id);
        $incentive->update(['status' => !$incentive->status]);
        return back()->with('success', 'Status updated.');
    }

    public function activeRushes()
    {
        $activations = DmRushActivation::currentlyActive()
            ->with(['rushIncentive', 'zone'])
            ->latest()
            ->paginate(25);
        return view('admin-views.payroll.rush-incentives.active', compact('activations'));
    }

    public function manualActivate(Request $request)
    {
        $request->validate([
            'rush_incentive_id' => 'required|exists:dm_rush_incentives,id',
            'zone_id' => 'required|exists:zones,id',
        ]);

        $rule = DmRushIncentive::findOrFail($request->rush_incentive_id);
        $service = new DmRushMonitorService();
        $service->activateRush($rule, $request->zone_id, 0);

        return back()->with('success', 'Rush manually activated.');
    }

    public function deactivateRush($id)
    {
        $activation = DmRushActivation::findOrFail($id);
        $activation->endSurge();
        return back()->with('success', 'Rush deactivated.');
    }
}
