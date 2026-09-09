<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add unique index on delivery_man_id for fast upsert operations.
     * First clean up any duplicate entries, keeping only the most recent.
     */
    public function up(): void
    {
        // Step 1: Clean up duplicate delivery_man_id entries (keep only the latest)
        DB::statement('
            DELETE dh1 FROM delivery_histories dh1
            INNER JOIN delivery_histories dh2
            WHERE dh1.delivery_man_id = dh2.delivery_man_id
            AND dh1.id < dh2.id
        ');

        // Step 2: Add unique index for upsert operations
        Schema::table('delivery_histories', function (Blueprint $table) {
            // Check if index already exists
            $indexes = DB::select("SHOW INDEX FROM delivery_histories WHERE Key_name = 'delivery_histories_delivery_man_id_unique'");
            if (count($indexes) === 0) {
                $table->unique('delivery_man_id', 'delivery_histories_delivery_man_id_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_histories', function (Blueprint $table) {
            $table->dropUnique('delivery_histories_delivery_man_id_unique');
        });
    }
};
