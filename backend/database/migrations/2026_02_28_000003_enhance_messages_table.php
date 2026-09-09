<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration 3 of 5: Enhance Messages Table
     * Adds soft deletes, edit tracking, threading support, and system messages
     * 100% backward compatible - all columns nullable or have defaults
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Soft deletes
            $table->timestamp('deleted_at')->nullable()->after('updated_at');

            // Edit tracking
            $table->foreignId('edited_by')->nullable()->after('deleted_at')->constrained('user_infos')->onDelete('set null');
            $table->timestamp('edited_at')->nullable()->after('edited_by');

            // System messages (automated messages)
            $table->boolean('is_system_message')->default(false)->after('edited_at');

            // Message threading (reply to message)
            $table->foreignId('reply_to_message_id')->nullable()->after('is_system_message')->constrained('messages')->onDelete('set null');

            // Indexes
            $table->index('deleted_at', 'idx_deleted_at');
            $table->index('reply_to_message_id', 'idx_reply_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['edited_by']);
            $table->dropForeign(['reply_to_message_id']);

            // Drop indexes
            $table->dropIndex('idx_deleted_at');
            $table->dropIndex('idx_reply_to');

            // Drop columns
            $table->dropColumn([
                'deleted_at',
                'edited_by',
                'edited_at',
                'is_system_message',
                'reply_to_message_id'
            ]);
        });
    }
};
