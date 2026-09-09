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
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Add index on last_used_at for faster updates
            if (!$this->indexExists('personal_access_tokens', 'idx_last_used_at')) {
                $table->index(['last_used_at'], 'idx_last_used_at');
            }

            // Add composite index for token lookups with last_used_at
            if (!$this->indexExists('personal_access_tokens', 'idx_tokenable_last_used')) {
                $table->index(['tokenable_type', 'tokenable_id', 'last_used_at'], 'idx_tokenable_last_used');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('idx_last_used_at');
            $table->dropIndex('idx_tokenable_last_used');
        });
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
