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
        Schema::create('bargaining_item_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bargaining_cart_item_id')->constrained('bargaining_cart_items')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');

            // Matched item
            $table->foreignId('matched_item_id')->nullable()->constrained('items')->onDelete('set null');
            $table->foreignId('matched_campaign_id')->nullable()->constrained('campaigns')->onDelete('set null');
            $table->string('matched_type', 20)->default('item')->comment('item or campaign');

            // Match quality
            $table->enum('match_method', ['barcode_exact', 'name_fuzzy', 'manual'])->default('barcode_exact');
            $table->decimal('match_score', 5, 2)->default(100.00)->comment('Match quality 0-100');

            // Pricing snapshot at time of matching
            $table->decimal('base_price', 24, 2);
            $table->decimal('discounted_price', 24, 2);
            $table->decimal('discount_amount', 24, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);

            // Flash sale info
            $table->boolean('flash_sale_active')->default(false);
            $table->decimal('flash_sale_price', 24, 2)->nullable();
            $table->timestamp('flash_sale_ends_at')->nullable();

            // Availability
            $table->boolean('in_stock')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->integer('max_order_quantity')->nullable();

            // Store info snapshot
            $table->decimal('store_tax', 5, 2)->default(0);
            $table->decimal('store_discount', 24, 2)->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['bargaining_cart_item_id', 'store_id'], 'idx_cart_item_store');
            $table->index('matched_item_id', 'idx_matched_item');
            $table->index('store_id', 'idx_store');
            $table->index('match_score', 'idx_match_score');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bargaining_item_matches');
    }
};
