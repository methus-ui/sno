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
        Schema::table('items', function (Blueprint $table) {
            // Composite index for status and approval checks (most common filter)
            if (!$this->indexExists('items', 'idx_items_status_approved')) {
                $table->index(['status', 'is_approved'], 'idx_items_status_approved');
            }

            // Composite index for store relationships
            if (!$this->indexExists('items', 'idx_items_store_status')) {
                $table->index(['store_id', 'status', 'is_approved'], 'idx_items_store_status');
            }

            // Index for module relationships
            if (!$this->indexExists('items', 'idx_items_module')) {
                $table->index(['module_id', 'status'], 'idx_items_module');
            }

            // Index for category searches
            if (!$this->indexExists('items', 'idx_items_category')) {
                $table->index(['category_id', 'status'], 'idx_items_category');
            }

            // Index for stock checks
            if (!$this->indexExists('items', 'idx_items_stock')) {
                $table->index(['stock', 'status'], 'idx_items_stock');
            }

            // Composite index for the full common query pattern
            if (!$this->indexExists('items', 'idx_items_active_search')) {
                $table->index(['status', 'is_approved', 'store_id', 'stock'], 'idx_items_active_search');
            }

            // Index for created_at ordering (used in temp_available queries)
            if (!$this->indexExists('items', 'idx_items_created_at')) {
                $table->index(['created_at'], 'idx_items_created_at');
            }

            // Index for order_count (used in popular items)
            if (!$this->indexExists('items', 'idx_items_order_count')) {
                $table->index(['order_count', 'status'], 'idx_items_order_count');
            }
        });

        // Add index to item_tag for tag searches
        if (Schema::hasTable('item_tag')) {
            Schema::table('item_tag', function (Blueprint $table) {
                if (!$this->indexExists('item_tag', 'idx_item_tag_item')) {
                    $table->index(['item_id'], 'idx_item_tag_item');
                }
            });
        }

        // Add index to translations for translation searches
        if (Schema::hasTable('translations')) {
            Schema::table('translations', function (Blueprint $table) {
                if (!$this->indexExists('translations', 'idx_translations_translationable')) {
                    $table->index(['translationable_id', 'translationable_type'], 'idx_translations_translationable');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_status_approved');
            $table->dropIndex('idx_items_store_status');
            $table->dropIndex('idx_items_module');
            $table->dropIndex('idx_items_category');
            $table->dropIndex('idx_items_stock');
            $table->dropIndex('idx_items_active_search');
            $table->dropIndex('idx_items_created_at');
            $table->dropIndex('idx_items_order_count');
        });

        if (Schema::hasTable('item_tag')) {
            Schema::table('item_tag', function (Blueprint $table) {
                $table->dropIndex('idx_item_tag_item');
            });
        }

        if (Schema::hasTable('translations')) {
            Schema::table('translations', function (Blueprint $table) {
                $table->dropIndex('idx_translations_translationable');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $index)
    {
        $conn = Schema::getConnection();
        $dbSchemaManager = $conn->getDoctrineSchemaManager();
        $indexes = $dbSchemaManager->listTableIndexes($table);
        return array_key_exists($index, $indexes);
    }
};
