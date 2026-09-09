<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('outside_purchase_amount', 24, 2)->default(0)->after('extra_packaging_amount');
        });

        Schema::table('order_transactions', function (Blueprint $table) {
            $table->decimal('outside_purchase_amount', 24, 2)->default(0)->after('extra_packaging_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('outside_purchase_amount');
        });

        Schema::table('order_transactions', function (Blueprint $table) {
            $table->dropColumn('outside_purchase_amount');
        });
    }
};
