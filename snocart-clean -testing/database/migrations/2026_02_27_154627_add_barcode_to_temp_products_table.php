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
        Schema::table('temp_products', function (Blueprint $table) {
            // Add barcode column to match items table structure
            $table->string('barcode', 100)->nullable()->after('is_halal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_products', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });
    }
};
