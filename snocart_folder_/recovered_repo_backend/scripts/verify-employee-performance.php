#!/usr/bin/env php
<?php

/**
 * Employee Performance Dashboard - Verification Script
 *
 * Tests:
 * 1. Admin model has assignedOrders relationship
 * 2. EmployeePerformanceService exists and methods work
 * 3. DashboardController has employee_performance_data method
 * 4. DashboardController has employee_performance_detail method
 * 5. Route exists for AJAX endpoint
 * 6. Translation keys exist
 * 7. Widget blade file exists
 * 8. Dashboard includes widget
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n🔍 Employee Performance Dashboard - Verification\n";
echo str_repeat("=", 60) . "\n\n";

$passedTests = 0;
$failedTests = 0;

// Test 1: Check Admin model has assignedOrders relationship
echo "1️⃣  Testing Admin::assignedOrders() relationship...\n";
try {
    $admin = new App\Models\Admin();
    if (method_exists($admin, 'assignedOrders')) {
        echo "   ✅ assignedOrders() method exists\n";

        // Test relationship type
        $relation = $admin->assignedOrders();
        if ($relation instanceof Illuminate\Database\Eloquent\Relations\HasMany) {
            echo "   ✅ Relationship is HasMany (correct)\n";
            $passedTests++;
        } else {
            echo "   ❌ Relationship is not HasMany (got: " . get_class($relation) . ")\n";
            $failedTests++;
        }
    } else {
        echo "   ❌ assignedOrders() method NOT found\n";
        $failedTests++;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 2: Check EmployeePerformanceService exists
echo "2️⃣  Testing EmployeePerformanceService class...\n";
try {
    if (class_exists('App\Services\EmployeePerformanceService')) {
        echo "   ✅ EmployeePerformanceService class exists\n";

        $service = new App\Services\EmployeePerformanceService();

        // Check methods
        $methods = ['calculateEmployeeMetrics', 'getTopPerformers'];
        $allMethodsExist = true;
        foreach ($methods as $method) {
            if (method_exists($service, $method)) {
                echo "   ✅ Method {$method}() exists\n";
            } else {
                echo "   ❌ Method {$method}() NOT found\n";
                $allMethodsExist = false;
            }
        }

        if ($allMethodsExist) {
            $passedTests++;
        } else {
            $failedTests++;
        }
    } else {
        echo "   ❌ EmployeePerformanceService class NOT found\n";
        $failedTests++;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 3: Check DashboardController methods
echo "3️⃣  Testing DashboardController methods...\n";
try {
    $controller = new App\Http\Controllers\Admin\DashboardController();

    $methods = ['employee_performance_data', 'employee_performance_detail'];
    $allMethodsExist = true;
    foreach ($methods as $method) {
        if (method_exists($controller, $method)) {
            echo "   ✅ Method {$method}() exists in DashboardController\n";
        } else {
            echo "   ❌ Method {$method}() NOT found in DashboardController\n";
            $allMethodsExist = false;
        }
    }

    if ($allMethodsExist) {
        $passedTests++;
    } else {
        $failedTests++;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 4: Check route exists
echo "4️⃣  Testing AJAX route registration...\n";
try {
    $routes = Route::getRoutes();
    $routeExists = false;

    foreach ($routes as $route) {
        if ($route->getName() === 'admin.dashboard.employee-performance-detail') {
            $routeExists = true;
            echo "   ✅ Route 'admin.dashboard.employee-performance-detail' exists\n";
            echo "   📍 URI: " . $route->uri() . "\n";
            echo "   🎯 Method: " . implode('|', $route->methods()) . "\n";
            break;
        }
    }

    if ($routeExists) {
        $passedTests++;
    } else {
        echo "   ❌ Route 'admin.dashboard.employee-performance-detail' NOT found\n";
        $failedTests++;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 5: Check translation keys
echo "5️⃣  Testing translation keys...\n";
try {
    $keys = [
        'Employee Performance',
        'My Performance',
        'Total Assigned',
        'Cancellation Rate',
        'Performance Score',
        'Completion Rate',
    ];

    $allKeysExist = true;
    foreach ($keys as $key) {
        $translated = translate($key);
        if ($translated !== $key || Lang::has("messages.{$key}")) {
            echo "   ✅ Translation key '{$key}' exists\n";
        } else {
            echo "   ❌ Translation key '{$key}' NOT found\n";
            $allKeysExist = false;
        }
    }

    if ($allKeysExist) {
        $passedTests++;
    } else {
        $failedTests++;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 6: Check widget blade file exists
echo "6️⃣  Testing widget blade file...\n";
$widgetPath = resource_path('views/admin-views/partials/_employee-performance.blade.php');
if (file_exists($widgetPath)) {
    echo "   ✅ Widget blade file exists\n";
    echo "   📁 Path: {$widgetPath}\n";
    echo "   📊 Size: " . number_format(filesize($widgetPath)) . " bytes\n";
    $passedTests++;
} else {
    echo "   ❌ Widget blade file NOT found\n";
    echo "   📁 Expected: {$widgetPath}\n";
    $failedTests++;
}

echo "\n";

// Test 7: Check dashboard includes widget
echo "7️⃣  Testing dashboard includes widget...\n";
$dashboardPath = resource_path('views/admin-views/dashboard-food.blade.php');
if (file_exists($dashboardPath)) {
    $content = file_get_contents($dashboardPath);
    if (strpos($content, "@include('admin-views.partials._employee-performance')") !== false) {
        echo "   ✅ Dashboard includes widget partial\n";
        echo "   📁 Dashboard: {$dashboardPath}\n";
        $passedTests++;
    } else {
        echo "   ❌ Dashboard does NOT include widget partial\n";
        $failedTests++;
    }
} else {
    echo "   ❌ Dashboard file NOT found\n";
    $failedTests++;
}

echo "\n";

// Test 8: Test with real data (if employees exist)
echo "8️⃣  Testing with database data...\n";
try {
    // Count admins with assigned orders
    $adminsWithOrders = App\Models\Admin::where('role_id', '!=', 1)
        ->where('status', 1)
        ->whereHas('assignedOrders', function($q) {
            $q->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()]);
        })
        ->count();

    echo "   📊 Employees with orders today: {$adminsWithOrders}\n";

    if ($adminsWithOrders > 0) {
        // Test service with real data
        $service = new App\Services\EmployeePerformanceService();
        $topPerformers = $service->getTopPerformers(5, now()->startOfDay(), now()->endOfDay());

        echo "   ✅ Service works with real data\n";
        echo "   🏆 Top performers found: " . $topPerformers->count() . "\n";

        if ($topPerformers->count() > 0) {
            $top = $topPerformers->first();
            echo "   🥇 #1: {$top['name']} - Score: {$top['metrics']['performance_score']}\n";
        }
        $passedTests++;
    } else {
        echo "   ⚠️  No employees with assigned orders today (data test skipped)\n";
        echo "   ℹ️  This is normal if no orders were assigned today\n";
        $passedTests++;
    }
} catch (Exception $e) {
    echo "   ❌ Error testing with real data: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Summary
echo str_repeat("=", 60) . "\n";
echo "📊 VERIFICATION SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Passed: {$passedTests} tests\n";
echo "❌ Failed: {$failedTests} tests\n";
echo "\n";

if ($failedTests === 0) {
    echo "🎉 All tests passed! Employee Performance Dashboard is ready.\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Login to admin dashboard\n";
    echo "2. Navigate to Dashboard\n";
    echo "3. Look for 'Employee Performance' or 'My Performance' widget\n";
    echo "4. Test period tabs (Today/This Week/This Month)\n";
    echo "5. Click on employee cards (super admin only)\n";
    echo "\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the errors above.\n";
    exit(1);
}
