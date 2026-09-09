<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Models\DmIncentiveSlab;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;

class IncentiveSlabController extends Controller
{
    public function index()
    {
        $slabs = DmIncentiveSlab::latest()->paginate(config('default_pagination'));
        return view('admin-views.payroll.incentive-slabs.index', compact('slabs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:delivery_count,avg_delivery_time',
            'min_value' => 'required|numeric|min:0',
            'max_value' => 'nullable|numeric|min:0',
            'bonus_amount' => 'required|numeric|min:0',
        ]);

        DmIncentiveSlab::create($request->only(['title', 'type', 'min_value', 'max_value', 'bonus_amount']));

        Toastr::success(translate('messages.incentive_slab_created'));
        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:delivery_count,avg_delivery_time',
            'min_value' => 'required|numeric|min:0',
            'max_value' => 'nullable|numeric|min:0',
            'bonus_amount' => 'required|numeric|min:0',
        ]);

        $slab = DmIncentiveSlab::findOrFail($id);
        $slab->update($request->only(['title', 'type', 'min_value', 'max_value', 'bonus_amount']));

        Toastr::success(translate('messages.incentive_slab_updated'));
        return back();
    }

    public function delete($id)
    {
        DmIncentiveSlab::findOrFail($id)->delete();
        Toastr::success(translate('messages.incentive_slab_deleted'));
        return back();
    }

    public function updateStatus($id, $status)
    {
        DmIncentiveSlab::findOrFail($id)->update(['status' => $status]);
        Toastr::success(translate('messages.status_updated'));
        return back();
    }
}
