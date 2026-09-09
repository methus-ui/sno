<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Models\DmDailyIncentiveRule;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DailyIncentiveController extends Controller
{
    public function index()
    {
        $rules = DmDailyIncentiveRule::latest()->paginate(25);
        return view('admin-views.payroll.daily-incentives.index', compact('rules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'delivery_count' => 'required|integer|min:1',
            'bonus_amount' => 'required|numeric|min:0',
        ]);

        DmDailyIncentiveRule::create([
            'title' => $request->title,
            'delivery_count' => $request->delivery_count,
            'bonus_amount' => $request->bonus_amount,
            'status' => 1,
            'created_by' => auth('admin')->id(),
        ]);

        return back()->with('success', 'Daily incentive rule created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'delivery_count' => 'required|integer|min:1',
            'bonus_amount' => 'required|numeric|min:0',
        ]);

        $rule = DmDailyIncentiveRule::findOrFail($id);
        $rule->update($request->only('title', 'delivery_count', 'bonus_amount'));

        return back()->with('success', 'Daily incentive rule updated.');
    }

    public function delete($id)
    {
        DmDailyIncentiveRule::findOrFail($id)->delete();
        return back()->with('success', 'Daily incentive rule deleted.');
    }

    public function toggleStatus($id)
    {
        $rule = DmDailyIncentiveRule::findOrFail($id);
        $rule->update(['status' => !$rule->status]);
        return back()->with('success', 'Status updated.');
    }
}
