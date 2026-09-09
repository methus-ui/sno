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
        Schema::create('wa_inbound_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Matched customer
            $table->string('phone', 20);
            $table->text('message_text')->nullable();
            $table->string('media_url', 500)->nullable();
            $table->enum('media_type', ['image', 'video', 'audio', 'document'])->nullable();
            $table->string('whatsapp_message_id', 255);
            $table->unsignedBigInteger('campaign_id')->nullable(); // If replying to campaign
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->default('neutral');
            $table->json('tags')->nullable(); // Auto-tagged: order_inquiry, complaint, feedback, etc.
            $table->unsignedBigInteger('assigned_to')->nullable(); // Admin ID
            $table->enum('status', ['unread', 'read', 'replied', 'archived'])->default('unread');
            $table->timestamp('received_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            // Indexes for inbox filtering
            $table->index(['status', 'received_at'], 'idx_status_received');
            $table->index('user_id', 'idx_user');
            $table->index('phone', 'idx_phone');
            $table->index('whatsapp_message_id', 'idx_message_id');
            $table->index('sentiment', 'idx_sentiment');
            $table->index('assigned_to', 'idx_assigned');
            $table->index('campaign_id', 'idx_campaign');

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('campaign_id')->references('id')->on('wa_campaigns')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wa_inbound_messages');
    }
};
