<?php

namespace App\Services;

use App\Models\DeliveryQRPayment;
use App\Models\Order;
use App\Models\DeliveryMan;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class RazorpayQRService
{
    private ?Api $api = null;
    private array $config;

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
            Log::error('Razorpay configuration not found');
            return;
        }

        $mode = $config->mode ?? 'live';
        $values = $mode === 'live' ? $config->live_values : $config->test_values;

        if (!$values) {
            Log::error('Razorpay values not found for mode: ' . $mode);
            return;
        }

        $razor = json_decode($values);

        if ($razor && isset($razor->api_key) && isset($razor->api_secret)) {
            $this->config = [
                'api_key' => $razor->api_key,
                'api_secret' => $razor->api_secret,
            ];

            try {
                $this->api = new Api($razor->api_key, $razor->api_secret);
                Log::info('Razorpay API initialized successfully');
            } catch (\Exception $e) {
                Log::error('Razorpay API initialization failed: ' . $e->getMessage());
            }
        } else {
            Log::error('Razorpay credentials missing');
        }
    }

    /**
     * Generate QR code for delivery payment
     *
     * @param int $orderId
     * @param int $deliveryManId
     * @param float $amount
     * @param array $metadata
     * @return DeliveryQRPayment|null
     */
    public function generateDeliveryQR(
        int $orderId,
        int $deliveryManId,
        float $amount,
        array $metadata = []
    ): ?DeliveryQRPayment {
        if (!$this->api) {
            Log::error('Razorpay API not initialized');
            return null;
        }

        try {
            $order = Order::find($orderId);
            $deliveryMan = DeliveryMan::find($deliveryManId);

            if (!$order || !$deliveryMan) {
                Log::error('Order or Delivery Man not found', [
                    'order_id' => $orderId,
                    'delivery_man_id' => $deliveryManId
                ]);
                return null;
            }

            // Prepare metadata
            $qrMetadata = array_merge([
                'order_id' => $orderId,
                'order_reference' => $order->id,
                'delivery_man_id' => $deliveryManId,
                'delivery_man_name' => $deliveryMan->f_name . ' ' . $deliveryMan->l_name,
                'customer_id' => $order->user_id,
                'created_by' => 'delivery_confirmation',
                'created_at' => now()->toDateTimeString(),
            ], $metadata);

            // Create Razorpay QR code
            // Note: Razorpay QR codes can be created with or without an order
            // For better tracking, we create both order and QR

            // Create QR code directly (without Razorpay order)
            // Using exact same format that worked in standalone test
            $qrParams = [
                'type' => 'upi_qr',
                'name' => 'Delivery Order #' . $orderId,
                'usage' => 'single_use',
                'fixed_amount' => true,
                'payment_amount' => (int) round($amount * 100),
                'description' => 'Delivery Payment',
                'close_by' => time() + (30 * 60),
            ];

            Log::info('Creating QR code', ['amount_paise' => $qrParams['payment_amount']]);

            $qrCode = $this->api->qrCode->create($qrParams);

            // Extract QR data - convert object to array if needed
            $qrArray = $qrCode instanceof \Razorpay\Api\QrCode ? $qrCode->toArray() : (array) $qrCode;

            // Try to fetch additional QR details to get payment data
            $qrImageUrl = $qrArray['image_url'] ?? $qrCode['image_url'] ?? null;
            $qrId = $qrArray['id'] ?? $qrCode['id'];

            // Razorpay doesn't provide qr_string directly via API for UPI QR
            // The image_url is the actual QR code image
            // For UPI string, we need to generate it based on VPA
            $upiString = null;

            // Try to fetch the QR to get payment details
            try {
                $fetchedQR = $this->api->qrCode->fetch($qrId);
                $fetchedArray = $fetchedQR instanceof \Razorpay\Api\QrCode ? $fetchedQR->toArray() : (array) $fetchedQR;

                Log::info('Fetched QR details', [
                    'qr_id' => $qrId,
                    'fields' => array_keys($fetchedArray),
                    'image_url' => $fetchedArray['image_url'] ?? 'not found'
                ]);

                // Use fetched data if available
                if (!$qrImageUrl && isset($fetchedArray['image_url'])) {
                    $qrImageUrl = $fetchedArray['image_url'];
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch QR details', ['error' => $e->getMessage()]);
            }

            $orderId_razorpay = null; // No order created

            // Store in database
            $deliveryQRPayment = DeliveryQRPayment::create([
                'order_id' => $orderId,
                'delivery_man_id' => $deliveryManId,
                'razorpay_qr_id' => $qrId,
                'razorpay_order_id' => $orderId_razorpay,
                'amount' => $amount,
                'currency' => 'INR',
                'status' => 'active',
                'qr_code_url' => $qrImageUrl, // This is the QR code image URL
                'qr_code_data' => $qrImageUrl, // Same - this is the scannable QR image
                'payment_link' => $qrImageUrl, // Deep link to payment page
                'metadata' => $qrMetadata,
                'expires_at' => now()->addMinutes(30),
            ]);

            Log::info('Delivery QR payment created', [
                'qr_payment_id' => $deliveryQRPayment->id,
                'razorpay_qr_id' => $qrCode['id'],
                'order_id' => $orderId,
                'amount' => $amount,
            ]);

            return $deliveryQRPayment;

        } catch (\Exception $e) {
            Log::error('Failed to generate delivery QR code', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'delivery_man_id' => $deliveryManId,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Extract UPI payment link from QR code data
     */
    private function extractUPILink(array $qrCode): ?string
    {
        // The qr_string contains the UPI payment link
        return $qrCode['qr_string'] ?? null;
    }

    /**
     * Check QR payment status
     *
     * @param string $qrId Razorpay QR ID
     * @return array
     */
    public function checkQRStatus(string $qrId): array
    {
        if (!$this->api) {
            return [
                'success' => false,
                'message' => 'Razorpay API not initialized'
            ];
        }

        try {
            $qrCode = $this->api->qrCode->fetch($qrId);

            // Convert to array if it's an object
            $qrArray = $qrCode instanceof \Razorpay\Api\QrCode ? $qrCode->toArray() : (array) $qrCode;

            // Check if payments were received
            $paymentsCountReceived = $qrArray['payments_count_received'] ?? 0;
            $paymentsAmountReceived = $qrArray['payments_amount_received'] ?? 0;
            $isPaid = $paymentsCountReceived > 0 && $paymentsAmountReceived > 0;

            $payments = [];

            // Try to fetch actual payment details if paid
            if ($isPaid) {
                try {
                    // Fetch payments for this QR code
                    $paymentsList = $this->api->payment->all([
                        'expand[]' => 'qr_code',
                    ]);

                    // Filter payments for this specific QR code
                    foreach ($paymentsList->items as $payment) {
                        $paymentArray = $payment instanceof \Razorpay\Api\Payment ? $payment->toArray() : (array) $payment;

                        // Check if this payment is associated with our QR code
                        if (isset($paymentArray['notes']['qr_id']) && $paymentArray['notes']['qr_id'] === $qrId) {
                            $payments[] = $paymentArray;
                        }
                    }

                    // If no payments found via notes, create a dummy payment entry from QR data
                    if (empty($payments) && $isPaid) {
                        $payments[] = [
                            'id' => 'pay_from_qr_' . substr($qrId, -10),
                            'amount' => $paymentsAmountReceived,
                            'status' => 'captured',
                            'created_at' => $qrArray['updated_at'] ?? time(),
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not fetch payment details for QR', [
                        'qr_id' => $qrId,
                        'error' => $e->getMessage()
                    ]);

                    // Create a placeholder payment based on QR data
                    if ($isPaid) {
                        $payments[] = [
                            'id' => 'pay_from_qr_' . substr($qrId, -10),
                            'amount' => $paymentsAmountReceived,
                            'status' => 'captured',
                            'created_at' => $qrArray['updated_at'] ?? time(),
                        ];
                    }
                }
            }

            return [
                'success' => true,
                'status' => $qrArray['status'] ?? 'unknown',
                'is_paid' => $isPaid,
                'payments' => $payments,
                'qr_data' => $qrArray,
                'payments_count' => $paymentsCountReceived,
                'amount_received' => $paymentsAmountReceived,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check QR status', [
                'qr_id' => $qrId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Mark QR payment as paid
     *
     * @param int $qrPaymentId
     * @param string $paymentId
     * @return bool
     */
    public function markAsPaid(int $qrPaymentId, string $paymentId): bool
    {
        try {
            $qrPayment = DeliveryQRPayment::find($qrPaymentId);

            if (!$qrPayment) {
                return false;
            }

            $qrPayment->markAsPaid($paymentId);

            Log::info('QR payment marked as paid', [
                'qr_payment_id' => $qrPaymentId,
                'payment_id' => $paymentId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to mark QR payment as paid', [
                'qr_payment_id' => $qrPaymentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Close/Cancel QR code
     *
     * @param int $qrPaymentId
     * @return bool
     */
    public function closeQR(int $qrPaymentId): bool
    {
        if (!$this->api) {
            return false;
        }

        try {
            $qrPayment = DeliveryQRPayment::find($qrPaymentId);

            if (!$qrPayment) {
                return false;
            }

            // Close QR code on Razorpay
            $this->api->qrCode->fetch($qrPayment->razorpay_qr_id)->close();

            // Update local status
            $qrPayment->update(['status' => 'cancelled']);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to close QR code', [
                'qr_payment_id' => $qrPaymentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get QR payment details
     *
     * @param int $qrPaymentId
     * @return array|null
     */
    public function getQRPaymentDetails(int $qrPaymentId): ?array
    {
        $qrPayment = DeliveryQRPayment::with(['order', 'deliveryMan'])
            ->find($qrPaymentId);

        if (!$qrPayment) {
            return null;
        }

        return [
            'id' => $qrPayment->id,
            'order_id' => $qrPayment->order_id,
            'delivery_man_id' => $qrPayment->delivery_man_id,
            'razorpay_qr_id' => $qrPayment->razorpay_qr_id,
            'razorpay_order_id' => $qrPayment->razorpay_order_id,
            'amount' => $qrPayment->amount,
            'currency' => $qrPayment->currency,
            'status' => $qrPayment->status,
            'qr_code_url' => $qrPayment->qr_code_url,
            'qr_code_data' => $qrPayment->qr_code_data,
            'payment_link' => $qrPayment->payment_link,
            'is_expired' => $qrPayment->isExpired(),
            'is_paid' => $qrPayment->isPaid(),
            'expires_at' => $qrPayment->expires_at,
            'paid_at' => $qrPayment->paid_at,
            'order' => $qrPayment->order,
            'delivery_man' => $qrPayment->deliveryMan,
        ];
    }
}
