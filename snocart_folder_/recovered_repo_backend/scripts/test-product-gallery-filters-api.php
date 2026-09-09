<?php
/**
 * Test Product Gallery API - Category & Type Filters
 * Tests the enhanced filtering in the vendor app API
 */

$baseUrl = 'https://new.snocart.com';

echo "🧪 Product Gallery API - Filter Test\n";
echo str_repeat("=", 70) . "\n\n";

// You'll need to replace this with a valid vendor token
$authToken = 'YOUR_VENDOR_TOKEN_HERE';

// Test cases
$tests = [
    [
        'name' => 'Test 1: Basic Browse (No Filters)',
        'url' => '/api/v1/vendor/product-gallery/browse?limit=5',
        'description' => 'Should return all products without any filters'
    ],
    [
        'name' => 'Test 2: Category Filter',
        'url' => '/api/v1/vendor/product-gallery/browse?category_id=3&limit=5',
        'description' => 'Should return products in category 3 + subcategories'
    ],
    [
        'name' => 'Test 3: Type Filter (Veg)',
        'url' => '/api/v1/vendor/product-gallery/browse?type=veg&limit=5',
        'description' => 'Should return only vegetarian products'
    ],
    [
        'name' => 'Test 4: Type Filter (Non-Veg)',
        'url' => '/api/v1/vendor/product-gallery/browse?type=non_veg&limit=5',
        'description' => 'Should return only non-vegetarian products'
    ],
    [
        'name' => 'Test 5: Combined Filters',
        'url' => '/api/v1/vendor/product-gallery/browse?search=rice&category_id=3&type=veg&limit=5',
        'description' => 'Should return veg products in category 3 matching "rice"'
    ],
    [
        'name' => 'Test 6: Filter Options',
        'url' => '/api/v1/vendor/product-gallery/browse?limit=1',
        'description' => 'Check if types are included in filters response'
    ]
];

foreach ($tests as $index => $test) {
    echo "\n" . ($index + 1) . ". {$test['name']}\n";
    echo "   " . str_repeat("-", 65) . "\n";
    echo "   📝 {$test['description']}\n";
    echo "   🔗 URL: {$test['url']}\n\n";

    $ch = curl_init($baseUrl . $test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $authToken,
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);

        if ($data['success']) {
            echo "   ✅ HTTP 200 OK\n";
            echo "   📊 Products found: " . count($data['data']['products']) . "\n";
            echo "   📄 Total: {$data['data']['pagination']['total_products']}\n";

            // Show first product details if available
            if (count($data['data']['products']) > 0) {
                $product = $data['data']['products'][0];
                echo "   📦 Sample: {$product['name']}\n";
                echo "      • Category: {$product['category_name']}\n";
                echo "      • Type: " . ($product['veg'] ? 'Veg' : 'Non-Veg') . "\n";
                echo "      • Store: {$product['source_store_name']}\n";
            }

            // Check filter options for Test 6
            if ($index === 5 && isset($data['data']['filters']['types'])) {
                echo "\n   🎯 Filter Options Available:\n";
                echo "      • Categories: " . count($data['data']['filters']['categories']) . "\n";
                echo "      • Stores: " . count($data['data']['filters']['stores']) . "\n";
                echo "      • Types: " . count($data['data']['filters']['types']) . "\n";

                echo "\n   📋 Type Options:\n";
                foreach ($data['data']['filters']['types'] as $type) {
                    $selected = $type['selected'] ? '✓' : ' ';
                    echo "      [{$selected}] {$type['name']} (id: {$type['id']})\n";
                }
            }

            // Show category structure for categories test
            if ($index === 1 && isset($data['data']['filters']['categories'])) {
                $categories = collect($data['data']['filters']['categories']);
                $rootCategories = $categories->where('parent_id', null)->take(3);

                if ($rootCategories->count() > 0) {
                    echo "\n   📂 Category Structure (sample):\n";
                    foreach ($rootCategories as $cat) {
                        echo "      • {$cat['name']} (ID: {$cat['id']})\n";
                        $subcats = $categories->where('parent_id', $cat['id'])->take(2);
                        foreach ($subcats as $sub) {
                            echo "         └─ {$sub['name']} (ID: {$sub['id']})\n";
                        }
                    }
                }
            }

        } else {
            echo "   ❌ API returned success=false\n";
            echo "   Message: {$data['message']}\n";
        }
    } else {
        echo "   ❌ HTTP $httpCode\n";
        echo "   Response: " . substr($response, 0, 200) . "\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Test Complete!\n\n";

echo "📌 API Endpoint: GET /api/v1/vendor/product-gallery/browse\n";
echo "\n📝 Supported Parameters:\n";
echo "   • search        - Search by name/description/barcode\n";
echo "   • category_id   - Filter by category (includes subcategories)\n";
echo "   • type          - Filter by type (all/veg/non_veg)\n";
echo "   • store_id      - Filter by source store\n";
echo "   • page          - Page number (default: 1)\n";
echo "   • limit         - Items per page (default: 20)\n";
echo "   • exclude_my_products - Exclude own products (default: true)\n";

echo "\n🔧 Example API Calls:\n";
echo "   • All veg products:\n";
echo "     GET /api/v1/vendor/product-gallery/browse?type=veg\n\n";
echo "   • Category 5 products:\n";
echo "     GET /api/v1/vendor/product-gallery/browse?category_id=5\n\n";
echo "   • Search rice in groceries (veg only):\n";
echo "     GET /api/v1/vendor/product-gallery/browse?search=rice&category_id=3&type=veg\n\n";

echo "💡 Note: Replace YOUR_VENDOR_TOKEN_HERE with actual token to test\n";
