<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration 2 of 5: Message Reactions Table
     * Enables emoji reactions on messages (thumbs up, heart, laugh, sad, angry, wow)
     */
    public function up(): void
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->foreignId('user_info_id')->constrained('user_infos')->onDelete('cascade');
            $table->string('reaction_type', 20)->comment('thumbs_up, heart, laugh, sad, angry, wow');
            $table->timestamps();

            // Unique constraint: one reaction per user per message
            $table->unique(['message_id', 'user_info_id'], 'unique_user_reaction');

            // Index for fast lookups
            $table->index('message_id', 'idx_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
};
