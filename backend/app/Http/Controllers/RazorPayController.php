<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Models\User;
use App\Traits\Processor;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

/**
 * RazorPayController - Updated 2026-01-28
 *
 * Features:
 * - Order-based checkout with AUTO-CAPTURE (no manual capture needed)
 * - Signature verification for security
 * - Webhook support for reliable payment confirmation
 * - Better error handling and logging
 * - Modern UI with multiple payment methods
 *
 * Rollback: Copy from storage/security_backups/20260128/RazorPayController.php.bak
 */
class RazorPayController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private $user;
    private ?Api $api = null;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('razor_pay', 'payment_config');
        $razor = false;

        if (!is_null($config) && $config->mode == 'live') {
            $razor = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $razor = json_decode($config->test_values);
        }

        if ($razor && isset($razor->api_key) && isset($razor->api_secret)) {
            $configArray = [
                'api_key' => $razor->api_key,
                'api_secret' => $razor->api_secret,
                'webhook_secret' => $razor->webhook_secret ?? null,
            ];
            Config::set('razor_config', $configArray);

            try {
                $this->api = new Api($razor->api_key, $razor->api_secret);
            } catch (\Exception $e) {
                Log::error('Razorpay API initialization failed: ' . $e->getMessage());
            }
        }

        $this->payment = $payment;
        $this->user = $user;
    }

    /**
     * Display payment page with Razorpay checkout
     */
    public function index(Request $request): View|Factory|JsonResponse|Application
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();

        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        if (!$this->api) {
            Log::error('Razorpay API not configured');
            return response()->json(['error' => 'Payment gateway not configured'], 500);
        }

        // FIX: Handle null payer_information gracefully
        $payer = json_decode($data['payer_information']);
        if (!is_object($payer)) {
            $payer = (object) ['name' => 'Customer', 'email' => ''];
        }

        // Get business details
        if ($data['additional_data'] != null) {
            $business = json_decode($data['additional_data']);
            $business_name = $business->business_name ?? "My Business";
            $business_logo = $business->business_logo ?? url('/');
        } else {
            $business_name = "My Business";
            $business_logo = url('/');
        }

        try {
            // Create Razorpay Order with AUTO-CAPTURE enabled
            // Note: Razorpay receipt field has 40 character limit
            $receiptId = substr(str_replace('-', '', $data->id), 0, 32); // Remove dashes from UUID, take first 32 chars
            $orderData = [
                'amount' => (int) round($data->payment_amount * 100), // Amount in paise
                'currency' => $data->currency_code ?? 'INR',
                'receipt' => 'rcpt_' . $receiptId, // Total: 5 + 32 = 37 chars (within 40 char limit)
                'payment_capture' => 1, // AUTO-CAPTURE: Payment is captured automatically
                'notes' => [
                    'payment_id' => $data->id,
                    'payer_name' => $payer->name ?? 'Customer',
                    'payer_email' => $payer->email ?? '',
                ]
            ];

            $razorpayOrder = $this->api->order->create($orderData);

            // Store order ID for verification later
            $data->update([
                'transaction_id' => $razorpayOrder['id']
            ]);

            Log::info('Razorpay order created', [
                'order_id' => $razorpayOrder['id'],
                'payment_id' => $data->id,
                'amount' => $data->payment_amount
            ]);

            return view('payment-views.razor-pay', [
                'data' => $data,
                'payer' => $payer,
                'business_logo' => $business_logo,
                'business_name' => $business_name,
                'razorpay_order_id' => $razorpayOrder['id'],
                'razorpay_key' => config('razor_config.api_key'),
            ]);

        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed', [
                'error' => $e->getMessage(),
                'payment_id' => $data->id
            ]);

            return response()->json([
                'error' => 'Failed to initialize payment. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Handle payment submission (POST from checkout form)
     */
    public function payment(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $validator = Validator::make($request->all(), [
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'payment_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            Log::warning('Razorpay payment validation failed', $validator->errors()->toArray());
            $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
            return $this->payment_response($payment_data, 'fail');
        }

        if (!$this->api) {
            Log::error('Razorpay API not configured for payment verification');
            $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
            return $this->payment_response($payment_data, 'fail');
        }

        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();

        if (!$payment_data) {
            Log::error('Payment record not found', ['payment_id' => $request['payment_id']]);
            return response()->json(['error' => 'Payment record not found'], 404);
        }

        try {
            // Verify payment signature for security
            $attributes = [
                'razorpay_order_id' => $request['razorpay_order_id'],
                'razorpay_payment_id' => $request['razorpay_payment_id'],
                'razorpay_signature' => $request['razorpay_signature']
            ];

            $this->api->utility->verifyPaymentSignature($attributes);

            // Signature verified - payment is authentic
            // With payment_capture: 1, payment is ALREADY captured automatically

            // Fetch payment details to confirm status
            $razorpayPayment = $this->api->payment->fetch($request['razorpay_payment_id']);

            Log::info('Razorpay payment verified', [
                'payment_id' => $request['razorpay_payment_id'],
                'order_id' => $request['razorpay_order_id'],
                'status' => $razorpayPayment['status'],
                'method' => $razorpayPayment['method'],
                'amount' => $razorpayPayment['amount'] / 100
            ]);

            // Check if payment is captured
            if ($razorpayPayment['status'] !== 'captured') {
                // This shouldn't happen with payment_capture: 1, but handle it
                Log::warning('Payment not captured, attempting manual capture', [
                    'payment_id' => $request['razorpay_payment_id'],
                    'status' => $razorpayPayment['status']
                ]);

                if ($razorpayPayment['status'] === 'authorized') {
                    $razorpayPayment->capture(['amount' => $razorpayPayment['amount']]);
                }
            }

            // Update payment record
            $payment_data->update([
                'payment_method' => 'razor_pay',
                'is_paid' => 1,
                'transaction_id' => $request['razorpay_payment_id'],
            ]);

            // Call success hook
            if (function_exists($payment_data->success_hook)) {
                call_user_func($payment_data->success_hook, $payment_data);
            }

            // Extra safety: Ensure order and order_payments are synced
            if ($payment_data->attribute == 'order' && $payment_data->attribute_id) {
                $order = \App\Models\Order::find($payment_data->attribute_id);
                if ($order && $order->payment_status == 'paid') {
                    // Sync any remaining unpaid payment records
                    \App\Models\OrderPayment::where('order_id', $order->id)
                        ->where('payment_status', 'unpaid')
                        ->update([
                            'payment_status' => 'paid',
                            'payment_method' => 'razor_pay'
                        ]);

                    Log::info('Razorpay: Synced order_payments with order status', [
                        'order_id' => $order->id,
                        'payment_id' => $request['razorpay_payment_id']
                    ]);
                }
            }

            return $this->payment_response($payment_data, 'success');

        } catch (SignatureVerificationError $e) {
            Log::error('Razorpay signature verification failed', [
                'error' => $e->getMessage(),
                'payment_id' => $request['payment_id']
            ]);

            if (function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }

            return $this->payment_response($payment_data, 'fail');

        } catch (\Exception $e) {
            Log::error('Razorpay payment processing failed', [
                'error' => $e->getMessage(),
                'payment_id' => $request['payment_id']
            ]);

            if (function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }

            return $this->payment_response($payment_data, 'fail');
        }
    }

    /**
     * Handle Razorpay callback (redirect after payment)
     */
    public function callback(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        // Decode payment data from URL
        if (!$request->has('payment_data') || empty($request->payment_data)) {
            Log::error('Razorpay callback: payment_data missing');
            return response()->json(['error' => 'Payment data not found'], 400);
        }

        $data_id = base64_decode($request->payment_data);

        if (!$data_id) {
            Log::error('Razorpay callback: invalid payment_data');
            return response()->json(['error' => 'Invalid payment data'], 400);
        }

        $payment_data = $this->payment::where(['id' => $data_id])->first();

        if (!$payment_data) {
            Log::error('Razorpay callback: payment record not found', ['decoded_id' => $data_id]);
            return response()->json(['error' => 'Payment record not found'], 404);
        }

        // Check if we have razorpay payment details
        if ($request->has('razorpay_payment_id') && !empty($request->razorpay_payment_id)) {
            // Verify the payment
            try {
                if ($this->api && $request->has('razorpay_signature')) {
                    $attributes = [
                        'razorpay_order_id' => $request->razorpay_order_id,
                        'razorpay_payment_id' => $request->razorpay_payment_id,
                        'razorpay_signature' => $request->razorpay_signature
                    ];

                    $this->api->utility->verifyPaymentSignature($attributes);
                }

                $payment_data->update([
                    'payment_method' => 'razor_pay',
                    'is_paid' => 1,
                    'transaction_id' => $request->razorpay_payment_id,
                ]);

                if (function_exists($payment_data->success_hook)) {
                    call_user_func($payment_data->success_hook, $payment_data);
                }

                // Extra safety: Ensure order and order_payments are synced
                if ($payment_data->attribute == 'order' && $payment_data->attribute_id) {
                    $order = \App\Models\Order::find($payment_data->attribute_id);
                    if ($order && $order->payment_status == 'paid') {
                        \App\Models\OrderPayment::where('order_id', $order->id)
                            ->where('payment_status', 'unpaid')
                            ->update([
                                'payment_status' => 'paid',
                                'payment_method' => 'razor_pay'
                            ]);
                    }
                }

                return $this->payment_response($payment_data, 'success');

            } catch (\Exception $e) {
                Log::error('Razorpay callback verification failed', [
                    'error' => $e->getMessage(),
                    'payment_id' => $data_id
                ]);
            }
        }

        // Payment failed or was cancelled
        if (function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }

        return $this->payment_response($payment_data, 'fail');
    }

    /**
     * Handle Razorpay Webhook (server-to-server notification)
     * This is the most reliable way to confirm payments
     */
    public function webhook(Request $request): JsonResponse
    {
        $webhookSecret = config('razor_config.webhook_secret');

        if (!$webhookSecret) {
            Log::warning('Razorpay webhook secret not configured');
            return response()->json(['status' => 'ok'], 200);
        }

        $webhookSignature = $request->header('X-Razorpay-Signature');
        $webhookBody = $request->getContent();

        try {
            // Verify webhook signature
            $this->api->utility->verifyWebhookSignature($webhookBody, $webhookSignature, $webhookSecret);

            $payload = json_decode($webhookBody, true);
            $event = $payload['event'] ?? '';

            Log::info('Razorpay webhook received', [
                'event' => $event,
                'payload' => $payload
            ]);

            switch ($event) {
                case 'payment.captured':
                    $this->handlePaymentCaptured($payload);
                    break;

                case 'payment.failed':
                    $this->handlePaymentFailed($payload);
                    break;

                case 'order.paid':
                    $this->handleOrderPaid($payload);
                    break;

                default:
                    Log::info('Razorpay webhook: unhandled event', ['event' => $event]);
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (SignatureVerificationError $e) {
            Log::error('Razorpay webhook signature verification failed', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);

        } catch (\Exception $e) {
            Log::error('Razorpay webhook processing failed', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle payment.captured webhook event
     */
    private function handlePaymentCaptured(array $payload): void
    {
        $paymentEntity = $payload['payload']['payment']['entity'] ?? null;

        if (!$paymentEntity) {
            return;
        }

        $orderId = $paymentEntity['order_id'] ?? null;
        $paymentId = $paymentEntity['id'] ?? null;
        $notes = $paymentEntity['notes'] ?? [];

        if (!$orderId) {
            return;
        }

        // Find payment by Razorpay order ID
        $payment_data = $this->payment::where('transaction_id', $orderId)->first();

        // Fallback: Try to find by payment_id in notes (for older payments)
        if (!$payment_data && isset($notes['payment_id'])) {
            $payment_data = $this->payment::where('id', $notes['payment_id'])->first();
        }

        if ($payment_data && !$payment_data->is_paid) {
            $payment_data->update([
                'payment_method' => 'razor_pay',
                'is_paid' => 1,
                'transaction_id' => $paymentId,
            ]);

            // Log wallet payment completion
            if ($payment_data->attribute == 'wallet_payments') {
                Log::info('Wallet payment captured via webhook', [
                    'wallet_payment_id' => $payment_data->attribute_id,
                    'user_id' => $payment_data->payer_id,
                    'amount' => $payment_data->payment_amount,
                    'razorpay_payment_id' => $paymentId
                ]);
            }

            if (function_exists($payment_data->success_hook)) {
                call_user_func($payment_data->success_hook, $payment_data);
            }

            // Extra safety: Ensure order and order_payments are synced
            if ($payment_data->attribute == 'order' && $payment_data->attribute_id) {
                $order = \App\Models\Order::find($payment_data->attribute_id);
                if ($order && $order->payment_status == 'paid') {
                    \App\Models\OrderPayment::where('order_id', $order->id)
                        ->where('payment_status', 'unpaid')
                        ->update([
                            'payment_status' => 'paid',
                            'payment_method' => 'razor_pay'
                        ]);
                }
            }

            Log::info('Payment marked as paid via webhook', [
                'payment_id' => $payment_data->id,
                'razorpay_payment_id' => $paymentId
            ]);
        }
    }

    /**
     * Handle payment.failed webhook event
     */
    private function handlePaymentFailed(array $payload): void
    {
        $paymentEntity = $payload['payload']['payment']['entity'] ?? null;

        if (!$paymentEntity) {
            return;
        }

        $orderId = $paymentEntity['order_id'] ?? null;
        $notes = $paymentEntity['notes'] ?? [];
        $errorReason = $paymentEntity['error_description'] ?? 'Unknown error';

        if (!$orderId) {
            return;
        }

        $payment_data = $this->payment::where('transaction_id', $orderId)->first();

        // Fallback: Try to find by payment_id in notes (for older payments)
        if (!$payment_data && isset($notes['payment_id'])) {
            $payment_data = $this->payment::where('id', $notes['payment_id'])->first();
        }

        if ($payment_data && !$payment_data->is_paid) {
            // Log wallet payment failure
            if ($payment_data->attribute == 'wallet_payments') {
                Log::warning('Wallet payment failed via webhook', [
                    'wallet_payment_id' => $payment_data->attribute_id,
                    'user_id' => $payment_data->payer_id,
                    'amount' => $payment_data->payment_amount,
                    'error' => $errorReason
                ]);
            }

            if (function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }

            Log::info('Payment marked as failed via webhook', [
                'payment_id' => $payment_data->id,
                'razorpay_order_id' => $orderId,
                'reason' => $errorReason
            ]);
        }
    }

    /**
     * Handle order.paid webhook event
     */
    private function handleOrderPaid(array $payload): void
    {
        $orderEntity = $payload['payload']['order']['entity'] ?? null;
        $paymentEntity = $payload['payload']['payment']['entity'] ?? null;

        if (!$orderEntity) {
            return;
        }

        $orderId = $orderEntity['id'] ?? null;
        $paymentId = $paymentEntity['id'] ?? null;

        if (!$orderId) {
            return;
        }

        $payment_data = $this->payment::where('transaction_id', $orderId)->first();

        if ($payment_data && !$payment_data->is_paid) {
            $payment_data->update([
                'payment_method' => 'razor_pay',
                'is_paid' => 1,
                'transaction_id' => $paymentId ?? $orderId,
            ]);

            if (function_exists($payment_data->success_hook)) {
                call_user_func($payment_data->success_hook, $payment_data);
            }

            // Extra safety: Ensure order and order_payments are synced
            if ($payment_data->attribute == 'order' && $payment_data->attribute_id) {
                $order = \App\Models\Order::find($payment_data->attribute_id);
                if ($order && $order->payment_status == 'paid') {
                    \App\Models\OrderPayment::where('order_id', $order->id)
                        ->where('payment_status', 'unpaid')
                        ->update([
                            'payment_status' => 'paid',
                            'payment_method' => 'razor_pay'
                        ]);
                }
            }

            Log::info('Payment marked as paid via order.paid webhook', [
                'payment_id' => $payment_data->id,
                'razorpay_order_id' => $orderId
            ]);
        }
    }

    /**
     * Handle payment cancellation
     */
    public function cancel(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();

        if ($payment_data) {
            Log::info('Razorpay payment cancelled by user', [
                'payment_id' => $payment_data->id
            ]);

            if (function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }
        }

        return $this->payment_response($payment_data, 'fail');
    }

    /**
     * Check payment status (AJAX endpoint)
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();

        if (!$payment_data) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        return response()->json([
            'is_paid' => (bool) $payment_data->is_paid,
            'transaction_id' => $payment_data->transaction_id,
        ]);
    }
}
