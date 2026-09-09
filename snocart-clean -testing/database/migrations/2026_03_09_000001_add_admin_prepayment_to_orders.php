<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminPrepaymentToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Quick flag for filtering
            $table->boolean('store_amount_prepaid')->default(false)->after('payment_confirmed_at');

            // Item amount store paid
            $table->decimal('prepaid_item_amount', 24, 2)->nullable()->after('store_amount_prepaid');

            // DM fees to collect
            $table->decimal('prepaid_delivery_fees', 24, 2)->nullable()->after('prepaid_item_amount');

            // When marked
            $table->timestamp('prepaid_at')->nullable()->after('prepaid_delivery_fees');

            // Admin ID who marked
            $table->bigInteger('prepaid_by')->unsigned()->nullable()->after('prepaid_at');

            // Optional notes
            $table->text('prepaid_notes')->nullable()->after('prepaid_by');

            // Foreign key constraint
            $table->foreign('prepaid_by')->references('id')->on('admins')->onDelete('set null');

            // Indexes for performance
            $table->index('store_amount_prepaid');
            $table->index('prepaid_at');
            $table->index('prepaid_by');
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
            // Drop foreign key first
            $table->dropForeign(['prepaid_by']);

            // Drop indexes
            $table->dropIndex(['store_amount_prepaid']);
            $table->dropIndex(['prepaid_at']);
            $table->dropIndex(['prepaid_by']);

            // Drop columns
            $table->dropColumn([
                'store_amount_prepaid',
                'prepaid_item_amount',
                'prepaid_delivery_fees',
                'prepaid_at',
                'prepaid_by',
                'prepaid_notes',
            ]);
        });
    }
}
