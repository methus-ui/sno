<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentAdjustmentFieldsToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('original_order_amount', 24, 2)->default(0)->after('order_amount');
            $table->decimal('adjustment_amount', 24, 2)->default(0)->after('original_order_amount');
            $table->decimal('cod_collection_amount', 24, 2)->default(0)->after('adjustment_amount');
            $table->boolean('wallet_refund_processed')->default(false)->after('cod_collection_amount');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['original_order_amount', 'adjustment_amount', 'cod_collection_amount', 'wallet_refund_processed']);
        });
    }
}
