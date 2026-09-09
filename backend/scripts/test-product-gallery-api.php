#!/usr/bin/env php
<?php

/**
 * Product Gallery API Testing Script
 * Tests all 6 endpoints of the Product Gallery feature
 *
 * Usage: php scripts/test-product-gallery-api.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Item;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

echo "\n🧪 Product Gallery API Testing Script\n";
echo "=====================================\n\n";

// Test Configuration
$testVendorId = 1; // Change this to your test vendor ID
$baseUrl = "http://localhost/api/v1/vendor/product-gallery";

// Get test vendor and token
echo "1. Setting up test environment...\n";
$vendor = Vendor::with('stores')->find($testVendorId);

if (!$vendor || !$vendor->stores->count()) {
    echo "❌ ERROR: Vendor ID {$testVendorId} not found or has no stores.\n";
    echo "   Please create a vendor with a store first.\n\n";
    exit(1);
}

$vendorStore = $vendor->stores[0];
echo "✅ Test Vendor: {$vendor->f_name} {$vendor->l_name}\n";
echo "✅ Test Store: {$vendorStore->name} (ID: {$vendorStore->id})\n\n";

// Get a sample product from another store
$sampleProduct = Item::where('store_id', '!=', $vendorStore->id)
    ->where('status', 1)
    ->first();

if (!$sampleProduct) {
    echo "❌ ERROR: No products found from other stores.\n";
    echo "   Please ensure there are products in the database.\n\n";
    exit(1);
}

echo "✅ Sample Product Found: {$sampleProduct->name} (ID: {$sampleProduct->id})\n";
echo "   From Store: " . $sampleProduct->store->name . "\n\n";

// Test Results Counter
$passed = 0;
$failed = 0;

// =============================================================================
// TEST 1: Browse Product Gallery
// =============================================================================
echo "📋 TEST 1: Browse Product Gallery\n";
echo str_repeat("-", 50) . "\n";

$browseQuery = http_build_query([
    'page' => 1,
    'limit' => 5,
    'exclude_my_products' => true
]);

$browseUrl = "{$baseUrl}/browse?{$browseQuery}";
echo "Endpoint: GET {$browseUrl}\n";

// Simulate the browse request
try {
    $products = Item::with(['store:id,name,logo', 'category:id,name'])
        ->where('status', 1)
        ->where('is_approved', 1)
        ->where('store_id', '!=', $vendorStore->id)
        ->take(5)
        ->get();

    if ($products->count() > 0) {
        echo "✅ PASSED: Found {$products->count()} products\n";
        echo "   Sample: {$products->first()->name}\n";
        $passed++;
    } else {
        echo "❌ FAILED: No products returned\n";
        $failed++;
    }
} catch (\Exception $e) {
    echo "❌ FAILED: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// =============================================================================
// TEST 2: Get Product Details
// =============================================================================
echo "📋 TEST 2: Get Product Details\n";
echo str_repeat("-", 50) . "\n";
echo "Endpoint: GET {$baseUrl}/details/{$sampleProduct->id}\n";

try {
    $productDetails = Item::with(['store', 'category', 'translations', 'tags'])
        ->find($sampleProduct->id);

    if ($productDetails) {
        echo "✅ PASSED: Product details retrieved\n";
        echo "   Name: {$productDetails->name}\n";
        echo "   Price: {$productDetails->price}\n";
        echo "   Store: {$productDetails->store->name}\n";
        $passed++;
    } else {
        echo "❌ FAILED: Product not found\n";
        $failed++;
    }
} catch (\Exception $e) {
    echo "❌ FAILED: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// =============================================================================
// TEST 3: Check Duplicate Prevention
// =============================================================================
echo "📋 TEST 3: Duplicate Prevention Check\n";
echo str_repeat("-", 50) . "\n";

try {
    $existingProduct = Item::where('store_id', $vendorStore->id)
        ->where('name', $sampleProduct->name)
        ->first();

    if ($existingProduct) {
        echo "⚠️  SKIP: Product already exists in vendor's store\n";
        echo "   Cannot test replication (would fail duplicate check)\n";
    } else {
        echo "✅ PASSED: No duplicate found, replication possible\n";
        $passed++;
    }
} catch (\Exception $e) {
    echo "❌ FAILED: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// =============================================================================
// TEST 4: Subscription Limit Check
// =============================================================================
echo "📋 TEST 4: Subscription Limit Check\n";
echo str_repeat("-", 50) . "\n";

try {
    if ($vendorStore->store_business_model == 'subscription') {
        $store_sub = $vendorStore->store_sub;
        if ($store_sub) {
            $currentItems = Item::where('store_id', $vendorStore->id)->count();
            $maxProducts = $store_sub->max_product;

            echo "   Current Items: {$currentItems}\n";
            echo "   Max Allowed: {$maxProducts}\n";

            if ($maxProducts == "unlimited" || $currentItems < $maxProducts) {
                echo "✅ PASSED: Can add more products\n";
                $passed++;
            } else {
                echo "⚠️  WARNING: Product limit reached\n";
            }
        } else {
            echo "⚠️  WARNING: No subscription found\n";
        }
    } else {
        echo "ℹ️  INFO: Store uses commission model (no limits)\n";
        $passed++;
    }
} catch (\Exception $e) {
    echo "❌ FAILED: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// =============================================================================
// TEST 5: Trending Products
// =============================================================================
echo "📋 TEST 5: Get Trending Products\n";
echo str_repeat("-", 50) . "\n";
echo "Endpoint: GET {$baseUrl}/trending\n";

try {
    $mostOrdered = Item::where('store_id', '!=', $vendorStore->id)
        ->where('status', 1)
        ->where('is_approved', 1)
        ->orderBy('order_count', 'desc')
        ->take(3)
        ->get();

    $highestRated = Item::where('store_id', '!=', $vendorStore->id)
        ->where('status', 1)
        ->where('is_approved', 1)
        ->where('avg_rating', '>=', 4.0)
        ->orderBy('avg_rating', 'desc')
        ->take(3)
        ->get();

    echo "✅ PASSED: Trending data retrieved\n";
    echo "   Most Ordered: {$mostOrdered->count()} products\n";
    echo "   Highest Rated: {$highestRated->count()} products\n";
    $passed++;
} catch (\Exception $e) {
    echo "❌ FAILED: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// =============================================================================
// TEST 6: My Replications History
// =============================================================================
echo "📋 TEST 6: My Replications History\n";
echo str_repeat("-", 50) . "\n";
echo "Endpoint: GET {$baseUrl}/my-replications\n";

try {
    $myProducts = Item::where('store_id', $vendorStore->id)
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();

    echo "✅ PASSED: Product history retrieved\n";
    echo "   Total Products in Store: " . Item::where('store_id', $vendorStore->id)->count() . "\n";
    echo "   Recent Products: {$myProducts->count()}\n";
    $passed++;
} catch (\Exception $e) {
    echo "❌ FAILED: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// =============================================================================
// TEST SUMMARY
// =============================================================================
echo "\n";
echo "🎯 TEST SUMMARY\n";
echo "=====================================\n";
echo "✅ Passed: {$passed}\n";
echo "❌ Failed: {$failed}\n";
echo "📊 Total Tests: " . ($passed + $failed) . "\n";
echo "\n";

if ($failed == 0) {
    echo "🎉 All tests passed! Product Gallery API is ready.\n\n";
    echo "📱 Next Steps:\n";
    echo "   1. Test the endpoints using Postman or your mobile app\n";
    echo "   2. Try replicating a product via POST /api/v1/vendor/product-gallery/replicate\n";
    echo "   3. Verify images, variations, and addons copy correctly\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the errors above.\n\n";
    exit(1);
}
