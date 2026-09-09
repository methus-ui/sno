<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEditTrackingToOrderTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_transactions', function (Blueprint $table) {
            // Edit status
            $table->boolean('is_edited')->default(false)->after('status');

            // Preserve original amounts (populated on first edit)
            $table->decimal('original_order_amount', 24, 2)->nullable()->after('is_edited');
            $table->decimal('original_store_amount', 24, 2)->nullable()->after('original_order_amount');
            $table->decimal('original_admin_commission', 24, 2)->nullable()->after('original_store_amount');

            // Track cumulative changes
            $table->decimal('total_adjustment', 24, 2)->default(0)->after('original_admin_commission');
            $table->integer('edit_count')->default(0)->after('total_adjustment');

            // Audit trail
            $table->timestamp('last_edited_at')->nullable()->after('edit_count');
            $table->unsignedBigInteger('last_edited_by')->nullable()->after('last_edited_at');
            $table->json('edit_history')->nullable()->after('last_edited_by');

            // Performance indexes
            $table->index('is_edited');
            $table->index('last_edited_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_transactions', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['is_edited']);
            $table->dropIndex(['last_edited_at']);

            // Drop columns
            $table->dropColumn([
                'is_edited',
                'original_order_amount',
                'original_store_amount',
                'original_admin_commission',
                'total_adjustment',
                'edit_count',
                'last_edited_at',
                'last_edited_by',
                'edit_history',
            ]);
        });
    }
}
