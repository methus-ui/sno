<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PERFORMANCE FIX - 2026-02-08
     * Adds critical indexes to fix slow query issues:
     * - carts table: composite index for frequent WHERE conditions
     * - delivery_histories table: unique index for delivery_man_id to optimize INSERT...ON DUPLICATE KEY UPDATE
     */
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // Add composite index for the most common query pattern
            // This fixes the 4-16 second cart update queries
            if (!$this->indexExists('carts', 'carts_user_guest_module_idx')) {
                $table->index(['user_id', 'is_guest', 'module_id'], 'carts_user_guest_module_idx');
            }
        });

        // Check if delivery_histories table has a unique constraint on delivery_man_id
        // If not, add it to optimize INSERT...ON DUPLICATE KEY UPDATE
        if (!$this->indexExists('delivery_histories', 'delivery_histories_delivery_man_id_unique')) {
            // First, remove any duplicate delivery_man_id records (keep latest)
            DB::statement("
                DELETE t1 FROM delivery_histories t1
                INNER JOIN delivery_histories t2
                WHERE t1.delivery_man_id = t2.delivery_man_id
                  AND t1.id < t2.id
            ");

            Schema::table('delivery_histories', function (Blueprint $table) {
                $table->unique('delivery_man_id', 'delivery_histories_delivery_man_id_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if ($this->indexExists('carts', 'carts_user_guest_module_idx')) {
                $table->dropIndex('carts_user_guest_module_idx');
            }
        });

        Schema::table('delivery_histories', function (Blueprint $table) {
            if ($this->indexExists('delivery_histories', 'delivery_histories_delivery_man_id_unique')) {
                $table->dropUnique('delivery_histories_delivery_man_id_unique');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};
