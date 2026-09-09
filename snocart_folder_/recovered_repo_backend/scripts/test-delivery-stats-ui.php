<?php
/**
 * Delivery Stats UI Diagnostic Test
 * Tests for icon loading and console message issues
 */

echo "\n=== Delivery Stats UI Diagnostic ===\n\n";

// Test 1: Check if Blade file exists
$bladePath = '/var/www/html/new_public/new/resources/views/admin-views/delivery-stats-v2.blade.php';
echo "1. Blade file exists: " . (file_exists($bladePath) ? "✓ YES" : "✗ NO") . "\n";

// Test 2: Check if JavaScript file exists
$jsPath = '/var/www/html/new_public/new/public/assets/admin/js/delivery-stats-simple.js';
echo "2. JavaScript file exists: " . (file_exists($jsPath) ? "✓ YES" : "✗ NO") . "\n";

// Test 3: Check FontAwesome link
$bladeContent = file_get_contents($bladePath);
if (strpos($bladeContent, 'font-awesome/6.4.0/css/all.min.css') !== false) {
    echo "3. FontAwesome CDN linked: ✓ YES\n";
} else {
    echo "3. FontAwesome CDN linked: ✗ NO\n";
}

// Test 4: Check for icon references
$iconCount = substr_count($bladeContent, 'class="fas fa-');
echo "4. Icon references found: $iconCount\n";

// Test 5: Check JavaScript console messages
$jsContent = file_get_contents($jsPath);
$emojiCount = preg_match_all('/console\.(log|warn|error)\([\'"][\x{1F300}-\x{1F9FF}]/u', $jsContent);
echo "5. Console messages with emojis: $emojiCount\n";

// Test 6: Test FontAwesome CDN accessibility
echo "6. Testing FontAwesome CDN...\n";
$ch = curl_init('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   HTTP Status: $httpCode " . ($httpCode == 200 ? "✓ OK" : "✗ FAILED") . "\n";

// Test 7: Check for typos in common words
$typos = [
    'meassage' => substr_count($bladeContent, 'meassage'),
    'mesage' => substr_count($bladeContent, 'mesage'),
    '.meassage' => substr_count($bladeContent, '.meassage'),
];

echo "\n7. Typo check:\n";
foreach ($typos as $typo => $count) {
    if ($count > 0) {
        echo "   ✗ Found '$typo': $count occurrences\n";
    }
}
if (array_sum($typos) === 0) {
    echo "   ✓ No typos found\n";
}

// Test 8: Check CSS classes
$cssClasses = [
    '.stat-card' => substr_count($bladeContent, 'stat-card'),
    '.insight-card' => substr_count($bladeContent, 'insight-card'),
    '.chart-card' => substr_count($bladeContent, 'chart-card'),
];

echo "\n8. CSS classes:\n";
foreach ($cssClasses as $class => $count) {
    echo "   $class: $count occurrences\n";
}

// Test 9: Check for broken icon syntax
$brokenIcons = preg_match_all('/<i\s+class="[^"]*fas[^"]*"[^>]*><\/i>/', $bladeContent, $matches);
echo "\n9. Icon elements: $brokenIcons found\n";

// Test 10: Verify ApexCharts is loaded
if (strpos($bladeContent, 'apex-charts/apexcharts.js') !== false) {
    echo "10. ApexCharts library: ✓ Linked\n";
} else {
    echo "10. ApexCharts library: ✗ NOT linked\n";
}

echo "\n=== Recommendations ===\n\n";

if ($httpCode != 200) {
    echo "⚠️  FontAwesome CDN might be blocked - consider using local copy\n";
}

if ($emojiCount > 0) {
    echo "⚠️  Console messages contain emojis - might not display properly in all browsers\n";
    echo "   Consider using plain text console messages\n";
}

if (array_sum($typos) > 0) {
    echo "⚠️  Typos found - need to be fixed\n";
}

if ($httpCode == 200 && array_sum($typos) == 0 && $iconCount > 0) {
    echo "✓ All checks passed - UI should be working correctly\n";
    echo "\nIf you're still seeing issues, please check:\n";
    echo "- Browser console for JavaScript errors (F12 → Console tab)\n";
    echo "- Network tab for failed resource loads (F12 → Network tab)\n";
    echo "- Clear browser cache (Ctrl+Shift+Delete)\n";
}

echo "\n=== Test Complete ===\n";
