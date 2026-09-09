<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration 4 of 5: Enhance Conversations Table
     * Adds typing indicators, soft deletes, and muting support
     * 100% backward compatible - all columns nullable or have defaults
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Soft deletes
            $table->timestamp('deleted_at')->nullable()->after('updated_at');

            // Typing indicators
            $table->timestamp('last_typing_at')->nullable()->after('deleted_at');
            $table->foreignId('last_typing_by')->nullable()->after('last_typing_at')->constrained('user_infos')->onDelete('set null');

            // Conversation muting
            $table->boolean('is_muted')->default(false)->after('last_typing_by');
            $table->timestamp('muted_until')->nullable()->after('is_muted')->comment('NULL = muted indefinitely, timestamp = muted until date');

            // Indexes
            $table->index('deleted_at', 'idx_deleted_at');
            $table->index('last_typing_at', 'idx_typing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['last_typing_by']);

            // Drop indexes
            $table->dropIndex('idx_deleted_at');
            $table->dropIndex('idx_typing');

            // Drop columns
            $table->dropColumn([
                'deleted_at',
                'last_typing_at',
                'last_typing_by',
                'is_muted',
                'muted_until'
            ]);
        });
    }
};
