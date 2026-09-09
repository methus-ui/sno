<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DeliveryMan;
use App\Models\Module;
use App\Models\Zone;
use App\Events\OrderAssignedEvent;
use App\Events\OrderStatusChangedEvent;
use App\Events\NewOrderEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DispatchController extends Controller
{
    /**
     * Unified Dispatch Dashboard
     */
    public function unifiedDashboard(Request $request)
    {
        // Get filter parameters
        $module_id = $request->get('module_id', 'all');
        $status = $request->get('status', 'all');
        $zone_id = $request->get('zone_id', 'all');

        // Get orders from all modules or specific module
        $orders = Order::with(['customer', 'store', 'module', 'delivery_man'])
            ->when($module_id !== 'all', function ($query) use ($module_id) {
                return $query->where('module_id', $module_id);
            })
            ->when($status !== 'all', function ($query) use ($status) {
                if ($status === 'unassigned') {
                    return $query->whereNull('delivery_man_id')
                        ->whereIn('order_status', ['pending', 'confirmed', 'processing']);
                } else {
                    return $query->where('order_status', $status);
                }
            })
            ->when($zone_id !== 'all', function ($query) use ($zone_id) {
                return $query->where('zone_id', $zone_id);
            })
            ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'picked_up', 'handover'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        // Get available delivery men
        $delivery_men = DeliveryMan::with(['zone', 'last_location'])
            ->where('active', 1)
            ->where('application_status', 'approved')
            ->where('status', 1)
            ->when($zone_id !== 'all', function ($query) use ($zone_id) {
                return $query->where('zone_id', $zone_id);
            })
            ->get();

        // Get modules
        $modules = Module::active()->get();

        // Get zones
        $zones = Zone::active()->get();

        // Calculate statistics
        $stats = [
            'unassigned_orders' => Order::whereNull('delivery_man_id')
                ->whereIn('order_status', ['pending', 'confirmed', 'processing'])
                ->count(),
            'active_deliveries' => Order::whereNotNull('delivery_man_id')
                ->whereIn('order_status', ['confirmed', 'processing', 'picked_up', 'handover'])
                ->count(),
            'available_dm' => DeliveryMan::where('active', 1)
                ->where('application_status', 'approved')
                ->where('status', 1)
                ->where('current_orders', '<', 3)
                ->count(),
            'total_orders_today' => Order::whereDate('created_at', today())
                ->count(),
        ];

        return view('admin-views.dispatch.unified-dashboard', compact(
            'orders',
            'delivery_men',
            'modules',
            'zones',
            'stats'
        ));
    }

    /**
     * Assign Delivery Man to Order
     */
    public function assignDeliveryMan(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'delivery_man_id' => 'required|exists:delivery_men,id',
        ]);

        try {
            $order = Order::findOrFail($request->order_id);
            $delivery_man = DeliveryMan::findOrFail($request->delivery_man_id);

            // Check if delivery man is available
            if ($delivery_man->current_orders >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery man is not available (already has 3+ orders)'
                ], 400);
            }

            // Check if delivery man is in the same zone as the order
            if ($order->zone_id && $delivery_man->zone_id != $order->zone_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery man is not in the same zone as the order'
                ], 400);
            }

            DB::beginTransaction();

            // Assign delivery man
            $order->delivery_man_id = $delivery_man->id;
            if ($order->order_status == 'pending') {
                $order->order_status = 'confirmed';
            }
            $order->save();

            // Update delivery man's current orders count
            $delivery_man->increment('current_orders');

            // Send notification to delivery man
            $this->sendDeliveryManNotification($order, $delivery_man);

            DB::commit();

            // Broadcast order assigned event via WebSocket
            broadcast(new OrderAssignedEvent($order))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Delivery man assigned successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Assign Delivery Man to Multiple Orders
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
            'delivery_man_id' => 'required|exists:delivery_men,id',
        ]);

        try {
            $delivery_man = DeliveryMan::findOrFail($request->delivery_man_id);
            $orders = Order::whereIn('id', $request->order_ids)->get();

            // Check if delivery man can handle all orders
            $available_capacity = 3 - $delivery_man->current_orders;
            if (count($orders) > $available_capacity) {
                return response()->json([
                    'success' => false,
                    'message' => "Delivery man capacity exceeded! Can only assign {$available_capacity} more orders."
                ], 400);
            }

            DB::beginTransaction();

            $assigned_count = 0;
            foreach ($orders as $order) {
                // Skip if already assigned or not in assignable status
                if ($order->delivery_man_id || !in_array($order->order_status, ['pending', 'confirmed', 'processing'])) {
                    continue;
                }

                // Check zone compatibility
                if ($order->zone_id && $delivery_man->zone_id != $order->zone_id) {
                    continue;
                }

                $order->delivery_man_id = $delivery_man->id;
                if ($order->order_status == 'pending') {
                    $order->order_status = 'confirmed';
                }
                $order->save();

                $assigned_count++;
            }

            // Update delivery man's current orders count
            $delivery_man->increment('current_orders', $assigned_count);

            DB::commit();

            // Broadcast order assigned events via WebSocket for each assigned order
            foreach ($orders as $order) {
                if ($order->delivery_man_id) {
                    broadcast(new OrderAssignedEvent($order))->toOthers();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully assigned {$assigned_count} orders!"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto Assign Orders to Available Delivery Men
     */
    public function autoAssign(Request $request)
    {
        try {
            // Get unassigned orders
            $orders = Order::whereNull('delivery_man_id')
                ->whereIn('order_status', ['pending', 'confirmed', 'processing'])
                ->orderBy('created_at', 'asc')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No unassigned orders found'
                ]);
            }

            // Get available delivery men grouped by zone
            $delivery_men_by_zone = DeliveryMan::where('active', 1)
                ->where('application_status', 'approved')
                ->where('status', 1)
                ->where('current_orders', '<', 3)
                ->get()
                ->groupBy('zone_id');

            DB::beginTransaction();

            $assigned_count = 0;

            foreach ($orders as $order) {
                // Get delivery men in the same zone
                $zone_delivery_men = $delivery_men_by_zone->get($order->zone_id);

                if (!$zone_delivery_men || $zone_delivery_men->isEmpty()) {
                    continue;
                }

                // Find delivery man with least orders
                $delivery_man = $zone_delivery_men->sortBy('current_orders')->first();

                if ($delivery_man->current_orders >= 3) {
                    continue;
                }

                // Assign order
                $order->delivery_man_id = $delivery_man->id;
                if ($order->order_status == 'pending') {
                    $order->order_status = 'confirmed';
                }
                $order->save();

                // Update delivery man's order count
                $delivery_man->increment('current_orders');

                // Send notification
                $this->sendDeliveryManNotification($order, $delivery_man);

                // Broadcast order assigned event
                broadcast(new OrderAssignedEvent($order))->toOthers();

                $assigned_count++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Auto-assign completed! {$assigned_count} orders assigned successfully."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Available Delivery Men for an Order
     */
    public function getAvailableDeliveryMen(Request $request)
    {
        $order_id = $request->get('order_id');
        $order = Order::findOrFail($order_id);

        $delivery_men = DeliveryMan::with(['zone', 'last_location'])
            ->where('active', 1)
            ->where('application_status', 'approved')
            ->where('status', 1)
            ->where('current_orders', '<', 3)
            ->when($order->zone_id, function ($query) use ($order) {
                return $query->where('zone_id', $order->zone_id);
            })
            ->get();

        return response()->json([
            'success' => true,
            'delivery_men' => $delivery_men
        ]);
    }

    /**
     * Get Dispatch Statistics
     */
    public function getStatistics(Request $request)
    {
        $zone_id = $request->get('zone_id', 'all');
        $module_id = $request->get('module_id', 'all');

        $stats = Cache::remember('dispatch_stats_' . $zone_id . '_' . $module_id, 60, function () use ($zone_id, $module_id) {
            $base_query = Order::query();

            if ($zone_id !== 'all') {
                $base_query->where('zone_id', $zone_id);
            }

            if ($module_id !== 'all') {
                $base_query->where('module_id', $module_id);
            }

            return [
                'total_orders' => (clone $base_query)->count(),
                'unassigned' => (clone $base_query)->whereNull('delivery_man_id')
                    ->whereIn('order_status', ['pending', 'confirmed', 'processing'])
                    ->count(),
                'in_progress' => (clone $base_query)->whereIn('order_status', ['confirmed', 'processing', 'picked_up'])
                    ->count(),
                'delivered_today' => (clone $base_query)->where('order_status', 'delivered')
                    ->whereDate('delivered', today())
                    ->count(),
                'average_delivery_time' => (clone $base_query)->where('order_status', 'delivered')
                    ->whereDate('delivered', today())
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, delivered)) as avg_time')
                    ->value('avg_time') ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Send Notification to Delivery Man
     */
    private function sendDeliveryManNotification($order, $delivery_man)
    {
        try {
            // Implement your notification logic here
            // You can use Firebase, SMS, or other notification services

            // Example: Send FCM notification
            if ($delivery_man->fcm_token) {
                $data = [
                    'title' => 'New Order Assigned',
                    'description' => 'Order ID: #' . $order->id,
                    'order_id' => $order->id,
                    'type' => 'new_order'
                ];

                // \App\CentralLogics\Helpers::send_push_notif_to_device($delivery_man->fcm_token, $data);
            }
        } catch (\Exception $e) {
            \Log::error('Delivery man notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Update Order Priority
     */
    public function updatePriority(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'priority' => 'required|in:low,normal,high,urgent'
        ]);

        $order = Order::findOrFail($request->order_id);
        $order->priority = $request->priority;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Priority updated successfully!'
        ]);
    }
}
