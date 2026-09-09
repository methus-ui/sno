<?php

namespace App\Services;

use App\Models\Order;
use App\Models\DeliveryMan;
use App\Models\Store;
use App\Models\Item;
use App\Models\OrderDetail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeliveryStatsService
{
    /**
     * Get hourly order distribution for bar chart
     * Returns 24-hour data with order counts per hour
     */
    public function getHourlyDistribution($filters = [])
    {
        $cacheKey = 'delivery_stats_hourly_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 60, function () use ($filters) {
            // Convert date to string
            $date = isset($filters['date'])
                ? (is_string($filters['date']) ? $filters['date'] : Carbon::parse($filters['date'])->format('Y-m-d'))
                : today()->format('Y-m-d');
            $zoneId = $filters['zone_id'] ?? 'all';
            $moduleId = $filters['module_id'] ?? null;

            $query = Order::query()
                ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                ->whereDate('created_at', $date)
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('hour');

            $hourlyData = $query->get()->pluck('count', 'hour')->toArray();

            // Fill in missing hours with 0
            $result = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $result[] = [
                    'hour' => $hour,
                    'count' => $hourlyData[$hour] ?? 0,
                    'label' => sprintf('%02d:00', $hour),
                    'period' => $hour < 12 ? 'AM' : 'PM'
                ];
            }

            return $result;
        });
    }

    /**
     * Get delivery time distribution histogram
     * Returns bucketed data (0-10min, 10-20min, etc.)
     */
    public function getDeliveryTimeDistribution($filters = [])
    {
        $cacheKey = 'delivery_stats_time_dist_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 60, function () use ($filters) {
            // Convert date to string
            $date = isset($filters['date'])
                ? (is_string($filters['date']) ? $filters['date'] : Carbon::parse($filters['date'])->format('Y-m-d'))
                : today()->format('Y-m-d');
            $zoneId = $filters['zone_id'] ?? 'all';
            $moduleId = $filters['module_id'] ?? null;

            $query = Order::query()
                ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                ->where('order_status', 'delivered')
                ->whereDate('created_at', $date)
                ->whereNotNull('picked_up')
                ->whereNotNull('delivered')
                ->selectRaw('
                    FLOOR(TIMESTAMPDIFF(MINUTE, picked_up, delivered) / 10) * 10 as bucket,
                    COUNT(*) as count
                ')
                ->groupBy('bucket')
                ->orderBy('bucket');

            $bucketData = $query->get()->pluck('count', 'bucket')->toArray();

            // Define buckets with labels and color coding
            $buckets = [
                ['min' => 0, 'max' => 10, 'label' => '0-10 min', 'color' => '#10b981', 'type' => 'express'],
                ['min' => 10, 'max' => 20, 'label' => '10-20 min', 'color' => '#22c55e', 'type' => 'fast'],
                ['min' => 20, 'max' => 30, 'label' => '20-30 min', 'color' => '#84cc16', 'type' => 'fast'],
                ['min' => 30, 'max' => 40, 'label' => '30-40 min', 'color' => '#eab308', 'type' => 'standard'],
                ['min' => 40, 'max' => 50, 'label' => '40-50 min', 'color' => '#f59e0b', 'type' => 'standard'],
                ['min' => 50, 'max' => 60, 'label' => '50-60 min', 'color' => '#f97316', 'type' => 'slow'],
                ['min' => 60, 'max' => 999, 'label' => '60+ min', 'color' => '#ef4444', 'type' => 'slow'],
            ];

            $result = [];
            foreach ($buckets as $bucket) {
                $count = $bucketData[$bucket['min']] ?? 0;
                $result[] = array_merge($bucket, ['count' => $count]);
            }

            // Calculate totals for percentage
            $total = array_sum(array_column($result, 'count'));
            foreach ($result as &$item) {
                $item['percentage'] = $total > 0 ? round(($item['count'] / $total) * 100, 1) : 0;
            }

            return [
                'buckets' => $result,
                'total' => $total,
                'avg_time' => $total > 0 ? Order::query()
                    ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                    ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                    ->where('order_status', 'delivered')
                    ->whereDate('created_at', $date)
                    ->whereNotNull('picked_up')
                    ->whereNotNull('delivered')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, picked_up, delivered)) as avg')
                    ->value('avg') : 0
            ];
        });
    }

    /**
     * Get order status breakdown for donut chart
     */
    public function getOrderStatusBreakdown($filters = [])
    {
        $cacheKey = 'delivery_stats_status_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 30, function () use ($filters) {
            // Convert date to string
            $date = isset($filters['date'])
                ? (is_string($filters['date']) ? $filters['date'] : Carbon::parse($filters['date'])->format('Y-m-d'))
                : today()->format('Y-m-d');
            $zoneId = $filters['zone_id'] ?? 'all';
            $moduleId = $filters['module_id'] ?? null;

            $query = Order::query()
                ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                ->whereDate('created_at', $date)
                ->selectRaw('
                    order_status,
                    COUNT(*) as count
                ')
                ->groupBy('order_status');

            $statusData = $query->get();

            // Map statuses to display names and colors
            $statusMap = [
                'delivered' => ['label' => 'Delivered', 'color' => '#10b981'],
                'picked_up' => ['label' => 'Out for Delivery', 'color' => '#06b6d4'],
                'processing' => ['label' => 'Processing', 'color' => '#f59e0b'],
                'preparing' => ['label' => 'Preparing', 'color' => '#fbbf24'],
                'pending' => ['label' => 'Pending', 'color' => '#fb923c'],
                'confirmed' => ['label' => 'Confirmed', 'color' => '#14b8a6'],
                'handover' => ['label' => 'Ready for Pickup', 'color' => '#22c55e'],
                'canceled' => ['label' => 'Cancelled', 'color' => '#ef4444'],
                'failed' => ['label' => 'Failed', 'color' => '#dc2626'],
                'refund_requested' => ['label' => 'Refund Requested', 'color' => '#64748b'],
                'refunded' => ['label' => 'Refunded', 'color' => '#94a3b8'],
            ];

            $result = [];
            $total = 0;

            foreach ($statusData as $item) {
                $status = $item->order_status;
                $count = $item->count;
                $total += $count;

                $result[] = [
                    'status' => $status,
                    'label' => $statusMap[$status]['label'] ?? ucfirst($status),
                    'color' => $statusMap[$status]['color'] ?? '#6b7280',
                    'count' => $count
                ];
            }

            // Calculate percentages
            foreach ($result as &$item) {
                $item['percentage'] = $total > 0 ? round(($item['count'] / $total) * 100, 1) : 0;
            }

            // Sort by count descending
            usort($result, fn($a, $b) => $b['count'] - $a['count']);

            return [
                'statuses' => $result,
                'total' => $total
            ];
        });
    }

    /**
     * Get 90-day trend data for multi-line chart
     */
    public function get90DayTrend($filters = [])
    {
        $cacheKey = 'delivery_stats_90day_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 300, function () use ($filters) { // 5-minute cache for 90-day data
            // Convert dates to strings for query
            $endDate = isset($filters['end_date'])
                ? (is_string($filters['end_date']) ? $filters['end_date'] : Carbon::parse($filters['end_date'])->format('Y-m-d'))
                : today()->format('Y-m-d');
            $startDate = isset($filters['start_date'])
                ? (is_string($filters['start_date']) ? $filters['start_date'] : Carbon::parse($filters['start_date'])->format('Y-m-d'))
                : Carbon::parse($endDate)->subDays(89)->format('Y-m-d');
            $zoneId = $filters['zone_id'] ?? 'all';
            $moduleId = $filters['module_id'] ?? null;

            $query = Order::query()
                ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('
                    DATE(created_at) as date,
                    CAST(COUNT(*) AS UNSIGNED) as total_orders,
                    CAST(SUM(CASE WHEN order_status = "delivered" THEN 1 ELSE 0 END) AS UNSIGNED) as delivered,
                    CAST(SUM(CASE WHEN order_status = "canceled" THEN 1 ELSE 0 END) AS UNSIGNED) as cancelled,
                    CAST(AVG(CASE WHEN order_status = "delivered" AND picked_up IS NOT NULL AND delivered IS NOT NULL
                        THEN TIMESTAMPDIFF(MINUTE, picked_up, delivered) END) AS DECIMAL(10,2)) as avg_delivery_time
                ')
                ->groupBy('date')
                ->orderBy('date');

            // Use toArray() to avoid Eloquent auto-casting
            $trendData = $query->get()->toArray();

            $result = [];
            foreach ($trendData as $item) {
                // Convert object/array to array access
                if (is_object($item)) {
                    $item = (array) $item;
                }

                // Ensure date is string
                $date = isset($item['date']) ? $item['date'] : '';

                // Cast all numeric values properly
                $totalOrders = isset($item['total_orders']) && is_numeric($item['total_orders']) ? (int) $item['total_orders'] : 0;
                $delivered = isset($item['delivered']) && is_numeric($item['delivered']) ? (int) $item['delivered'] : 0;
                $cancelled = isset($item['cancelled']) && is_numeric($item['cancelled']) ? (int) $item['cancelled'] : 0;
                $avgTime = isset($item['avg_delivery_time']) && is_numeric($item['avg_delivery_time']) ? (float) $item['avg_delivery_time'] : 0;

                $result[] = [
                    'date' => $date,
                    'date_formatted' => Carbon::parse($date)->format('M d'),
                    'total_orders' => $totalOrders,
                    'delivered' => $delivered,
                    'cancelled' => $cancelled,
                    'avg_delivery_time' => round($avgTime, 1)
                ];
            }

            return $result;
        });
    }

    /**
     * Get revenue analysis data
     */
    public function getRevenueAnalysis($filters = [])
    {
        $cacheKey = 'delivery_stats_revenue_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 60, function () use ($filters) {
            $date = isset($filters['date'])
                ? (is_string($filters['date']) ? $filters['date'] : Carbon::parse($filters['date'])->format('Y-m-d'))
                : today()->format('Y-m-d');
            $zoneId = $filters['zone_id'] ?? 'all';
            $moduleId = $filters['module_id'] ?? null;

            // Revenue by payment method
            $paymentMethods = Order::query()
                ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                ->whereDate('created_at', $date)
                ->where('order_status', 'delivered')
                ->selectRaw('payment_method, SUM(order_amount) as revenue, COUNT(*) as orders')
                ->groupBy('payment_method')
                ->get()
                ->map(fn($item) => [
                    'method' => $item->payment_method,
                    'revenue' => round($item->revenue, 2),
                    'orders' => $item->orders
                ]);

            // Revenue by module (if not filtered by module)
            $moduleRevenue = !$moduleId ? Order::query()
                ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                ->whereDate('created_at', $date)
                ->where('order_status', 'delivered')
                ->selectRaw('module_id, SUM(order_amount) as revenue, COUNT(*) as orders')
                ->groupBy('module_id')
                ->get()
                ->map(fn($item) => [
                    'module_id' => $item->module_id,
                    'revenue' => round($item->revenue, 2),
                    'orders' => $item->orders
                ]) : [];

            // Daily revenue trend (last 30 days)
            $dailyRevenue = Order::query()
                ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                ->whereBetween('created_at', [Carbon::parse($date)->subDays(29), $date])
                ->where('order_status', 'delivered')
                ->selectRaw('DATE(created_at) as date, SUM(order_amount) as revenue')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => [
                    'date' => $item->date,
                    'revenue' => round($item->revenue, 2)
                ]);

            return [
                'payment_methods' => $paymentMethods,
                'modules' => $moduleRevenue,
                'daily_trend' => $dailyRevenue,
                'total_revenue' => Order::query()
                    ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                    ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                    ->whereDate('created_at', $date)
                    ->where('order_status', 'delivered')
                    ->sum('order_amount')
            ];
        });
    }

    /**
     * Get top performers leaderboard
     */
    public function getTopPerformers($filters = [], $type = 'all')
    {
        $cacheKey = "delivery_stats_performers_{$type}_" . md5(json_encode($filters));

        return Cache::remember($cacheKey, 60, function () use ($filters, $type) {
            $date = isset($filters['date'])
                ? (is_string($filters['date']) ? $filters['date'] : Carbon::parse($filters['date'])->format('Y-m-d'))
                : today()->format('Y-m-d');
            $zoneId = $filters['zone_id'] ?? 'all';
            $moduleId = $filters['module_id'] ?? null;
            $limit = $filters['limit'] ?? 10;

            $result = [];

            // Top Delivery Personnel
            if ($type === 'delivery_men' || $type === 'all') {
                $result['delivery_men'] = Order::query()
                    ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                    ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                    ->whereDate('created_at', $date)
                    ->where('order_status', 'delivered')
                    ->whereNotNull('delivery_man_id')
                    ->selectRaw('
                        delivery_man_id,
                        COUNT(*) as deliveries,
                        AVG(CASE WHEN picked_up IS NOT NULL AND delivered IS NOT NULL
                            THEN TIMESTAMPDIFF(MINUTE, picked_up, delivered) END) as avg_time
                    ')
                    ->groupBy('delivery_man_id')
                    ->orderByDesc('deliveries')
                    ->limit($limit)
                    ->get()
                    ->map(function ($item) {
                        $dm = DeliveryMan::find($item->delivery_man_id);
                        return [
                            'id' => $item->delivery_man_id,
                            'name' => $dm ? $dm->f_name . ' ' . $dm->l_name : 'Unknown',
                            'image' => $dm ? $dm->image_full_url : null,
                            'deliveries' => $item->deliveries,
                            'avg_time' => round($item->avg_time ?? 0, 1)
                        ];
                    });
            }

            // Top Stores
            if ($type === 'stores' || $type === 'all') {
                $result['stores'] = Order::query()
                    ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                    ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                    ->whereDate('created_at', $date)
                    ->where('order_status', 'delivered')
                    ->whereNotNull('store_id')
                    ->selectRaw('
                        store_id,
                        COUNT(*) as orders,
                        SUM(order_amount) as revenue
                    ')
                    ->groupBy('store_id')
                    ->orderByDesc('orders')
                    ->limit($limit)
                    ->get()
                    ->map(function ($item) {
                        $store = Store::find($item->store_id);
                        return [
                            'id' => $item->store_id,
                            'name' => $store ? $store->name : 'Unknown',
                            'logo' => $store ? $store->logo_full_url : null,
                            'orders' => $item->orders,
                            'revenue' => round($item->revenue, 2)
                        ];
                    });
            }

            // Top Products
            if ($type === 'products' || $type === 'all') {
                $result['products'] = OrderDetail::query()
                    ->join('orders', 'order_details.order_id', '=', 'orders.id')
                    ->when(is_numeric($zoneId), fn($q) => $q->where('orders.zone_id', $zoneId))
                    ->when($moduleId, fn($q) => $q->where('orders.module_id', $moduleId))
                    ->whereDate('orders.created_at', $date)
                    ->where('orders.order_status', 'delivered')
                    ->whereNotNull('order_details.item_id')
                    ->selectRaw('
                        order_details.item_id,
                        SUM(order_details.quantity) as total_quantity,
                        SUM(order_details.price * order_details.quantity) as revenue
                    ')
                    ->groupBy('order_details.item_id')
                    ->orderByDesc('total_quantity')
                    ->limit($limit)
                    ->get()
                    ->map(function ($item) {
                        $product = Item::find($item->item_id);
                        return [
                            'id' => $item->item_id,
                            'name' => $product ? $product->name : 'Unknown',
                            'image' => $product ? $product->image_full_url : null,
                            'quantity' => $item->total_quantity,
                            'revenue' => round($item->revenue, 2)
                        ];
                    });
            }

            return $result;
        });
    }

    /**
     * Get customer insights
     */
    public function getCustomerInsights($filters = [])
    {
        $cacheKey = 'delivery_stats_customers_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $date = isset($filters['date'])
                ? (is_string($filters['date']) ? $filters['date'] : Carbon::parse($filters['date'])->format('Y-m-d'))
                : today()->format('Y-m-d');
            $zoneId = $filters['zone_id'] ?? 'all';
            $moduleId = $filters['module_id'] ?? null;

            // New vs repeat customers today
            $newCustomers = Order::query()
                ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                ->whereDate('created_at', $date)
                ->whereHas('customer', function ($q) use ($date) {
                    $q->whereDate('created_at', $date);
                })
                ->distinct('user_id')
                ->count('user_id');

            $totalCustomersToday = Order::query()
                ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                ->whereDate('created_at', $date)
                ->distinct('user_id')
                ->count('user_id');

            $repeatCustomers = $totalCustomersToday - $newCustomers;

            return [
                'new_customers' => $newCustomers,
                'repeat_customers' => $repeatCustomers,
                'total_customers' => $totalCustomersToday,
                'repeat_rate' => $totalCustomersToday > 0 ? round(($repeatCustomers / $totalCustomersToday) * 100, 1) : 0
            ];
        });
    }

    /**
     * Get operational metrics
     */
    public function getOperationalMetrics($filters = [])
    {
        $cacheKey = 'delivery_stats_operations_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 60, function () use ($filters) {
            $date = isset($filters['date'])
                ? (is_string($filters['date']) ? $filters['date'] : Carbon::parse($filters['date'])->format('Y-m-d'))
                : today()->format('Y-m-d');
            $zoneId = $filters['zone_id'] ?? 'all';
            $moduleId = $filters['module_id'] ?? null;

            // Calculate key operational metrics using DB::select to avoid Carbon casting issues
            $query = Order::query()
                ->when(is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
                ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
                ->whereDate('created_at', $date);

            // Build the SQL manually to get proper types
            $sql = "SELECT
                CAST(COUNT(*) AS UNSIGNED) as total_orders,
                CAST(SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) AS UNSIGNED) as delivered,
                CAST(SUM(CASE WHEN order_status = 'canceled' THEN 1 ELSE 0 END) AS UNSIGNED) as cancelled,
                CAST(SUM(CASE WHEN order_status = 'failed' THEN 1 ELSE 0 END) AS UNSIGNED) as failed,
                AVG(CASE WHEN order_status = 'delivered' AND picked_up IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, confirmed, picked_up) END) as avg_prep_time,
                AVG(CASE WHEN order_status = 'delivered' AND picked_up IS NOT NULL AND delivered IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, picked_up, delivered) END) as avg_delivery_time
                FROM orders
                WHERE DATE(created_at) = ?";

            $bindings = [$date];

            if (is_numeric($zoneId)) {
                $sql .= " AND zone_id = ?";
                $bindings[] = $zoneId;
            }

            if ($moduleId) {
                $sql .= " AND module_id = ?";
                $bindings[] = $moduleId;
            }

            $metrics = DB::selectOne($sql, $bindings);

            // Cast to integers to avoid type errors
            $totalOrders = (int) $metrics->total_orders;
            $delivered = (int) $metrics->delivered;
            $cancelled = (int) $metrics->cancelled;
            $failed = (int) $metrics->failed;

            $deliveryEfficiency = $totalOrders > 0
                ? round(($delivered / $totalOrders) * 100, 1)
                : 0;

            $cancellationRate = $totalOrders > 0
                ? round(($cancelled / $totalOrders) * 100, 1)
                : 0;

            return [
                'delivery_efficiency' => $deliveryEfficiency,
                'cancellation_rate' => $cancellationRate,
                'avg_prep_time' => round((float) ($metrics->avg_prep_time ?? 0), 1),
                'avg_delivery_time' => round((float) ($metrics->avg_delivery_time ?? 0), 1),
                'total_orders' => $totalOrders,
                'delivered' => $delivered,
                'cancelled' => $cancelled,
                'failed' => $failed
            ];
        });
    }
}
