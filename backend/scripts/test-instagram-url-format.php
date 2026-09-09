<?php

/**
 * Test Instagram URL Format Validation
 * Tests various Instagram URL formats to ensure they're properly recognized
 */

// Test URLs
$testUrls = [
    // Format 1: Direct reel URL
    'https://www.instagram.com/reel/ABC123/',
    'https://instagram.com/reel/ABC123/',
    'http://www.instagram.com/reel/ABC123/',

    // Format 2: Username in path (user's format)
    'https://www.instagram.com/snocart.app/reel/DSkiLUfgdm-/',
    'https://instagram.com/snocart.app/reel/DSkiLUfgdm-/',
    'https://www.instagram.com/username/reel/XYZ789/',
    'https://instagram.com/test_user/reel/123ABC/',

    // Format 3: Posts (should also work)
    'https://www.instagram.com/p/ABC123/',
    'https://instagram.com/username/p/ABC123/',

    // Invalid URLs (should fail)
    'https://facebook.com/reel/ABC123/',
    'https://www.instagram.com/stories/ABC123/',
    'not a url',
];

// Updated regex pattern (same as in JavaScript)
$pattern = '/^https?:\/\/(www\.)?instagram\.com\/([\w.]+\/)?(reel|p)\/[A-Za-z0-9_-]+\/?$/';

// Test each URL
echo "=== Instagram URL Format Validation Test ===\n\n";

foreach ($testUrls as $url) {
    $isValid = preg_match($pattern, $url);
    $status = $isValid ? '✅ VALID' : '❌ INVALID';

    echo sprintf("%-60s %s\n", $url, $status);

    // If valid, extract reel ID
    if ($isValid) {
        preg_match('/instagram\.com\/(?:[\w.]+\/)?(reel|p)\/([A-Za-z0-9_-]+)/', $url, $matches);
        $reelId = $matches[2] ?? 'N/A';
        echo sprintf("   └─ Reel ID: %s\n", $reelId);
    }

    echo "\n";
}

echo "\n=== Test Embed URL Generation ===\n\n";

// Test embed URL generation
$testReelUrl = 'https://www.instagram.com/snocart.app/reel/DSkiLUfgdm-/';
$embedUrl = rtrim($testReelUrl, '/');
if (!str_ends_with($embedUrl, '/embed')) {
    $embedUrl .= '/embed';
}

echo "Original URL: $testReelUrl\n";
echo "Embed URL:    $embedUrl\n\n";

// Test with InstagramService
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\InstagramService;

$service = new InstagramService();

echo "\n=== InstagramService Test ===\n\n";

$testUrls = [
    'https://www.instagram.com/snocart.app/reel/DSkiLUfgdm-/',
    'https://www.instagram.com/reel/ABC123/',
];

foreach ($testUrls as $url) {
    $reelId = $service->extractReelIdFromUrl($url);
    echo "URL: $url\n";
    echo "Extracted Reel ID: " . ($reelId ?? 'NULL') . "\n\n";
}

echo "✅ All tests completed!\n";
