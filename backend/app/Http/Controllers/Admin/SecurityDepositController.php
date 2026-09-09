<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\SecurityDepositPayment;
use App\Services\SecurityDepositService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityDepositController extends Controller
{
    protected SecurityDepositService $securityDepositService;

    public function __construct(SecurityDepositService $securityDepositService)
    {
        $this->securityDepositService = $securityDepositService;
    }

    /**
     * Display security deposit settings page
     */
    public function settings()
    {
        $enabled = DB::table('business_settings')
            ->where('key', 'security_deposit_enabled')
            ->first()?->value == '1';

        $amount = DB::table('business_settings')
            ->where('key', 'security_deposit_amount')
            ->first()?->value ?? 0;

        return view('admin.security-deposit.settings', compact('enabled', 'amount'));
    }

    /**
     * Update security deposit settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'security_deposit_enabled' => 'required|boolean',
            'security_deposit_amount' => 'required|numeric|min:0',
        ]);

        DB::table('business_settings')->updateOrInsert(
            ['key' => 'security_deposit_enabled'],
            [
                'value' => $request->security_deposit_enabled ? '1' : '0',
                'updated_at' => now(),
            ]
        );

        DB::table('business_settings')->updateOrInsert(
            ['key' => 'security_deposit_amount'],
            [
                'value' => $request->security_deposit_amount,
                'updated_at' => now(),
            ]
        );

        Toastr::success(translate('Security deposit settings updated successfully'));
        return back();
    }

    /**
     * Display list of delivery men with security deposit status
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $search = $request->get('search');

        $query = DeliveryMan::with(['latestSecurityDepositPayment'])
            ->select('delivery_men.*');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('f_name', 'like', "%{$search}%")
                    ->orWhere('l_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('security_deposit_status', $status);
        }

        $deliveryMen = $query->orderBy('created_at', 'desc')->paginate(25);

        $stats = [
            'total' => DeliveryMan::count(),
            'paid' => DeliveryMan::where('security_deposit_status', 'paid')->count(),
            'unpaid' => DeliveryMan::where('security_deposit_status', 'unpaid')->count(),
            'refunded' => DeliveryMan::where('security_deposit_status', 'refunded')->count(),
        ];

        return view('admin.security-deposit.index', compact('deliveryMen', 'stats', 'status', 'search'));
    }

    /**
     * Show security deposit details for a delivery man
     */
    public function show($id)
    {
        $deliveryMan = DeliveryMan::with(['securityDepositPayments'])->findOrFail($id);

        return view('admin.security-deposit.show', compact('deliveryMan'));
    }

    /**
     * Refund security deposit
     */
    public function refund(Request $request, $id)
    {
        $request->validate([
            'refund_reason' => 'required|string|max:500',
        ]);

        $admin = auth('admin')->user();

        $result = $this->securityDepositService->refund(
            $id,
            $request->refund_reason,
            $admin->id
        );

        if ($result['success']) {
            Toastr::success(translate('Security deposit refunded successfully'));
        } else {
            Toastr::error(translate($result['message']));
        }

        return back();
    }

    /**
     * View payment details
     */
    public function paymentDetails($paymentId)
    {
        $payment = SecurityDepositPayment::with(['deliveryMan', 'refundedBy'])->findOrFail($paymentId);

        return view('admin.security-deposit.payment-details', compact('payment'));
    }

    /**
     * Export delivery men with security deposit status
     */
    public function export(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = DeliveryMan::with(['latestSecurityDepositPayment']);

        if ($status !== 'all') {
            $query->where('security_deposit_status', $status);
        }

        $deliveryMen = $query->get();

        $fileName = 'security_deposits_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($deliveryMen) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'ID',
                'Name',
                'Phone',
                'Email',
                'Deposit Amount',
                'Status',
                'Paid At',
                'Transaction ID',
                'Payment Method',
            ]);

            // Add data
            foreach ($deliveryMen as $dm) {
                fputcsv($file, [
                    $dm->id,
                    $dm->f_name . ' ' . $dm->l_name,
                    $dm->phone,
                    $dm->email,
                    $dm->security_deposit_amount,
                    $dm->security_deposit_status,
                    $dm->security_deposit_paid_at?->format('Y-m-d H:i:s'),
                    $dm->security_deposit_transaction_id,
                    $dm->security_deposit_payment_method,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
