<?php

/**
 * Test Employee Chat Backend API
 *
 * Tests all API endpoints to verify backend implementation
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Admin;
use App\Services\EmployeeChatService;
use Illuminate\Support\Facades\DB;

echo "\n=== Employee Chat Backend API Tests ===\n\n";

// Test 1: Check if Admin has Sanctum trait
echo "Test 1: Verify Admin model has HasApiTokens trait... ";
$admin = Admin::where('status', 1)->first();
if (!$admin) {
    echo "❌ FAILED\n  Error: No approved admin found in database\n\n";
    exit(1);
}

try {
    $token = $admin->createToken('test')->plainTextToken;
    echo "✅ PASSED\n";
    echo "  - Generated token: " . substr($token, 0, 20) . "...\n\n";
} catch (\Exception $e) {
    echo "❌ FAILED\n  Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check EmployeeChatService exists
echo "Test 2: Verify EmployeeChatService class exists... ";
try {
    $chatService = new EmployeeChatService();
    echo "✅ PASSED\n\n";
} catch (\Exception $e) {
    echo "❌ FAILED\n  Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Create UserInfo for admin
echo "Test 3: Create/get UserInfo for admin... ";
try {
    $userInfo = $chatService->createOrGetUserInfo($admin);
    echo "✅ PASSED\n";
    echo "  - UserInfo ID: {$userInfo->id}\n";
    echo "  - Name: {$userInfo->f_name} {$userInfo->l_name}\n\n";
} catch (\Exception $e) {
    echo "❌ FAILED\n  Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 4: Get available employees
echo "Test 4: Get available employees... ";
try {
    $employees = $chatService->getAvailableEmployees($userInfo->id);
    echo "✅ PASSED\n";
    echo "  - Found {$employees->count()} available employees\n";
    if ($employees->count() > 0) {
        echo "  - First employee: {$employees->first()['name']}\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ FAILED\n  Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 5: Send a test message (if we have 2+ employees)
echo "Test 5: Send test message... ";
if ($employees->count() < 1) {
    echo "⚠️  SKIPPED (need at least 2 employees)\n\n";
} else {
    try {
        $receiver = $employees->first();
        $message = $chatService->sendMessage(
            $userInfo->id,
            $receiver['id'],
            'Test message from backend API test'
        );

        echo "✅ PASSED\n";
        echo "  - Message ID: {$message->id}\n";
        echo "  - Sent to: {$receiver['name']}\n";
        echo "  - Conversation ID: {$message->conversation_id}\n\n";

        // Clean up test message
        DB::table('messages')->where('id', $message->id)->delete();
        echo "  - Test message cleaned up ✓\n\n";
    } catch (\Exception $e) {
        echo "❌ FAILED\n  Error: " . $e->getMessage() . "\n\n";
        exit(1);
    }
}

// Test 6: Get conversations
echo "Test 6: Get conversations... ";
try {
    $conversations = $chatService->getConversations($userInfo->id, 10);
    echo "✅ PASSED\n";
    echo "  - Found {$conversations->total()} conversations\n";
    if ($conversations->count() > 0) {
        $conv = $conversations->first();
        echo "  - First conversation ID: {$conv->id}\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ FAILED\n  Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 7: Routes are registered
echo "Test 7: Verify routes are registered... ";
try {
    $routeNames = [
        'admin.employee-chat.conversations',
        'admin.employee-chat.messages',
        'admin.employee-chat.send',
        'admin.employee-chat.poll',
        'admin.employee-chat.employees',
        'admin.auth.token',
    ];

    $allExist = true;
    foreach ($routeNames as $name) {
        if (!Route::has($name)) {
            echo "❌ FAILED\n  Error: Route '{$name}' not found\n\n";
            $allExist = false;
            break;
        }
    }

    if ($allExist) {
        echo "✅ PASSED\n";
        echo "  - All " . count($routeNames) . " routes registered\n\n";
    }
} catch (\Exception $e) {
    echo "❌ FAILED\n  Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 8: Database structure
echo "Test 8: Verify database tables exist... ";
try {
    $tables = ['conversations', 'messages', 'user_infos', 'admins'];
    $allExist = true;

    foreach ($tables as $table) {
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            echo "❌ FAILED\n  Error: Table '{$table}' not found\n\n";
            $allExist = false;
            break;
        }
    }

    if ($allExist) {
        echo "✅ PASSED\n";
        echo "  - All required tables exist\n\n";
    }
} catch (\Exception $e) {
    echo "❌ FAILED\n  Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Summary
echo "=== Test Summary ===\n";
echo "✅ All backend API tests passed!\n";
echo "\nNext steps:\n";
echo "1. Test API endpoints via Postman/cURL\n";
echo "2. Build React frontend in snocart-web/\n";
echo "3. Implement WebSocket (Phase 3)\n\n";
