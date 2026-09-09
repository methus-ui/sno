<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->boolean('is_outside_purchase')->default(false)->after('total_add_on_price');
            $table->decimal('outside_purchase_cost', 24, 2)->nullable()->after('is_outside_purchase');
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['is_outside_purchase', 'outside_purchase_cost']);
        });
    }
};
