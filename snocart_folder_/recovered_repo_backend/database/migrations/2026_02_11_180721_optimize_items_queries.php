<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration optimizes the items table queries by:
     * 1. Analyzing the table for optimal index usage
     * 2. Updating table statistics for better query planning
     */
    public function up(): void
    {
        // Optimize the items table
        DB::statement('OPTIMIZE TABLE items');
        
        // Analyze the table to update statistics for better query planning
        DB::statement('ANALYZE TABLE items');
        
        // Note: The slow query issue is primarily due to SELECT * on a 40-column table
        // with 47K+ rows. Code should use Store::itemsOptimized() instead of Store::items()
        // when fetching large datasets.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for table optimization
    }
};
