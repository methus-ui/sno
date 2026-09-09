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
     * @return void
     */
    public function up()
    {
        // Add analytics indexes for delivery stats charts (90-day queries)

        // 1. Users table - customer acquisition trends
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Check if index doesn't already exist
                $indexExists = DB::select("SHOW INDEX FROM users WHERE Key_name = 'idx_users_created_at'");
                if (empty($indexExists)) {
                    $table->index('created_at', 'idx_users_created_at');
                    echo "✓ Added index idx_users_created_at to users table\n";
                } else {
                    echo "⊙ Index idx_users_created_at already exists on users table\n";
                }
            });
        }

        // 2. Orders table - payment method revenue analysis
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Check if index doesn't already exist
                $indexExists = DB::select("SHOW INDEX FROM orders WHERE Key_name = 'idx_orders_payment_method'");
                if (empty($indexExists)) {
                    $table->index('payment_method', 'idx_orders_payment_method');
                    echo "✓ Added index idx_orders_payment_method to orders table\n";
                } else {
                    echo "⊙ Index idx_orders_payment_method already exists on orders table\n";
                }

                // Composite index for module revenue analysis
                $indexExists = DB::select("SHOW INDEX FROM orders WHERE Key_name = 'idx_orders_module_status'");
                if (empty($indexExists)) {
                    $table->index(['module_id', 'order_status'], 'idx_orders_module_status');
                    echo "✓ Added composite index idx_orders_module_status to orders table\n";
                } else {
                    echo "⊙ Index idx_orders_module_status already exists on orders table\n";
                }
            });
        }

        // 3. Order details table - top products analysis
        if (Schema::hasTable('order_details')) {
            Schema::table('order_details', function (Blueprint $table) {
                // Check if index doesn't already exist
                $indexExists = DB::select("SHOW INDEX FROM order_details WHERE Key_name = 'idx_order_details_item_id'");
                if (empty($indexExists)) {
                    $table->index('item_id', 'idx_order_details_item_id');
                    echo "✓ Added index idx_order_details_item_id to order_details table\n";
                } else {
                    echo "⊙ Index idx_order_details_item_id already exists on order_details table\n";
                }

                // Composite index for campaign products
                $indexExists = DB::select("SHOW INDEX FROM order_details WHERE Key_name = 'idx_order_details_item_campaign_id'");
                if (empty($indexExists)) {
                    $table->index('item_campaign_id', 'idx_order_details_item_campaign_id');
                    echo "✓ Added index idx_order_details_item_campaign_id to order_details table\n";
                } else {
                    echo "⊙ Index idx_order_details_item_campaign_id already exists on order_details table\n";
                }
            });
        }

        // 4. Delivery histories table - timeline analysis (if table exists)
        if (Schema::hasTable('delivery_histories')) {
            Schema::table('delivery_histories', function (Blueprint $table) {
                // Check if index doesn't already exist
                $indexExists = DB::select("SHOW INDEX FROM delivery_histories WHERE Key_name = 'idx_delivery_histories_created_at'");
                if (empty($indexExists)) {
                    $table->index('created_at', 'idx_delivery_histories_created_at');
                    echo "✓ Added index idx_delivery_histories_created_at to delivery_histories table\n";
                } else {
                    echo "⊙ Index idx_delivery_histories_created_at already exists on delivery_histories table\n";
                }
            });
        }

        // 5. Order transactions table - revenue reporting (if not already indexed)
        if (Schema::hasTable('order_transactions')) {
            Schema::table('order_transactions', function (Blueprint $table) {
                // Check if index doesn't already exist
                $indexExists = DB::select("SHOW INDEX FROM order_transactions WHERE Key_name = 'idx_order_transactions_created_at'");
                if (empty($indexExists)) {
                    $table->index('created_at', 'idx_order_transactions_created_at');
                    echo "✓ Added index idx_order_transactions_created_at to order_transactions table\n";
                } else {
                    echo "⊙ Index idx_order_transactions_created_at already exists on order_transactions table\n";
                }
            });
        }

        echo "\n✅ Analytics indexes migration completed successfully!\n";
        echo "ℹ️  These indexes optimize 90-day trend queries and revenue analysis.\n";
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove analytics indexes

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_created_at');
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('idx_orders_payment_method');
                $table->dropIndex('idx_orders_module_status');
            });
        }

        if (Schema::hasTable('order_details')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->dropIndex('idx_order_details_item_id');
                $table->dropIndex('idx_order_details_item_campaign_id');
            });
        }

        if (Schema::hasTable('delivery_histories')) {
            Schema::table('delivery_histories', function (Blueprint $table) {
                $table->dropIndex('idx_delivery_histories_created_at');
            });
        }

        if (Schema::hasTable('order_transactions')) {
            Schema::table('order_transactions', function (Blueprint $table) {
                $table->dropIndex('idx_order_transactions_created_at');
            });
        }

        echo "\n✅ Analytics indexes rolled back successfully!\n";
    }
};
