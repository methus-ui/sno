<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'barcode')) return;

            $table->index('barcode');
            $table->index('status');
            $table->index('slug');

            $table->index(['store_id', 'name']);
            $table->index(['store_id', 'name', 'barcode']);
            $table->index(['store_id', 'status', 'category_id']);
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['barcode']);
            $table->dropIndex(['status']);
            $table->dropIndex(['slug']);
            $table->dropIndex(['store_id', 'name']);
            $table->dropIndex(['store_id', 'name', 'barcode']);
            $table->dropIndex(['store_id', 'status', 'category_id']);
        });
    }

};
