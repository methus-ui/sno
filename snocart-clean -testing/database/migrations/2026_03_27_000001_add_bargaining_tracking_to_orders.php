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
        Schema::table('orders', function (Blueprint $table) {
            // Add bargaining tracking columns after order_type
            $table->foreignId('bargaining_request_id')->nullable()->after('order_type')
                ->constrained('bargaining_requests')->onDelete('set null');
            $table->foreignId('bargaining_accepted_offer_id')->nullable()->after('bargaining_request_id')
                ->constrained('bargaining_store_offers')->onDelete('set null');
            $table->boolean('is_bargaining_order')->default(false)->after('bargaining_accepted_offer_id');

            // Indexes for performance
            $table->index('bargaining_request_id', 'idx_bargaining_request');
            $table->index('is_bargaining_order', 'idx_is_bargaining');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_is_bargaining');
            $table->dropIndex('idx_bargaining_request');

            // Drop foreign keys
            $table->dropForeign(['bargaining_request_id']);
            $table->dropForeign(['bargaining_accepted_offer_id']);

            // Drop columns
            $table->dropColumn(['bargaining_request_id', 'bargaining_accepted_offer_id', 'is_bargaining_order']);
        });
    }
};
