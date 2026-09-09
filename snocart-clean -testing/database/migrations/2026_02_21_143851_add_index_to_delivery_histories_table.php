<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds composite index to optimize delivery man location queries.
     * This fixes the N+1 query problem in auto-assign feature.
     */
    public function up(): void
    {
        Schema::table('delivery_histories', function (Blueprint $table) {
            // Composite index for queries like:
            // WHERE delivery_man_id = ? ORDER BY created_at DESC
            $table->index(['delivery_man_id', 'created_at'], 'idx_dm_location_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_histories', function (Blueprint $table) {
            $table->dropIndex('idx_dm_location_lookup');
        });
    }
};
