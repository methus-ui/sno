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
        Schema::create('bargaining_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code', 20)->unique()->comment('BR-XXXXXX format');

            // Customer identification
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('guest_id', 100)->nullable();

            // Zone and module context
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');

            // Cart snapshot
            $table->json('original_cart_snapshot')->comment('Cart state at bargaining start');
            $table->decimal('original_cart_value', 24, 2)->default(0);
            $table->integer('total_cart_items')->default(0);

            // Delivery info
            $table->json('delivery_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Bargaining mode
            $table->enum('mode', ['instant', 'wait'])->default('instant');
            $table->decimal('customer_budget', 24, 2)->nullable();

            // Status tracking
            $table->enum('status', [
                'initiated',
                'matching',
                'matching_completed',
                'offers_received',
                'awarded',
                'accepted',
                'cancelled',
                'expired',
                'completed'
            ])->default('initiated');

            // Results
            $table->integer('total_stores_matched')->default(0);
            $table->integer('total_offers_received')->default(0);
            $table->foreignId('awarded_store_id')->nullable()->constrained('stores')->onDelete('set null');
            $table->foreignId('accepted_offer_id')->nullable()->constrained('bargaining_store_offers')->onDelete('set null');
            $table->decimal('final_price', 24, 2)->nullable();
            $table->decimal('total_savings', 24, 2)->nullable();

            // Timing
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['zone_id', 'module_id', 'status'], 'idx_zone_module_status');
            $table->index('request_code', 'idx_request_code');
            $table->index('created_at', 'idx_created_at');
            $table->index('status', 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bargaining_requests');
    }
};
