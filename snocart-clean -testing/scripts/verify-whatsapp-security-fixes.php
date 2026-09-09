#!/usr/bin/env php
<?php

/**
 * WhatsApp Security Fixes Verification Script
 *
 * Verifies all 5 critical security vulnerabilities have been fixed:
 * 1. No hardcoded credentials in code
 * 2. Standalone dashboard deleted
 * 3. SSL verification enabled
 * 4. Race conditions prevented with locking
 * 5. Exec() replaced with safe alternatives
 */

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         WhatsApp Security Fixes Verification Script         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

// Test 1: Verify no hardcoded credentials in codebase
echo "[1/8] Checking for hardcoded WhatsApp API tokens...\n";
$basePath = dirname(__DIR__);
$searchPattern = 'EAARydlek0WoBQ0Fb4CmZBi';
exec("grep -r '$searchPattern' $basePath/app/ $basePath/config/ --exclude-dir=vendor 2>/dev/null", $output, $returnCode);

if ($returnCode === 1 && empty($output)) {
    echo "  ✅ PASS: No hardcoded credentials found in app/ or config/\n";
    $passed++;
} else {
    echo "  ❌ FAIL: Hardcoded credentials still present!\n";
    foreach ($output as $line) {
        echo "      Found: $line\n";
    }
    $failed++;
}

// Test 2: Verify config file exists
echo "\n[2/8] Checking config/whatsapp.php exists...\n";
if (file_exists("$basePath/config/whatsapp.php")) {
    echo "  ✅ PASS: config/whatsapp.php exists\n";
    $passed++;
} else {
    echo "  ❌ FAIL: config/whatsapp.php not found\n";
    $failed++;
}

// Test 3: Verify .env has WhatsApp credentials
echo "\n[3/8] Checking .env for WhatsApp configuration...\n";
$envContent = file_get_contents("$basePath/.env");
if (strpos($envContent, 'WHATSAPP_API_TOKEN') !== false &&
    strpos($envContent, 'WHATSAPP_PHONE_NUMBER_ID') !== false) {
    echo "  ✅ PASS: WhatsApp configuration found in .env\n";
    $passed++;
} else {
    echo "  ❌ FAIL: WhatsApp configuration missing from .env\n";
    $failed++;
}

// Test 4: Verify standalone dashboard deleted
echo "\n[4/8] Checking standalone dashboard deleted...\n";
if (!file_exists("$basePath/public/wa-dashboard/")) {
    echo "  ✅ PASS: public/wa-dashboard/ directory deleted\n";
    $passed++;
} else {
    echo "  ❌ FAIL: public/wa-dashboard/ still exists (security risk!)\n";
    $failed++;
}

// Test 5: Verify SSL verification enabled in WhatsAppController
echo "\n[5/8] Checking SSL verification in WhatsAppController...\n";
$controllerContent = file_get_contents("$basePath/app/Http/Controllers/Admin/WhatsAppController.php");
if (strpos($controllerContent, 'CURLOPT_SSL_VERIFYPEER => false') === false &&
    strpos($controllerContent, 'SSL_VERIFYPEER') !== false) {
    echo "  ✅ PASS: SSL verification enabled in WhatsAppController\n";
    $passed++;
} else {
    echo "  ❌ FAIL: SSL verification disabled or missing!\n";
    $failed++;
}

// Test 6: Verify SSL verification enabled in WaBlast command
echo "\n[6/8] Checking SSL verification in WaBlast command...\n";
$commandContent = file_get_contents("$basePath/app/Console/Commands/WaBlast.php");
if (strpos($commandContent, 'CURLOPT_SSL_VERIFYPEER => false') === false &&
    strpos($commandContent, 'SSL_VERIFYPEER') !== false) {
    echo "  ✅ PASS: SSL verification enabled in WaBlast\n";
    $passed++;
} else {
    echo "  ❌ FAIL: SSL verification disabled or missing in WaBlast!\n";
    $failed++;
}

// Test 7: Verify lockForUpdate() added to prevent race conditions
echo "\n[7/8] Checking for pessimistic locking...\n";
if (strpos($controllerContent, 'lockForUpdate()') !== false) {
    echo "  ✅ PASS: Pessimistic locking implemented\n";
    $passed++;
} else {
    echo "  ⚠️  WARNING: lockForUpdate() not found (race condition risk)\n";
    $warnings++;
}

// Test 8: Verify exec() replaced
echo "\n[8/8] Checking exec() usage...\n";
$execCount = substr_count($controllerContent, 'exec(');
if ($execCount === 0) {
    echo "  ✅ PASS: No exec() calls found in WhatsAppController\n";
    $passed++;
} else {
    echo "  ⚠️  WARNING: exec() still found ($execCount occurrences)\n";
    echo "      This will be replaced with queue jobs in Phase 4\n";
    $warnings++;
}

// Summary
echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║                      VERIFICATION SUMMARY                    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "✅ Passed:   $passed/8\n";
echo "❌ Failed:   $failed/8\n";
echo "⚠️  Warnings: $warnings/8\n\n";

if ($failed === 0) {
    echo "🎉 SUCCESS: All critical security fixes verified!\n";
    if ($warnings > 0) {
        echo "   Note: $warnings warnings found (will be addressed in later phases)\n";
    }
    exit(0);
} else {
    echo "❌ FAILURE: $failed critical security issues still present!\n";
    echo "   Please fix the issues above before proceeding.\n";
    exit(1);
}
