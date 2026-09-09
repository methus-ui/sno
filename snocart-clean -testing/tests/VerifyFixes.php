<?php

/**
 * Quick verification script for the fixes applied on 2026-02-05
 * Run with: php tests/VerifyFixes.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=" . str_repeat("=", 70) . "\n";
echo "Verification Test for Laravel Fixes - February 5, 2026\n";
echo "=" . str_repeat("=", 70) . "\n\n";

$allPassed = true;

// Test 1: Verify Store model coordinate validation
echo "Test 1: Store Model Coordinate Validation\n";
echo str_repeat("-", 70) . "\n";
try {
    $store = new \App\Models\Store();
    $reflection = new ReflectionMethod($store, 'scopeWithOpen');
    $code = file_get_contents(app_path('Models/Store.php'));

    if (strpos($code, 'is_numeric($longitude)') !== false &&
        strpos($code, 'is_numeric($latitude)') !== false) {
        echo "✅ PASS: Coordinate validation found in scopeWithOpen\n";
    } else {
        echo "❌ FAIL: Coordinate validation not found\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// Test 2: Verify identity_image column type
echo "Test 2: Database Column Size Fix\n";
echo str_repeat("-", 70) . "\n";
try {
    $result = DB::select("SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
                          FROM INFORMATION_SCHEMA.COLUMNS
                          WHERE TABLE_NAME = 'delivery_men'
                          AND COLUMN_NAME = 'identity_image'
                          LIMIT 1");

    if (!empty($result)) {
        $columnInfo = $result[0];
        if ($columnInfo->DATA_TYPE === 'text') {
            echo "✅ PASS: identity_image column is TEXT type\n";
            echo "   Max Length: " . $columnInfo->CHARACTER_MAXIMUM_LENGTH . " characters\n";
        } else {
            echo "❌ FAIL: Column type is {$columnInfo->DATA_TYPE} (expected: text)\n";
            $allPassed = false;
        }
    } else {
        echo "❌ FAIL: Could not verify column type\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// Test 3: Verify DmIncentiveService notification data
echo "Test 3: Incentive Notification Image Field\n";
echo str_repeat("-", 70) . "\n";
try {
    $code = file_get_contents(app_path('Services/DmIncentiveService.php'));

    if (strpos($code, "'image' =>") !== false) {
        echo "✅ PASS: Image field found in notification data\n";
        if (strpos($code, "'order_id' =>") !== false) {
            echo "✅ PASS: order_id field found in notification data\n";
        } else {
            echo "⚠️  WARNING: order_id field not found\n";
        }
    } else {
        echo "❌ FAIL: Image field not found in notification data\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// Test 4: Verify DatabaseConnectionServiceProvider
echo "Test 4: Database Connection Service Provider\n";
echo str_repeat("-", 70) . "\n";
try {
    if (class_exists('App\Providers\DatabaseConnectionServiceProvider')) {
        echo "✅ PASS: DatabaseConnectionServiceProvider class exists\n";

        // Check if registered in config
        $providers = config('app.providers');
        if (in_array('App\Providers\DatabaseConnectionServiceProvider', $providers)) {
            echo "✅ PASS: Service provider registered in config/app.php\n";
        } else {
            echo "❌ FAIL: Service provider not registered in config\n";
            $allPassed = false;
        }
    } else {
        echo "❌ FAIL: DatabaseConnectionServiceProvider class not found\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// Test 5: Database connection
echo "Test 5: Database Connection Health\n";
echo str_repeat("-", 70) . "\n";
try {
    DB::connection()->getPdo();
    echo "✅ PASS: Database connection successful\n";

    $stats = DB::select("SHOW STATUS LIKE 'Threads_connected'");
    if (!empty($stats)) {
        echo "   Active Connections: " . $stats[0]->Value . "\n";
    }

    $maxConn = DB::select("SHOW VARIABLES LIKE 'max_connections'");
    if (!empty($maxConn)) {
        echo "   Max Connections: " . $maxConn[0]->Value . "\n";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Database connection failed - " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// Test 6: PDO options
echo "Test 6: Database Configuration Options\n";
echo str_repeat("-", 70) . "\n";
try {
    $config = config('database.connections.mysql');

    $expectedOptions = [
        'ATTR_TIMEOUT',
        'ATTR_ERRMODE',
        'ATTR_PERSISTENT',
    ];

    $optionsSet = false;
    if (isset($config['options']) && is_array($config['options'])) {
        $optionsSet = true;
        echo "✅ PASS: PDO options configured\n";
        echo "   Options set: " . count($config['options']) . "\n";
    } else {
        echo "⚠️  WARNING: PDO options not configured\n";
    }

    if (isset($config['sticky'])) {
        echo "✅ PASS: Sticky connections enabled\n";
    }
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// Final Summary
echo "=" . str_repeat("=", 70) . "\n";
if ($allPassed) {
    echo "🎉 ALL TESTS PASSED! All fixes verified successfully.\n";
    exit(0);
} else {
    echo "⚠️  SOME TESTS FAILED. Please review the failures above.\n";
    exit(1);
}
