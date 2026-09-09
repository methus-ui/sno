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
        Schema::create('bargaining_offer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bargaining_store_offer_id')->constrained('bargaining_store_offers')->onDelete('cascade');
            $table->foreignId('bargaining_cart_item_id')->constrained('bargaining_cart_items')->onDelete('cascade');

            // Matched item (if available at this store)
            $table->foreignId('matched_item_id')->nullable()->constrained('items')->onDelete('set null');
            $table->foreignId('matched_campaign_id')->nullable()->constrained('campaigns')->onDelete('set null');
            $table->string('matched_type', 20)->default('item');

            // Availability flag (CRITICAL)
            $table->boolean('is_available')->default(true);

            // Substitute suggestion
            $table->boolean('substitute_suggested')->default(false);
            $table->foreignId('substitute_item_id')->nullable()->constrained('items')->onDelete('set null');
            $table->text('substitute_notes')->nullable();

            // Pricing
            $table->integer('quantity');
            $table->decimal('unit_price', 24, 2)->comment('Base price per unit');
            $table->decimal('discount_per_unit', 24, 2)->default(0);
            $table->decimal('final_price_per_unit', 24, 2)->comment('After discount');
            $table->decimal('line_total', 24, 2)->comment('final_price_per_unit * quantity');

            // Flash sale
            $table->boolean('flash_sale_applied')->default(false);
            $table->decimal('flash_sale_savings', 24, 2)->default(0);

            // Variations/Add-ons pricing
            $table->json('variation_prices')->nullable();
            $table->json('addon_prices')->nullable();
            $table->decimal('variation_total', 24, 2)->default(0);
            $table->decimal('addon_total', 24, 2)->default(0);

            $table->timestamps();

            // Indexes
            $table->index('bargaining_store_offer_id', 'idx_offer_id');
            $table->index('bargaining_cart_item_id', 'idx_cart_item_id');
            $table->index('is_available', 'idx_availability');
            $table->index('matched_item_id', 'idx_matched_item');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bargaining_offer_items');
    }
};
