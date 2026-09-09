<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration 1 of 5: Message Delivery Status Table
     * Tracks delivery attempts and status for messages with automatic retry logic
     */
    public function up(): void
    {
        Schema::create('message_delivery_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'read'])->default('pending');
            $table->string('delivery_channel', 50)->nullable()->comment('pusher, fcm, database');
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable()->comment('FCM token, Pusher channel info, etc.');
            $table->timestamps();

            // Indexes for performance
            $table->index(['message_id', 'status'], 'idx_message_status');
            $table->index('status', 'idx_status');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_delivery_status');
    }
};
