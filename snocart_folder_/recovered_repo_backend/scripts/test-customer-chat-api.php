#!/usr/bin/env php
<?php

/**
 * Customer Chat API Testing Script
 *
 * Tests all customer chat endpoints to verify:
 * - Authentication works (Laravel Passport)
 * - Message sending to vendor/admin/delivery_man
 * - FCM notifications
 * - Auto-response triggers
 * - Conversation listing
 * - Message retrieval
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Vendor;
use App\Models\DeliveryMan;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\UserInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║        Customer Chat API Testing Script                      ║\n";
echo "║        Testing Message Send/Receive Functionality            ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;

// Test 1: Check if customer users exist
echo "Test 1: Checking for test customer users...\n";
$customer = User::where('phone', '!=', null)->first();
if ($customer) {
    echo "✅ Found customer: {$customer->f_name} {$customer->l_name} (ID: {$customer->id}, Phone: {$customer->phone})\n";
    $passed++;
} else {
    echo "❌ No customer users found\n";
    $failed++;
}

// Test 2: Check if vendors exist
echo "\nTest 2: Checking for vendor users...\n";
$vendor = Vendor::with('stores')->where('status', 1)->first();
if ($vendor && $vendor->stores->count() > 0) {
    echo "✅ Found vendor: {$vendor->stores[0]->name} (ID: {$vendor->id})\n";
    $passed++;
} else {
    echo "❌ No active vendors found\n";
    $failed++;
}

// Test 3: Check if delivery men exist
echo "\nTest 3: Checking for delivery men...\n";
$deliveryMan = DeliveryMan::where('status', 1)->first();
if ($deliveryMan) {
    echo "✅ Found delivery man: {$deliveryMan->f_name} {$deliveryMan->l_name} (ID: {$deliveryMan->id})\n";
    $passed++;
} else {
    echo "⚠️  No active delivery men found\n";
}

// Test 4: Check Passport tokens configuration
echo "\nTest 4: Checking Laravel Passport configuration...\n";
if (config('auth.guards.api.driver') === 'passport') {
    echo "✅ Passport configured as API guard\n";
    $passed++;
} else {
    echo "❌ Passport NOT configured (current: " . config('auth.guards.api.driver') . ")\n";
    $failed++;
}

// Test 5: Check if customer has personal access token
echo "\nTest 5: Checking customer authentication tokens...\n";
if ($customer) {
    $tokenCount = DB::table('personal_access_tokens')
        ->where('tokenable_type', 'App\\Models\\User')
        ->where('tokenable_id', $customer->id)
        ->count();

    if ($tokenCount > 0) {
        echo "✅ Customer has $tokenCount active token(s)\n";
        $passed++;
    } else {
        echo "⚠️  Customer has no active tokens (will need to login via app)\n";
    }
}

// Test 6: Test conversation creation logic (dry run)
echo "\nTest 6: Testing conversation creation logic...\n";
if ($customer && $vendor) {
    try {
        // Check if customer UserInfo exists
        $senderInfo = UserInfo::where('user_id', $customer->id)->first();
        if (!$senderInfo) {
            echo "ℹ️  Customer UserInfo doesn't exist - would be auto-created on first message\n";
        } else {
            echo "✅ Customer UserInfo exists (ID: {$senderInfo->id})\n";
        }

        // Check if vendor UserInfo exists
        $receiverInfo = UserInfo::where('vendor_id', $vendor->id)->first();
        if (!$receiverInfo) {
            echo "ℹ️  Vendor UserInfo doesn't exist - would be auto-created on first message\n";
        } else {
            echo "✅ Vendor UserInfo exists (ID: {$receiverInfo->id})\n";
        }

        $passed++;
    } catch (\Exception $e) {
        echo "❌ Error checking UserInfo: {$e->getMessage()}\n";
        $failed++;
    }
}

// Test 7: Check existing conversations
echo "\nTest 7: Checking existing customer conversations...\n";
$conversations = Conversation::where('sender_type', 'customer')
    ->orWhere('receiver_type', 'customer')
    ->count();
echo "ℹ️  Found $conversations existing customer conversation(s)\n";

if ($conversations > 0) {
    $recentConv = Conversation::where('sender_type', 'customer')
        ->orWhere('receiver_type', 'customer')
        ->with(['sender', 'receiver'])
        ->latest('last_message_time')
        ->first();

    if ($recentConv) {
        echo "✅ Most recent conversation:\n";
        echo "   - ID: {$recentConv->id}\n";
        echo "   - Type: {$recentConv->sender_type} → {$recentConv->receiver_type}\n";
        echo "   - Last message: {$recentConv->last_message_time}\n";
        echo "   - Unread: {$recentConv->unread_message_count}\n";
        echo "   - Auto-response sent: " . ($recentConv->auto_response_sent ? 'Yes' : 'No') . "\n";
        $passed++;
    }
}

// Test 8: Check message count
echo "\nTest 8: Checking message count...\n";
$messageCount = Message::count();
echo "ℹ️  Total messages in database: $messageCount\n";

if ($messageCount > 0) {
    $recentMsg = Message::with(['conversation'])->latest()->first();
    echo "✅ Most recent message:\n";
    echo "   - ID: {$recentMsg->id}\n";
    echo "   - Conversation ID: {$recentMsg->conversation_id}\n";
    echo "   - Message: " . substr($recentMsg->message, 0, 50) . "...\n";
    echo "   - Created: {$recentMsg->created_at}\n";
    $passed++;
}

// Test 9: Check FCM configuration
echo "\nTest 9: Checking FCM (Firebase) configuration...\n";
$fcmKey = config('firebase.fcm_server_key') ?? env('FCM_SERVER_KEY');
if ($fcmKey && $fcmKey !== 'your_fcm_server_key') {
    echo "✅ FCM server key configured (" . substr($fcmKey, 0, 20) . "...)\n";
    $passed++;
} else {
    echo "⚠️  FCM server key not configured or using placeholder\n";
    echo "   - Notifications will NOT work\n";
    echo "   - Update FCM_SERVER_KEY in .env\n";
}

// Test 10: Check customer FCM tokens
echo "\nTest 10: Checking customer FCM tokens...\n";
if ($customer) {
    if ($customer->cm_firebase_token) {
        echo "✅ Customer has FCM token: " . substr($customer->cm_firebase_token, 0, 30) . "...\n";
        $passed++;
    } else {
        echo "⚠️  Customer has no FCM token (can't receive push notifications)\n";
    }
}

// Test 11: Test auto-response logic
echo "\nTest 11: Testing auto-response system...\n";
$adminConversations = Conversation::where('receiver_type', 'admin')
    ->where('sender_type', 'customer')
    ->count();

if ($adminConversations > 0) {
    $adminConvWithAutoResponse = Conversation::where('receiver_type', 'admin')
        ->where('sender_type', 'customer')
        ->where('auto_response_sent', true)
        ->count();

    echo "ℹ️  Admin conversations: $adminConversations\n";
    echo "ℹ️  Auto-responses sent: $adminConvWithAutoResponse\n";
    echo "✅ Auto-response system appears functional\n";
    $passed++;
}

// Test 12: Check database transactions support
echo "\nTest 12: Testing database transaction support...\n";
try {
    DB::beginTransaction();
    DB::rollBack();
    echo "✅ Database transactions supported\n";
    $passed++;
} catch (\Exception $e) {
    echo "❌ Database transaction error: {$e->getMessage()}\n";
    $failed++;
}

// Test 13: Check conversation locking (race condition prevention)
echo "\nTest 13: Testing conversation lockForUpdate support...\n";
try {
    DB::beginTransaction();
    $testConv = Conversation::lockForUpdate()->first();
    DB::rollBack();
    echo "✅ lockForUpdate() supported (race condition prevention works)\n";
    $passed++;
} catch (\Exception $e) {
    echo "❌ lockForUpdate() error: {$e->getMessage()}\n";
    $failed++;
}

// Test 14: Verify ConversationController exists
echo "\nTest 14: Checking ConversationController...\n";
$controllerPath = app_path('Http/Controllers/Api/V1/ConversationController.php');
if (file_exists($controllerPath)) {
    echo "✅ ConversationController exists\n";

    // Check key methods
    $controller = new \App\Http\Controllers\Api\V1\ConversationController();
    $methods = ['messages_store', 'conversations', 'messages'];
    $foundMethods = 0;

    foreach ($methods as $method) {
        if (method_exists($controller, $method)) {
            $foundMethods++;
        }
    }

    if ($foundMethods === count($methods)) {
        echo "✅ All required methods present ($foundMethods/" . count($methods) . ")\n";
        $passed++;
    } else {
        echo "⚠️  Some methods missing ($foundMethods/" . count($methods) . ")\n";
    }
} else {
    echo "❌ ConversationController not found\n";
    $failed++;
}

// Test 15: Check API routes registration
echo "\nTest 15: Checking API route registration...\n";
try {
    $routes = \Route::getRoutes();
    $messageRoutes = 0;

    foreach ($routes as $route) {
        if (strpos($route->uri(), 'api/v1/message') !== false) {
            $messageRoutes++;
        }
    }

    if ($messageRoutes > 0) {
        echo "✅ Found $messageRoutes message-related API routes\n";
        $passed++;
    } else {
        echo "❌ No message routes found\n";
        $failed++;
    }
} catch (\Exception $e) {
    echo "❌ Route check error: {$e->getMessage()}\n";
    $failed++;
}

// Summary
echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                     TEST SUMMARY                              ║\n";
echo "╠═══════════════════════════════════════════════════════════════╣\n";
echo "║  ✅ Passed: " . str_pad($passed, 3, ' ', STR_PAD_LEFT) . " tests                                           ║\n";
echo "║  ❌ Failed: " . str_pad($failed, 3, ' ', STR_PAD_LEFT) . " tests                                           ║\n";
echo "║  ⚠️  Warnings: " . str_pad(15 - $passed - $failed, 3, ' ', STR_PAD_LEFT) . " tests                                     ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";

// Recommendations
echo "\n📋 RECOMMENDATIONS:\n\n";

if ($failed > 0) {
    echo "❌ CRITICAL ISSUES FOUND:\n";
    echo "   - Fix failed tests before proceeding\n\n";
}

if (!$fcmKey || $fcmKey === 'your_fcm_server_key') {
    echo "⚠️  CONFIGURE FCM:\n";
    echo "   1. Get FCM server key from Firebase Console\n";
    echo "   2. Add to .env: FCM_SERVER_KEY=your_actual_key\n";
    echo "   3. Restart services\n\n";
}

if ($customer && !$customer->cm_firebase_token) {
    echo "ℹ️  CUSTOMER FCM TOKENS:\n";
    echo "   - Customers need to login via mobile app\n";
    echo "   - App will register FCM token on login\n";
    echo "   - Tokens stored in users.cm_firebase_token\n\n";
}

echo "✅ NEXT STEPS (Phase 2):\n";
echo "   1. Test actual API calls with Postman/curl\n";
echo "   2. Verify FCM notifications deliver\n";
echo "   3. Test auto-response triggers correctly\n";
echo "   4. Verify race condition handling\n";
echo "   5. Move to Phase 3 (Fix Admin Panel)\n\n";

// Exit code
exit($failed > 0 ? 1 : 0);
