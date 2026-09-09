<?php

namespace App\Services;

use App\Models\SecurityDepositPayment;
use App\Models\DeliveryMan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;

class SecurityDepositService
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
        $config = DB::table('addon_settings')
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
                Log::info('Razorpay API initialized successfully for security deposit');
            } catch (\Exception $e) {
                Log::error('Razorpay API initialization failed: ' . $e->getMessage());
            }
        } else {
            Log::error('Razorpay credentials missing');
        }
    }

    /**
     * Check if security deposit is enabled
     */
    public function isEnabled(): bool
    {
        $setting = DB::table('business_settings')
            ->where('key', 'security_deposit_enabled')
            ->first();

        return $setting && $setting->value === '1';
    }

    /**
     * Get security deposit amount
     */
    public function getAmount(): float
    {
        $setting = DB::table('business_settings')
            ->where('key', 'security_deposit_amount')
            ->first();

        return $setting ? (float) $setting->value : 0;
    }

    /**
     * Initiate security deposit payment
     *
     * @param int $deliveryManId
     * @param string $paymentMethod
     * @return array
     */
    public function initiatePayment(int $deliveryManId, string $paymentMethod = 'razorpay'): array
    {
        try {
            $deliveryMan = DeliveryMan::find($deliveryManId);

            if (!$deliveryMan) {
                return [
                    'success' => false,
                    'message' => 'Delivery man not found',
                ];
            }

            // Check if already paid
            if ($deliveryMan->security_deposit_status === 'paid') {
                return [
                    'success' => false,
                    'message' => 'Security deposit already paid',
                ];
            }

            // Check if security deposit is enabled
            if (!$this->isEnabled()) {
                return [
                    'success' => false,
                    'message' => 'Security deposit is not enabled',
                ];
            }

            $amount = $this->getAmount();

            if ($amount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid security deposit amount',
                ];
            }

            // Check for existing pending payment
            $existingPayment = SecurityDepositPayment::where('delivery_man_id', $deliveryManId)
                ->where('status', 'pending')
                ->first();

            if ($existingPayment) {
                // Return existing payment details
                return $this->formatPaymentResponse($existingPayment);
            }

            // Create new payment based on method
            if ($paymentMethod === 'razorpay') {
                return $this->createRazorpayPayment($deliveryMan, $amount);
            }

            return [
                'success' => false,
                'message' => 'Payment method not supported',
            ];

        } catch (\Exception $e) {
            Log::error('Security deposit payment initiation failed: ' . $e->getMessage(), [
                'delivery_man_id' => $deliveryManId,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create Razorpay payment
     */
    private function createRazorpayPayment(DeliveryMan $deliveryMan, float $amount): array
    {
        if (!$this->api) {
            return [
                'success' => false,
                'message' => 'Razorpay API not initialized',
            ];
        }

        try {
            // Create Razorpay order
            $razorpayOrder = $this->api->order->create([
                'amount' => $amount * 100, // Convert to paise
                'currency' => 'INR',
                'receipt' => 'SD_' . $deliveryMan->id . '_' . time(),
                'notes' => [
                    'delivery_man_id' => $deliveryMan->id,
                    'delivery_man_name' => $deliveryMan->f_name . ' ' . $deliveryMan->l_name,
                    'phone' => $deliveryMan->phone,
                    'type' => 'security_deposit',
                ],
            ]);

            // Create payment record
            $payment = SecurityDepositPayment::create([
                'delivery_man_id' => $deliveryMan->id,
                'amount' => $amount,
                'currency' => 'INR',
                'status' => 'pending',
                'payment_method' => 'razorpay',
                'razorpay_order_id' => $razorpayOrder->id,
                'callback_url' => route('api.v1.delivery-man.security-deposit.callback'),
                'metadata' => [
                    'delivery_man_name' => $deliveryMan->f_name . ' ' . $deliveryMan->l_name,
                    'phone' => $deliveryMan->phone,
                    'email' => $deliveryMan->email,
                ],
            ]);

            // Generate payment URL for mobile app
            $paymentUrl = $this->generateRazorpayPaymentUrl($razorpayOrder->id, $amount, $deliveryMan);

            $payment->update([
                'payment_url' => $paymentUrl,
            ]);

            return $this->formatPaymentResponse($payment);

        } catch (\Exception $e) {
            Log::error('Razorpay security deposit creation failed: ' . $e->getMessage(), [
                'delivery_man_id' => $deliveryMan->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create Razorpay payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate Razorpay payment URL
     */
    private function generateRazorpayPaymentUrl(string $orderId, float $amount, DeliveryMan $deliveryMan): string
    {
        $baseUrl = config('app.url');
        $callbackUrl = route('api.v1.delivery-man.security-deposit.callback');

        // For mobile apps, we return the checkout URL with parameters
        return $baseUrl . '/security-deposit-payment?' . http_build_query([
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'INR',
            'name' => $deliveryMan->f_name . ' ' . $deliveryMan->l_name,
            'email' => $deliveryMan->email,
            'phone' => $deliveryMan->phone,
            'callback_url' => $callbackUrl,
            'key' => $this->config['api_key'] ?? '',
        ]);
    }

    /**
     * Format payment response
     */
    private function formatPaymentResponse(SecurityDepositPayment $payment): array
    {
        return [
            'success' => true,
            'message' => 'Payment initiated successfully',
            'data' => [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'payment_url' => $payment->payment_url,
                'redirect_url' => $payment->payment_url,
                'callback_url' => $payment->callback_url,
                'razorpay_order_id' => $payment->razorpay_order_id,
                'razorpay_key' => $this->config['api_key'] ?? '',
            ],
        ];
    }

    /**
     * Verify and process payment
     */
    public function verifyPayment(string $paymentId, array $paymentData): array
    {
        try {
            $payment = SecurityDepositPayment::find($paymentId);

            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment not found',
                ];
            }

            if ($payment->status === 'success') {
                return [
                    'success' => true,
                    'message' => 'Payment already verified',
                    'data' => $this->getPaymentStatus($payment),
                ];
            }

            // Verify Razorpay payment
            if ($payment->payment_method === 'razorpay') {
                return $this->verifyRazorpayPayment($payment, $paymentData);
            }

            return [
                'success' => false,
                'message' => 'Payment method not supported',
            ];

        } catch (\Exception $e) {
            Log::error('Payment verification failed: ' . $e->getMessage(), [
                'payment_id' => $paymentId,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Razorpay payment
     */
    private function verifyRazorpayPayment(SecurityDepositPayment $payment, array $paymentData): array
    {
        if (!$this->api) {
            return [
                'success' => false,
                'message' => 'Razorpay API not initialized',
            ];
        }

        try {
            $razorpayPaymentId = $paymentData['razorpay_payment_id'] ?? null;
            $razorpayOrderId = $paymentData['razorpay_order_id'] ?? null;
            $razorpaySignature = $paymentData['razorpay_signature'] ?? null;

            if (!$razorpayPaymentId || !$razorpayOrderId || !$razorpaySignature) {
                return [
                    'success' => false,
                    'message' => 'Missing payment verification data',
                ];
            }

            // Verify signature
            $attributes = [
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
            ];

            $this->api->utility->verifyPaymentSignature($attributes);

            // Fetch payment details
            $razorpayPayment = $this->api->payment->fetch($razorpayPaymentId);

            if ($razorpayPayment->status === 'captured' || $razorpayPayment->status === 'authorized') {
                // Mark payment as successful
                $payment->update([
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'razorpay_signature' => $razorpaySignature,
                    'transaction_id' => $razorpayPaymentId,
                ]);

                $payment->markAsSuccess($razorpayPaymentId, $razorpayPaymentId);

                return [
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'data' => $this->getPaymentStatus($payment),
                ];
            }

            return [
                'success' => false,
                'message' => 'Payment not captured',
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay payment verification failed: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $payment->markAsFailed();

            return [
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($payment): array
    {
        if (is_numeric($payment)) {
            $payment = SecurityDepositPayment::find($payment);
        }

        if (!$payment) {
            return [
                'status' => 'not_found',
                'message' => 'Payment not found',
            ];
        }

        return [
            'status' => $payment->status === 'success' ? 'paid' : $payment->status,
            'amount' => $payment->amount,
            'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
            'transaction_id' => $payment->transaction_id,
            'payment_method' => $payment->payment_method,
            'razorpay_order_id' => $payment->razorpay_order_id,
            'razorpay_payment_id' => $payment->razorpay_payment_id,
        ];
    }

    /**
     * Get delivery man security deposit status
     */
    public function getDeliveryManStatus(int $deliveryManId): array
    {
        $deliveryMan = DeliveryMan::find($deliveryManId);

        if (!$deliveryMan) {
            return [
                'enabled' => false,
                'status' => 'not_found',
                'message' => 'Delivery man not found',
            ];
        }

        return [
            'security_deposit_enabled' => $this->isEnabled(),
            'security_deposit_amount' => $deliveryMan->security_deposit_amount ?: $this->getAmount(),
            'security_deposit_status' => $deliveryMan->security_deposit_status,
            'security_deposit_paid_at' => $deliveryMan->security_deposit_paid_at?->format('Y-m-d H:i:s'),
            'security_deposit_transaction_id' => $deliveryMan->security_deposit_transaction_id,
            'security_deposit_payment_method' => $deliveryMan->security_deposit_payment_method,
        ];
    }

    /**
     * Refund security deposit
     */
    public function refund(int $deliveryManId, string $reason, int $refundedBy): array
    {
        try {
            $deliveryMan = DeliveryMan::find($deliveryManId);

            if (!$deliveryMan) {
                return [
                    'success' => false,
                    'message' => 'Delivery man not found',
                ];
            }

            if ($deliveryMan->security_deposit_status !== 'paid') {
                return [
                    'success' => false,
                    'message' => 'Security deposit not paid',
                ];
            }

            // Find the successful payment
            $payment = SecurityDepositPayment::where('delivery_man_id', $deliveryManId)
                ->where('status', 'success')
                ->first();

            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment record not found',
                ];
            }

            // For Razorpay, create refund
            if ($payment->payment_method === 'razorpay' && $this->api) {
                try {
                    $refund = $this->api->payment->fetch($payment->razorpay_payment_id)
                        ->refund([
                            'amount' => $payment->amount * 100, // Convert to paise
                            'notes' => [
                                'reason' => $reason,
                                'refunded_by' => $refundedBy,
                            ],
                        ]);

                    Log::info('Razorpay refund created', [
                        'refund_id' => $refund->id,
                        'payment_id' => $payment->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Razorpay refund failed: ' . $e->getMessage());
                    // Continue with marking as refunded even if Razorpay API fails
                }
            }

            // Mark payment as refunded
            $payment->markAsRefunded($reason, $refundedBy);

            return [
                'success' => true,
                'message' => 'Security deposit refunded successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Refund failed: ' . $e->getMessage(), [
                'delivery_man_id' => $deliveryManId,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Refund failed: ' . $e->getMessage(),
            ];
        }
    }
}
