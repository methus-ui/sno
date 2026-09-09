<?php

namespace App\Http\Controllers\Admin\DeliveryMan;

use App\Models\DmPerformanceTier;
use App\Services\DmRankingService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PerformanceTierController extends Controller
{
    public function index()
    {
        $tiers = DmPerformanceTier::ordered()->paginate(25);
        return view('admin-views.delivery-man.performance-tiers.index', compact('tiers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rank_order' => 'required|integer|min:1',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'min_deliveries' => 'nullable|integer|min:0',
            'min_acceptance_rate' => 'nullable|integer|min:0|max:100',
            'bonus_per_delivery' => 'required|numeric|min:0',
        ]);

        DmPerformanceTier::create([
            'name' => $request->name,
            'rank_order' => $request->rank_order,
            'icon' => $request->icon,
            'color' => $request->color ?? '#000000',
            'min_rating' => $request->min_rating,
            'min_deliveries' => $request->min_deliveries,
            'min_acceptance_rate' => $request->min_acceptance_rate,
            'bonus_per_delivery' => $request->bonus_per_delivery,
            'priority_boost' => $request->priority_boost ?? 0,
            'status' => 1,
        ]);

        return back()->with('success', 'Performance tier created.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rank_order' => 'required|integer|min:1',
            'bonus_per_delivery' => 'required|numeric|min:0',
        ]);

        $tier = DmPerformanceTier::findOrFail($id);
        $tier->update($request->only([
            'name', 'rank_order', 'icon', 'color', 'min_rating',
            'min_deliveries', 'min_acceptance_rate', 'bonus_per_delivery', 'priority_boost',
        ]));

        return back()->with('success', 'Performance tier updated.');
    }

    public function destroy($id)
    {
        DmPerformanceTier::findOrFail($id)->delete();
        return back()->with('success', 'Performance tier deleted.');
    }

    public function toggleStatus($id)
    {
        $tier = DmPerformanceTier::findOrFail($id);
        $tier->update(['status' => !$tier->status]);
        return back()->with('success', 'Status updated.');
    }

    public function recalculateAllTiers()
    {
        $service = new DmRankingService();
        $count = $service->updateAllTiers();
        return back()->with('success', "Recalculated tiers for {$count} delivery men.");
    }
}
