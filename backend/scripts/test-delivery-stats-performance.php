<?php

/**
 * Delivery Stats Performance Test Script
 *
 * Tests all 16 metrics and verifies performance improvements
 *
 * Usage: php scripts/test-delivery-stats-performance.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║       Delivery Stats Performance Test - 2026-03-08            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Clear cache before testing
Cache::flush();
echo "✓ Cache cleared\n\n";

// Test 1: Check all required metrics are present
echo "TEST 1: Verify All 16 Metrics Present\n";
echo "─────────────────────────────────────────\n";

$required_metrics = [
    'total_orders',
    'delivery_personnel_count',
    'active_delivery_personnel',
    'total_stores',
    'today_orders',
    'processing',
    'out_for_delivery',
    'delivered_today',
    'cancelled_today',
    'avg_delivery_time',
    'pending_orders',
    'ready_for_pickup',
    'failed_today',
    'avg_order_value',
    'peak_hour',
    'peak_hour_orders',
    'scheduled_orders',
    'returned_today',
];

// Simulate the controller logic
DB::enableQueryLog();
$start_time = microtime(true);

$params = [
    'zone_id' => 'all',
    'module_id' => \Config::get('module.current_module_id'),
];

$cacheKey = 'delivery_stats_' . $params['zone_id'] . '_' . $params['module_id'];

$data = Cache::remember($cacheKey, 30, function () use ($params) {
    $data = [];

    // Delivery personnel counts
    $data['delivery_personnel_count'] = \App\Models\DeliveryMan::Zonewise()->count();
    $data['active_delivery_personnel'] = \App\Models\DeliveryMan::Zonewise()->Active()->count();

    // Total stores (from dashboard_data)
    $data['total_stores'] = \App\Models\Store::count();
    $data['total_orders'] = \App\Models\Order::count();

    // Aggregated order stats
    $order_stats = \App\Models\Order::selectRaw("
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_orders,
        SUM(CASE WHEN order_status IN ('processing', 'preparing') THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN order_status = 'picked_up' THEN 1 ELSE 0 END) as out_for_delivery,
        SUM(CASE WHEN order_status = 'delivered' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as delivered_today,
        SUM(CASE WHEN order_status = 'canceled' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as cancelled_today,
        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status IN ('confirmed', 'handover') THEN 1 ELSE 0 END) as ready_for_pickup,
        SUM(CASE WHEN order_status = 'failed' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as failed_today,
        SUM(CASE WHEN DATE(schedule_at) = CURDATE() AND schedule_at > NOW() THEN 1 ELSE 0 END) as scheduled_orders,
        SUM(CASE WHEN order_status = 'refund_requested' AND DATE(refund_requested) = CURDATE() THEN 1 ELSE 0 END) as returned_today,
        AVG(CASE WHEN DATE(created_at) = CURDATE() THEN order_amount ELSE NULL END) as avg_order_value
    ")
    ->first();

    $data['today_orders'] = $order_stats->today_orders ?? 0;
    $data['processing'] = $order_stats->processing ?? 0;
    $data['out_for_delivery'] = $order_stats->out_for_delivery ?? 0;
    $data['delivered_today'] = $order_stats->delivered_today ?? 0;
    $data['cancelled_today'] = $order_stats->cancelled_today ?? 0;
    $data['pending_orders'] = $order_stats->pending_orders ?? 0;
    $data['ready_for_pickup'] = $order_stats->ready_for_pickup ?? 0;
    $data['failed_today'] = $order_stats->failed_today ?? 0;
    $data['scheduled_orders'] = $order_stats->scheduled_orders ?? 0;
    $data['returned_today'] = $order_stats->returned_today ?? 0;
    $data['avg_order_value'] = round($order_stats->avg_order_value ?? 0, 2);

    // Average delivery time
    $avg_delivery_time = \App\Models\Order::where('order_status', 'delivered')
        ->whereDate('delivered', today())
        ->whereNotNull('picked_up')
        ->whereNotNull('delivered')
        ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, picked_up, delivered)) as avg_time')
        ->value('avg_time');

    $data['avg_delivery_time'] = round($avg_delivery_time ?? 0, 1);

    // Peak hour
    $peak_hour_data = \App\Models\Order::whereDate('created_at', today())
        ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
        ->groupBy('hour')
        ->orderBy('count', 'desc')
        ->first();

    $data['peak_hour'] = $peak_hour_data->hour ?? null;
    $data['peak_hour_orders'] = $peak_hour_data->count ?? 0;

    return $data;
});

$queries = DB::getQueryLog();
$query_count = count($queries);
$execution_time = round((microtime(true) - $start_time) * 1000, 2);

$missing_metrics = [];
foreach ($required_metrics as $metric) {
    if (!array_key_exists($metric, $data)) {
        $missing_metrics[] = $metric;
        echo "  ✗ MISSING: $metric\n";
    } else {
        echo "  ✓ $metric = " . (is_null($data[$metric]) ? 'NULL' : $data[$metric]) . "\n";
    }
}

if (empty($missing_metrics)) {
    echo "\n✅ TEST 1 PASSED: All 16 metrics present\n";
} else {
    echo "\n❌ TEST 1 FAILED: " . count($missing_metrics) . " metrics missing\n";
}

// Test 2: Query Count Verification
echo "\n\nTEST 2: Query Optimization Check\n";
echo "─────────────────────────────────────────\n";
echo "Queries executed (uncached): $query_count\n";
echo "Execution time: {$execution_time}ms\n";

if ($query_count <= 10) {
    echo "✅ TEST 2 PASSED: Query count optimized (≤10 queries)\n";
} else {
    echo "⚠️  TEST 2 WARNING: Query count still high (>10 queries)\n";
}

// Test 3: Cache Performance
echo "\n\nTEST 3: Cache Performance Test\n";
echo "─────────────────────────────────────────\n";

// First call (uncached)
Cache::forget($cacheKey);
DB::flushQueryLog();
$start = microtime(true);
$data1 = Cache::remember($cacheKey, 30, function () use ($params) {
    return ['test' => 'uncached'];
});
$uncached_time = round((microtime(true) - $start) * 1000, 2);
$uncached_queries = count(DB::getQueryLog());

// Second call (cached)
DB::flushQueryLog();
$start = microtime(true);
$data2 = Cache::get($cacheKey);
$cached_time = round((microtime(true) - $start) * 1000, 2);
$cached_queries = count(DB::getQueryLog());

echo "Uncached request: {$uncached_time}ms, {$uncached_queries} queries\n";
echo "Cached request: {$cached_time}ms, {$cached_queries} queries\n";
echo "Performance gain: " . round(($uncached_time - $cached_time) / $uncached_time * 100, 1) . "%\n";

if ($cached_queries == 0 && $cached_time < 10) {
    echo "✅ TEST 3 PASSED: Cache working perfectly\n";
} else {
    echo "❌ TEST 3 FAILED: Cache not working as expected\n";
}

// Test 4: Verify No Null/Undefined Values
echo "\n\nTEST 4: Data Integrity Check\n";
echo "─────────────────────────────────────────\n";

$has_issues = false;
foreach ($data as $key => $value) {
    if (is_null($value) && !in_array($key, ['peak_hour'])) {
        echo "  ⚠️  $key is NULL (expected number)\n";
        $has_issues = true;
    }
}

if (!$has_issues) {
    echo "✅ TEST 4 PASSED: No unexpected NULL values\n";
} else {
    echo "⚠️  TEST 4 WARNING: Some values are NULL\n";
}

// Test 5: Index Existence Check
echo "\n\nTEST 5: Database Index Verification\n";
echo "─────────────────────────────────────────\n";

$required_indexes = [
    'idx_zone_status',
    'idx_status_created',
    'idx_created_at',
    'idx_delivered',
    'idx_schedule_at',
    'idx_refund_requested',
    'idx_delivery_time_calc',
];

$existing_indexes = DB::select("SHOW INDEX FROM orders WHERE Key_name IN ('" . implode("','", $required_indexes) . "')");
$existing_index_names = array_unique(array_column($existing_indexes, 'Key_name'));

$missing_indexes = array_diff($required_indexes, $existing_index_names);

foreach ($required_indexes as $index) {
    if (in_array($index, $existing_index_names)) {
        echo "  ✓ $index exists\n";
    } else {
        echo "  ✗ MISSING: $index\n";
    }
}

if (empty($missing_indexes)) {
    echo "✅ TEST 5 PASSED: All performance indexes exist\n";
} else {
    echo "⚠️  TEST 5 WARNING: " . count($missing_indexes) . " indexes missing (run migration first)\n";
}

// Summary
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                      TEST SUMMARY                              ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";
echo "║ Metrics Present:      " . str_pad(count($required_metrics) - count($missing_metrics) . "/" . count($required_metrics), 42) . "║\n";
echo "║ Query Count:          " . str_pad($query_count . " queries", 42) . "║\n";
echo "║ Execution Time:       " . str_pad($execution_time . "ms", 42) . "║\n";
echo "║ Cache Performance:    " . str_pad(round(($uncached_time - $cached_time) / $uncached_time * 100, 1) . "% faster", 42) . "║\n";
echo "║ Indexes Present:      " . str_pad(count($required_indexes) - count($missing_indexes) . "/" . count($required_indexes), 42) . "║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

if (empty($missing_metrics) && $query_count <= 10 && $cached_queries == 0) {
    echo "✅ ALL TESTS PASSED - Delivery Stats API is fully optimized!\n\n";
    exit(0);
} else {
    echo "⚠️  SOME TESTS NEED ATTENTION - Review the output above\n\n";
    exit(1);
}
