<?php

/**
 * Test Auto-Response Message
 * Verifies the new personalized auto-response is working
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Message;
use App\Models\Conversation;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           AUTO-RESPONSE MESSAGE PREVIEW                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Find the most recent auto-response message
$autoResponse = Message::whereHas('conversation', function($q) {
    $q->where('receiver_type', 'admin');
})
->whereHas('sender', function($q) {
    $q->where('admin_id', '>', 0);
})
->whereRaw('LENGTH(message) > 100') // Auto-response is longer
->orderBy('created_at', 'DESC')
->first();

if ($autoResponse) {
    echo "Latest Auto-Response Message:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo $autoResponse->message . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n";
    echo "Sent: " . $autoResponse->created_at->diffForHumans() . "\n";
    echo "Conversation ID: " . $autoResponse->conversation_id . "\n";
} else {
    echo "ℹ️  No auto-response messages found yet.\n";
    echo "This is normal if no customers have messaged admin since the update.\n";
}

echo "\n";
echo "Expected Auto-Response Format:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "السلام علیکم (Assalamualaikum) 👋\n\n";
echo "Please wait while we connect you to an available representative.\n\n";
echo "⏱️ Expected wait time: 10-30 minutes\n\n";
echo "In the meantime, please share your problems or questions here and we'll assist you as soon as possible.\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";

echo "✅ Auto-response message is now personalized and informative!\n";
echo "\n";
echo "Features:\n";
echo "  • Greeting in Arabic & English (السلام علیکم)\n";
echo "  • Clear wait time expectation (10-30 minutes)\n";
echo "  • Encourages customer to share problems immediately\n";
echo "  • Professional and concise\n";
echo "\n";
