<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        echo "\n🚀 Adding performance indexes...\n\n";
        
        // ============= ORDERS TABLE =============
        echo "📦 Processing orders table...\n";
        
        Schema::table('orders', function (Blueprint $table) {
            // Critical: order_status index (already has MUL, but let's ensure it's optimal)
            if (!$this->hasIndex('orders', 'idx_orders_order_status')) {
                $table->index('order_status', 'idx_orders_order_status');
                echo "  ✓ Added idx_orders_order_status\n";
            } else {
                echo "  ⊙ idx_orders_order_status already exists\n";
            }
            
            // Critical: created_at index (already has MUL, but ensure it's there)
            if (!$this->hasIndex('orders', 'idx_orders_created_at')) {
                $table->index('created_at', 'idx_orders_created_at');
                echo "  ✓ Added idx_orders_created_at\n";
            } else {
                echo "  ⊙ idx_orders_created_at already exists\n";
            }
            
            // Critical: delivery_man_id index (already has MUL key)
            if (!$this->hasIndex('orders', 'idx_orders_delivery_man_id')) {
                $table->index('delivery_man_id', 'idx_orders_delivery_man_id');
                echo "  ✓ Added idx_orders_delivery_man_id\n";
            } else {
                echo "  ⊙ idx_orders_delivery_man_id already exists\n";
            }
            
            // Composite index for delivery man's active orders
            if (!$this->hasIndex('orders', 'idx_orders_dm_status_created')) {
                $table->index(['delivery_man_id', 'order_status', 'created_at'], 'idx_orders_dm_status_created');
                echo "  ✓ Added idx_orders_dm_status_created (composite)\n";
            } else {
                echo "  ⊙ idx_orders_dm_status_created already exists\n";
            }
            
            // Composite for popular items query optimization
            if (!$this->hasIndex('orders', 'idx_orders_status_id')) {
                $table->index(['order_status', 'id'], 'idx_orders_status_id');
                echo "  ✓ Added idx_orders_status_id (for JOIN optimization)\n";
            } else {
                echo "  ⊙ idx_orders_status_id already exists\n";
            }
        });

        // ============= ORDER_DETAILS TABLE =============
        echo "\n📋 Processing order_details table...\n";
        
        Schema::table('order_details', function (Blueprint $table) {
            // Critical: order_id index for JOIN (already has MUL key)
            if (!$this->hasIndex('order_details', 'idx_order_details_order_id')) {
                $table->index('order_id', 'idx_order_details_order_id');
                echo "  ✓ Added idx_order_details_order_id\n";
            } else {
                echo "  ⊙ idx_order_details_order_id already exists\n";
            }
            
            // Critical: item_id index for GROUP BY (already has MUL key)
            if (!$this->hasIndex('order_details', 'idx_order_details_item_id')) {
                $table->index('item_id', 'idx_order_details_item_id');
                echo "  ✓ Added idx_order_details_item_id\n";
            } else {
                echo "  ⊙ idx_order_details_item_id already exists\n";
            }
            
            // Composite index for the slow query
            if (!$this->hasIndex('order_details', 'idx_order_details_item_order')) {
                $table->index(['item_id', 'order_id', 'quantity'], 'idx_order_details_item_order');
                echo "  ✓ Added idx_order_details_item_order (composite)\n";
            } else {
                echo "  ⊙ idx_order_details_item_order already exists\n";
            }
        });

        // ============= DELIVERY_HISTORIES TABLE =============
        echo "\n🚚 Processing delivery_histories table...\n";
        
        Schema::table('delivery_histories', function (Blueprint $table) {
            // order_id index
            if (!$this->hasIndex('delivery_histories', 'idx_dh_order_id')) {
                $table->index('order_id', 'idx_dh_order_id');
                echo "  ✓ Added idx_dh_order_id\n";
            } else {
                echo "  ⊙ idx_dh_order_id already exists\n";
            }
            
            // delivery_man_id index (already has MUL key)
            if (!$this->hasIndex('delivery_histories', 'idx_dh_delivery_man_id')) {
                $table->index('delivery_man_id', 'idx_dh_delivery_man_id');
                echo "  ✓ Added idx_dh_delivery_man_id\n";
            } else {
                echo "  ⊙ idx_dh_delivery_man_id already exists\n";
            }
            
            // Composite for tracking
            if (!$this->hasIndex('delivery_histories', 'idx_dh_dm_time')) {
                $table->index(['delivery_man_id', 'time'], 'idx_dh_dm_time');
                echo "  ✓ Added idx_dh_dm_time (composite)\n";
            } else {
                echo "  ⊙ idx_dh_dm_time already exists\n";
            }
        });

        // ============= NOTIFICATIONS TABLE =============
        echo "\n🔔 Processing notifications table...\n";
        
        // Notifications table only has: id, title, description, image, status, created_at, updated_at, tergat, zone_id
        Schema::table('notifications', function (Blueprint $table) {
            // Add index on status and created_at
            if (!$this->hasIndex('notifications', 'idx_notifications_status_created')) {
                $table->index(['status', 'created_at'], 'idx_notifications_status_created');
                echo "  ✓ Added idx_notifications_status_created\n";
            } else {
                echo "  ⊙ idx_notifications_status_created already exists\n";
            }
            
            // Add index on zone_id
            if (!$this->hasIndex('notifications', 'idx_notifications_zone_id')) {
                $table->index('zone_id', 'idx_notifications_zone_id');
                echo "  ✓ Added idx_notifications_zone_id\n";
            } else {
                echo "  ⊙ idx_notifications_zone_id already exists\n";
            }
        });

        echo "\n✅ All indexes added successfully!\n";
        echo "🔄 Analyzing tables...\n";
        
        // Analyze tables to update statistics
        DB::statement('ANALYZE TABLE orders');
        DB::statement('ANALYZE TABLE order_details');
        DB::statement('ANALYZE TABLE delivery_histories');
        DB::statement('ANALYZE TABLE notifications');
        
        echo "✅ Table analysis complete!\n\n";
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $this->dropIndexIfExists('orders', 'idx_orders_order_status');
            $this->dropIndexIfExists('orders', 'idx_orders_created_at');
            $this->dropIndexIfExists('orders', 'idx_orders_delivery_man_id');
            $this->dropIndexIfExists('orders', 'idx_orders_dm_status_created');
            $this->dropIndexIfExists('orders', 'idx_orders_status_id');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $this->dropIndexIfExists('order_details', 'idx_order_details_order_id');
            $this->dropIndexIfExists('order_details', 'idx_order_details_item_id');
            $this->dropIndexIfExists('order_details', 'idx_order_details_item_order');
        });

        Schema::table('delivery_histories', function (Blueprint $table) {
            $this->dropIndexIfExists('delivery_histories', 'idx_dh_order_id');
            $this->dropIndexIfExists('delivery_histories', 'idx_dh_delivery_man_id');
            $this->dropIndexIfExists('delivery_histories', 'idx_dh_dm_time');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $this->dropIndexIfExists('notifications', 'idx_notifications_status_created');
            $this->dropIndexIfExists('notifications', 'idx_notifications_zone_id');
        });
    }

    // Helper: Check if index exists
    private function hasIndex($table, $indexName)
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (\Exception $e) {
            echo "  ⚠ Error checking index {$indexName}: " . $e->getMessage() . "\n";
            return false;
        }
    }

    // Helper: Drop index if exists
    private function dropIndexIfExists($table, $indexName)
    {
        if ($this->hasIndex($table, $indexName)) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
        }
    }
};
