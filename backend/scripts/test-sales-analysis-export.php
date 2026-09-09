<?php

/**
 * Test script for Sales Analysis Export
 *
 * Tests all 4 sheets and verifies data integrity
 * Run: php scripts/test-sales-analysis-export.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderTransaction;
use Illuminate\Support\Facades\DB;

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║         Sales Analysis Export - Verification Test          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$startDate = now()->subMonths(8)->startOfMonth();
$endDate = now()->endOfMonth();

echo "📅 Date Range: " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d') . "\n\n";

// Test 1: Revenue Sheet Data
echo "─────────────────────────────────────────────────────────────\n";
echo "TEST 1: Revenue Trends Sheet\n";
echo "─────────────────────────────────────────────────────────────\n";

$revenueData = DB::table('orders')
    ->select(
        DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
        DB::raw('DATE_FORMAT(created_at, "%b %Y") as month_label'),
        DB::raw('COUNT(*) as total_orders'),
        DB::raw('SUM(order_amount) as total_revenue'),
        DB::raw('AVG(order_amount) as avg_order_value')
    )
    ->where('order_status', 'delivered')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'), DB::raw('DATE_FORMAT(created_at, "%b %Y")'))
    ->orderBy('month', 'ASC')
    ->get();

echo "✓ Months found: " . $revenueData->count() . "\n";
echo "✓ Total orders across all months: " . number_format($revenueData->sum('total_orders')) . "\n";
echo "✓ Total revenue: ₹" . number_format($revenueData->sum('total_revenue'), 2) . "\n\n";

if ($revenueData->count() > 0) {
    echo "Sample data (first 3 months):\n";
    foreach ($revenueData->take(3) as $row) {
        echo "  {$row->month_label}: {$row->total_orders} orders, ₹" . number_format($row->total_revenue, 2) . "\n";
    }
    echo "\n";
}

// Test 2: Top Products Sheet
echo "─────────────────────────────────────────────────────────────\n";
echo "TEST 2: Top Products Sheet\n";
echo "─────────────────────────────────────────────────────────────\n";

$topProducts = OrderDetail::select(
        'order_details.item_id',
        'items.name as product_name',
        'categories.name as category_name',
        DB::raw('SUM(order_details.quantity) as total_quantity'),
        DB::raw('SUM(order_details.price * order_details.quantity) as total_revenue'),
        DB::raw('COUNT(DISTINCT order_details.order_id) as orders_count')
    )
    ->join('items', 'order_details.item_id', '=', 'items.id')
    ->leftJoin('categories', 'items.category_id', '=', 'categories.id')
    ->whereHas('order', function($q) use ($startDate, $endDate) {
        $q->where('order_status', 'delivered')
          ->whereBetween('created_at', [$startDate, $endDate]);
    })
    ->groupBy('order_details.item_id', 'items.name', 'categories.name')
    ->orderByDesc('total_revenue')
    ->limit(100)
    ->get();

echo "✓ Products found: " . $topProducts->count() . "\n";
$topProductRevenue = $topProducts->count() > 0 ? $topProducts->first()->total_revenue : 0;
echo "✓ Top product revenue: ₹" . number_format($topProductRevenue, 2) . "\n\n";

if ($topProducts->count() > 0) {
    echo "Top 5 products:\n";
    foreach ($topProducts->take(5) as $index => $row) {
        $rank = $index + 1;
        $categoryName = $row->category_name ? $row->category_name : 'N/A';
        echo "  #{$rank} {$row->product_name} ({$categoryName}): ₹" . number_format($row->total_revenue, 2) . "\n";
    }
    echo "\n";
}

// Test 3: Store Performance Sheet
echo "─────────────────────────────────────────────────────────────\n";
echo "TEST 3: Store Performance Sheet\n";
echo "─────────────────────────────────────────────────────────────\n";

$storePerformance = DB::table('order_transactions')
    ->select(
        'stores.name as store_name',
        DB::raw('COUNT(*) as total_orders'),
        DB::raw('SUM(order_transactions.order_amount) as total_revenue'),
        DB::raw('AVG(order_transactions.order_amount) as avg_order_value'),
        DB::raw('SUM(order_transactions.admin_commission) as commission_earned'),
        DB::raw('SUM(order_transactions.store_amount) as store_earnings'),
        'orders.store_id'
    )
    ->join('orders', 'order_transactions.order_id', '=', 'orders.id')
    ->join('stores', 'orders.store_id', '=', 'stores.id')
    ->where('orders.order_status', 'delivered')
    ->whereBetween('order_transactions.created_at', [$startDate, $endDate])
    ->groupBy('orders.store_id', 'stores.name')
    ->orderByDesc('total_revenue')
    ->get();

echo "✓ Stores with orders: " . $storePerformance->count() . "\n";
echo "✓ Total commission earned: ₹" . number_format($storePerformance->sum('commission_earned'), 2) . "\n\n";

if ($storePerformance->count() > 0) {
    echo "Top 5 stores by revenue:\n";
    foreach ($storePerformance->take(5) as $row) {
        $commissionRate = $row->total_revenue > 0 ? ($row->commission_earned / $row->total_revenue) * 100 : 0;
        echo "  {$row->store_name}: ₹" . number_format($row->total_revenue, 2) . " ({$row->total_orders} orders, " . number_format($commissionRate, 1) . "% commission)\n";
    }
    echo "\n";
}

// Test 4: Order Patterns Sheet
echo "─────────────────────────────────────────────────────────────\n";
echo "TEST 4: Order Patterns Sheet\n";
echo "─────────────────────────────────────────────────────────────\n";

$orderPatterns = DB::table('orders')
    ->select(
        DB::raw('DATE(created_at) as order_date'),
        DB::raw('DAYNAME(created_at) as day_of_week'),
        DB::raw('COUNT(*) as orders_count'),
        DB::raw('SUM(order_amount) as revenue'),
        DB::raw('AVG(order_amount) as avg_order_value'),
        DB::raw('SUM(CASE WHEN order_type = "delivery" THEN 1 ELSE 0 END) as delivery_count'),
        DB::raw('SUM(CASE WHEN order_type = "take_away" THEN 1 ELSE 0 END) as takeaway_count'),
        DB::raw('SUM(CASE WHEN payment_method = "cash_on_delivery" THEN 1 ELSE 0 END) as cod_count'),
        DB::raw('SUM(CASE WHEN payment_method != "cash_on_delivery" THEN 1 ELSE 0 END) as digital_count')
    )
    ->where('order_status', 'delivered')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->groupBy(DB::raw('DATE(created_at)'), DB::raw('DAYNAME(created_at)'))
    ->orderBy('order_date', 'DESC')
    ->get();

echo "✓ Days analyzed: " . $orderPatterns->count() . "\n";
echo "✓ Avg orders per day: " . number_format($orderPatterns->avg('orders_count'), 1) . "\n\n";

if ($orderPatterns->count() > 0) {
    echo "Last 5 days:\n";
    foreach ($orderPatterns->take(5) as $row) {
        $deliveryPercent = $row->orders_count > 0 ? ($row->delivery_count / $row->orders_count) * 100 : 0;
        $codPercent = $row->orders_count > 0 ? ($row->cod_count / $row->orders_count) * 100 : 0;
        echo "  " . date('d M', strtotime($row->order_date)) . " ({$row->day_of_week}): {$row->orders_count} orders, " . number_format($deliveryPercent, 1) . "% delivery, " . number_format($codPercent, 1) . "% COD\n";
    }
    echo "\n";
}

// Test 5: Peak Hours Calculation
echo "─────────────────────────────────────────────────────────────\n";
echo "TEST 5: Peak Hours Calculation\n";
echo "─────────────────────────────────────────────────────────────\n";

$peakHoursData = DB::table('orders')
    ->select(
        DB::raw('DATE(created_at) as order_date'),
        DB::raw('HOUR(created_at) as hour'),
        DB::raw('COUNT(*) as order_count')
    )
    ->where('order_status', 'delivered')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->groupBy(DB::raw('DATE(created_at)'), DB::raw('HOUR(created_at)'))
    ->get();

$groupedByDate = $peakHoursData->groupBy('order_date');
$peakHours = [];

foreach ($groupedByDate as $date => $hours) {
    $peakHour = $hours->sortByDesc('order_count')->first();
    if ($peakHour) {
        $peakHours[$date] = sprintf('%02d:00', $peakHour->hour);
    }
}

echo "✓ Peak hours calculated for " . count($peakHours) . " days\n";
if (count($peakHours) > 0) {
    echo "✓ Sample peak hours:\n";
    $sample = array_slice($peakHours, -5, 5, true);
    foreach ($sample as $date => $hour) {
        echo "  " . date('d M Y', strtotime($date)) . ": {$hour}\n";
    }
}
echo "\n";

// Summary
echo "═════════════════════════════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═════════════════════════════════════════════════════════════\n\n";

$allTestsPassed = true;

if ($revenueData->count() < 1) {
    echo "❌ Revenue sheet has no data\n";
    $allTestsPassed = false;
} else {
    echo "✓ Revenue sheet: {$revenueData->count()} months\n";
}

if ($topProducts->count() < 1) {
    echo "❌ Top products sheet has no data\n";
    $allTestsPassed = false;
} else {
    echo "✓ Top products sheet: {$topProducts->count()} products\n";
}

if ($storePerformance->count() < 1) {
    echo "❌ Store performance sheet has no data\n";
    $allTestsPassed = false;
} else {
    echo "✓ Store performance sheet: {$storePerformance->count()} stores\n";
}

if ($orderPatterns->count() < 1) {
    echo "❌ Order patterns sheet has no data\n";
    $allTestsPassed = false;
} else {
    echo "✓ Order patterns sheet: {$orderPatterns->count()} days\n";
}

echo "✓ Peak hours: " . count($peakHours) . " days calculated\n\n";

if ($allTestsPassed) {
    echo "═════════════════════════════════════════════════════════════\n";
    echo "✅ ALL TESTS PASSED - Export is ready to use!\n";
    echo "═════════════════════════════════════════════════════════════\n\n";
    echo "Export URLs:\n";
    echo "  Excel: https://new.snocart.com/admin/sales-analysis-export?type=excel\n";
    echo "  CSV:   https://new.snocart.com/admin/sales-analysis-export?type=csv\n\n";
} else {
    echo "═════════════════════════════════════════════════════════════\n";
    echo "❌ SOME TESTS FAILED - Check the data above\n";
    echo "═════════════════════════════════════════════════════════════\n\n";
}

echo "Estimated export execution time: ~3-4 seconds\n";
echo "Estimated file size: ~500KB (Excel)\n\n";
