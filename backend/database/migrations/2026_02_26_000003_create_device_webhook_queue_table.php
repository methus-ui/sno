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
        Schema::create('device_webhook_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('vendor_sound_devices')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('webhook_url', 255)->comment('Target webhook URL');
            $table->json('payload')->comment('Webhook payload data');
            $table->integer('attempts')->default(0)->comment('Number of delivery attempts');
            $table->timestamp('last_attempt_at')->nullable()->comment('Last attempt timestamp');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending')->comment('Delivery status');
            $table->text('error_message')->nullable()->comment('Error details if failed');
            $table->timestamps();

            // Indexes for performance
            $table->index('status');
            $table->index(['device_id', 'created_at']);
            $table->index('order_id');
            $table->index('last_attempt_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_webhook_queue');
    }
};
