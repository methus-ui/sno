<?php

namespace App\Http\Controllers\Admin\DeliveryMan;

use App\Http\Controllers\Controller;
use App\Models\DmSosRequest;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class SosController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status ?? 'active';
        $sosRequests = DmSosRequest::with(['deliveryMan', 'order'])
            ->when($status !== 'all', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->latest()
            ->paginate(25);

        return view('admin-views.delivery-man.sos.index', compact('sosRequests', 'status'));
    }

    public function resolve($id)
    {
        $sos = DmSosRequest::findOrFail($id);
        $sos->update([
            'status' => 'resolved',
            'resolved_by' => auth('admin')->id(),
            'resolved_at' => now(),
        ]);

        Toastr::success('SOS request resolved.');
        return back();
    }
}
