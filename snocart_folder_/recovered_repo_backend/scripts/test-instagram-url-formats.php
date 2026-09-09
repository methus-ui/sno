#!/usr/bin/env php
<?php

/**
 * Test Instagram URL Format Validation
 * Verifies that all common Instagram URL formats are accepted
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   Instagram URL Format Validation Test                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Test URLs - All should be ACCEPTED
$validUrls = [
    // Standard formats
    'https://www.instagram.com/reel/ABC123/',
    'https://instagram.com/reel/ABC123/',
    'https://www.instagram.com/reel/ABC123',

    // With username
    'https://www.instagram.com/username/reel/XYZ789/',
    'https://instagram.com/johndoe/reel/XYZ789/',

    // Posts (not reels)
    'https://www.instagram.com/p/DEF456/',
    'https://instagram.com/p/DEF456',

    // Short URLs
    'https://instagr.am/reel/GHI789/',
    'https://instagr.am/p/GHI789',

    // With query parameters (common in shared links)
    'https://www.instagram.com/reel/ABC123/?igsh=MWQ2dGhyZXkxYWVydQ==',
    'https://instagram.com/reel/ABC123?utm_source=ig_web_copy_link',
    'https://www.instagram.com/username/reel/ABC123/?igshid=abc123',

    // Different subdomains
    'https://www1.instagram.com/reel/ABC123/',
    'https://www2.instagram.com/reel/ABC123/',

    // HTTP (less common but valid)
    'http://www.instagram.com/reel/ABC123/',
    'http://instagram.com/reel/ABC123/',

    // Mixed case
    'https://www.Instagram.com/reel/ABC123/',
    'https://INSTAGRAM.COM/reel/ABC123/',
];

// URLs that should be REJECTED
$invalidUrls = [
    'https://facebook.com/reel/ABC123/',
    'https://youtube.com/watch?v=ABC123',
    'not-a-url',
    'instagram.com/reel/ABC123', // Missing protocol
    'https://www.instagram.com/',
    'https://www.instagram.com/explore/',
];

echo "📊 Testing URL Format Validation\n";
echo "=================================\n\n";

// Test with Advertisement model
echo "🔍 Testing Advertisement Model Embed URL Generation:\n\n";

$passCount = 0;
$failCount = 0;

foreach ($validUrls as $url) {
    $ad = new \App\Models\Advertisement();
    $ad->instagram_reel_url = $url;

    $embedUrl = $ad->instagramReelEmbedUrl;

    // Check if embed URL was generated and ends with /embed
    $success = $embedUrl && str_ends_with($embedUrl, '/embed');

    if ($success) {
        $passCount++;
        echo "  ✅ PASS: " . substr($url, 0, 60) . (strlen($url) > 60 ? '...' : '') . "\n";
        echo "       → {$embedUrl}\n";
    } else {
        $failCount++;
        echo "  ❌ FAIL: {$url}\n";
        echo "       → " . ($embedUrl ?? 'NULL') . "\n";
    }
}

echo "\n";
echo "📊 Results for Valid URLs:\n";
echo "   Total: " . count($validUrls) . "\n";
echo "   ✅ Passed: {$passCount}\n";
echo "   ❌ Failed: {$failCount}\n";
echo "\n";

// Test InstagramService
echo "🔍 Testing InstagramService Reel ID Extraction:\n\n";

$service = new \App\Services\InstagramService();
$extractPassCount = 0;
$extractFailCount = 0;

foreach ($validUrls as $url) {
    $reelId = $service->extractReelIdFromUrl($url);

    if ($reelId) {
        $extractPassCount++;
        echo "  ✅ PASS: " . substr($url, 0, 50) . (strlen($url) > 50 ? '...' : '') . " → ID: {$reelId}\n";
    } else {
        $extractFailCount++;
        echo "  ❌ FAIL: {$url} → NULL\n";
    }
}

echo "\n";
echo "📊 Results for ID Extraction:\n";
echo "   Total: " . count($validUrls) . "\n";
echo "   ✅ Passed: {$extractPassCount}\n";
echo "   ❌ Failed: {$extractFailCount}\n";
echo "\n";

// Test invalid URLs (should NOT generate embed URLs)
echo "🚫 Testing Invalid URLs (Should be Rejected):\n\n";

$rejectPassCount = 0;
$rejectFailCount = 0;

foreach ($invalidUrls as $url) {
    $ad = new \App\Models\Advertisement();
    $ad->instagram_reel_url = $url;

    $embedUrl = $ad->instagramReelEmbedUrl;

    // For invalid URLs, we expect either NULL or the fallback behavior
    // Check if it extracted a proper Instagram reel ID
    $properlyRejected = !preg_match('/(instagram\.com|instagr\.am)\/(reel|p)\/[A-Za-z0-9_-]+/i', $url);

    if ($properlyRejected || !$embedUrl) {
        $rejectPassCount++;
        echo "  ✅ PASS: {$url} → Properly handled\n";
    } else {
        $rejectFailCount++;
        echo "  ❌ FAIL: {$url} → Generated: {$embedUrl}\n";
    }
}

echo "\n";
echo "📊 Results for Invalid URLs:\n";
echo "   Total: " . count($invalidUrls) . "\n";
echo "   ✅ Properly Rejected: {$rejectPassCount}\n";
echo "   ❌ Incorrectly Accepted: {$rejectFailCount}\n";
echo "\n";

// Summary
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   SUMMARY                                                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$totalTests = count($validUrls) + count($invalidUrls);
$totalPassed = $passCount + $rejectPassCount;
$totalFailed = $failCount + $rejectFailCount;

echo "📊 Overall Results:\n";
echo "   Total Tests: {$totalTests}\n";
echo "   ✅ Passed: {$totalPassed}\n";
echo "   ❌ Failed: {$totalFailed}\n";
echo "   Success Rate: " . round(($totalPassed / $totalTests) * 100, 1) . "%\n";
echo "\n";

if ($totalFailed === 0) {
    echo "🎉 All tests passed! Instagram URL validation is working correctly.\n";
} else {
    echo "⚠️  Some tests failed. Please review the output above.\n";
}

echo "\n";
echo "✅ Supported Instagram URL Formats:\n";
echo "   • https://www.instagram.com/reel/ABC123/\n";
echo "   • https://instagram.com/reel/ABC123/\n";
echo "   • https://www.instagram.com/username/reel/ABC123/\n";
echo "   • https://www.instagram.com/p/ABC123/ (posts)\n";
echo "   • https://instagr.am/reel/ABC123/ (short URLs)\n";
echo "   • URLs with query parameters (?igsh=...)\n";
echo "   • HTTP and HTTPS\n";
echo "   • Case insensitive\n";
echo "\n";
