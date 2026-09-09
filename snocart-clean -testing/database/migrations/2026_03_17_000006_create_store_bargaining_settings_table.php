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
        Schema::create('store_bargaining_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->unique()->constrained('stores')->onDelete('cascade');

            // Participation settings
            $table->boolean('bargaining_enabled')->default(true)->comment('Store participates in bargaining');
            $table->boolean('auto_participate')->default(true)->comment('Auto-calculate offers');
            $table->boolean('manual_bidding_enabled')->default(false)->comment('Allow manual counter-offers');

            // Notification preferences
            $table->boolean('notify_on_new_request')->default(false);
            $table->boolean('notify_on_award')->default(true);
            $table->string('notification_channel', 50)->default('push')->comment('push, sms, email');

            // Auto-discount rules
            $table->decimal('auto_discount_percentage', 5, 2)->default(0)->comment('Auto-apply discount %');
            $table->decimal('min_order_value_for_discount', 24, 2)->default(0);
            $table->decimal('max_discount_amount', 24, 2)->nullable()->comment('Cap on discount');

            // Competitive settings
            $table->boolean('always_match_lowest_price')->default(false);
            $table->decimal('price_match_buffer', 5, 2)->default(0)->comment('% below lowest to auto-bid');

            // Restrictions
            $table->integer('max_concurrent_bargains')->default(50);
            $table->integer('min_cart_value')->default(0);
            $table->integer('max_cart_items')->default(100);

            // Business hours for manual bidding
            $table->time('bidding_start_time')->nullable();
            $table->time('bidding_end_time')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('bargaining_enabled', 'idx_enabled');
            $table->index('auto_participate', 'idx_auto_participate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_bargaining_settings');
    }
};
