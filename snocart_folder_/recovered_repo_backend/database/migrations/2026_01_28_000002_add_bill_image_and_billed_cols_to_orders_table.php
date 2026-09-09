<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('bill_image')->nullable()->after('order_proof');
            $table->boolean('is_billed')->default(false)->after('bill_image');
            $table->timestamp('billed_at')->nullable()->after('is_billed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['bill_image', 'is_billed', 'billed_at']);
        });
    }
};
