<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Optimizes slow queries identified in MySQL slow query log
     */
    public function up(): void
    {
        // 1. Add unique index on delivery_histories.delivery_man_id
        // Fixes slow UPDATE queries (2-5s) caused by lock contention
        Schema::table('delivery_histories', function (Blueprint $table) {
            // First drop existing non-unique indexes on delivery_man_id
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('delivery_histories');

            foreach ($indexes as $indexName => $index) {
                if (in_array('delivery_man_id', $index->getColumns()) && $indexName !== 'PRIMARY') {
                    $table->dropIndex($indexName);
                }
            }
        });

        Schema::table('delivery_histories', function (Blueprint $table) {
            // Add unique index - each delivery man should have only one history record
            $table->unique('delivery_man_id', 'idx_dh_delivery_man_unique');
        });

        // 2. Add index on stores for subscription business model queries
        // Fixes slow nested subqueries checking store_business_model
        Schema::table('stores', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('stores');

            if (!isset($indexes['idx_stores_zone_module_status'])) {
                $table->index(['zone_id', 'module_id', 'status'], 'idx_stores_zone_module_status');
            }

            if (!isset($indexes['idx_stores_business_model'])) {
                $table->index(['store_business_model', 'status'], 'idx_stores_business_model');
            }
        });

        // 3. Add composite index on store_subscriptions for faster MAX(id) queries
        Schema::table('store_subscriptions', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('store_subscriptions');

            if (!isset($indexes['idx_store_subs_latest'])) {
                $table->index(['store_id', 'id', 'status', 'max_order'], 'idx_store_subs_latest');
            }
        });

        // 4. Add index for search_logs trending queries
        if (Schema::hasTable('search_logs')) {
            Schema::table('search_logs', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('search_logs');

                if (!isset($indexes['idx_search_logs_trending'])) {
                    $table->index(['created_at', 'term'], 'idx_search_logs_trending');
                }
            });
        }

        // 5. Add rating_count index on items for review filtering
        Schema::table('items', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('items');

            if (!isset($indexes['idx_items_rating_count'])) {
                $table->index(['rating_count', 'avg_rating'], 'idx_items_rating_count');
            }
        });

        // 6. Add index for orders delivery_man lookups
        Schema::table('orders', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('orders');

            if (!isset($indexes['idx_orders_dm_status'])) {
                $table->index(['delivery_man_id', 'order_status'], 'idx_orders_dm_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_histories', function (Blueprint $table) {
            $table->dropIndex('idx_dh_delivery_man_unique');
            $table->index('delivery_man_id', 'idx_dh_delivery_man_id');
        });

        Schema::table('stores', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('stores');

            if (isset($indexes['idx_stores_zone_module_status'])) {
                $table->dropIndex('idx_stores_zone_module_status');
            }
            if (isset($indexes['idx_stores_business_model'])) {
                $table->dropIndex('idx_stores_business_model');
            }
        });

        Schema::table('store_subscriptions', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('store_subscriptions');

            if (isset($indexes['idx_store_subs_latest'])) {
                $table->dropIndex('idx_store_subs_latest');
            }
        });

        if (Schema::hasTable('search_logs')) {
            Schema::table('search_logs', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('search_logs');

                if (isset($indexes['idx_search_logs_trending'])) {
                    $table->dropIndex('idx_search_logs_trending');
                }
            });
        }

        Schema::table('items', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('items');

            if (isset($indexes['idx_items_rating_count'])) {
                $table->dropIndex('idx_items_rating_count');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('orders');

            if (isset($indexes['idx_orders_dm_status'])) {
                $table->dropIndex('idx_orders_dm_status');
            }
        });
    }
};
