#!/usr/bin/env php
<?php

/**
 * Debug Charts Not Populating Issue
 * Checks common causes and provides fixes
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\DeliveryStatsService;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║          DELIVERY STATS CHARTS - DEBUG TOOL                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Test 1: Check if service class exists
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo " 1. Checking Service Class\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (class_exists('App\Services\DeliveryStatsService')) {
    echo "✅ DeliveryStatsService class exists\n";
} else {
    echo "❌ DeliveryStatsService class NOT FOUND\n";
    echo "   Fix: Run 'composer dump-autoload'\n";
    exit(1);
}

// Test 2: Check if JavaScript files exist
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo " 2. Checking JavaScript Files\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$jsFiles = [
    'public/assets/admin/js/apex-charts/apexcharts.js',
    'public/assets/admin/js/delivery-stats-charts.js',
    'public/assets/admin/js/delivery-stats-filters.js',
];

foreach ($jsFiles as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        echo "✅ $file exists (" . number_format($size) . " bytes)\n";
    } else {
        echo "❌ $file NOT FOUND\n";
    }
}

// Test 3: Check if there's actual data
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo " 3. Checking Database Data\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $todayOrders = DB::table('orders')->whereDate('created_at', today())->count();
    $totalOrders = DB::table('orders')->count();
    $deliveredToday = DB::table('orders')
        ->whereDate('created_at', today())
        ->where('order_status', 'delivered')
        ->count();

    echo "Total orders in database: " . number_format($totalOrders) . "\n";
    echo "Orders created today: $todayOrders\n";
    echo "Delivered today: $deliveredToday\n";

    if ($todayOrders == 0) {
        echo "\n⚠️  WARNING: No orders created today!\n";
        echo "   This means hourly and status charts will be empty.\n";
        echo "   Try selecting 'Yesterday' or 'Last 7 Days' in the date filter.\n";
    }
} catch (\Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 4: Test service methods with real data
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo " 4. Testing Service Methods with Real Data\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$service = new DeliveryStatsService();
$filters = ['zone_id' => 'all', 'module_id' => 2, 'date' => today()];

try {
    // Test hourly distribution
    $hourlyData = $service->getHourlyDistribution($filters);
    $hasHourlyData = array_sum(array_column($hourlyData, 'count')) > 0;
    echo "Hourly Distribution: " . ($hasHourlyData ? "✅ Has data" : "⚠️  No data (0 orders today)") . "\n";

    // Test status breakdown
    $statusData = $service->getOrderStatusBreakdown($filters);
    echo "Status Breakdown: " . ($statusData['total'] > 0 ? "✅ Has data ({$statusData['total']} orders)" : "⚠️  No data") . "\n";

    // Test delivery time distribution
    $timeData = $service->getDeliveryTimeDistribution($filters);
    echo "Delivery Time Distribution: " . ($timeData['total'] > 0 ? "✅ Has data ({$timeData['total']} deliveries)" : "⚠️  No data") . "\n";

    // Test 90-day trend
    $trendData = $service->get90DayTrend($filters);
    $hasTrendData = count($trendData) > 0;
    echo "90-Day Trend: " . ($hasTrendData ? "✅ Has data (" . count($trendData) . " days)" : "⚠️  No historical data") . "\n";

} catch (\Exception $e) {
    echo "❌ Service method error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

// Test 5: Check routes
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo " 5. Checking Routes\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$routes = [
    'delivery-stats.chart.hourly',
    'delivery-stats.chart.delivery-time',
    'delivery-stats.chart.status',
    'delivery-stats.chart.trend',
];

foreach ($routes as $routeName) {
    if (\Illuminate\Support\Facades\Route::has($routeName)) {
        echo "✅ Route '$routeName' exists\n";
    } else {
        echo "❌ Route '$routeName' NOT FOUND\n";
        echo "   Fix: Run 'php artisan route:clear'\n";
    }
}

// Summary
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                         SUMMARY                             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

if ($todayOrders == 0) {
    echo "🔍 ROOT CAUSE: No orders created today\n";
    echo "\n";
    echo "📋 SOLUTION:\n";
    echo "   1. In the dashboard, change the date filter to 'Yesterday' or 'Last 7 Days'\n";
    echo "   2. Charts will then show historical data\n";
    echo "   3. Alternatively, create some test orders for today\n";
    echo "\n";
} else {
    echo "✅ All systems operational\n";
    echo "\n";
    echo "📋 NEXT STEPS:\n";
    echo "   1. Clear browser cache (Ctrl+F5)\n";
    echo "   2. Check browser console (F12) for JavaScript errors\n";
    echo "   3. Verify chart containers exist in HTML\n";
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "For browser debugging, open Developer Tools (F12) and check:\n";
echo "  console.log(typeof ApexCharts);           // Should be 'function'\n";
echo "  console.log(typeof DeliveryStatsCharts);  // Should be 'object'\n";
echo "  console.log(typeof DeliveryStatsFilters); // Should be 'object'\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";
