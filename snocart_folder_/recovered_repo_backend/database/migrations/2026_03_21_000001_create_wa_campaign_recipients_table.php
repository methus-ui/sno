<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per-message tracking for WhatsApp campaigns.
     * Tracks individual message status (sent/delivered/read/failed).
     */
    public function up(): void
    {
        Schema::create('wa_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('user_id');
            $table->string('phone', 20);
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->string('whatsapp_message_id', 100)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('campaign_id')->references('id')->on('wa_campaigns')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for performance
            $table->index(['campaign_id', 'status'], 'idx_campaign_status');
            $table->index(['user_id', 'campaign_id'], 'idx_user_campaign');
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_campaign_recipients');
    }
};
