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
     * Optimize slow queries by adding strategic composite indexes
     * and analyzing query performance
     */
    public function up(): void
    {
        // Analyze tables to update statistics for query optimizer
        DB::statement('ANALYZE TABLE items');
        DB::statement('ANALYZE TABLE stores');
        DB::statement('ANALYZE TABLE store_subscriptions');
        DB::statement('ANALYZE TABLE module_zone');
        DB::statement('ANALYZE TABLE zones');
        DB::statement('ANALYZE TABLE modules');

        // Add index for translations table if it doesn't exist
        if (Schema::hasTable('translations')) {
            // Use raw SQL for TEXT column index with length
            if (!$this->indexExists('translations', 'idx_trans_type_id')) {
                DB::statement('ALTER TABLE translations ADD INDEX idx_trans_type_id (translationable_type, translationable_id)');
            }
            if (!$this->indexExists('translations', 'idx_trans_value')) {
                // Index first 100 characters of value column
                DB::statement('ALTER TABLE translations ADD INDEX idx_trans_value (value(100))');
            }
        }

        // Add index for tags table if it doesn't exist
        if (Schema::hasTable('tags')) {
            Schema::table('tags', function (Blueprint $table) {
                if (!$this->indexExists('tags', 'idx_tag_name')) {
                    $table->index(['tag'], 'idx_tag_name');
                }
            });
        }

        // Add index for item_tag pivot table
        if (Schema::hasTable('item_tag')) {
            Schema::table('item_tag', function (Blueprint $table) {
                if (!$this->indexExists('item_tag', 'idx_item_tag_item')) {
                    $table->index(['item_id'], 'idx_item_tag_item');
                }
                if (!$this->indexExists('item_tag', 'idx_item_tag_tag')) {
                    $table->index(['tag_id'], 'idx_item_tag_tag');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('translations')) {
            Schema::table('translations', function (Blueprint $table) {
                $table->dropIndex('idx_trans_type_id');
                $table->dropIndex('idx_trans_value');
            });
        }

        if (Schema::hasTable('tags')) {
            Schema::table('tags', function (Blueprint $table) {
                $table->dropIndex('idx_tag_name');
            });
        }

        if (Schema::hasTable('item_tag')) {
            Schema::table('item_tag', function (Blueprint $table) {
                $table->dropIndex('idx_item_tag_item');
                $table->dropIndex('idx_item_tag_tag');
            });
        }
    }

    /**
     * Check if index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};
