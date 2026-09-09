<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Add composite index on sender_id and receiver_id for improved query performance
            // This index optimizes the whereConversation scope which frequently queries these columns
            $table->index(['sender_id', 'receiver_id'], 'conversations_sender_receiver_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Drop the composite index
            $table->dropIndex('conversations_sender_receiver_index');
        });
    }
};
