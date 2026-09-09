<?php

namespace App\Http\Controllers\Admin\DeliveryMan;

use App\Http\Controllers\Controller;
use App\Models\DmInstantWithdrawal;
use App\Services\DmWithdrawalService;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class InstantWithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status ?? 'pending';
        $withdrawals = DmInstantWithdrawal::with('deliveryMan')
            ->when($status !== 'all', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->latest()
            ->paginate(25);

        return view('admin-views.delivery-man.instant-withdrawals.index', compact('withdrawals', 'status'));
    }

    public function approve($id)
    {
        $service = new DmWithdrawalService();
        $result = $service->processWithdrawal($id, 'approve');
        $result['success'] ? Toastr::success($result['message']) : Toastr::error($result['message']);
        return back();
    }

    public function reject($id)
    {
        $service = new DmWithdrawalService();
        $result = $service->processWithdrawal($id, 'reject');
        $result['success'] ? Toastr::success($result['message']) : Toastr::error($result['message']);
        return back();
    }
}
