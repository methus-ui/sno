<?php
/**
 * Speed Display Verification Script
 *
 * Tests if speed data is properly stored and retrieved from database
 *
 * Usage: php scripts/verify-speed-display.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\DeliveryHistory;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║       SPEED DISPLAY VERIFICATION - 2026-03-27            ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Test 1: Check orders with dm_last_location
echo "TEST 1: Checking orders with speed data in dm_last_location...\n";
echo str_repeat("-", 60) . "\n";

$ordersWithSpeed = Order::whereNotNull('dm_last_location')
    ->whereNotNull('delivery_man_id')
    ->whereIn('order_status', ['confirmed', 'accepted', 'processing', 'handover', 'picked_up'])
    ->limit(10)
    ->get();

if ($ordersWithSpeed->isEmpty()) {
    echo "❌ No active orders with DM location found\n\n";
} else {
    echo "✅ Found " . $ordersWithSpeed->count() . " active orders with DM tracking\n\n";

    foreach ($ordersWithSpeed as $order) {
        $location = $order->dm_last_location;
        $hasSpeed = isset($location['speed']);
        $speedValue = $location['speed'] ?? 'N/A';

        echo "Order #{$order->id}:\n";
        echo "  - DM ID: {$order->delivery_man_id}\n";
        echo "  - Location: " . ($location['latitude'] ?? 'N/A') . ", " . ($location['longitude'] ?? 'N/A') . "\n";
        echo "  - Speed: " . ($hasSpeed ? $speedValue . " km/h" : "❌ NOT SET") . "\n";
        echo "  - Status: " . ($hasSpeed ? "✅ Speed data available" : "⚠️ Speed data missing") . "\n";
        echo "\n";
    }
}

// Test 2: Check delivery_histories table for recent speed data
echo "\nTEST 2: Checking recent delivery history records for speed...\n";
echo str_repeat("-", 60) . "\n";

$recentHistories = DeliveryHistory::whereNotNull('speed')
    ->where('created_at', '>', now()->subHours(24))
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($recentHistories->isEmpty()) {
    echo "❌ No delivery history with speed data in last 24 hours\n\n";
} else {
    echo "✅ Found " . $recentHistories->count() . " recent location updates with speed\n\n";

    foreach ($recentHistories as $history) {
        echo "DM #{$history->delivery_man_id}:\n";
        echo "  - Speed: {$history->speed} km/h\n";
        echo "  - Location: {$history->latitude}, {$history->longitude}\n";
        echo "  - Timestamp: {$history->created_at->diffForHumans()}\n";
        echo "\n";
    }
}

// Test 3: Check for DMs currently on delivery with speed
echo "\nTEST 3: Current active deliveries with speed tracking...\n";
echo str_repeat("-", 60) . "\n";

$activeDeliveries = Order::whereNotNull('delivery_man_id')
    ->whereIn('order_status', ['picked_up', 'handover'])
    ->with('delivery_man')
    ->get();

if ($activeDeliveries->isEmpty()) {
    echo "❌ No deliveries currently in progress\n\n";
} else {
    echo "✅ Found " . $activeDeliveries->count() . " deliveries in progress\n\n";

    foreach ($activeDeliveries as $order) {
        $location = $order->dm_last_location;
        $dmName = $order->delivery_man ? $order->delivery_man->f_name . ' ' . $order->delivery_man->l_name : 'Unknown';

        echo "Order #{$order->id} ({$order->order_status}):\n";
        echo "  - DM: {$dmName} (#{$order->delivery_man_id})\n";

        if ($location && isset($location['speed'])) {
            echo "  - Current Speed: {$location['speed']} km/h\n";
            echo "  - Status: ✅ Speed tracking active\n";
        } else {
            echo "  - Current Speed: ⚠️ Not available\n";
            echo "  - Status: ❌ Speed data not set\n";
        }
        echo "\n";
    }
}

// Test 4: Check if dm_last_location has speed field schema
echo "\nTEST 4: Verifying dm_last_location field structure...\n";
echo str_repeat("-", 60) . "\n";

$sampleOrder = Order::whereNotNull('dm_last_location')->first();

if (!$sampleOrder) {
    echo "❌ No orders with dm_last_location to verify structure\n\n";
} else {
    $location = $sampleOrder->dm_last_location;
    echo "Sample dm_last_location structure:\n";
    echo json_encode($location, JSON_PRETTY_PRINT) . "\n\n";

    $hasLatitude = isset($location['latitude']);
    $hasLongitude = isset($location['longitude']);
    $hasSpeed = isset($location['speed']);

    echo "Field Verification:\n";
    echo "  - latitude: " . ($hasLatitude ? "✅ Present" : "❌ Missing") . "\n";
    echo "  - longitude: " . ($hasLongitude ? "✅ Present" : "❌ Missing") . "\n";
    echo "  - speed: " . ($hasSpeed ? "✅ Present" : "⚠️ Missing (needs mobile app update)") . "\n";
    echo "\n";
}

// Summary
echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                    VERIFICATION SUMMARY                   ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$totalOrders = Order::whereNotNull('dm_last_location')->count();
$ordersWithSpeedData = Order::whereNotNull('dm_last_location')
    ->whereRaw("JSON_EXTRACT(dm_last_location, '$.speed') IS NOT NULL")
    ->count();
$speedCoverage = $totalOrders > 0 ? round(($ordersWithSpeedData / $totalOrders) * 100, 1) : 0;

echo "Orders with DM location: {$totalOrders}\n";
echo "Orders with speed data: {$ordersWithSpeedData}\n";
echo "Speed data coverage: {$speedCoverage}%\n\n";

if ($speedCoverage >= 80) {
    echo "✅ Speed tracking is working well!\n";
} elseif ($speedCoverage >= 50) {
    echo "⚠️ Speed tracking is partially working\n";
    echo "   Some DMs may be using older mobile app version\n";
} else {
    echo "❌ Speed tracking needs attention\n";
    echo "   Mobile app may need to be updated to send speed data\n";
}

echo "\n";
echo "NEXT STEPS:\n";
echo "1. Clear browser cache (Ctrl+F5)\n";
echo "2. Open order view page\n";
echo "3. Check if speed displays correctly in tracking section\n";
echo "4. Verify speed updates when DM location changes\n";
echo "\n";

echo "Verification complete!\n\n";
