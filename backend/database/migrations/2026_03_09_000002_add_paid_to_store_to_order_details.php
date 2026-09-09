<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaidToStoreToOrderDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_details', function (Blueprint $table) {
            // Track if item amount has been paid to store by delivery boy
            $table->boolean('item_amount_paid_to_store')->default(false)->after('mrp_update_status');

            // When marked as paid
            $table->timestamp('paid_to_store_at')->nullable()->after('item_amount_paid_to_store');

            // Admin who marked it
            $table->bigInteger('paid_to_store_by')->unsigned()->nullable()->after('paid_to_store_at');

            // Foreign key constraint
            $table->foreign('paid_to_store_by')->references('id')->on('admins')->onDelete('set null');

            // Index for filtering
            $table->index('item_amount_paid_to_store');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_details', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['paid_to_store_by']);

            // Drop index
            $table->dropIndex(['item_amount_paid_to_store']);

            // Drop columns
            $table->dropColumn([
                'item_amount_paid_to_store',
                'paid_to_store_at',
                'paid_to_store_by',
            ]);
        });
    }
}
