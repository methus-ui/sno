<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryQRPayment;
use App\Models\Order;
use App\Services\RazorpayQRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DeliveryQRPaymentController extends Controller
{
    private RazorpayQRService $qrService;

    public function __construct(RazorpayQRService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * Generate QR code for delivery payment
     *
     * POST /api/v1/delivery/qr-payment/generate
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateQR(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'amount' => 'nullable|numeric|min:1',
            'delivery_man_id' => 'nullable|integer|exists:delivery_men,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get order details first
            $order = Order::find($request->order_id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Get delivery man ID from request or authenticated user
            $deliveryManId = $request->delivery_man_id;

            if (!$deliveryManId && $request->user() && isset($request->user()->deliveryman)) {
                $deliveryManId = $request->user()->deliveryman->id;
            }

            if (!$deliveryManId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery man ID is required'
                ], 400);
            }

            // Get amount from request or calculate from order total
            $amount = $request->amount;
            if (!$amount) {
                // Use order total amount if not provided
                $amount = $order->order_amount ?? 0;

                if ($amount <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid order amount. Please provide amount explicitly.'
                    ], 400);
                }
            }

            // Check if order already has an active QR payment
            $existingQR = DeliveryQRPayment::where('order_id', $request->order_id)
                ->where('delivery_man_id', $deliveryManId)
                ->active()
                ->first();

            if ($existingQR) {
                return response()->json([
                    'success' => true,
                    'message' => 'QR code already exists for this order',
                    'data' => $this->formatQRResponse($existingQR)
                ]);
            }

            $metadata = [
                'customer_name' => $order->customer ? $order->customer->f_name . ' ' . $order->customer->l_name : 'Guest',
                'store_id' => $order->store_id,
                'store_name' => $order->store ? $order->store->name : null,
            ];

            // Generate QR code
            $qrPayment = $this->qrService->generateDeliveryQR(
                $request->order_id,
                $deliveryManId,
                $amount,
                $metadata
            );

            if (!$qrPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate QR code. Please try again.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'QR code generated successfully',
                'data' => $this->formatQRResponse($qrPayment)
            ]);

        } catch (\Exception $e) {
            Log::error('QR generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating QR code'
            ], 500);
        }
    }

    /**
     * Check QR payment status
     *
     * GET /api/v1/delivery/qr-payment/status/{qr_payment_id}
     *
     * @param int $qrPaymentId
     * @return JsonResponse
     */
    public function checkStatus(int $qrPaymentId): JsonResponse
    {
        try {
            $qrPayment = DeliveryQRPayment::find($qrPaymentId);

            if (!$qrPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR payment not found'
                ], 404);
            }

            // Check if already paid
            if ($qrPayment->isPaid()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment completed',
                    'data' => [
                        'status' => 'paid',
                        'is_paid' => true,
                        'paid_at' => $qrPayment->paid_at,
                        'payment_id' => $qrPayment->razorpay_payment_id,
                        'qr_payment' => $this->formatQRResponse($qrPayment)
                    ]
                ]);
            }

            // Check if expired
            if ($qrPayment->isExpired()) {
                return response()->json([
                    'success' => true,
                    'message' => 'QR code has expired',
                    'data' => [
                        'status' => 'expired',
                        'is_paid' => false,
                        'expires_at' => $qrPayment->expires_at,
                        'qr_payment' => $this->formatQRResponse($qrPayment)
                    ]
                ]);
            }

            // Check with Razorpay for latest status
            $razorpayStatus = $this->qrService->checkQRStatus($qrPayment->razorpay_qr_id);

            if ($razorpayStatus['success'] && $razorpayStatus['is_paid']) {
                // Payment found! Update our records
                $payments = $razorpayStatus['payments'];
                $latestPayment = end($payments);

                $qrPayment->markAsPaid($latestPayment['id']);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment completed',
                    'data' => [
                        'status' => 'paid',
                        'is_paid' => true,
                        'paid_at' => $qrPayment->paid_at,
                        'payment_id' => $qrPayment->razorpay_payment_id,
                        'qr_payment' => $this->formatQRResponse($qrPayment)
                    ]
                ]);
            }

            // Still pending
            return response()->json([
                'success' => true,
                'message' => 'Payment pending',
                'data' => [
                    'status' => 'pending',
                    'is_paid' => false,
                    'qr_payment' => $this->formatQRResponse($qrPayment)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('QR status check failed', [
                'qr_payment_id' => $qrPaymentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status'
            ], 500);
        }
    }

    /**
     * Get QR payment details
     *
     * GET /api/v1/delivery/qr-payment/{qr_payment_id}
     *
     * @param int $qrPaymentId
     * @return JsonResponse
     */
    public function getDetails(int $qrPaymentId): JsonResponse
    {
        try {
            $details = $this->qrService->getQRPaymentDetails($qrPaymentId);

            if (!$details) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR payment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $details
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get QR details', [
                'qr_payment_id' => $qrPaymentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve QR payment details'
            ], 500);
        }
    }

    /**
     * Cancel QR payment
     *
     * POST /api/v1/delivery/qr-payment/cancel/{qr_payment_id}
     *
     * @param int $qrPaymentId
     * @return JsonResponse
     */
    public function cancelQR(int $qrPaymentId): JsonResponse
    {
        try {
            $qrPayment = DeliveryQRPayment::find($qrPaymentId);

            if (!$qrPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR payment not found'
                ], 404);
            }

            if ($qrPayment->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel a paid QR code'
                ], 400);
            }

            $success = $this->qrService->closeQR($qrPaymentId);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'QR payment cancelled successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel QR payment'
            ], 500);

        } catch (\Exception $e) {
            Log::error('QR cancellation failed', [
                'qr_payment_id' => $qrPaymentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel QR payment'
            ], 500);
        }
    }

    /**
     * Get all QR payments for a delivery man
     *
     * GET /api/v1/delivery/qr-payment/my-qr-payments
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyQRPayments(Request $request): JsonResponse
    {
        try {
            $deliveryManId = $request->user()->deliveryman->id ?? null;

            if (!$deliveryManId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery man not found'
                ], 404);
            }

            $qrPayments = DeliveryQRPayment::where('delivery_man_id', $deliveryManId)
                ->with(['order', 'order.customer'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $formattedPayments = $qrPayments->map(function ($qrPayment) {
                return $this->formatQRResponse($qrPayment);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPayments,
                'pagination' => [
                    'total' => $qrPayments->total(),
                    'per_page' => $qrPayments->perPage(),
                    'current_page' => $qrPayments->currentPage(),
                    'last_page' => $qrPayments->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get QR payments', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve QR payments'
            ], 500);
        }
    }

    /**
     * Format QR payment response
     *
     * @param DeliveryQRPayment $qrPayment
     * @return array
     */
    private function formatQRResponse(DeliveryQRPayment $qrPayment): array
    {
        return [
            'id' => $qrPayment->id,
            'order_id' => $qrPayment->order_id,
            'delivery_man_id' => $qrPayment->delivery_man_id,
            'razorpay_qr_id' => $qrPayment->razorpay_qr_id,
            'razorpay_order_id' => $qrPayment->razorpay_order_id,
            'amount' => (float) $qrPayment->amount,
            'currency' => $qrPayment->currency,
            'status' => $qrPayment->status,
            'qr_code_url' => $qrPayment->qr_code_url,
            'qr_code_data' => $qrPayment->qr_code_data,
            'payment_link' => $qrPayment->payment_link,
            'is_expired' => $qrPayment->isExpired(),
            'is_paid' => $qrPayment->isPaid(),
            'expires_at' => $qrPayment->expires_at ? $qrPayment->expires_at->toIso8601String() : null,
            'paid_at' => $qrPayment->paid_at ? $qrPayment->paid_at->toIso8601String() : null,
            'created_at' => $qrPayment->created_at->toIso8601String(),
            'order' => $qrPayment->order ? [
                'id' => $qrPayment->order->id,
                'order_amount' => $qrPayment->order->order_amount,
                'customer' => $qrPayment->order->customer ? [
                    'id' => $qrPayment->order->customer->id,
                    'name' => $qrPayment->order->customer->f_name . ' ' . $qrPayment->order->customer->l_name,
                    'phone' => $qrPayment->order->customer->phone,
                ] : null,
            ] : null,
        ];
    }
}
