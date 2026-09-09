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
        Schema::table('conversations', function (Blueprint $table) {
            // Add archive support
            $table->boolean('is_archived')->default(false)->after('assigned_at');

            // Add index for better query performance on archived conversations
            $table->index('is_archived', 'idx_is_archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('idx_is_archived');
            $table->dropColumn('is_archived');
        });
    }
};
