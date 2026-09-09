<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\DmMinWageSetting;
use App\Models\DmWageAdjustment;
use App\Models\Zone;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class MinWageController extends Controller
{
    public function index()
    {
        $settings = DmMinWageSetting::with('zone')->get();
        $adjustments = DmWageAdjustment::with('deliveryMan')->latest()->paginate(25);
        $zones = Zone::all();
        return view('admin-views.payroll.min-wage.index', compact('settings', 'adjustments', 'zones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'min_daily_amount' => 'required|numeric|min:0',
            'min_hours_required' => 'required|numeric|min:1|max:24',
        ]);

        DmMinWageSetting::updateOrCreate(
            ['zone_id' => $request->zone_id ?: null, 'dm_type' => $request->dm_type ?? 'all'],
            [
                'min_daily_amount' => $request->min_daily_amount,
                'min_hours_required' => $request->min_hours_required,
                'status' => $request->has('status'),
            ]
        );

        Toastr::success('Minimum wage setting saved.');
        return back();
    }

    public function destroy($id)
    {
        DmMinWageSetting::findOrFail($id)->delete();
        Toastr::success('Setting deleted.');
        return back();
    }

    public function approveAdjustment($id)
    {
        $adj = DmWageAdjustment::findOrFail($id);
        if ($adj->status === 'pending') {
            $service = new \App\Services\DmMinWageService();
            $service->payAdjustment($adj);
            Toastr::success('Adjustment paid.');
        }
        return back();
    }

    public function rejectAdjustment($id)
    {
        $adj = DmWageAdjustment::findOrFail($id);
        $adj->update(['status' => 'rejected']);
        Toastr::success('Adjustment rejected.');
        return back();
    }
}
