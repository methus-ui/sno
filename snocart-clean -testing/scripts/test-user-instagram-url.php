#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "Testing Your Instagram URL\n";
echo "==========================\n\n";

$userUrl = 'https://www.instagram.com/reel/DSkiLUfgdm-/';

echo "URL: {$userUrl}\n\n";

// Test 1: Advertisement Model
echo "1️⃣ Advertisement Model Test:\n";
$ad = new \App\Models\Advertisement();
$ad->instagram_reel_url = $userUrl;

$embedUrl = $ad->instagramReelEmbedUrl;

if ($embedUrl) {
    echo "   ✅ SUCCESS!\n";
    echo "   Original URL: {$userUrl}\n";
    echo "   Embed URL: {$embedUrl}\n";
    echo "   Ends with /embed: " . (str_ends_with($embedUrl, '/embed') ? '✅ YES' : '❌ NO') . "\n";
} else {
    echo "   ❌ FAILED - No embed URL generated\n";
}

echo "\n";

// Test 2: InstagramService
echo "2️⃣ InstagramService Test:\n";
$service = new \App\Services\InstagramService();
$reelId = $service->extractReelIdFromUrl($userUrl);

if ($reelId) {
    echo "   ✅ SUCCESS!\n";
    echo "   Extracted Reel ID: {$reelId}\n";
} else {
    echo "   ❌ FAILED - Could not extract reel ID\n";
}

echo "\n";

// Test 3: JavaScript Validation Simulation
echo "3️⃣ JavaScript Validation Test:\n";

$instagramUrlPattern = '/^https?:\/\/(www\d?\.)?instagram\.com\/([\w.]+\/)?(reel|p)\/[A-Za-z0-9_-]+/i';
$instagramShortPattern = '/^https?:\/\/(www\.)?instagr\.am\/(reel|p)\/[A-Za-z0-9_-]+/i';

$passesValidation = preg_match($instagramUrlPattern, $userUrl) || preg_match($instagramShortPattern, $userUrl);

if ($passesValidation) {
    echo "   ✅ SUCCESS!\n";
    echo "   URL passes JavaScript validation\n";
    echo "   You can now paste this URL in the admin panel!\n";
} else {
    echo "   ❌ FAILED - URL would be rejected\n";
}

echo "\n";
echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║  FINAL RESULT                                         ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n";
echo "\n";

if ($embedUrl && $reelId && $passesValidation) {
    echo "🎉 ALL TESTS PASSED!\n\n";
    echo "Your Instagram URL is VALID and will work!\n\n";
    echo "Next Steps:\n";
    echo "1. Go to: https://new.snocart.com/admin/advertisement/create\n";
    echo "2. Select: Video Promotion\n";
    echo "3. Choose: 'Paste Instagram URL'\n";
    echo "4. Paste: {$userUrl}\n";
    echo "5. Click: Preview\n";
    echo "6. Fill form and Submit\n\n";
    echo "✅ It will work!\n";
} else {
    echo "❌ SOME TESTS FAILED\n\n";
    echo "Please check the output above.\n";
}

echo "\n";
