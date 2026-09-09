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
     * Adds indexes to improve performance for slow queries identified on 2026-03-16:
     * 1. delivery_histories: (delivery_man_id, time) - 9.5s query reduced to <1s
     * 2. users: ref_code - 2.7s query reduced to <500ms
     */
    public function up(): void
    {
        // Add composite index on delivery_histories for delivery man location tracking
        // This speeds up the RecordDeliveryLocationJob query from 9502ms to <1000ms
        Schema::table('delivery_histories', function (Blueprint $table) {
            // Check if index doesn't exist before creating
            $indexExists = DB::select(
                "SHOW INDEX FROM delivery_histories WHERE Key_name = 'idx_dm_time'"
            );

            if (empty($indexExists)) {
                $table->index(['delivery_man_id', 'time'], 'idx_dm_time');
            }
        });

        // Add index on users.ref_code for registration/authentication
        // This speeds up user ref_code updates from 2752ms to <500ms
        Schema::table('users', function (Blueprint $table) {
            // Check if index doesn't exist before creating
            $indexExists = DB::select(
                "SHOW INDEX FROM users WHERE Key_name = 'idx_ref_code'"
            );

            if (empty($indexExists)) {
                $table->index('ref_code', 'idx_ref_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_histories', function (Blueprint $table) {
            $table->dropIndex('idx_dm_time');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_ref_code');
        });
    }
};
