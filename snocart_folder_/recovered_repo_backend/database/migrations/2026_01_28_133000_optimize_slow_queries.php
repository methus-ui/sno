<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Optimize indexes for slow queries identified in MySQL slow query log.
     *
     * Issues fixed:
     * 1. delivery_histories - Lock contention on updates, slow lookups
     * 2. delivery_men - Slow updates on active status
     * 3. items - Complex query examining 345k+ rows
     * 4. stores - Missing composite index for business model queries
     * 5. store_subscriptions - Slow subquery for latest subscription
     */
    public function up(): void
    {
        // =====================================================
        // DELIVERY_HISTORIES - Priority: Make as fast as possible
        // =====================================================
        Schema::table('delivery_histories', function (Blueprint $table) {
            // Drop the unique constraint causing lock contention
            // This allows concurrent updates without blocking
            try {
                $table->dropUnique('idx_dh_delivery_man_unique');
            } catch (\Exception $e) {
                // Index might not exist
            }
        });

        Schema::table('delivery_histories', function (Blueprint $table) {
            // Add optimized index for delivery_man lookups (non-unique for faster writes)
            if (!$this->indexExists('delivery_histories', 'idx_dh_dm_id')) {
                $table->index('delivery_man_id', 'idx_dh_dm_id');
            }

            // Composite index for time-based queries
            if (!$this->indexExists('delivery_histories', 'idx_dh_dm_time')) {
                $table->index(['delivery_man_id', 'time'], 'idx_dh_dm_time');
            }

            // Index for fast updates by id (covering updated_at)
            if (!$this->indexExists('delivery_histories', 'idx_dh_id_updated')) {
                $table->index(['id', 'updated_at'], 'idx_dh_id_updated');
            }
        });

        // =====================================================
        // DELIVERY_MEN - Optimize active status updates
        // =====================================================
        Schema::table('delivery_men', function (Blueprint $table) {
            // Index for status/active queries
            if (!$this->indexExists('delivery_men', 'idx_dm_id_active')) {
                $table->index(['id', 'active'], 'idx_dm_id_active');
            }

            // Index for zone-based active delivery men queries
            if (!$this->indexExists('delivery_men', 'idx_dm_zone_active_status')) {
                $table->index(['zone_id', 'active', 'status', 'application_status'], 'idx_dm_zone_active_status');
            }

            // Index for store-based delivery men
            if (!$this->indexExists('delivery_men', 'idx_dm_store_active')) {
                $table->index(['store_id', 'active', 'status'], 'idx_dm_store_active');
            }
        });

        // =====================================================
        // ITEMS - Optimize complex listing query (345k rows examined)
        // =====================================================
        Schema::table('items', function (Blueprint $table) {
            // Composite index matching the WHERE clause pattern
            if (!$this->indexExists('items', 'idx_items_listing')) {
                $table->index(
                    ['status', 'is_approved', 'store_id', 'module_id', 'stock', 'created_at'],
                    'idx_items_listing'
                );
            }

            // Index for stock availability queries
            if (!$this->indexExists('items', 'idx_items_available')) {
                $table->index(['store_id', 'status', 'is_approved', 'stock'], 'idx_items_available');
            }
        });

        // =====================================================
        // STORES - Optimize joins in items query
        // =====================================================
        Schema::table('stores', function (Blueprint $table) {
            // Covering index for the EXISTS subquery in items
            if (!$this->indexExists('stores', 'idx_stores_items_join')) {
                $table->index(
                    ['id', 'status', 'store_business_model', 'module_id', 'zone_id'],
                    'idx_stores_items_join'
                );
            }

            // Index for active stores by zone
            if (!$this->indexExists('stores', 'idx_stores_active_zone')) {
                $table->index(['status', 'zone_id', 'module_id'], 'idx_stores_active_zone');
            }
        });

        // =====================================================
        // STORE_SUBSCRIPTIONS - Optimize MAX(id) subquery
        // =====================================================
        Schema::table('store_subscriptions', function (Blueprint $table) {
            // Optimized index for the latestOfMany pattern
            if (!$this->indexExists('store_subscriptions', 'idx_subs_latest_active')) {
                $table->index(
                    ['store_id', 'id', 'status', 'max_order'],
                    'idx_subs_latest_active'
                );
            }
        });

        // =====================================================
        // Optimize table settings for delivery_histories
        // =====================================================
        try {
            // Set InnoDB settings for faster writes on delivery_histories
            DB::statement('ALTER TABLE delivery_histories ROW_FORMAT=DYNAMIC');
        } catch (\Exception $e) {
            // Ignore if already set
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_histories', function (Blueprint $table) {
            $table->dropIndex('idx_dh_dm_id');
            $table->dropIndex('idx_dh_dm_time');
            $table->dropIndex('idx_dh_id_updated');
        });

        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropIndex('idx_dm_id_active');
            $table->dropIndex('idx_dm_zone_active_status');
            $table->dropIndex('idx_dm_store_active');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_listing');
            $table->dropIndex('idx_items_available');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex('idx_stores_items_join');
            $table->dropIndex('idx_stores_active_zone');
        });

        Schema::table('store_subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_subs_latest_active');
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
