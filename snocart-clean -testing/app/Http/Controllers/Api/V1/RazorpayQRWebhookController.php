<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryQRPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayQRWebhookController extends Controller
{
    private ?Api $api = null;

    public function __construct()
    {
        $this->initializeRazorpay();
    }

    /**
     * Initialize Razorpay API
     */
    private function initializeRazorpay(): void
    {
        $config = \DB::table('addon_settings')
            ->where('key_name', 'razor_pay')
            ->first();

        if (!$config) {
            return;
        }

        $mode = $config->mode ?? 'live';
        $values = $mode === 'live' ? $config->live_values : $config->test_values;

        if (!$values) {
            return;
        }

        $razor = json_decode($values);

        if ($razor && isset($razor->api_key) && isset($razor->api_secret)) {
            try {
                $this->api = new Api($razor->api_key, $razor->api_secret);
            } catch (\Exception $e) {
                Log::error('Razorpay API initialization failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle Razorpay QR Code webhook
     *
     * POST /api/v1/webhooks/razorpay-qr
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        if (!$this->api) {
            Log::error('Razorpay API not initialized for webhook');
            return response()->json(['status' => 'error'], 500);
        }

        $webhookSecret = config('razor_config.webhook_secret');
        $webhookSignature = $request->header('X-Razorpay-Signature');
        $webhookBody = $request->getContent();

        // Verify webhook signature if secret is configured
        if ($webhookSecret) {
            try {
                $this->api->utility->verifyWebhookSignature(
                    $webhookBody,
                    $webhookSignature,
                    $webhookSecret
                );
            } catch (SignatureVerificationError $e) {
                Log::error('Razorpay webhook signature verification failed', [
                    'error' => $e->getMessage()
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        $payload = json_decode($webhookBody, true);
        $event = $payload['event'] ?? '';

        Log::info('Razorpay QR webhook received', [
            'event' => $event,
            'payload' => $payload
        ]);

        try {
            switch ($event) {
                case 'qr_code.credited':
                    $this->handleQRCodeCredited($payload);
                    break;

                case 'qr_code.closed':
                    $this->handleQRCodeClosed($payload);
                    break;

                case 'payment.captured':
                    $this->handlePaymentCaptured($payload);
                    break;

                default:
                    Log::info('Razorpay QR webhook: unhandled event', ['event' => $event]);
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error('Razorpay QR webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle qr_code.credited event
     * This is triggered when a payment is made to the QR code
     */
    private function handleQRCodeCredited(array $payload): void
    {
        $qrEntity = $payload['payload']['qr_code']['entity'] ?? null;
        $paymentEntity = $payload['payload']['payment']['entity'] ?? null;

        if (!$qrEntity || !$paymentEntity) {
            Log::warning('QR code credited webhook missing required data');
            return;
        }

        $qrId = $qrEntity['id'] ?? null;
        $paymentId = $paymentEntity['id'] ?? null;

        if (!$qrId || !$paymentId) {
            return;
        }

        // Find our QR payment record
        $qrPayment = DeliveryQRPayment::where('razorpay_qr_id', $qrId)->first();

        if (!$qrPayment) {
            Log::warning('QR payment not found for Razorpay QR ID', ['qr_id' => $qrId]);
            return;
        }

        // Mark as paid
        $qrPayment->markAsPaid($paymentId);

        Log::info('QR payment marked as paid via webhook', [
            'qr_payment_id' => $qrPayment->id,
            'razorpay_payment_id' => $paymentId,
            'order_id' => $qrPayment->order_id,
        ]);

        // TODO: Trigger any post-payment actions
        // e.g., update order status, send notifications, etc.
        $this->postPaymentActions($qrPayment);
    }

    /**
     * Handle qr_code.closed event
     */
    private function handleQRCodeClosed(array $payload): void
    {
        $qrEntity = $payload['payload']['qr_code']['entity'] ?? null;

        if (!$qrEntity) {
            return;
        }

        $qrId = $qrEntity['id'] ?? null;

        if (!$qrId) {
            return;
        }

        $qrPayment = DeliveryQRPayment::where('razorpay_qr_id', $qrId)->first();

        if (!$qrPayment) {
            return;
        }

        // Update status to closed/cancelled if not already paid
        if (!$qrPayment->isPaid()) {
            $qrPayment->update(['status' => 'cancelled']);

            Log::info('QR payment marked as closed via webhook', [
                'qr_payment_id' => $qrPayment->id,
            ]);
        }
    }

    /**
     * Handle payment.captured event
     * Alternative way to detect payment (in case qr_code.credited is not triggered)
     */
    private function handlePaymentCaptured(array $payload): void
    {
        $paymentEntity = $payload['payload']['payment']['entity'] ?? null;

        if (!$paymentEntity) {
            return;
        }

        $paymentId = $paymentEntity['id'] ?? null;
        $notes = $paymentEntity['notes'] ?? [];

        // Check if this payment is linked to a QR code
        if (isset($notes['order_id'])) {
            $orderId = $notes['order_id'];

            // Find QR payment by order ID
            $qrPayment = DeliveryQRPayment::where('order_id', $orderId)
                ->whereNull('razorpay_payment_id')
                ->first();

            if ($qrPayment) {
                $qrPayment->markAsPaid($paymentId);

                Log::info('QR payment marked as paid via payment.captured webhook', [
                    'qr_payment_id' => $qrPayment->id,
                    'payment_id' => $paymentId,
                ]);

                $this->postPaymentActions($qrPayment);
            }
        }
    }

    /**
     * Execute post-payment actions
     *
     * IMPORTANT: This method ONLY updates the order's payment status.
     * All wallet updates, commissions, and transactions are handled by
     * OrderLogic::create_transaction() when the order is delivered.
     */
    private function postPaymentActions(DeliveryQRPayment $qrPayment): void
    {
        try {
            $order = \App\Models\Order::find($qrPayment->order_id);

            if (!$order) {
                Log::warning('Order not found for post-payment actions', [
                    'qr_payment_id' => $qrPayment->id,
                    'order_id' => $qrPayment->order_id
                ]);
                return;
            }

            // Update order payment method and status
            $oldPaymentMethod = $order->payment_method;
            $oldPaymentStatus = $order->payment_status;

            // Update payment method to show QR payment at delivery
            $order->payment_method = 'qr_payment_at_delivery';

            // Update payment status to paid
            $order->payment_status = 'paid';

            $order->save();

            Log::info('QR Payment webhook: Order payment status updated', [
                'order_id' => $order->id,
                'qr_payment_id' => $qrPayment->id,
                'payment_method' => $oldPaymentMethod . ' → qr_payment_at_delivery',
                'payment_status' => $oldPaymentStatus . ' → paid',
            ]);

            Log::info('Post-payment actions completed - wallet/commission handled by create_transaction()', [
                'qr_payment_id' => $qrPayment->id,
                'order_id' => $qrPayment->order_id,
                'amount' => $qrPayment->amount,
            ]);

        } catch (\Exception $e) {
            Log::error('Post-payment actions failed', [
                'qr_payment_id' => $qrPayment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
