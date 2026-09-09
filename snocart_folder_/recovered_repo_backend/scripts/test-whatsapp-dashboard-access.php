#!/usr/bin/env php
<?php

/**
 * Test WhatsApp Dashboard Access
 */

echo "Testing WhatsApp Dashboard Access...\n\n";

// Test 1: Check if routes are registered
echo "[1] Checking if routes are registered...\n";
exec("php artisan route:list --name=admin.whatsapp.dashboard 2>&1", $output, $code);
if ($code === 0 && !empty($output)) {
    echo "  ✅ Route 'admin.whatsapp.dashboard' is registered\n";
} else {
    echo "  ❌ Route 'admin.whatsapp.dashboard' NOT registered\n";
    exit(1);
}

// Test 2: Check if controller exists
echo "\n[2] Checking if DashboardController exists...\n";
$controllerPath = __DIR__ . '/../app/Http/Controllers/Admin/WhatsApp/DashboardController.php';
if (file_exists($controllerPath)) {
    echo "  ✅ DashboardController exists\n";
} else {
    echo "  ❌ DashboardController NOT found\n";
    exit(1);
}

// Test 3: Check if models exist
echo "\n[3] Checking if models exist...\n";
$models = ['WaCampaign', 'WaCampaignRecipient', 'WaCustomerSegment', 'WaCampaignAnalytic', 'WaCustomerJourney'];
$allExist = true;
foreach ($models as $model) {
    $modelPath = __DIR__ . "/../app/Models/$model.php";
    if (file_exists($modelPath)) {
        echo "  ✅ Model $model exists\n";
    } else {
        echo "  ❌ Model $model NOT found\n";
        $allExist = false;
    }
}

// Test 4: Check if view exists
echo "\n[4] Checking if dashboard view exists...\n";
$viewPath = __DIR__ . '/../resources/views/admin-views/whatsapp/dashboard.blade.php';
if (file_exists($viewPath)) {
    echo "  ✅ Dashboard view exists\n";

    // Check for route errors in view
    $viewContent = file_get_contents($viewPath);
    if (strpos($viewContent, "route('admin.whatsapp.settings')") !== false) {
        echo "  ⚠️  WARNING: View still references non-existent 'admin.whatsapp.settings' route\n";
    } else {
        echo "  ✅ No missing route references found\n";
    }
} else {
    echo "  ❌ Dashboard view NOT found\n";
    exit(1);
}

// Test 5: Check recent errors
echo "\n[5] Checking recent Laravel errors...\n";
$logFile = __DIR__ . '/../storage/logs/laravel-' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    exec("tail -100 $logFile | grep 'production.ERROR' | tail -3", $errors);
    if (empty($errors)) {
        echo "  ✅ No recent errors in logs\n";
    } else {
        echo "  ⚠️  Recent errors found:\n";
        foreach ($errors as $error) {
            echo "      " . substr($error, 0, 150) . "...\n";
        }
    }
} else {
    echo "  ℹ️  No log file for today yet\n";
}

// Test 6: Test HTTP response
echo "\n[6] Testing HTTP response...\n";
exec("curl -s -o /dev/null -w '%{http_code}' https://new.snocart.com/admin/whatsapp 2>&1", $httpCode);
$code = trim(implode('', $httpCode));
if ($code == '302') {
    echo "  ✅ HTTP 302 (Redirect to login - expected for non-authenticated users)\n";
} elseif ($code == '200') {
    echo "  ✅ HTTP 200 (Page loaded successfully)\n";
} elseif ($code == '500') {
    echo "  ❌ HTTP 500 (Server error - check logs above)\n";
} else {
    echo "  ⚠️  HTTP $code (Unexpected response code)\n";
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 60) . "\n";

if ($code == '302' || $code == '200') {
    echo "✅ Dashboard should be accessible\n\n";
    echo "If you're still seeing an error:\n";
    echo "  1. Clear your browser cache (Ctrl+Shift+Delete)\n";
    echo "  2. Hard refresh the page (Ctrl+F5)\n";
    echo "  3. Try incognito/private browsing mode\n";
    echo "  4. Clear Laravel cache: php artisan cache:clear\n";
    echo "  5. Login to admin panel first, then navigate to /admin/whatsapp\n\n";
    echo "Access URL: https://new.snocart.com/admin/whatsapp\n";
} else {
    echo "❌ Dashboard has issues - check errors above\n";
}

echo "\n";
