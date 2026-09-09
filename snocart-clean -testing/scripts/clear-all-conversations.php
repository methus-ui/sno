<?php

/**
 * Clear All Conversations Script
 * WARNING: This will delete ALL conversations and messages
 * Use with caution!
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     CLEAR ALL CONVERSATIONS - CONFIRMATION REQUIRED        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Get counts before deletion
$conversationCount = DB::table('conversations')->count();
$messageCount = DB::table('messages')->count();

echo "Current Database Status:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total Conversations: " . number_format($conversationCount) . "\n";
echo "Total Messages: " . number_format($messageCount) . "\n";
echo "\n";

if ($conversationCount === 0 && $messageCount === 0) {
    echo "✅ No conversations or messages to delete.\n";
    exit(0);
}

echo "⚠️  WARNING: This will permanently delete:\n";
echo "   • All " . number_format($conversationCount) . " conversations\n";
echo "   • All " . number_format($messageCount) . " messages\n";
echo "   • All chat history between customers, vendors, admins, and delivery men\n";
echo "\n";
echo "This action CANNOT be undone!\n";
echo "\n";
echo "Type 'DELETE ALL' to confirm (case-sensitive): ";

$confirmation = trim(fgets(STDIN));

if ($confirmation !== 'DELETE ALL') {
    echo "\n";
    echo "❌ Deletion cancelled. No data was deleted.\n";
    exit(1);
}

echo "\n";
echo "Starting deletion process...\n";
echo "\n";

DB::beginTransaction();

try {
    // Delete all messages first (foreign key constraint)
    echo "🗑️  Deleting messages...";
    $deletedMessages = DB::table('messages')->delete();
    echo " ✅ Deleted " . number_format($deletedMessages) . " messages\n";

    // Delete all conversations
    echo "🗑️  Deleting conversations...";
    $deletedConversations = DB::table('conversations')->delete();
    echo " ✅ Deleted " . number_format($deletedConversations) . " conversations\n";

    DB::commit();

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║                   DELETION COMPLETE                        ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Summary:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Deleted " . number_format($deletedConversations) . " conversations\n";
    echo "✅ Deleted " . number_format($deletedMessages) . " messages\n";
    echo "✅ Database is now clean\n";
    echo "\n";
    echo "All chat history has been permanently removed.\n";
    echo "Users can start fresh conversations now.\n";
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n";
    echo "❌ ERROR: Deletion failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Transaction rolled back. No data was deleted.\n";
    exit(1);
}
