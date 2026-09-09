<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Customer behavior timeline after campaigns.
     * Tracks: campaign_sent → message_delivered → message_read → order_placed.
     */
    public function up(): void
    {
        Schema::create('wa_customer_journey', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('campaign_id');
            $table->enum('event_type', [
                'campaign_sent',
                'message_delivered',
                'message_read',
                'order_placed',
                'app_opened'
            ]);
            $table->json('event_data')->nullable(); // Additional context
            $table->timestamp('event_at')->useCurrent();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('wa_campaigns')->onDelete('cascade');

            // Indexes for timeline queries
            $table->index(['user_id', 'campaign_id'], 'idx_user_campaign');
            $table->index(['event_type', 'event_at'], 'idx_event_type_time');
            $table->index('campaign_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_customer_journey');
    }
};
