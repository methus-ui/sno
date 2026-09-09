#!/usr/bin/env php
<?php

/**
 * Fix Delivery Location Tracking - Missing Unique Index
 *
 * Problem: delivery_histories table is missing unique index on delivery_man_id
 * This causes RecordDeliveryLocationJob to insert duplicates instead of updating
 *
 * Solution: Add the unique index that the migration failed to create
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n";
echo "=================================================================\n";
echo "  FIX DELIVERY LOCATION TRACKING - Add Missing Unique Index\n";
echo "=================================================================\n\n";

try {
    // Step 1: Check current state
    echo "[1/5] Checking current table state...\n";

    $indexes = DB::select("SHOW INDEX FROM delivery_histories WHERE Key_name = 'delivery_histories_delivery_man_id_unique'");

    if (count($indexes) > 0) {
        echo "   ✅ Unique index already exists! No fix needed.\n";

        // Show current stats
        $stats = DB::selectOne("
            SELECT
                COUNT(*) as total_records,
                COUNT(DISTINCT delivery_man_id) as unique_dm,
                MAX(updated_at) as last_update
            FROM delivery_histories
        ");

        echo "\n";
        echo "   Current state:\n";
        echo "   - Total records: {$stats->total_records}\n";
        echo "   - Unique delivery men: {$stats->unique_dm}\n";
        echo "   - Last update: {$stats->last_update}\n";

        exit(0);
    }

    echo "   ❌ Unique index NOT found (this is the problem!)\n\n";

    // Step 2: Check for duplicates
    echo "[2/5] Checking for duplicate delivery_man_id entries...\n";

    $duplicates = DB::select("
        SELECT delivery_man_id, COUNT(*) as count
        FROM delivery_histories
        GROUP BY delivery_man_id
        HAVING count > 1
    ");

    if (count($duplicates) > 0) {
        echo "   ⚠️  Found " . count($duplicates) . " delivery men with duplicate entries\n";

        // Step 3: Clean up duplicates (keep only the most recent)
        echo "\n[3/5] Cleaning up duplicate entries (keeping only latest)...\n";

        $deleted = DB::delete('
            DELETE dh1 FROM delivery_histories dh1
            INNER JOIN delivery_histories dh2
            WHERE dh1.delivery_man_id = dh2.delivery_man_id
            AND dh1.id < dh2.id
        ');

        echo "   ✅ Deleted {$deleted} old duplicate entries\n";
    } else {
        echo "   ✅ No duplicates found\n";
        echo "\n[3/5] Skipping cleanup (no duplicates)\n";
    }

    // Step 4: Add unique index
    echo "\n[4/5] Adding unique index on delivery_man_id...\n";

    DB::statement('
        ALTER TABLE delivery_histories
        ADD UNIQUE INDEX delivery_histories_delivery_man_id_unique (delivery_man_id)
    ');

    echo "   ✅ Unique index created successfully!\n";

    // Step 5: Verify and show final state
    echo "\n[5/5] Verifying fix...\n";

    $indexes = DB::select("SHOW INDEX FROM delivery_histories WHERE Key_name = 'delivery_histories_delivery_man_id_unique'");

    if (count($indexes) > 0) {
        echo "   ✅ Unique index verified!\n";
    } else {
        echo "   ❌ Index verification failed!\n";
        exit(1);
    }

    // Show final stats
    $stats = DB::selectOne("
        SELECT
            COUNT(*) as total_records,
            COUNT(DISTINCT delivery_man_id) as unique_dm,
            MAX(updated_at) as last_update
        FROM delivery_histories
    ");

    echo "\n";
    echo "=================================================================\n";
    echo "  ✅ FIX COMPLETE!\n";
    echo "=================================================================\n\n";
    echo "Final state:\n";
    echo "  - Total records: {$stats->total_records}\n";
    echo "  - Unique delivery men: {$stats->unique_dm}\n";
    echo "  - Last update: {$stats->last_update}\n";
    echo "\n";
    echo "What's fixed:\n";
    echo "  ✅ Unique index created on delivery_histories.delivery_man_id\n";
    echo "  ✅ RecordDeliveryLocationJob will now UPDATE instead of INSERT\n";
    echo "  ✅ Location tracking will work from now on\n";
    echo "\n";
    echo "Next steps:\n";
    echo "  1. Test location update from delivery man app\n";
    echo "  2. Check delivery_histories table for new updates\n";
    echo "  3. Monitor logs: tail -f storage/logs/laravel-*.log | grep location\n";
    echo "\n";

} catch (\Exception $e) {
    echo "\n";
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    echo "\n";
    exit(1);
}
