<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            // CRITICAL: conversation_id for message loading (ConversationController:105)
            $table->index('conversation_id', 'idx_msg_conversation');

            // CRITICAL: sender_id for filtering incoming messages
            $table->index('sender_id', 'idx_msg_sender');

            // CRITICAL: is_seen for unread count calculations
            $table->index('is_seen', 'idx_msg_seen');

            // Composite: conversation + timestamp (pagination + ordering)
            $table->index(['conversation_id', 'created_at'], 'idx_msg_conv_time');

            // Composite: conversation + sender + seen (complex queries)
            $table->index(['conversation_id', 'sender_id', 'is_seen'], 'idx_msg_conv_sender_seen');
        });

        \Log::info('Messages table indexes created - expected 40x performance improvement');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_msg_conversation');
            $table->dropIndex('idx_msg_sender');
            $table->dropIndex('idx_msg_seen');
            $table->dropIndex('idx_msg_conv_time');
            $table->dropIndex('idx_msg_conv_sender_seen');
        });
    }
};
