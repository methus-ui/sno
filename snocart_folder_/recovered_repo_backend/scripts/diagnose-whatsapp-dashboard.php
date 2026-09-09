#!/usr/bin/env php
<?php

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  WhatsApp Dashboard - Complete Diagnostics                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$checks = [];

// 1. Check controllers exist
echo "📋 Checking Controllers...\n";
$controllers = [
    'DashboardController' => 'app/Http/Controllers/Admin/WhatsApp/DashboardController.php',
    'CampaignController' => 'app/Http/Controllers/Admin/WhatsApp/CampaignController.php',
    'SegmentController' => 'app/Http/Controllers/Admin/WhatsApp/SegmentController.php',
    'CustomerController' => 'app/Http/Controllers/Admin/WhatsApp/CustomerController.php',
];

foreach ($controllers as $name => $path) {
    if (file_exists(__DIR__ . '/../' . $path)) {
        echo "   ✅ $name exists\n";
        $checks[] = true;
    } else {
        echo "   ❌ $name MISSING at $path\n";
        $checks[] = false;
    }
}

// 2. Check services exist
echo "\n📋 Checking Services...\n";
$services = [
    'WhatsAppApiService' => 'app/Services/WhatsAppApiService.php',
    'CustomerSegmentationService' => 'app/Services/CustomerSegmentationService.php',
];

foreach ($services as $name => $path) {
    if (file_exists(__DIR__ . '/../' . $path)) {
        echo "   ✅ $name exists\n";
        $checks[] = true;
    } else {
        echo "   ❌ $name MISSING at $path\n";
        $checks[] = false;
    }
}

// 3. Check view files exist
echo "\n📋 Checking View Files...\n";
$views = [
    'dashboard' => 'resources/views/admin-views/whatsapp/dashboard.blade.php',
    'CSS' => 'public/assets/admin/css/whatsapp-design-system.css',
    'JS' => 'public/assets/admin/js/whatsapp-design-system.js',
];

foreach ($views as $name => $path) {
    if (file_exists(__DIR__ . '/../' . $path)) {
        echo "   ✅ $name exists\n";
        $checks[] = true;
    } else {
        echo "   ❌ $name MISSING at $path\n";
        $checks[] = false;
    }
}

// 4. Check config
echo "\n📋 Checking Configuration...\n";
if (file_exists(__DIR__ . '/../config/whatsapp.php')) {
    echo "   ✅ config/whatsapp.php exists\n";
    $checks[] = true;
} else {
    echo "   ❌ config/whatsapp.php MISSING\n";
    $checks[] = false;
}

// 5. Load Laravel and check database tables
echo "\n📋 Checking Database Tables...\n";
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = ['wa_campaigns', 'wa_customer_segments'];

foreach ($tables as $table) {
    try {
        $count = DB::table($table)->count();
        echo "   ✅ $table exists ($count records)\n";
        $checks[] = true;
    } catch (\Exception $e) {
        echo "   ❌ $table missing or error: " . $e->getMessage() . "\n";
        $checks[] = false;
    }
}

// 6. Check for syntax errors in controllers
echo "\n📋 Checking PHP Syntax...\n";
foreach ($controllers as $name => $path) {
    $fullPath = __DIR__ . '/../' . $path;
    if (file_exists($fullPath)) {
        exec("php -l " . escapeshellarg($fullPath) . " 2>&1", $output, $return);
        if ($return === 0) {
            echo "   ✅ $name has no syntax errors\n";
            $checks[] = true;
        } else {
            echo "   ❌ $name has syntax errors:\n";
            echo "      " . implode("\n      ", $output) . "\n";
            $checks[] = false;
        }
    }
}

// Summary
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC SUMMARY                                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$total = count($checks);
$passed = count(array_filter($checks));
$failed = $total - $passed;

echo "Total Checks: $total\n";
echo "Passed: $passed ✅\n";
echo "Failed: $failed " . ($failed > 0 ? "❌" : "✅") . "\n";
echo "\n";

if ($failed === 0) {
    echo "✅ All checks passed! If 500 error persists, check:\n";
    echo "   1. Laravel logs in storage/logs/\n";
    echo "   2. Apache error logs\n";
    echo "   3. Enable APP_DEBUG=true temporarily to see detailed error\n";
} else {
    echo "❌ Some checks failed. Fix the issues above and retry.\n";
}

echo "\n";
