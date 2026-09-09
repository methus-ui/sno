#!/usr/bin/env php
<?php

/**
 * Test script to verify database indexes migration
 *
 * This script checks:
 * 1. If indexes already exist
 * 2. Table structure
 * 3. Migration preview
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Database Indexes Migration Test ===\n\n";

// Test 1: Check delivery_histories table structure
echo "1. Checking delivery_histories table...\n";
try {
    $columns = DB::select("SHOW COLUMNS FROM delivery_histories");
    echo "   ✅ Table exists with " . count($columns) . " columns\n";

    // Check for delivery_man_id and time columns
    $hasDeliveryManId = false;
    $hasTime = false;
    foreach ($columns as $col) {
        if ($col->Field === 'delivery_man_id') $hasDeliveryManId = true;
        if ($col->Field === 'time') $hasTime = true;
    }

    if ($hasDeliveryManId && $hasTime) {
        echo "   ✅ Required columns exist (delivery_man_id, time)\n";
    } else {
        echo "   ❌ Missing required columns\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check existing indexes on delivery_histories
echo "\n2. Checking existing indexes on delivery_histories...\n";
try {
    $indexes = DB::select("SHOW INDEX FROM delivery_histories");
    $indexNames = array_map(fn($idx) => $idx->Key_name, $indexes);
    $uniqueIndexes = array_unique($indexNames);

    echo "   Current indexes: " . implode(', ', $uniqueIndexes) . "\n";

    if (in_array('idx_dm_time', $uniqueIndexes)) {
        echo "   ⚠️  Index 'idx_dm_time' already exists - migration will skip\n";
    } else {
        echo "   ✅ Index 'idx_dm_time' does not exist - will be created\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check users table structure
echo "\n3. Checking users table...\n";
try {
    $columns = DB::select("SHOW COLUMNS FROM users");
    echo "   ✅ Table exists with " . count($columns) . " columns\n";

    // Check for ref_code column
    $hasRefCode = false;
    foreach ($columns as $col) {
        if ($col->Field === 'ref_code') {
            $hasRefCode = true;
            echo "   ✅ ref_code column exists (Type: {$col->Type}, Null: {$col->Null})\n";
        }
    }

    if (!$hasRefCode) {
        echo "   ❌ ref_code column does not exist\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Check existing indexes on users
echo "\n4. Checking existing indexes on users...\n";
try {
    $indexes = DB::select("SHOW INDEX FROM users");
    $indexNames = array_map(fn($idx) => $idx->Key_name, $indexes);
    $uniqueIndexes = array_unique($indexNames);

    echo "   Current indexes: " . implode(', ', $uniqueIndexes) . "\n";

    if (in_array('idx_ref_code', $uniqueIndexes)) {
        echo "   ⚠️  Index 'idx_ref_code' already exists - migration will skip\n";
    } else {
        echo "   ✅ Index 'idx_ref_code' does not exist - will be created\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Count records to estimate migration time
echo "\n5. Estimating migration time...\n";
try {
    $deliveryHistoriesCount = DB::table('delivery_histories')->count();
    $usersCount = DB::table('users')->count();

    echo "   delivery_histories: " . number_format($deliveryHistoriesCount) . " records\n";
    echo "   users: " . number_format($usersCount) . " records\n";

    // Rough estimate: 1000 records = ~0.1 seconds per index
    $estimatedTime = ($deliveryHistoriesCount + $usersCount) / 10000;
    echo "   Estimated migration time: ~" . round($estimatedTime, 2) . " seconds\n";
} catch (\Exception $e) {
    echo "   ⚠️  Could not estimate time: " . $e->getMessage() . "\n";
}

// Test 6: Check migration file syntax
echo "\n6. Validating migration file...\n";
$migrationFile = __DIR__.'/../database/migrations/2026_03_16_214056_add_indexes_for_slow_queries.php';
if (file_exists($migrationFile)) {
    echo "   ✅ Migration file exists\n";

    // Check syntax
    $output = [];
    $return = 0;
    exec("php -l " . escapeshellarg($migrationFile), $output, $return);

    if ($return === 0) {
        echo "   ✅ Migration syntax is valid\n";
    } else {
        echo "   ❌ Migration has syntax errors:\n";
        echo "      " . implode("\n      ", $output) . "\n";
        exit(1);
    }
} else {
    echo "   ❌ Migration file not found\n";
    exit(1);
}

echo "\n=== All Tests Passed ✅ ===\n";
echo "\nReady to run migration:\n";
echo "  php artisan migrate --path=database/migrations/2026_03_16_214056_add_indexes_for_slow_queries.php --force\n\n";

exit(0);
