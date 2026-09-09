#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n🎨 Testing Unsplash API Connection...\n";
echo str_repeat("=", 60) . "\n\n";

// Check if API key is set
$apiKey = env('UNSPLASH_ACCESS_KEY');
if (empty($apiKey)) {
    echo "❌ UNSPLASH_ACCESS_KEY not found in .env\n";
    exit(1);
}

echo "✅ API Key found: " . substr($apiKey, 0, 10) . "...\n\n";

// Initialize service
$service = new \App\Services\UnsplashImageService();

// Test queries
$testQueries = [
    'milky way galaxy stars night sky space',
    'hummingbird flying colorful bird wings',
    'northern lights aurora borealis green sky'
];

echo "Testing 3 sample queries:\n";
echo str_repeat("-", 60) . "\n\n";

$successCount = 0;
foreach ($testQueries as $query) {
    echo "Query: '$query'\n";

    $result = $service->getImageForTopic(
        $query,
        'https://fallback.url/image.jpg'
    );

    if (strpos($result, 'fallback') !== false) {
        echo "❌ API failed, using fallback\n";
        echo "   Result: $result\n\n";
    } else {
        echo "✅ API working! Fresh image fetched\n";
        echo "   Result: " . substr($result, 0, 80) . "...\n\n";
        $successCount++;
    }

    // Small delay to avoid rate limiting
    usleep(500000); // 0.5 seconds
}

echo str_repeat("=", 60) . "\n";
echo "\n📊 Results: $successCount/3 successful API calls\n\n";

if ($successCount === 3) {
    echo "🎉 Perfect! Unsplash API is working correctly!\n";
    echo "   Your login page will now show fresh images.\n\n";
} elseif ($successCount > 0) {
    echo "⚠️  Partial success. Some API calls worked.\n";
    echo "   Check your internet connection or API rate limits.\n\n";
} else {
    echo "❌ All API calls failed.\n";
    echo "   Please check:\n";
    echo "   1. API key is correct in .env\n";
    echo "   2. Internet connection is working\n";
    echo "   3. Unsplash API is not down\n\n";
}

echo "💡 Tip: Fresh images are cached for 1 hour to save API calls.\n";
echo "   Current cache status:\n";

$cacheKey = 'unsplash_image_' . md5('milky way galaxy stars night sky space' . date('Y-m-d-H'));
$cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
if ($cached) {
    echo "   ✅ First query is now cached (valid for 1 hour)\n";
} else {
    echo "   ℹ️  No cached images yet\n";
}

echo "\n";
