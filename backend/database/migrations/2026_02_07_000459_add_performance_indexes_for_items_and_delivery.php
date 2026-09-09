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
     * Adds composite indexes to optimize slow queries identified in MySQL slow log:
     * 1. Items queries with multiple EXISTS subqueries (2-4 seconds)
     * 2. Delivery history updates with lock contention (2-7 seconds)
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Optimize queries filtering by status, is_approved, and stock
            // Used in: SELECT * FROM items WHERE status=1 AND is_approved=1 AND stock>0
            if (!$this->indexExists('items', 'idx_items_active_stock')) {
                $table->index(['status', 'is_approved', 'stock'], 'idx_items_active_stock');
            }

            // Optimize queries with store_id, status, is_approved filtering
            // Used in complex item listing queries
            if (!$this->indexExists('items', 'idx_items_store_active')) {
                $table->index(['store_id', 'status', 'is_approved', 'stock'], 'idx_items_store_active');
            }

            // Optimize queries filtering by module and category with status checks
            if (!$this->indexExists('items', 'idx_items_module_status')) {
                $table->index(['module_id', 'status', 'is_approved', 'created_at'], 'idx_items_module_status');
            }

            // Optimize discount queries (items with discount > 0)
            if (!$this->indexExists('items', 'idx_items_discount')) {
                $table->index(['discount', 'status', 'is_approved'], 'idx_items_discount');
            }

            // Optimize recommended items queries
            if (!$this->indexExists('items', 'idx_items_recommended')) {
                $table->index(['recommended', 'status', 'is_approved'], 'idx_items_recommended');
            }
        });

        Schema::table('stores', function (Blueprint $table) {
            // Optimize store active checks in item queries
            // Used in: WHERE stores.status=1 AND store_business_model='commission'
            if (!$this->indexExists('stores', 'idx_stores_active_model')) {
                $table->index(['status', 'store_business_model', 'zone_id'], 'idx_stores_active_model');
            }
        });

        Schema::table('store_subscriptions', function (Blueprint $table) {
            // Optimize subscription checks in item queries
            // Used in complex EXISTS subqueries
            if (!$this->indexExists('store_subscriptions', 'idx_sub_store_status')) {
                $table->index(['store_id', 'status', 'max_order'], 'idx_sub_store_status');
            }
        });

        // Optimize delivery_histories to reduce lock contention
        // The table uses ON DUPLICATE KEY UPDATE which causes table-level locks
        DB::statement('ALTER TABLE delivery_histories ENGINE=InnoDB ROW_FORMAT=DYNAMIC');

        // Ensure innodb_lock_wait_timeout is reasonable (will show warning if not set)
        $timeout = DB::selectOne("SHOW VARIABLES LIKE 'innodb_lock_wait_timeout'")->Value ?? 50;
        if ($timeout > 10) {
            // Log recommendation to reduce lock wait timeout
            \Log::warning("Consider setting innodb_lock_wait_timeout to 5-10 seconds for better lock contention handling. Current: {$timeout}s");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_active_stock');
            $table->dropIndex('idx_items_store_active');
            $table->dropIndex('idx_items_module_status');
            $table->dropIndex('idx_items_discount');
            $table->dropIndex('idx_items_recommended');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex('idx_stores_active_model');
        });

        Schema::table('store_subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_sub_store_status');
        });
    }

    /**
     * Check if an index already exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};
