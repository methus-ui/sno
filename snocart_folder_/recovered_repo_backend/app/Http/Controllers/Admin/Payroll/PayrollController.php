<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Models\Order;
use App\Models\DmPayroll;
use App\Models\DeliveryMan;
use App\Models\DmIncentiveSlab;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exports\PayrollExport;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $payrolls = DmPayroll::with('deliveryMan')
            ->when($search, function ($q) use ($search) {
                $q->whereHas('deliveryMan', function ($dq) use ($search) {
                    $dq->where('f_name', 'like', "%{$search}%")
                       ->orWhere('l_name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status && $status != 'all', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->where('period_from', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->where('period_to', '<=', $dateTo);
            })
            ->latest()
            ->paginate(config('default_pagination'));

        return view('admin-views.payroll.index', compact('payrolls', 'search', 'status', 'dateFrom', 'dateTo'));
    }

    public function generate()
    {
        $salariedCount = DeliveryMan::where('earning', 0)->where('status', 1)->count();
        return view('admin-views.payroll.generate', compact('salariedCount'));
    }

    public function storePayroll(Request $request)
    {
        $request->validate([
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
        ]);

        $periodFrom = $request->period_from;
        $periodTo = $request->period_to;

        // Check for duplicate payroll in same period
        $existing = DmPayroll::where('period_from', $periodFrom)
            ->where('period_to', $periodTo)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($existing) {
            Toastr::error(translate('messages.payroll_already_generated_for_this_period'));
            return back();
        }

        $salariedDMs = DeliveryMan::where('earning', 0)
            ->where('status', 1)
            ->where('application_status', 'approved')
            ->get();

        if ($salariedDMs->isEmpty()) {
            Toastr::warning(translate('messages.no_salaried_delivery_men_found'));
            return back();
        }

        $incentiveSlabs = DmIncentiveSlab::active()->get();
        $generated = 0;

        DB::beginTransaction();
        try {
            foreach ($salariedDMs as $dm) {
                $deliveredOrders = Order::where('delivery_man_id', $dm->id)
                    ->where('order_status', 'delivered')
                    ->whereDate('delivered', '>=', $periodFrom)
                    ->whereDate('delivered', '<=', $periodTo)
                    ->get();

                $totalDeliveries = $deliveredOrders->count();

                $avgDeliveryTime = $deliveredOrders->filter(function ($order) {
                    return $order->accepted && $order->delivered;
                })->avg(function ($order) {
                    return Carbon::parse($order->delivered)->diffInMinutes(Carbon::parse($order->accepted));
                });

                // Calculate incentives
                $incentiveAmount = 0;
                $incentiveBreakdown = [];

                foreach ($incentiveSlabs as $slab) {
                    $qualifies = false;

                    if ($slab->type == 'delivery_count' && $totalDeliveries >= $slab->min_value) {
                        $qualifies = true;
                    } elseif ($slab->type == 'avg_delivery_time' && $avgDeliveryTime !== null) {
                        $meetsMin = $avgDeliveryTime >= $slab->min_value;
                        $meetsMax = $slab->max_value === null || $avgDeliveryTime <= $slab->max_value;
                        if ($meetsMin && $meetsMax) {
                            $qualifies = true;
                        }
                    }

                    if ($qualifies) {
                        $incentiveAmount += $slab->bonus_amount;
                        $incentiveBreakdown[] = [
                            'slab_id' => $slab->id,
                            'title' => $slab->title,
                            'type' => $slab->type,
                            'bonus_amount' => $slab->bonus_amount,
                            'actual_value' => $slab->type == 'delivery_count' ? $totalDeliveries : round($avgDeliveryTime, 1),
                        ];
                    }
                }

                $salaryAmount = $dm->salary_amount ?? 0;
                $totalAmount = $salaryAmount + $incentiveAmount;

                DmPayroll::create([
                    'delivery_man_id' => $dm->id,
                    'salary_amount' => $salaryAmount,
                    'incentive_amount' => $incentiveAmount,
                    'incentive_breakdown' => $incentiveBreakdown,
                    'total_deliveries' => $totalDeliveries,
                    'avg_delivery_time' => $avgDeliveryTime ? round($avgDeliveryTime, 1) : null,
                    'total_amount' => $totalAmount,
                    'deductions' => 0,
                    'net_payable' => $totalAmount,
                    'period_from' => $periodFrom,
                    'period_to' => $periodTo,
                    'status' => 'generated',
                    'created_by' => auth('admin')->id(),
                ]);

                $generated++;
            }

            DB::commit();
            Toastr::success($generated . ' ' . translate('messages.payrolls_generated_successfully'));
            return redirect()->route('admin.transactions.payroll.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error(translate('messages.something_went_wrong'));
            return back();
        }
    }

    public function show($id)
    {
        $payroll = DmPayroll::with('deliveryMan')->findOrFail($id);
        return view('admin-views.payroll.show', compact('payroll'));
    }

    public function markPaid(Request $request, $id)
    {
        $request->validate([
            'paid_method' => 'required|string',
        ]);

        $payroll = DmPayroll::findOrFail($id);

        if ($payroll->status != 'generated') {
            Toastr::error(translate('messages.payroll_cannot_be_marked_paid'));
            return back();
        }

        $payroll->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_method' => $request->paid_method,
            'paid_ref' => $request->paid_ref,
            'note' => $request->note,
        ]);

        Toastr::success(translate('messages.payroll_marked_as_paid'));
        return back();
    }

    public function cancel($id)
    {
        $payroll = DmPayroll::findOrFail($id);

        if ($payroll->status == 'paid') {
            Toastr::error(translate('messages.paid_payroll_cannot_be_cancelled'));
            return back();
        }

        $payroll->update(['status' => 'cancelled']);
        Toastr::success(translate('messages.payroll_cancelled'));
        return back();
    }

    public function regenerate($id)
    {
        $payroll = DmPayroll::findOrFail($id);

        if ($payroll->status == 'paid') {
            Toastr::error(translate('messages.paid_payroll_cannot_be_regenerated'));
            return back();
        }

        $dm = DeliveryMan::find($payroll->delivery_man_id);
        if (!$dm) {
            Toastr::error(translate('messages.delivery_man_not_found'));
            return back();
        }

        $deliveredOrders = Order::where('delivery_man_id', $dm->id)
            ->where('order_status', 'delivered')
            ->whereDate('delivered', '>=', $payroll->period_from)
            ->whereDate('delivered', '<=', $payroll->period_to)
            ->get();

        $totalDeliveries = $deliveredOrders->count();

        $avgDeliveryTime = $deliveredOrders->filter(function ($order) {
            return $order->accepted && $order->delivered;
        })->avg(function ($order) {
            return Carbon::parse($order->delivered)->diffInMinutes(Carbon::parse($order->accepted));
        });

        $incentiveSlabs = DmIncentiveSlab::active()->get();
        $incentiveAmount = 0;
        $incentiveBreakdown = [];

        foreach ($incentiveSlabs as $slab) {
            $qualifies = false;

            if ($slab->type == 'delivery_count' && $totalDeliveries >= $slab->min_value) {
                $qualifies = true;
            } elseif ($slab->type == 'avg_delivery_time' && $avgDeliveryTime !== null) {
                $meetsMin = $avgDeliveryTime >= $slab->min_value;
                $meetsMax = $slab->max_value === null || $avgDeliveryTime <= $slab->max_value;
                if ($meetsMin && $meetsMax) {
                    $qualifies = true;
                }
            }

            if ($qualifies) {
                $incentiveAmount += $slab->bonus_amount;
                $incentiveBreakdown[] = [
                    'slab_id' => $slab->id,
                    'title' => $slab->title,
                    'type' => $slab->type,
                    'bonus_amount' => $slab->bonus_amount,
                    'actual_value' => $slab->type == 'delivery_count' ? $totalDeliveries : round($avgDeliveryTime, 1),
                ];
            }
        }

        $salaryAmount = $dm->salary_amount ?? 0;
        $totalAmount = $salaryAmount + $incentiveAmount;

        $payroll->update([
            'salary_amount' => $salaryAmount,
            'incentive_amount' => $incentiveAmount,
            'incentive_breakdown' => $incentiveBreakdown,
            'total_deliveries' => $totalDeliveries,
            'avg_delivery_time' => $avgDeliveryTime ? round($avgDeliveryTime, 1) : null,
            'total_amount' => $totalAmount,
            'net_payable' => $totalAmount - $payroll->deductions,
            'status' => 'generated',
        ]);

        Toastr::success(translate('messages.payroll_regenerated_successfully'));
        return back();
    }

    public function export(Request $request)
    {
        $payrolls = DmPayroll::with('deliveryMan')
            ->when($request->status && $request->status != 'all', function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->get();

        $data = ['payrolls' => $payrolls];

        if ($request->type == 'excel') {
            return Excel::download(new PayrollExport($data), 'Payroll.xlsx');
        }
        return Excel::download(new PayrollExport($data), 'Payroll.csv');
    }
}
