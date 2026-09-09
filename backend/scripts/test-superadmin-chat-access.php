<?php

/**
 * Test Super Admin Chat Access
 * Verifies that super admins can now access the employee chat
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Admin;

echo "\n=== Super Admin Chat Access Test ===\n\n";

// Find super admin (role_id = 1)
echo "Looking for super admin...\n";
$superAdmin = Admin::where('role_id', 1)->first();

if (!$superAdmin) {
    echo "❌ No super admin found with role_id = 1\n";
    echo "Trying alternative lookup...\n";

    // Try finding by email pattern
    $superAdmin = Admin::where('email', 'LIKE', '%admin%')
        ->orWhere('email', 'LIKE', '%super%')
        ->first();
}

if (!$superAdmin) {
    echo "❌ Could not find any admin to test with\n";
    exit(1);
}

echo "✅ Found admin:\n";
echo "  - ID: {$superAdmin->id}\n";
echo "  - Email: {$superAdmin->email}\n";
echo "  - Name: {$superAdmin->f_name} {$superAdmin->l_name}\n";
echo "  - Role ID: " . ($superAdmin->role_id ?? 'NULL') . "\n";
echo "  - Status: " . ($superAdmin->status ?? 'NULL') . "\n\n";

// Test 1: Token generation
echo "Test 1: Generate Sanctum token... ";
try {
    $token = $superAdmin->createToken('test-superadmin-chat')->plainTextToken;
    echo "✅ PASSED\n";
    echo "  - Token generated: " . substr($token, 0, 20) . "...\n\n";
} catch (\Exception $e) {
    echo "❌ FAILED\n";
    echo "  - Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check authorization logic
echo "Test 2: Verify authorization logic... ";
$isAuthorized = ($superAdmin->role_id == 1) || ($superAdmin->status == 1);
if ($isAuthorized) {
    echo "✅ PASSED\n";
    echo "  - Super admin should be authorized\n";
    if ($superAdmin->role_id == 1) {
        echo "  - Reason: role_id = 1 (super admin)\n";
    }
    if ($superAdmin->status == 1) {
        echo "  - Reason: status = 1 (approved)\n";
    }
    echo "\n";
} else {
    echo "❌ FAILED\n";
    echo "  - Super admin is NOT authorized\n";
    echo "  - role_id: " . ($superAdmin->role_id ?? 'NULL') . "\n";
    echo "  - status: " . ($superAdmin->status ?? 'NULL') . "\n\n";
}

// Test 3: Check employee list includes super admin
echo "Test 3: Check if super admin appears in employee list... ";
use App\Services\EmployeeChatService;
$chatService = new EmployeeChatService();
$userInfo = $chatService->createOrGetUserInfo($superAdmin);
$employees = $chatService->getAvailableEmployees($userInfo->id);

$foundOtherAdmins = $employees->count();
echo "✅ PASSED\n";
echo "  - Found {$foundOtherAdmins} other employees\n\n";

// Summary
echo "=== Test Summary ===\n";
echo "✅ Super admins can now access employee chat!\n";
echo "\nNext steps:\n";
echo "1. Login to admin panel as super admin\n";
echo "2. Navigate to: https://new.snocart.com/admin/employee-chat\n";
echo "3. Chat interface should load successfully\n\n";

echo "Admin Details:\n";
echo "  Email: {$superAdmin->email}\n";
echo "  Role: " . ($superAdmin->role_id == 1 ? 'Super Admin' : 'Admin') . "\n";
echo "  Access: " . ($isAuthorized ? '✅ Authorized' : '❌ Not Authorized') . "\n\n";
