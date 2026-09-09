<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, remove duplicate conversations (keeping the oldest one)
        DB::statement("
            DELETE c1 FROM conversations c1
            INNER JOIN conversations c2
            WHERE c1.id > c2.id
            AND (
                (c1.sender_id = c2.sender_id AND c1.receiver_id = c2.receiver_id
                 AND c1.sender_type = c2.sender_type AND c1.receiver_type = c2.receiver_type)
                OR
                (c1.sender_id = c2.receiver_id AND c1.receiver_id = c2.sender_id
                 AND c1.sender_type = c2.receiver_type AND c1.receiver_type = c2.sender_type)
            )
        ");

        // Add composite index for faster lookups
        Schema::table('conversations', function (Blueprint $table) {
            // Index for finding conversations between two users (bidirectional)
            $table->index(['sender_id', 'receiver_id', 'sender_type', 'receiver_type'], 'idx_conversation_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('idx_conversation_lookup');
        });
    }
};
