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
        Schema::create('wa_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('event_type', 50); // sent, delivered, read, failed
            $table->string('whatsapp_message_id', 255)->nullable();
            $table->string('phone', 20);
            $table->timestamp('event_timestamp');
            $table->json('raw_payload')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes for fast lookups
            $table->index(['campaign_id', 'event_type'], 'idx_campaign_event');
            $table->index('whatsapp_message_id', 'idx_message_id');
            $table->index('event_timestamp', 'idx_timestamp');
            $table->index('phone', 'idx_phone');
            $table->index('processed_at', 'idx_processed');

            // Foreign keys
            $table->foreign('campaign_id')->references('id')->on('wa_campaigns')->onDelete('cascade');
            $table->foreign('recipient_id')->references('id')->on('wa_campaign_recipients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wa_webhook_events');
    }
};
