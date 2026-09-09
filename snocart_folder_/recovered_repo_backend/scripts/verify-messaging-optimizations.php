#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "=== MESSAGING SYSTEM OPTIMIZATION VERIFICATION ===\n\n";

// Test 1: Check database indexes
echo "1. Database Indexes Check:\n";
try {
    $indexes = DB::select("SHOW INDEX FROM messages WHERE Key_name LIKE 'idx_msg_%'");
    if (count($indexes) >= 5) {
        echo "   ✓ All 5 critical indexes created\n";
        foreach ($indexes as $idx) {
            echo "     - {$idx->Key_name} on {$idx->Column_name}\n";
        }
    } else {
        echo "   ✗ FAILED: Only " . count($indexes) . " indexes found (expected 5)\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Check config file exists
echo "2. Configuration File Check:\n";
if (file_exists(__DIR__ . '/../config/messaging_performance.php')) {
    echo "   ✓ messaging_performance.php exists\n";
    $config = config('messaging_performance');
    echo "     - Enabled: " . ($config['enabled'] ? 'Yes' : 'No') . "\n";
    echo "     - Messages per page: " . $config['messages_per_page'] . "\n";
    echo "     - Template cache TTL: " . $config['template_cache_ttl'] . "s\n";
} else {
    echo "   ✗ FAILED: Config file missing\n";
}
echo "\n";

// Test 3: Check route exists
echo "3. Route Check:\n";
try {
    $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('admin.message.get-messages');
    if ($route) {
        echo "   ✓ admin.message.get-messages route registered\n";
        echo "     - URI: " . $route->uri() . "\n";
        echo "     - Method: " . implode(',', $route->methods()) . "\n";
    } else {
        echo "   ✗ FAILED: Route not found\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Check controller method exists
echo "4. Controller Method Check:\n";
if (method_exists(\App\Http\Controllers\Admin\ConversationController::class, 'getMessages')) {
    echo "   ✓ ConversationController::getMessages() exists\n";
} else {
    echo "   ✗ FAILED: Method not found\n";
}
echo "\n";

// Test 5: Database performance comparison
echo "5. Query Performance Test:\n";
try {
    $conversationId = DB::table('conversations')->where('sender_type', 'admin')->orWhere('receiver_type', 'admin')->value('id');
    if ($conversationId) {
        // Test indexed query
        $start = microtime(true);
        $count = DB::table('messages')
            ->where('conversation_id', $conversationId)
            ->count();
        $duration = (microtime(true) - $start) * 1000;

        echo "   ✓ Indexed query completed in " . round($duration, 2) . "ms\n";
        echo "     - Found {$count} messages\n";

        if ($duration < 10) {
            echo "   ✓ EXCELLENT: Query is very fast (< 10ms)\n";
        } elseif ($duration < 50) {
            echo "   ✓ GOOD: Query is fast (< 50ms)\n";
        } else {
            echo "   ⚠ WARNING: Query is slow (> 50ms) - may need more optimization\n";
        }
    } else {
        echo "   ℹ No test data available\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Cache functionality
echo "6. Template Cache Test:\n";
try {
    Cache::put('test_messaging_cache', 'test_value', 60);
    $value = Cache::get('test_messaging_cache');
    if ($value === 'test_value') {
        echo "   ✓ Cache is working\n";
        Cache::forget('test_messaging_cache');
    } else {
        echo "   ✗ FAILED: Cache not working properly\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Check backup scripts
echo "7. Backup Scripts Check:\n";
$backupScript = __DIR__ . '/backup-before-messaging-optimization.sh';
$rollbackScript = __DIR__ . '/rollback-messaging-optimizations.sh';

if (file_exists($backupScript) && is_executable($backupScript)) {
    echo "   ✓ Backup script exists and is executable\n";
} else {
    echo "   ✗ FAILED: Backup script missing or not executable\n";
}

if (file_exists($rollbackScript) && is_executable($rollbackScript)) {
    echo "   ✓ Rollback script exists and is executable\n";
} else {
    echo "   ✗ FAILED: Rollback script missing or not executable\n";
}
echo "\n";

// Test 8: Statistics
echo "8. System Statistics:\n";
try {
    $totalConversations = DB::table('conversations')->where('sender_type', 'admin')->orWhere('receiver_type', 'admin')->count();
    $totalMessages = DB::table('messages')->count();
    $avgMessagesPerConv = $totalConversations > 0 ? round($totalMessages / $totalConversations, 1) : 0;

    echo "   - Total conversations: {$totalConversations}\n";
    echo "   - Total messages: {$totalMessages}\n";
    echo "   - Avg messages per conversation: {$avgMessagesPerConv}\n";

    // Calculate expected performance improvement
    $expectedImprovement = 73; // From plan
    echo "\n   Expected performance improvement: {$expectedImprovement}%\n";
    echo "   - Page load: 1.5s → 0.4s\n";
    echo "   - Message view: 800ms → 150ms\n";
    echo "   - Search: 650ms → 80ms\n";
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== VERIFICATION COMPLETE ===\n";
echo "\nNext Steps:\n";
echo "1. Run: bash scripts/backup-before-messaging-optimization.sh\n";
echo "2. Clear cache: php artisan cache:clear && php artisan config:clear\n";
echo "3. Test the messaging system at /admin/message/list\n";
echo "4. Monitor Laravel logs for any errors\n";
echo "\nRollback if needed:\n";
echo "bash scripts/rollback-messaging-optimizations.sh\n";
