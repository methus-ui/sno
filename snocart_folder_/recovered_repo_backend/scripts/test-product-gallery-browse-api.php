<?php

/**
 * Test Product Gallery Browse API
 * Tests if the 500 error is fixed
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Vendor;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Vendor\ProductGalleryController;

echo "===== Testing Product Gallery Browse API =====\n\n";

// Find a vendor with a store
$vendor = Vendor::with('stores')->whereHas('stores')->first();

if (!$vendor) {
    echo "❌ No vendor found with stores\n";
    exit(1);
}

echo "✓ Using vendor ID: {$vendor->id}\n";
echo "✓ Store ID: {$vendor->stores[0]->id}\n\n";

// Create a mock request
$request = Request::create('/api/v1/vendor/product-gallery/browse', 'GET', [
    'search' => 'maggi',
    'offset' => 1,
    'limit' => 10
]);

// Set the vendor on the request
$request->merge(['vendor' => $vendor]);

try {
    $controller = new ProductGalleryController();
    $response = $controller->browse($request);

    $statusCode = $response->getStatusCode();
    $content = json_decode($response->getContent(), true);

    echo "Response Status: {$statusCode}\n";

    if ($statusCode === 200) {
        echo "✅ SUCCESS! API returned 200 OK\n\n";
        echo "Response Summary:\n";
        echo "  - Success: " . ($content['success'] ? 'true' : 'false') . "\n";
        echo "  - Products found: " . count($content['data']['products'] ?? []) . "\n";
        echo "  - Total products: " . ($content['data']['pagination']['total_products'] ?? 0) . "\n";
        echo "  - Categories: " . count($content['data']['filters']['categories'] ?? []) . "\n";
        echo "  - Stores: " . count($content['data']['filters']['stores'] ?? []) . "\n";

        // Check if store logos are properly formatted
        if (!empty($content['data']['products'])) {
            $firstProduct = $content['data']['products'][0];
            echo "\nFirst Product:\n";
            echo "  - Name: {$firstProduct['name']}\n";
            echo "  - Store Logo: " . ($firstProduct['source_store_logo'] ? 'Present' : 'Missing') . "\n";

            if ($firstProduct['source_store_logo']) {
                echo "  - Logo URL: {$firstProduct['source_store_logo']}\n";
            }
        }

        echo "\n✅ All tests PASSED - 500 error is FIXED!\n";
        exit(0);
    } else {
        echo "❌ FAILED! Status code: {$statusCode}\n";
        echo "Response: " . json_encode($content, JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
