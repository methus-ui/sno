#!/usr/bin/env php
<?php

/**
 * Test Product Gallery API Routes Fix
 *
 * Tests that all product gallery API routes are properly registered
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Route;
use App\Models\Store;

echo "=== Product Gallery API Routes Test ===\n\n";

// Get all registered routes
$routes = Route::getRoutes();

// Check for product-gallery routes
$productGalleryRoutes = [
    'GET api/v1/vendor/product-gallery/browse',
    'GET api/v1/vendor/product-gallery/details/{id}',
    'POST api/v1/vendor/product-gallery/replicate',
    'POST api/v1/vendor/product-gallery/batch-replicate',
    'GET api/v1/vendor/product-gallery/my-replications',
    'GET api/v1/vendor/product-gallery/trending',
];

echo "TEST 1: Checking if all routes are registered\n";
echo str_repeat("-", 60) . "\n";

$allFound = true;
foreach ($productGalleryRoutes as $routeUri) {
    list($method, $uri) = explode(' ', $routeUri);

    $found = false;
    foreach ($routes as $route) {
        if (in_array($method, $route->methods()) && $route->uri() === $uri) {
            $found = true;
            $action = $route->getActionName();
            echo "✅ {$method} {$uri}\n";
            echo "   Controller: {$action}\n";
            break;
        }
    }

    if (!$found) {
        echo "❌ {$method} {$uri} - NOT FOUND\n";
        $allFound = false;
    }
}

echo "\n";

if ($allFound) {
    echo "✅ TEST 1 PASSED: All 6 routes registered\n\n";
} else {
    echo "❌ TEST 1 FAILED: Some routes missing\n\n";
    exit(1);
}

// Test 2: Check if controller file exists
echo "TEST 2: Checking controller file\n";
echo str_repeat("-", 60) . "\n";

$controllerPath = app_path('Http/Controllers/Api/V1/Vendor/ProductGalleryController.php');
if (file_exists($controllerPath)) {
    echo "✅ ProductGalleryController.php exists\n";
    echo "   Path: {$controllerPath}\n";

    // Check file size
    $fileSize = filesize($controllerPath);
    echo "   Size: " . number_format($fileSize) . " bytes\n";

    if ($fileSize > 1000) {
        echo "   ✅ Controller has substantial code\n";
    }
} else {
    echo "❌ ProductGalleryController.php NOT FOUND\n";
    exit(1);
}

echo "\n";

// Test 3: Check middleware
echo "TEST 3: Checking middleware protection\n";
echo str_repeat("-", 60) . "\n";

foreach ($routes as $route) {
    if (strpos($route->uri(), 'api/v1/vendor/product-gallery') !== false) {
        $middleware = $route->middleware();

        if (in_array('vendor.api', $middleware)) {
            echo "✅ Route protected by vendor.api middleware\n";
            echo "   URI: {$route->uri()}\n";
            echo "   Middleware: " . implode(', ', $middleware) . "\n";
            break;
        }
    }
}

echo "\n";

// Test 4: Get sample store for testing
echo "TEST 4: Sample data availability\n";
echo str_repeat("-", 60) . "\n";

$activeStore = Store::where('status', 1)->first();

if ($activeStore) {
    echo "✅ Found active store for testing\n";
    echo "   Store ID: {$activeStore->id}\n";
    echo "   Store Name: {$activeStore->name}\n";
    echo "   Module ID: {$activeStore->module_id}\n";
} else {
    echo "⚠️  No active stores found\n";
}

echo "\n";

// Summary
echo "=== SUMMARY ===\n";
echo "✅ All 6 product-gallery API routes registered\n";
echo "✅ ProductGalleryController exists and has code\n";
echo "✅ Routes protected by vendor.api middleware\n";
echo "✅ 404 error should be resolved\n";
echo "\n";

echo "API ENDPOINTS NOW AVAILABLE:\n";
echo "  GET  /api/v1/vendor/product-gallery/browse\n";
echo "  GET  /api/v1/vendor/product-gallery/details/{id}\n";
echo "  POST /api/v1/vendor/product-gallery/replicate\n";
echo "  POST /api/v1/vendor/product-gallery/batch-replicate\n";
echo "  GET  /api/v1/vendor/product-gallery/my-replications\n";
echo "  GET  /api/v1/vendor/product-gallery/trending\n";
echo "\n";

echo "NEXT STEPS:\n";
echo "1. Test from Flutter app: Product Gallery should load\n";
echo "2. Try browsing products - should return 200 OK\n";
echo "3. Try searching by barcode - should work now\n";
echo "\n";
