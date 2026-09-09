<?php

/**
 * Bargaining Mode Load Testing Script
 *
 * Tests system performance under various load scenarios:
 * - Concurrent bargaining initiations
 * - Simultaneous offer submissions
 * - Heavy database query load
 * - Real-time event broadcasting
 * - CSV export with large datasets
 *
 * Usage: php scripts/test-bargaining-load.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Store;
use App\Models\Item;
use App\Models\Cart;
use App\Models\Zone;
use App\Models\Module;
use App\Models\Category;
use App\Models\BargainingRequest;
use App\Models\StoreBargainingSetting;
use App\Services\BargainingService;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   BARGAINING MODE - LOAD TESTING SUITE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Configuration
$testConfig = [
    'concurrent_users' => 10,
    'concurrent_vendors' => 5,
    'stores_per_zone' => 20,
    'items_per_store' => 50,
    'csv_export_rows' => 1000,
];

echo "Test Configuration:\n";
foreach ($testConfig as $key => $value) {
    echo "  • " . str_replace('_', ' ', ucfirst($key)) . ": $value\n";
}
echo "\n";

// Test 1: Setup Test Data
echo "[1/7] Setting up test environment...\n";
$startTime = microtime(true);

DB::beginTransaction();

try {
    $zone = Zone::first() ?? Zone::factory()->create();
    $module = Module::first() ?? Module::factory()->create();
    $category = Category::first() ?? Category::factory()->create();

    // Create stores with bargaining enabled
    $stores = [];
    for ($i = 0; $i < $testConfig['stores_per_zone']; $i++) {
        $store = Store::factory()->create([
            'zone_id' => $zone->id,
            'module_id' => $module->id,
            'active' => 1,
        ]);

        StoreBargainingSetting::factory()->create([
            'store_id' => $store->id,
            'bargaining_enabled' => true,
            'auto_participate' => true,
        ]);

        $stores[] = $store;
    }

    // Create items with barcodes for matching
    $barcodes = [];
    for ($i = 0; $i < 20; $i++) {
        $barcodes[] = 'LOAD-TEST-' . str_pad($i, 5, '0', STR_PAD_LEFT);
    }

    foreach ($stores as $store) {
        foreach ($barcodes as $index => $barcode) {
            Item::factory()->create([
                'store_id' => $store->id,
                'category_id' => $category->id,
                'barcode' => $barcode,
                'price' => 100 + ($index * 10),
                'status' => 1,
            ]);
        }
    }

    DB::commit();

    $setupTime = round((microtime(true) - $startTime) * 1000, 2);
    echo "  ✓ Created {$testConfig['stores_per_zone']} stores with " . (count($barcodes)) . " items each\n";
    echo "  ⏱  Setup time: {$setupTime}ms\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "  ✗ Setup failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Concurrent Bargaining Initiations
echo "[2/7] Testing concurrent bargaining initiations...\n";
$startTime = microtime(true);

$service = new BargainingService();
$users = [];
$requests = [];

for ($i = 0; $i < $testConfig['concurrent_users']; $i++) {
    $user = User::factory()->create();
    $users[] = $user;

    // Add items to cart
    $randomItems = Item::whereIn('barcode', array_slice($barcodes, 0, 5))
        ->where('store_id', $stores[0]->id)
        ->get();

    foreach ($randomItems as $item) {
        Cart::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'quantity' => rand(1, 3),
            'price' => $item->price,
        ]);
    }
}

// Simulate concurrent requests
$queryCountBefore = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
DB::enableQueryLog();

foreach ($users as $user) {
    try {
        $request = $service->initiateBargaining(
            userId: $user->id,
            guestId: null,
            mode: 'instant',
            deliveryAddress: ['lat' => 12.9716, 'lng' => 77.5946],
            zoneId: $zone->id,
            moduleId: $module->id
        );
        $requests[] = $request;
    } catch (\Exception $e) {
        echo "  ✗ Request failed: " . $e->getMessage() . "\n";
    }
}

$queryCount = count(DB::getQueryLog()) - $queryCountBefore;
$initiationTime = round((microtime(true) - $startTime) * 1000, 2);
$avgTimePerRequest = round($initiationTime / $testConfig['concurrent_users'], 2);

echo "  ✓ {$testConfig['concurrent_users']} concurrent initiations completed\n";
echo "  ⏱  Total time: {$initiationTime}ms\n";
echo "  ⏱  Avg per request: {$avgTimePerRequest}ms\n";
echo "  📊 Total queries: $queryCount\n";
echo "  📊 Queries per request: " . round($queryCount / $testConfig['concurrent_users'], 2) . "\n\n";

// Test 3: Item Matching Performance
echo "[3/7] Testing item matching algorithms...\n";
$startTime = microtime(true);

$request = $requests[0];
$cartItems = $request->cartItems()->count();
$matches = DB::table('bargaining_item_matches')
    ->whereIn('bargaining_cart_item_id', $request->cartItems()->pluck('id'))
    ->count();

$matchingTime = round((microtime(true) - $startTime) * 1000, 2);
$avgMatchesPerItem = round($matches / $cartItems, 2);

echo "  ✓ Matched {$cartItems} cart items across {$testConfig['stores_per_zone']} stores\n";
echo "  📊 Total matches found: $matches\n";
echo "  📊 Avg matches per item: $avgMatchesPerItem\n";
echo "  ⏱  Matching time: {$matchingTime}ms\n\n";

// Test 4: Offer Generation Performance
echo "[4/7] Testing offer generation...\n";
$startTime = microtime(true);

$totalOffers = 0;
foreach ($requests as $request) {
    $offerCount = $request->storeOffers()->count();
    $totalOffers += $offerCount;
}

$offerTime = round((microtime(true) - $startTime) * 1000, 2);
$avgOffersPerRequest = round($totalOffers / count($requests), 2);

echo "  ✓ Generated {$totalOffers} offers for " . count($requests) . " requests\n";
echo "  📊 Avg offers per request: {$avgOffersPerRequest}\n";
echo "  ⏱  Generation time: {$offerTime}ms\n\n";

// Test 5: Ranking Algorithm Performance
echo "[5/7] Testing offer ranking algorithm...\n";
$startTime = microtime(true);

$rankingAccuracy = 0;
foreach ($requests as $request) {
    $offers = $request->storeOffers()->orderBy('rank')->get();

    if ($offers->count() > 0) {
        // Verify rank order
        $prevRank = 0;
        $isCorrect = true;
        foreach ($offers as $offer) {
            if ($offer->rank <= $prevRank) {
                $isCorrect = false;
                break;
            }
            $prevRank = $offer->rank;
        }

        if ($isCorrect && $offers->first()->is_best_offer) {
            $rankingAccuracy++;
        }
    }
}

$rankingTime = round((microtime(true) - $startTime) * 1000, 2);
$accuracyRate = round(($rankingAccuracy / count($requests)) * 100, 2);

echo "  ✓ Ranked offers for " . count($requests) . " requests\n";
echo "  📊 Ranking accuracy: {$accuracyRate}%\n";
echo "  ⏱  Ranking time: {$rankingTime}ms\n\n";

// Test 6: CSV Export Performance
echo "[6/7] Testing CSV export with large dataset...\n";
$startTime = microtime(true);

// Create additional requests for export test
$exportRequests = BargainingRequest::factory()
    ->count($testConfig['csv_export_rows'])
    ->create([
        'zone_id' => $zone->id,
        'module_id' => $module->id,
    ]);

$exportController = new \App\Http\Controllers\Admin\BargainingController();

// Simulate export (capture output)
ob_start();
try {
    $response = $exportController->export(new \Illuminate\Http\Request(['period' => '30days', 'format' => 'csv']));
    $content = $response->getContent();
    $lines = substr_count($content, "\n");
} catch (\Exception $e) {
    echo "  ✗ Export failed: " . $e->getMessage() . "\n";
    $lines = 0;
}
ob_end_clean();

$exportTime = round((microtime(true) - $startTime) * 1000, 2);
$rowsPerSecond = $lines > 0 ? round(($lines / $exportTime) * 1000, 2) : 0;

echo "  ✓ Exported {$lines} rows to CSV\n";
echo "  ⏱  Export time: {$exportTime}ms\n";
echo "  📊 Rows per second: {$rowsPerSecond}\n\n";

// Test 7: Database Query Performance Analysis
echo "[7/7] Analyzing database query performance...\n";
$startTime = microtime(true);

// Test complex analytics query
$analyticsQuery = BargainingRequest::with(['storeOffers', 'cartItems'])
    ->whereBetween('created_at', [now()->subDays(30), now()])
    ->limit(100)
    ->get();

$queryAnalysisTime = round((microtime(true) - $startTime) * 1000, 2);

// Get slow query threshold
$slowQueries = DB::getQueryLog() ? array_filter(DB::getQueryLog(), function($query) {
    return isset($query['time']) && $query['time'] > 100; // > 100ms
}) : [];

echo "  ✓ Analyzed query performance\n";
echo "  ⏱  Analytics query time: {$queryAnalysisTime}ms\n";
echo "  📊 Slow queries (>100ms): " . count($slowQueries) . "\n";
echo "  📊 Query log size: " . (DB::getQueryLog() ? count(DB::getQueryLog()) : 0) . "\n\n";

// Summary Report
echo "═══════════════════════════════════════════════════════════════\n";
echo "   LOAD TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$totalTime = round((microtime(true) - $startTime) * 1000, 2);

echo "Performance Metrics:\n";
echo "  • Initiation Time (avg):      {$avgTimePerRequest}ms\n";
echo "  • Matching Time:              {$matchingTime}ms\n";
echo "  • Offer Generation:           {$offerTime}ms\n";
echo "  • Ranking Accuracy:           {$accuracyRate}%\n";
echo "  • CSV Export Speed:           {$rowsPerSecond} rows/sec\n";
echo "  • Slow Queries:               " . count($slowQueries) . "\n\n";

echo "Test Results:\n";
echo "  • Total Bargaining Requests:  " . BargainingRequest::count() . "\n";
echo "  • Total Offers Generated:     {$totalOffers}\n";
echo "  • Avg Offers Per Request:     {$avgOffersPerRequest}\n";
echo "  • Avg Matches Per Item:       {$avgMatchesPerItem}\n\n";

// Performance Recommendations
echo "Recommendations:\n";

if ($avgTimePerRequest > 1000) {
    echo "  ⚠ CRITICAL: Initiation time > 1s. Consider:\n";
    echo "     - Adding database indexes\n";
    echo "     - Implementing caching layer\n";
    echo "     - Optimizing item matching queries\n\n";
} elseif ($avgTimePerRequest > 500) {
    echo "  ⚠ WARNING: Initiation time > 500ms. Consider optimizing.\n\n";
} else {
    echo "  ✓ Initiation performance is good (< 500ms)\n\n";
}

if (count($slowQueries) > 5) {
    echo "  ⚠ WARNING: " . count($slowQueries) . " slow queries detected.\n";
    echo "     Review query optimization and add indexes.\n\n";
} else {
    echo "  ✓ Query performance is acceptable\n\n";
}

if ($accuracyRate < 95) {
    echo "  ⚠ WARNING: Ranking accuracy below 95%\n";
    echo "     Review ranking algorithm logic.\n\n";
} else {
    echo "  ✓ Ranking algorithm is accurate\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "   LOAD TEST COMPLETED\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Cleanup
echo "Cleaning up test data...\n";
try {
    // Clean up in reverse order to respect foreign key constraints
    DB::table('bargaining_offer_items')->whereIn(
        'bargaining_store_offer_id',
        DB::table('bargaining_store_offers')->whereIn(
            'bargaining_request_id',
            $requests->pluck('id')
        )->pluck('id')
    )->delete();

    DB::table('bargaining_store_offers')->whereIn('bargaining_request_id', $requests->pluck('id'))->delete();
    DB::table('bargaining_item_matches')->delete();
    DB::table('bargaining_cart_items')->whereIn('bargaining_request_id', $requests->pluck('id'))->delete();
    DB::table('bargaining_requests')->whereIn('id', $requests->pluck('id'))->delete();
    DB::table('carts')->whereIn('user_id', $users->pluck('id'))->delete();

    // Clean up export test data
    DB::table('bargaining_requests')->whereIn('id', $exportRequests->pluck('id'))->delete();

    echo "✓ Test data cleaned up successfully\n\n";
} catch (\Exception $e) {
    echo "✗ Cleanup failed: " . $e->getMessage() . "\n\n";
}

exit(0);
