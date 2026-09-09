<?php

/**
 * Performance Testing Script
 * Compare query performance before and after optimization
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Item;
use Illuminate\Support\Facades\DB;

echo "=" . str_repeat("=", 70) . "\n";
echo "Performance Testing - Database Optimization\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "=" . str_repeat("=", 70) . "\n\n";

$tests = [];

// Test 1: Item Query (Before Optimization Pattern)
echo "Test 1: Complex Item Query (Slow Pattern)\n";
echo str_repeat("-", 70) . "\n";

$start = microtime(true);
$result1 = Item::where('status', 1)
    ->where('is_approved', 1)
    ->where('module_id', 2)
    ->where('stock', '>', 0)
    ->whereHas('store', function($q) {
        $q->where('status', 1);
    })
    ->limit(10)
    ->get();
$time1 = (microtime(true) - $start) * 1000;

echo "Results: " . $result1->count() . " items\n";
echo "Time: " . round($time1, 2) . " ms\n\n";

$tests['slow_query'] = $time1;

// Test 2: Optimized Query with Eager Loading
echo "Test 2: Optimized Item Query (Fast Pattern)\n";
echo str_repeat("-", 70) . "\n";

$start = microtime(true);
$result2 = Item::select('items.*')
    ->where('items.status', 1)
    ->where('items.is_approved', 1)
    ->where('items.module_id', 2)
    ->where('items.stock', '>', 0)
    ->join('stores', 'items.store_id', '=', 'stores.id')
    ->where('stores.status', 1)
    ->with('store:id,name,active')
    ->limit(10)
    ->get();
$time2 = (microtime(true) - $start) * 1000;

echo "Results: " . $result2->count() . " items\n";
echo "Time: " . round($time2, 2) . " ms\n";
echo "Improvement: " . round((($time1 - $time2) / $time1) * 100, 1) . "%\n\n";

$tests['optimized_query'] = $time2;
$tests['improvement_query'] = round((($time1 - $time2) / $time1) * 100, 1);

// Test 3: Random Selection (Before - Using RAND())
echo "Test 3: Random Items with RAND() (Slow)\n";
echo str_repeat("-", 70) . "\n";

$start = microtime(true);
$result3 = Item::where('is_approved', 1)
    ->where('module_id', 2)
    ->inRandomOrder()
    ->limit(12)
    ->get();
$time3 = (microtime(true) - $start) * 1000;

echo "Results: " . $result3->count() . " items\n";
echo "Time: " . round($time3, 2) . " ms\n\n";

$tests['rand_slow'] = $time3;

// Test 4: Random Selection (After - PHP Random)
echo "Test 4: Random Items with PHP Selection (Fast)\n";
echo str_repeat("-", 70) . "\n";

$start = microtime(true);
$ids = Item::where('is_approved', 1)
    ->where('module_id', 2)
    ->pluck('id')
    ->toArray();
$randomIds = array_rand(array_flip($ids), min(12, count($ids)));
$result4 = Item::whereIn('id', is_array($randomIds) ? $randomIds : [$randomIds])->get();
$time4 = (microtime(true) - $start) * 1000;

echo "Results: " . $result4->count() . " items\n";
echo "Time: " . round($time4, 2) . " ms\n";
echo "Improvement: " . round((($time3 - $time4) / $time3) * 100, 1) . "%\n\n";

$tests['rand_fast'] = $time4;
$tests['improvement_rand'] = round((($time3 - $time4) / $time3) * 100, 1);

// Test 5: Database Connection Test
echo "Test 5: Database Connection Health\n";
echo str_repeat("-", 70) . "\n";

try {
    $status = DB::select("SHOW STATUS WHERE Variable_name IN ('Threads_connected', 'Queries', 'Slow_queries')");
    foreach ($status as $row) {
        echo $row->Variable_name . ": " . $row->Value . "\n";
    }
    echo "✅ Database connection: Healthy\n\n";
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n\n";
}

// Summary
echo "=" . str_repeat("=", 70) . "\n";
echo "SUMMARY\n";
echo "=" . str_repeat("=", 70) . "\n\n";

echo "Item Query Performance:\n";
echo "  Before: " . round($tests['slow_query'], 2) . " ms\n";
echo "  After:  " . round($tests['optimized_query'], 2) . " ms\n";
echo "  Gain:   " . $tests['improvement_query'] . "%\n\n";

echo "Random Query Performance:\n";
echo "  Before: " . round($tests['rand_slow'], 2) . " ms\n";
echo "  After:  " . round($tests['rand_fast'], 2) . " ms\n";
echo "  Gain:   " . $tests['improvement_rand'] . "%\n\n";

$avgImprovement = ($tests['improvement_query'] + $tests['improvement_rand']) / 2;
echo "Average Improvement: " . round($avgImprovement, 1) . "%\n\n";

if ($avgImprovement > 50) {
    echo "🎉 Excellent! Queries are significantly faster!\n";
} elseif ($avgImprovement > 20) {
    echo "✅ Good! Noticeable performance improvement.\n";
} else {
    echo "⚠️  Modest improvement. More optimization may be needed.\n";
}

echo "\n";
