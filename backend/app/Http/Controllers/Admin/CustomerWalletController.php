<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\WalletTransaction;
use App\CentralLogics\CustomerLogic;
use App\Exports\CustomerWalletTransactionExport;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class CustomerWalletController extends Controller
{
    public function add_fund_view()
    {
        if (BusinessSetting::where('key', 'wallet_status')->first()->value != 1) {
            Toastr::error(trans('messages.customer_wallet_disable_warning_admin'));
            return back();
        }
        return view('admin-views.customer.wallet.add_fund');
    }

    public function add_fund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'=>'exists:users,id',
            'amount'=>'numeric|min:.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        // ✅ NEW: Validate if this is a refund for an order
        $orderRefundCheck = null;
        if ($request->referance) {
            // Check if reference contains an order ID
            if (preg_match('/\b(\d{5,})\b/', $request->referance, $matches)) {
                $orderId = $matches[1];

                // Verify order exists and belongs to this customer
                $order = DB::table('orders')
                    ->where('id', $orderId)
                    ->where('user_id', $request->customer_id)
                    ->first();

                if ($order) {
                    // Check if automatic refund already exists
                    $autoRefund = DB::table('wallet_transactions')
                        ->where('transaction_type', 'order_refund')
                        ->where('reference', $orderId)
                        ->first();

                    // Check if manual refunds already exist
                    $existingManualRefunds = DB::table('wallet_transactions')
                        ->where('transaction_type', 'add_fund_by_admin')
                        ->where('reference', 'LIKE', "%{$orderId}%")
                        ->get();

                    $orderRefundCheck = [
                        'order_id' => $orderId,
                        'order' => $order,
                        'auto_refund' => $autoRefund,
                        'existing_manual_refunds' => $existingManualRefunds,
                        'total_manual_refunded' => $existingManualRefunds->sum('credit'),
                        'auto_refunded' => $autoRefund ? $autoRefund->credit : 0,
                        'total_refunded' => ($autoRefund ? $autoRefund->credit : 0) + $existingManualRefunds->sum('credit'),
                        'order_reduction' => abs($order->adjustment_amount),
                    ];

                    // ⚠️ WARNING: Check for potential duplicate or over-refund
                    $warnings = [];

                    if ($autoRefund) {
                        $warnings[] = "⚠️ Automatic refund of ₹{$autoRefund->credit} already exists for this order!";
                    }

                    if (count($existingManualRefunds) > 0) {
                        $warnings[] = "⚠️ {$existingManualRefunds->count()} manual refund(s) totaling ₹{$orderRefundCheck['total_manual_refunded']} already exist!";
                    }

                    $totalAfterThisRefund = $orderRefundCheck['total_refunded'] + $request->amount;
                    if ($totalAfterThisRefund > $orderRefundCheck['order_reduction'] + 1) {
                        $warnings[] = "⚠️ OVER-REFUND WARNING: Total refunds (₹{$totalAfterThisRefund}) will exceed order reduction (₹{$orderRefundCheck['order_reduction']})!";
                    }

                    if (count($warnings) > 0) {
                        // Return validation warnings - require admin confirmation
                        return response()->json([
                            'requires_confirmation' => true,
                            'warnings' => $warnings,
                            'order_details' => [
                                'order_id' => $orderId,
                                'order_amount' => $order->order_amount,
                                'original_amount' => $order->original_order_amount,
                                'adjustment' => $order->adjustment_amount,
                                'reduction' => abs($order->adjustment_amount),
                                'already_refunded' => $orderRefundCheck['total_refunded'],
                                'requested_refund' => $request->amount,
                                'total_after_refund' => $totalAfterThisRefund,
                            ],
                            'message' => 'Please confirm this refund carefully. Multiple refunds detected for this order.',
                        ], 200);
                    }
                }
            }
        }

        // ✅ Proceed with wallet transaction (only if no warnings OR user confirmed)
        if ($request->input('confirmed') !== 'true' && $orderRefundCheck && isset($orderRefundCheck['warnings'])) {
            // Should not reach here, but safety check
            return response()->json([
                'errors' => [['message' => 'Confirmation required for this refund']]
            ], 422);
        }

        $wallet_transaction = CustomerLogic::create_wallet_transaction(
            $request->customer_id,
            $request->amount,
            'add_fund_by_admin',
            $request->referance
        );

        if($wallet_transaction)
        {
            try{
                Helpers::add_fund_push_notification($request->customer_id);
                if(config('mail.status') && Helpers::get_mail_status('add_fund_mail_status_user') == '1' &&  Helpers::getNotificationStatusData('customer','customer_add_fund_to_wallet','mail_status') ) {
                    Mail::to($wallet_transaction->user->email)->send(new \App\Mail\AddFundToWallet($wallet_transaction));
                }

                // ✅ Log manual refund for audit trail
                \Log::info('Manual wallet refund added by admin', [
                    'admin_id' => auth('admin')->id(),
                    'customer_id' => $request->customer_id,
                    'amount' => $request->amount,
                    'reference' => $request->referance,
                    'transaction_id' => $wallet_transaction->transaction_id,
                    'order_check' => $orderRefundCheck ? [
                        'order_id' => $orderRefundCheck['order_id'],
                        'total_refunded_before' => $orderRefundCheck['total_refunded'],
                        'total_refunded_after' => $orderRefundCheck['total_refunded'] + $request->amount,
                    ] : null,
                ]);
            }catch(\Exception $ex)
            {
                info($ex->getMessage());
            }

            return response()->json([], 200);
        }

        return response()->json(['errors'=>[
            'message'=>trans('messages.failed_to_create_transaction')
        ]], 200);
    }

    public function report(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $filter = $request->query('filter', 'all_time');
        $key = [];
        if ($request->search) {
            $key = explode(' ', $request['search']);
        }
        $data = WalletTransaction::selectRaw('sum(credit+admin_bonus) as total_credit, sum(debit) as total_debit, SUM(IF(transaction_type = "add_fund_by_admin", credit, 0)) as add_fund_total,SUM(IF(transaction_type = "order_refund", credit, 0)) as order_refund_total,SUM(IF(transaction_type = "loyalty_point", credit, 0)) as loyalty_point_total,SUM(IF(transaction_type = "order_place", credit, 0)) as order_place_total')
            ->when(($request->from && $request->to),function($query)use($request){
                $query->whereBetween('created_at', [$request->from.' 00:00:00', $request->to.' 23:59:59']);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($request->transaction_type) && ($request->transaction_type != 'all'), function($query)use($request){
                $query->where('transaction_type',$request->transaction_type);
            })
            ->when(isset($request->customer_id) && is_numeric($request->customer_id), function($query)use($request){
                $query->where('user_id',$request->customer_id);
            })
        ->when(count($key) > 0, function($query) use($key){
            $query->wherehas('user',    function ($query) use ($key) {
                foreach ($key as $value) {
                    $query->where(function($query) use($value){
                        $query->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                    });
                };
            });
       })
        ->get();

        $transactions = WalletTransaction::with('user')->
            when(($request->from && $request->to),function($query)use($request){
                $query->whereBetween('created_at', [$request->from.' 00:00:00', $request->to.' 23:59:59']);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($request->transaction_type) && ($request->transaction_type != 'all'), function($query)use($request){
                $query->where('transaction_type',$request->transaction_type);
            })
            ->when(isset($request->customer_id) && is_numeric($request->customer_id), function($query)use($request){
                $query->where('user_id',$request->customer_id);
            })
        ->when(count($key) > 0, function($query) use($key){
            $query->wherehas('user',    function ($query) use ($key) {
                foreach ($key as $value) {
                    $query->where(function($query) use($value){
                        $query->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                    });
                };
            });
       })
        ->latest()
        ->paginate(config('default_pagination'));

        return view('admin-views.customer.wallet.report', compact('data','transactions','filter'));
    }

    public function export(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $filter = $request->query('filter', 'all_time');
        $key = [];
        if ($request->search) {
            $key = explode(' ', $request['search']);
        }

        $data = WalletTransaction::selectRaw('sum(credit) as total_credit, sum(debit) as total_debit')
            ->when(($request->from && $request->to),function($query)use($request){
                $query->whereBetween('created_at', [$request->from.' 00:00:00', $request->to.' 23:59:59']);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($request->transaction_type) && ($request->transaction_type != 'all'), function($query)use($request){
                $query->where('transaction_type',$request->transaction_type);
            })
            ->when(isset($request->customer_id) && is_numeric($request->customer_id), function($query)use($request){
                $query->where('user_id',$request->customer_id);
            })
        ->when(count($key) > 0, function($query) use($key){
            $query->wherehas('user',    function ($query) use ($key) {
                foreach ($key as $value) {
                    $query->where(function($query) use($value){
                        $query->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                    });
                };
            });
       })
       ->get();

        $transactions = WalletTransaction::
            when(($request->from && $request->to),function($query)use($request){
                $query->whereBetween('created_at', [$request->from.' 00:00:00', $request->to.' 23:59:59']);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($request->transaction_type) && ($request->transaction_type != 'all'), function($query)use($request){
                $query->where('transaction_type',$request->transaction_type);
            })
            ->when(isset($request->customer_id) && is_numeric($request->customer_id), function($query)use($request){
                $query->where('user_id',$request->customer_id);
            })
        ->when(count($key) > 0, function($query) use($key){
            $query->wherehas('user',    function ($query) use ($key) {
                foreach ($key as $value) {
                    $query->where(function($query) use($value){
                        $query->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                    });
                };
            });
       })
        ->latest()
        ->get();

        $data = [
            'transactions'=>$transactions,
            'data'=>$data,
            'from'=>$request->from??null,
            'to'=>$request->to??null,
            'transaction_type'=>$request->transaction_type??null,
            'customer'=>$request->customer_id?Helpers::get_customer_name($request->customer_id):$request['search']?? null,

        ];

        if ($request->type == 'excel') {
            return Excel::download(new CustomerWalletTransactionExport($data), 'CustomerWalletTransactions.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new CustomerWalletTransactionExport($data), 'CustomerWalletTransactions.csv');
        }
    }

    public function set_date(Request $request)
    {
        session()->put('from_date', date('Y-m-d', strtotime($request['from'])));
        session()->put('to_date', date('Y-m-d', strtotime($request['to'])));
        return back();
    }

}
