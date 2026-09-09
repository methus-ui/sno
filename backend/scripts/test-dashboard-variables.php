#!/usr/bin/env php
<?php

/**
 * Test that all dashboard methods pass employee_performance and current_period variables
 */

echo "\n🔍 Testing Dashboard Controller Variable Passing\n";
echo str_repeat("=", 60) . "\n\n";

$controller_file = __DIR__ . '/../app/Http/Controllers/Admin/DashboardController.php';
$content = file_get_contents($controller_file);

$dashboard_methods = [
    'user_dashboard',
    'transaction_dashboard',
    'dispatch_dashboard',
    'dashboard',
];

$passed = 0;
$failed = 0;

foreach ($dashboard_methods as $method) {
    echo "Testing {$method}()...\n";

    // Find the method
    $pattern = "/public function {$method}\(.*?\n.*?return view/s";
    if (preg_match($pattern, $content, $matches)) {
        $method_content = $matches[0];

        // Check if it has employee_performance in compact or variables
        $has_employee_perf = (
            strpos($method_content, 'employee_performance') !== false
        );

        // Check if it has current_period
        $has_current_period = (
            strpos($method_content, 'current_period') !== false
        );

        if ($has_employee_perf && $has_current_period) {
            echo "   ✅ Passes employee_performance and current_period\n";
            $passed++;
        } else {
            echo "   ❌ Missing variables:\n";
            if (!$has_employee_perf) echo "      - employee_performance\n";
            if (!$has_current_period) echo "      - current_period\n";
            $failed++;
        }
    } else {
        echo "   ⚠️  Method not found or doesn't return a view\n";
        $failed++;
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "📊 SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Passed: {$passed}/{}" . count($dashboard_methods) . "\n";
echo "❌ Failed: {$failed}/{}" . count($dashboard_methods) . "\n\n";

if ($failed === 0) {
    echo "🎉 All dashboard methods pass required variables!\n";
    exit(0);
} else {
    echo "⚠️  Some methods are missing variables.\n";
    exit(1);
}
