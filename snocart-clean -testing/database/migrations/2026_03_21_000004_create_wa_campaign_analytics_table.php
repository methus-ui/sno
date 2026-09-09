<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Campaign performance metrics and ROI tracking.
     * Calculated after campaign completes + tracks re-engagement orders.
     */
    public function up(): void
    {
        Schema::create('wa_campaign_analytics', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->primary();

            // Delivery metrics
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('read_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->decimal('delivery_rate', 5, 2)->default(0); // %
            $table->decimal('read_rate', 5, 2)->default(0); // %

            // Re-engagement metrics (orders placed after campaign)
            $table->integer('orders_placed_24h')->default(0);
            $table->integer('orders_placed_7d')->default(0);
            $table->integer('orders_placed_30d')->default(0);
            $table->decimal('revenue_generated_24h', 10, 2)->default(0);
            $table->decimal('revenue_generated_7d', 10, 2)->default(0);
            $table->decimal('revenue_generated_30d', 10, 2)->default(0);

            // Conversion metrics
            $table->decimal('conversion_rate_24h', 5, 2)->default(0); // %
            $table->decimal('conversion_rate_7d', 5, 2)->default(0); // %
            $table->decimal('avg_order_value', 10, 2)->default(0);
            $table->decimal('roi', 10, 2)->default(0); // Return on Investment %

            // Performance metrics
            $table->integer('processing_time_seconds')->default(0);
            $table->timestamp('calculated_at')->nullable();

            // Foreign key
            $table->foreign('campaign_id')->references('id')->on('wa_campaigns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_campaign_analytics');
    }
};
