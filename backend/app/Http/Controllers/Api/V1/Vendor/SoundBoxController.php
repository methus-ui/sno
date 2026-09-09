<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SoundBoxController extends Controller
{
    /**
     * Quick accept order (entire order)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickAccept(Request $request)
    {
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $order = Order::where('id', $request->order_id)
                ->where('store_id', $device->store_id)
                ->firstOrFail();

            // Check if order can be accepted
            if (!in_array($order->order_status, ['pending', 'accepted'])) {
                return response()->json([
                    'errors' => [
                        ['code' => 'order', 'message' => 'Order cannot be accepted in current status']
                    ]
                ], 400);
            }

            DB::beginTransaction();

            // Update order status to confirmed
            $order->order_status = 'confirmed';
            $order->confirmed = now();
            $order->save();

            // Send notification to customer
            $fcm_token = $order->customer?->cm_firebase_token;
            $value = Helpers::order_status_update_message('confirmed');
            if ($value && $fcm_token) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            }

            DB::commit();

            if (config('soundbox.logging.log_order_actions', true)) {
                Log::info("Order {$order->id} quick-accepted by device {$device->id}");
            }

            return response()->json([
                'status' => 'accepted',
                'order_id' => $order->id,
                'new_status' => 'confirmed',
                'message' => 'Order accepted successfully',
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Quick accept failed: {$e->getMessage()}");

            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }

    /**
     * Quick reject order (entire order with reason)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickReject(Request $request)
    {
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $order = Order::where('id', $request->order_id)
                ->where('store_id', $device->store_id)
                ->firstOrFail();

            // Check if order can be rejected
            if (!in_array($order->order_status, ['pending', 'accepted', 'confirmed'])) {
                return response()->json([
                    'errors' => [
                        ['code' => 'order', 'message' => 'Order cannot be rejected in current status']
                    ]
                ], 400);
            }

            DB::beginTransaction();

            $reason = $request->reason ?? 'Rejected via sound box';

            // Update order status to canceled
            $order->order_status = 'canceled';
            $order->canceled = now();
            $order->cancellation_reason = $reason;
            $order->canceled_by = 'vendor';
            $order->save();

            // Refund if already paid
            if (in_array($order->payment_method, ['digital_payment', 'wallet'])) {
                Helpers::refund_order($order->id, $reason);
            }

            // Send notification to customer
            $fcm_token = $order->customer?->cm_firebase_token;
            $value = Helpers::order_status_update_message('canceled');
            if ($value && $fcm_token) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            }

            DB::commit();

            if (config('soundbox.logging.log_order_actions', true)) {
                Log::info("Order {$order->id} quick-rejected by device {$device->id}: {$reason}");
            }

            return response()->json([
                'status' => 'rejected',
                'order_id' => $order->id,
                'new_status' => 'canceled',
                'refund_initiated' => in_array($order->payment_method, ['digital_payment', 'wallet']),
                'message' => 'Order rejected successfully',
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Quick reject failed: {$e->getMessage()}");

            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }

    /**
     * Mark specific items as unavailable
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markItemsUnavailable(Request $request)
    {
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'order_detail_ids' => 'required|array',
            'order_detail_ids.*' => 'required|integer|exists:order_details,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $order = Order::where('id', $request->order_id)
                ->where('store_id', $device->store_id)
                ->with('details')
                ->firstOrFail();

            DB::beginTransaction();

            $updatedCount = 0;
            foreach ($request->order_detail_ids as $detailId) {
                $detail = OrderDetail::where('id', $detailId)
                    ->where('order_id', $order->id)
                    ->first();

                if ($detail) {
                    $detail->is_unavailable = 1;
                    $detail->unavailable_note = 'Marked unavailable via sound box';
                    $detail->save();
                    $updatedCount++;
                }
            }

            // Recalculate order total
            $itemAmount = 0;
            foreach ($order->details as $detail) {
                if (!$detail->is_unavailable) {
                    $itemAmount += $detail->price;
                }
            }

            $order->order_amount = $itemAmount + $order->delivery_charge + $order->total_tax_amount - $order->coupon_discount_amount;
            $order->save();

            DB::commit();

            if (config('soundbox.logging.log_order_actions', true)) {
                Log::info("Order {$order->id} - {$updatedCount} items marked unavailable by device {$device->id}");
            }

            return response()->json([
                'message' => 'Items marked unavailable successfully',
                'updated_items' => $updatedCount,
                'new_order_total' => (float) $order->order_amount,
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Mark items unavailable failed: {$e->getMessage()}");

            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }

    /**
     * Partial accept - Accept order with some items unavailable
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function partialAccept(Request $request)
    {
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'unavailable_items' => 'required|array',
            'unavailable_items.*.order_detail_id' => 'required|integer|exists:order_details,id',
            'unavailable_items.*.note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $order = Order::where('id', $request->order_id)
                ->where('store_id', $device->store_id)
                ->with('details')
                ->firstOrFail();

            // Check if order can be accepted
            if (!in_array($order->order_status, ['pending', 'accepted'])) {
                return response()->json([
                    'errors' => [
                        ['code' => 'order', 'message' => 'Order cannot be accepted in current status']
                    ]
                ], 400);
            }

            DB::beginTransaction();

            // Mark unavailable items
            $unavailableCount = 0;
            foreach ($request->unavailable_items as $item) {
                $detail = OrderDetail::where('id', $item['order_detail_id'])
                    ->where('order_id', $order->id)
                    ->first();

                if ($detail) {
                    $detail->is_unavailable = 1;
                    $detail->unavailable_note = $item['note'] ?? 'Unavailable (via sound box)';
                    $detail->save();
                    $unavailableCount++;
                }
            }

            // Recalculate order total
            $itemAmount = 0;
            foreach ($order->details as $detail) {
                if (!$detail->is_unavailable) {
                    $itemAmount += $detail->price;
                }
            }

            $order->order_amount = $itemAmount + $order->delivery_charge + $order->total_tax_amount - $order->coupon_discount_amount;

            // Confirm order
            $order->order_status = 'confirmed';
            $order->confirmed = now();
            $order->save();

            // Send notification to customer
            $fcm_token = $order->customer?->cm_firebase_token;
            if ($fcm_token) {
                $message = "Your order has been confirmed. {$unavailableCount} item(s) are unavailable.";
                $data = [
                    'title' => translate('Order'),
                    'description' => $message,
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            }

            DB::commit();

            if (config('soundbox.logging.log_order_actions', true)) {
                Log::info("Order {$order->id} partial-accepted by device {$device->id}: {$unavailableCount} items unavailable");
            }

            return response()->json([
                'status' => 'accepted',
                'order_id' => $order->id,
                'new_status' => 'confirmed',
                'unavailable_count' => $unavailableCount,
                'new_total' => (float) $order->order_amount,
                'message' => 'Order accepted with unavailable items',
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Partial accept failed: {$e->getMessage()}");

            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }

    /**
     * Get pending orders needing action
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pendingActions(Request $request)
    {
        $device = $request->attributes->get('device');

        try {
            $orders = Order::where('store_id', $device->store_id)
                ->whereIn('order_status', ['pending', 'accepted'])
                ->with(['details.item', 'customer'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $ordersData = $orders->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'order_code' => '#ORD-' . $order->id,
                    'customer_name' => $order->customer ? $order->customer->f_name . ' ' . $order->customer->l_name : 'Guest',
                    'total_amount' => (float) $order->order_amount,
                    'item_count' => $order->details->count(),
                    'order_status' => $order->order_status,
                    'created_at' => $order->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'orders' => $ordersData,
                'total_pending' => $orders->count(),
            ], 200);

        } catch (Exception $e) {
            Log::error("Get pending actions failed: {$e->getMessage()}");

            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }
}
