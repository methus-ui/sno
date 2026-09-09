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
     * Migration 5 of 5: Add Full-Text Search Indexes
     * Enables fast full-text search on messages and user information
     * Improves search performance by 10-100x compared to LIKE queries
     */
    public function up(): void
    {
        // Check if using MySQL (full-text only works on MySQL/MariaDB)
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Add full-text index to messages table
        DB::statement('ALTER TABLE messages ADD FULLTEXT INDEX ft_message (message)');

        // Add full-text index to user_infos table (for conversation search)
        DB::statement('ALTER TABLE user_infos ADD FULLTEXT INDEX ft_user_names (f_name, l_name, phone)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if using MySQL
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Drop full-text indexes
        DB::statement('ALTER TABLE messages DROP INDEX ft_message');
        DB::statement('ALTER TABLE user_infos DROP INDEX ft_user_names');
    }
};
