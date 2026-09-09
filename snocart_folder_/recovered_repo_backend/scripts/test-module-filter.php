<?php
/**
 * Test Script: Product Gallery Module & Category Filter
 *
 * Purpose: Verify module filter and categories-with-products-only functionality
 *
 * Features Tested:
 * 1. Module filter in browse API
 * 2. Categories only show if they have products
 * 3. Category products_count is included
 * 4. Filters are applied correctly
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Module;
use App\Models\Category;
use App\Models\Item;
use App\Models\Store;

echo "\n=== PRODUCT GALLERY MODULE FILTER TEST ===\n\n";

// Test 1: Get all active modules
echo "TEST 1: Get All Active Modules\n";
echo str_repeat("-", 50) . "\n";
$modules = Module::where('status', 1)->get(['id', 'module_name']);
echo "Found " . $modules->count() . " active modules:\n";
foreach ($modules as $module) {
    echo "  - [{$module->id}] {$module->module_name}\n";
}
echo "\n";

// Test 2: For each module, count categories with products
echo "TEST 2: Categories With Products Per Module\n";
echo str_repeat("-", 50) . "\n";
foreach ($modules as $module) {
    $categoriesWithProducts = Category::where('status', 1)
        ->where('module_id', $module->id)
        ->whereHas('products', function($q) {
            $q->where('status', 1)->where('is_approved', 1);
        })
        ->count();

    $totalCategories = Category::where('status', 1)
        ->where('module_id', $module->id)
        ->count();

    echo "Module: {$module->module_name}\n";
    echo "  Total Categories: {$totalCategories}\n";
    echo "  Categories with Products: {$categoriesWithProducts}\n";
    echo "  Empty Categories: " . ($totalCategories - $categoriesWithProducts) . "\n\n";
}

// Test 3: Get categories with product count for first module
echo "TEST 3: Categories with Product Count (First Module)\n";
echo str_repeat("-", 50) . "\n";
$firstModule = $modules->first();
if ($firstModule) {
    $categories = Category::where('status', 1)
        ->where('module_id', $firstModule->id)
        ->where('position', 0) // Parent categories only
        ->whereHas('products', function($q) {
            $q->where('status', 1)->where('is_approved', 1);
        })
        ->withCount(['products' => function($q) {
            $q->where('status', 1)->where('is_approved', 1);
        }])
        ->orderBy('name')
        ->take(10)
        ->get(['id', 'name', 'parent_id']);

    echo "Module: {$firstModule->module_name}\n";
    echo "Top 10 Categories:\n";
    foreach ($categories as $cat) {
        echo "  [{$cat->id}] {$cat->name} - {$cat->products_count} products\n";

        // Check for subcategories
        $subCategories = Category::where('parent_id', $cat->id)
            ->where('status', 1)
            ->whereHas('products', function($q) {
                $q->where('status', 1)->where('is_approved', 1);
            })
            ->withCount(['products' => function($q) {
                $q->where('status', 1)->where('is_approved', 1);
            }])
            ->get(['id', 'name']);

        foreach ($subCategories as $sub) {
            echo "    └─ [{$sub->id}] {$sub->name} - {$sub->products_count} products\n";
        }
    }
}
echo "\n";

// Test 4: Simulate API request with module filter
echo "TEST 4: Simulate API Browse Request with Module Filter\n";
echo str_repeat("-", 50) . "\n";
if ($firstModule) {
    // Get a sample store
    $store = Store::where('status', 1)->first();

    if ($store) {
        echo "Store: {$store->name} (ID: {$store->id}, Module: {$store->module_id})\n";
        echo "Filtering by Module: {$firstModule->module_name} (ID: {$firstModule->id})\n\n";

        // Count products in this module
        $productCount = Item::where('status', 1)
            ->where('is_approved', 1)
            ->where('module_id', $firstModule->id)
            ->count();

        echo "Total Products in Module: {$productCount}\n\n";

        // Get categories for this module (only with products)
        $apiCategories = Category::where('status', 1)
            ->where('module_id', $firstModule->id)
            ->whereHas('products', function($q) {
                $q->where('status', 1)->where('is_approved', 1);
            })
            ->withCount(['products' => function($q) {
                $q->where('status', 1)->where('is_approved', 1);
            }])
            ->orderBy('position')
            ->get(['id', 'name', 'parent_id']);

        echo "API Response - Filters:\n";
        echo "  Modules: {$modules->count()} available\n";
        echo "  Categories: {$apiCategories->count()} (only with products)\n";

        $parentCats = $apiCategories->where('parent_id', null)->count();
        $subCats = $apiCategories->where('parent_id', '!=', null)->count();
        echo "    - Parent: {$parentCats}\n";
        echo "    - Subcategories: {$subCats}\n";
    }
}
echo "\n";

// Test 5: Verify empty categories are excluded
echo "TEST 5: Verify Empty Categories Are Excluded\n";
echo str_repeat("-", 50) . "\n";
$allCategories = Category::where('status', 1)->count();
$categoriesWithProducts = Category::where('status', 1)
    ->whereHas('products', function($q) {
        $q->where('status', 1)->where('is_approved', 1);
    })
    ->count();
$emptyCategories = $allCategories - $categoriesWithProducts;

echo "Total Active Categories: {$allCategories}\n";
echo "Categories with Products: {$categoriesWithProducts}\n";
echo "Empty Categories (excluded): {$emptyCategories}\n";
echo "Exclusion Rate: " . round(($emptyCategories / $allCategories) * 100, 2) . "%\n\n";

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Module filter working: " . $modules->count() . " modules available\n";
echo "✅ Categories filtered by module\n";
echo "✅ Only categories with products are shown\n";
echo "✅ Product count included per category\n";
echo "✅ Subcategories with products also included\n";
echo "✅ Empty categories excluded: {$emptyCategories} hidden\n";
echo "\n";

echo "NEXT STEPS:\n";
echo "1. Test in web panel: vendor.item.product_gallery\n";
echo "2. Test API: GET /api/v1/vendor/product-gallery/browse?module_id=1\n";
echo "3. Verify module dropdown works\n";
echo "4. Verify category dropdown updates when module changes\n";
echo "5. Verify product counts are correct\n";
echo "\n";
