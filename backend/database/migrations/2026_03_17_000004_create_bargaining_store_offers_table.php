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
        Schema::create('bargaining_store_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bargaining_request_id')->constrained('bargaining_requests')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');

            // Offer type
            $table->enum('offer_type', ['auto_calculated', 'vendor_submitted'])->default('auto_calculated');

            // Fulfillment tracking (CRITICAL for "item not available" handling)
            $table->integer('items_available')->default(0);
            $table->integer('items_missing')->default(0);
            $table->decimal('fulfillment_percentage', 5, 2)->default(0)->comment('% of cart items available');

            // Pricing breakdown
            $table->decimal('subtotal', 24, 2)->default(0);
            $table->decimal('item_discount', 24, 2)->default(0)->comment('Sum of item-level discounts');
            $table->decimal('store_discount', 24, 2)->default(0)->comment('Store-wide discount');
            $table->decimal('flash_sale_discount', 24, 2)->default(0);
            $table->decimal('coupon_discount', 24, 2)->default(0);
            $table->decimal('tax_amount', 24, 2)->default(0);
            $table->decimal('delivery_charge', 24, 2)->default(0);
            $table->decimal('packaging_charge', 24, 2)->default(0);
            $table->decimal('total_amount', 24, 2)->default(0);

            // Vendor-specific fields (for manual counter-offers)
            $table->decimal('special_discount', 24, 2)->nullable()->comment('Extra discount offered by vendor');
            $table->text('vendor_notes')->nullable();
            $table->integer('estimated_delivery_time')->nullable()->comment('Minutes');
            $table->foreignId('submitted_by_employee_id')->nullable()->constrained('vendor_employees')->onDelete('set null');

            // Ranking
            $table->integer('rank')->default(999)->comment('Offer ranking - 1 is best');
            $table->boolean('is_best_offer')->default(false);

            // Status
            $table->enum('status', ['draft', 'submitted', 'withdrawn', 'accepted', 'rejected', 'expired'])->default('submitted');

            // Missing items detail
            $table->json('missing_items_detail')->nullable()->comment('List of missing item names');
            $table->json('substitutes_suggested')->nullable()->comment('Vendor-suggested substitutes');

            // Timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            // Unique constraint - prevent duplicate offers
            $table->unique(['bargaining_request_id', 'store_id', 'offer_type'], 'unique_offer');

            // Indexes
            $table->index(['bargaining_request_id', 'rank'], 'idx_request_rank');
            $table->index(['bargaining_request_id', 'is_best_offer'], 'idx_request_best');
            $table->index(['store_id', 'status'], 'idx_store_status');
            $table->index('fulfillment_percentage', 'idx_fulfillment');
            $table->index('total_amount', 'idx_total_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bargaining_store_offers');
    }
};
