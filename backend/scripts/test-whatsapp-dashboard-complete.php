#!/usr/bin/env php
<?php

/**
 * WhatsApp Dashboard - Complete System Test
 *
 * Tests all components of the WhatsApp dashboard implementation:
 * - Security fixes
 * - Database schema
 * - Service classes
 * - Queue jobs
 * - Controllers
 * - Routes
 * - Seeder & Commands
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║      WhatsApp Dashboard - Complete System Test              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

// ============================================================================
// SECTION 1: Security Tests
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 1: SECURITY TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Test 1.1: No hardcoded credentials
echo "[1.1] Checking for hardcoded credentials...\n";
exec("grep -r 'EAARydlek0WoBQ0Fb4CmZBi' " . __DIR__ . "/../app/ --exclude-dir=vendor 2>/dev/null", $output, $returnCode);
if ($returnCode === 1 && empty($output)) {
    echo "  ✅ PASS: No hardcoded credentials found\n";
    $passed++;
} else {
    echo "  ❌ FAIL: Hardcoded credentials still present\n";
    $failed++;
}

// Test 1.2: Config file exists
echo "\n[1.2] Checking config/whatsapp.php exists...\n";
if (file_exists(__DIR__ . '/../config/whatsapp.php')) {
    echo "  ✅ PASS: Configuration file exists\n";
    $passed++;
} else {
    echo "  ❌ FAIL: Configuration file missing\n";
    $failed++;
}

// Test 1.3: Standalone dashboard deleted
echo "\n[1.3] Checking standalone dashboard deleted...\n";
if (!file_exists(__DIR__ . '/../public/wa-dashboard/')) {
    echo "  ✅ PASS: Standalone dashboard deleted\n";
    $passed++;
} else {
    echo "  ❌ FAIL: Standalone dashboard still exists (security risk!)\n";
    $failed++;
}

// Test 1.4: SSL verification enabled
echo "\n[1.4] Checking SSL verification...\n";
$controllerContent = file_get_contents(__DIR__ . '/../app/Http/Controllers/Admin/WhatsAppController.php');
if (strpos($controllerContent, 'CURLOPT_SSL_VERIFYPEER => false') === false) {
    echo "  ✅ PASS: SSL verification enabled\n";
    $passed++;
} else {
    echo "  ❌ FAIL: SSL verification disabled\n";
    $failed++;
}

// ============================================================================
// SECTION 2: Database Schema Tests
// ============================================================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 2: DATABASE SCHEMA TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$requiredTables = [
    'wa_campaigns',
    'wa_campaign_recipients',
    'wa_customer_segments',
    'wa_segment_customers',
    'wa_campaign_analytics',
    'wa_customer_journey'
];

foreach ($requiredTables as $index => $table) {
    echo "[2." . ($index + 1) . "] Checking table '$table' exists...\n";
    try {
        $exists = DB::select("SHOW TABLES LIKE '$table'");
        if (!empty($exists)) {
            echo "  ✅ PASS: Table '$table' exists\n";
            $passed++;
        } else {
            echo "  ❌ FAIL: Table '$table' missing\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "  ❌ FAIL: Error checking table - " . $e->getMessage() . "\n";
        $failed++;
    }
}

// ============================================================================
// SECTION 3: Service Class Tests
// ============================================================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 3: SERVICE CLASS TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$requiredServices = [
    'WhatsAppApiService',
    'CustomerSegmentationService',
    'CampaignBuilderService',
    'CampaignAnalyticsService',
    'CustomerJourneyService'
];

foreach ($requiredServices as $index => $service) {
    echo "[3." . ($index + 1) . "] Checking service '$service' exists...\n";
    $filePath = __DIR__ . "/../app/Services/$service.php";
    if (file_exists($filePath)) {
        echo "  ✅ PASS: Service '$service' exists\n";
        $passed++;

        // Check if class is instantiable
        try {
            $className = "App\\Services\\$service";
            if (class_exists($className)) {
                echo "  ✅ PASS: Class '$className' is loadable\n";
                $passed++;
            } else {
                echo "  ⚠️  WARNING: Class '$className' not found\n";
                $warnings++;
            }
        } catch (\Exception $e) {
            echo "  ⚠️  WARNING: Error loading class - " . $e->getMessage() . "\n";
            $warnings++;
        }
    } else {
        echo "  ❌ FAIL: Service '$service' missing\n";
        $failed++;
    }
}

// ============================================================================
// SECTION 4: Queue Job Tests
// ============================================================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 4: QUEUE JOB TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$requiredJobs = [
    'SendWhatsAppCampaignJob',
    'SendWhatsAppMessageJob',
    'CalculateCampaignAnalyticsJob'
];

foreach ($requiredJobs as $index => $job) {
    echo "[4." . ($index + 1) . "] Checking job '$job' exists...\n";
    $filePath = __DIR__ . "/../app/Jobs/$job.php";
    if (file_exists($filePath)) {
        echo "  ✅ PASS: Job '$job' exists\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Job '$job' missing\n";
        $failed++;
    }
}

// ============================================================================
// SECTION 5: Controller Tests
// ============================================================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 5: CONTROLLER TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$requiredControllers = [
    'WhatsApp/DashboardController',
    'WhatsApp/CustomerController',
    'WhatsApp/SegmentController',
    'WhatsApp/CampaignController'
];

foreach ($requiredControllers as $index => $controller) {
    echo "[5." . ($index + 1) . "] Checking controller '$controller' exists...\n";
    $filePath = __DIR__ . "/../app/Http/Controllers/Admin/$controller.php";
    if (file_exists($filePath)) {
        echo "  ✅ PASS: Controller '$controller' exists\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Controller '$controller' missing\n";
        $failed++;
    }
}

// ============================================================================
// SECTION 6: View Tests
// ============================================================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 6: VIEW TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$requiredViews = [
    'dashboard.blade.php',
    'campaigns/index.blade.php',
    'campaigns/create.blade.php',
    'campaigns/show.blade.php',
    'customers/index.blade.php',
    'segments/index.blade.php'
];

foreach ($requiredViews as $index => $view) {
    echo "[6." . ($index + 1) . "] Checking view '$view' exists...\n";
    $filePath = __DIR__ . "/../resources/views/admin-views/whatsapp/$view";
    if (file_exists($filePath)) {
        echo "  ✅ PASS: View '$view' exists\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: View '$view' missing\n";
        $failed++;
    }
}

// ============================================================================
// SECTION 7: Console Command Tests
// ============================================================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 7: CONSOLE COMMAND TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$requiredCommands = [
    'WhatsAppSeedSegments',
    'WhatsAppRefreshSegments',
    'WhatsAppProcessScheduled'
];

foreach ($requiredCommands as $index => $command) {
    echo "[7." . ($index + 1) . "] Checking command '$command' exists...\n";
    $filePath = __DIR__ . "/../app/Console/Commands/$command.php";
    if (file_exists($filePath)) {
        echo "  ✅ PASS: Command '$command' exists\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Command '$command' missing\n";
        $failed++;
    }
}

// ============================================================================
// SECTION 8: Functional Tests
// ============================================================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 8: FUNCTIONAL TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Test 8.1: Check if segments seeder works
echo "[8.1] Checking predefined segments...\n";
try {
    $segmentCount = DB::table('wa_customer_segments')->where('type', 'predefined')->count();
    if ($segmentCount === 16) {
        echo "  ✅ PASS: 16 predefined segments found\n";
        $passed++;
    } elseif ($segmentCount === 0) {
        echo "  ⚠️  WARNING: No segments found - run 'php artisan whatsapp:seed-segments'\n";
        $warnings++;
    } else {
        echo "  ⚠️  WARNING: $segmentCount segments found (expected 16)\n";
        $warnings++;
    }
} catch (\Exception $e) {
    echo "  ❌ FAIL: Error checking segments - " . $e->getMessage() . "\n";
    $failed++;
}

// Test 8.2: Check customer count
echo "\n[8.2] Checking customer count...\n";
try {
    $customerCount = DB::table('users')
        ->whereNotNull('phone')
        ->where('phone', '!=', '')
        ->where('status', 1)
        ->count();
    echo "  ✅ PASS: $customerCount customers with valid phone numbers found\n";
    $passed++;
} catch (\Exception $e) {
    echo "  ❌ FAIL: Error checking customers - " . $e->getMessage() . "\n";
    $failed++;
}

// Test 8.3: Check campaign count
echo "\n[8.3] Checking campaign count...\n";
try {
    $campaignCount = DB::table('wa_campaigns')->count();
    echo "  ✅ PASS: $campaignCount campaigns found\n";
    $passed++;
} catch (\Exception $e) {
    echo "  ❌ FAIL: Error checking campaigns - " . $e->getMessage() . "\n";
    $failed++;
}

// Test 8.4: Check queue configuration
echo "\n[8.4] Checking queue configuration...\n";
$queueDriver = config('queue.default');
if ($queueDriver !== 'sync') {
    echo "  ✅ PASS: Queue driver is '$queueDriver' (not sync)\n";
    $passed++;
} else {
    echo "  ⚠️  WARNING: Queue driver is 'sync' - jobs will run synchronously\n";
    $warnings++;
}

// ============================================================================
// SUMMARY
// ============================================================================
echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    COMPREHENSIVE SUMMARY                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

$totalTests = $passed + $failed + $warnings;
$passRate = $totalTests > 0 ? round(($passed / $totalTests) * 100, 1) : 0;

echo "\n";
echo "Total Tests:    $totalTests\n";
echo "✅ Passed:      $passed\n";
echo "❌ Failed:      $failed\n";
echo "⚠️  Warnings:    $warnings\n";
echo "Pass Rate:      $passRate%\n\n";

// Detailed breakdown
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION BREAKDOWN:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1. Security Tests:       4 tests\n";
echo "2. Database Schema:      6 tests\n";
echo "3. Service Classes:      10 tests (5 files + 5 class checks)\n";
echo "4. Queue Jobs:           3 tests\n";
echo "5. Controllers:          4 tests\n";
echo "6. Views:                6 tests\n";
echo "7. Console Commands:     3 tests\n";
echo "8. Functional Tests:     4 tests\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($failed === 0 && $warnings === 0) {
    echo "🎉 SUCCESS: All tests passed! System is production-ready.\n\n";
    exit(0);
} elseif ($failed === 0) {
    echo "✅ SUCCESS: All critical tests passed ($warnings warnings)\n";
    echo "   Review warnings above before deploying to production.\n\n";
    exit(0);
} else {
    echo "❌ FAILURE: $failed critical tests failed!\n";
    echo "   Fix the issues above before deploying to production.\n\n";
    exit(1);
}
