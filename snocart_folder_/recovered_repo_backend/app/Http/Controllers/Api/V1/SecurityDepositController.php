<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Library\Payment;
use App\Library\Payer;
use App\Library\Payment as PaymentInfo;
use App\Library\Receiver;
use App\Models\BusinessSetting;
use App\Models\DeliveryMan;
use App\Models\SecurityDepositPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SecurityDepositController extends Controller
{
    /**
     * Initiate security deposit payment
     * POST /api/v1/delivery-man/security-deposit/pay
     */
    public function pay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_gateway' => 'required',
            'callback' => 'required',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        if (!$dm) {
            return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);
        }

        // Check if already paid
        if ($dm->security_deposit_status === 'paid') {
            return response()->json(['errors' => [['code' => 'already_paid', 'message' => 'Security deposit already paid']]], 403);
        }

        // Check if security deposit is enabled
        $enabled = BusinessSetting::where('key', 'security_deposit_enabled')->first()?->value == '1';
        if (!$enabled) {
            return response()->json(['errors' => [['code' => 'disabled', 'message' => 'Security deposit is not enabled']]], 403);
        }

        // Get deposit amount
        $amount = BusinessSetting::where('key', 'security_deposit_amount')->first()?->value ?? 0;
        if ($amount <= 0) {
            return response()->json(['errors' => [['code' => 'invalid_amount', 'message' => 'Invalid security deposit amount']]], 403);
        }

        // Update delivery man deposit amount if not set
        if (!$dm->security_deposit_amount || $dm->security_deposit_amount == 0) {
            $dm->security_deposit_amount = $amount;
            $dm->save();
        }

        // Create payment record
        $payment = SecurityDepositPayment::create([
            'delivery_man_id' => $dm->id,
            'amount' => $dm->security_deposit_amount,
            'currency' => Helpers::currency_code(),
            'status' => 'pending',
            'payment_method' => $request->payment_gateway,
            'callback_url' => $request->callback,
            'metadata' => [
                'delivery_man_name' => $dm->f_name . ' ' . $dm->l_name,
                'phone' => $dm->phone,
                'email' => $dm->email,
            ],
        ]);

        // Prepare payer information
        $payer = new Payer(
            $dm->f_name,
            $dm->email,
            $dm->phone,
            ''
        );

        // Prepare additional data
        $store_logo = BusinessSetting::where(['key' => 'logo'])->first();
        $additional_data = [
            'business_name' => BusinessSetting::where(['key' => 'business_name'])->first()?->value,
            'business_logo' => Helpers::get_full_url('business', $store_logo?->value, $store_logo?->storage[0]?->value ?? 'public')
        ];

        // Prepare payment info
        $payment_info = new PaymentInfo(
            success_hook: 'security_deposit_success',
            failure_hook: 'security_deposit_fail',
            currency_code: Helpers::currency_code(),
            payment_method: $request->payment_gateway,
            payment_platform: 'app',
            payer_id: $dm->id,
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $dm->security_deposit_amount,
            external_redirect_link: $request->has('callback') ? $request['callback'] : session('callback'),
            attribute: 'security_deposit_payment',
            attribute_id: $payment->id,
        );

        // Prepare receiver info
        $receiver_info = new Receiver('Admin', 'example.png');

        // Generate payment link
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        // Update payment with redirect link
        $payment->update(['payment_url' => $redirect_link, 'redirect_url' => $redirect_link]);

        $data = [
            'redirect_link' => $redirect_link,
        ];

        return response()->json($data, 200);
    }

    /**
     * Get security deposit payment status
     * GET /api/v1/delivery-man/security-deposit/status
     */
    public function status(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        if (!$dm) {
            return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);
        }

        $enabled = BusinessSetting::where('key', 'security_deposit_enabled')->first()?->value == '1';
        $amount = BusinessSetting::where('key', 'security_deposit_amount')->first()?->value ?? 0;

        return response()->json([
            'security_deposit_enabled' => (bool) $enabled,
            'security_deposit_amount' => (float) ($dm->security_deposit_amount ?: $amount),
            'security_deposit_status' => $dm->security_deposit_status ?? 'unpaid',
            'security_deposit_paid_at' => $dm->security_deposit_paid_at?->format('Y-m-d H:i:s'),
            'security_deposit_transaction_id' => $dm->security_deposit_transaction_id,
            'security_deposit_payment_method' => $dm->security_deposit_payment_method,
        ], 200);
    }
}
