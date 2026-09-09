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
        Schema::create('bargaining_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bargaining_request_id')->constrained('bargaining_requests')->onDelete('cascade');

            // Original cart item data
            $table->foreignId('original_item_id')->nullable()->constrained('items')->onDelete('set null');
            $table->foreignId('original_campaign_id')->nullable()->constrained('campaigns')->onDelete('set null');
            $table->string('item_type', 20)->default('item')->comment('item or campaign');

            // Item details for matching
            $table->string('item_name');
            $table->string('item_barcode', 100)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('sub_category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->text('item_description')->nullable();

            // Quantity and pricing
            $table->integer('quantity')->default(1);
            $table->decimal('original_price', 24, 2);
            $table->decimal('original_discount', 24, 2)->default(0);

            // Variations/Add-ons
            $table->json('variations')->nullable();
            $table->json('add_ons')->nullable();

            // Matching results
            $table->enum('match_method', ['barcode', 'fuzzy', 'none'])->nullable();
            $table->integer('total_matches_found')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('bargaining_request_id', 'idx_request_id');
            $table->index('item_barcode', 'idx_barcode');
            $table->index('category_id', 'idx_category');
            $table->index('original_item_id', 'idx_original_item');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bargaining_cart_items');
    }
};
