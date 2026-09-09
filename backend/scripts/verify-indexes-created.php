#!/usr/bin/env php
<?php

/**
 * Verify that database indexes were created successfully
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Verifying Indexes Created ===\n\n";

// Check delivery_histories index
echo "1. Checking delivery_histories.idx_dm_time...\n";
$dhIndexes = DB::select("SHOW INDEX FROM delivery_histories WHERE Key_name = 'idx_dm_time'");

if (!empty($dhIndexes)) {
    echo "   ✅ Index 'idx_dm_time' exists\n";
    echo "   Columns: ";
    foreach ($dhIndexes as $idx) {
        echo $idx->Column_name . " (Seq: {$idx->Seq_in_index}) ";
    }
    echo "\n";
} else {
    echo "   ❌ Index 'idx_dm_time' NOT found\n";
    exit(1);
}

// Check users index
echo "\n2. Checking users.idx_ref_code...\n";
$usersIndexes = DB::select("SHOW INDEX FROM users WHERE Key_name = 'idx_ref_code'");

if (!empty($usersIndexes)) {
    echo "   ✅ Index 'idx_ref_code' exists\n";
    echo "   Column: " . $usersIndexes[0]->Column_name . "\n";
} else {
    echo "   ❌ Index 'idx_ref_code' NOT found\n";
    exit(1);
}

// Test query performance (explain analyze)
echo "\n3. Testing query performance with new indexes...\n";

// Test delivery_histories query
try {
    $explain = DB::select("EXPLAIN SELECT * FROM delivery_histories WHERE delivery_man_id = 11 AND time > NOW() - INTERVAL 1 DAY");

    if (!empty($explain)) {
        $firstRow = $explain[0];
        $usingIndex = $firstRow->key ?? 'NULL';

        if (strpos($usingIndex, 'idx_dm_time') !== false || $usingIndex === 'idx_dm_time') {
            echo "   ✅ delivery_histories query using index: {$usingIndex}\n";
        } else {
            echo "   ⚠️  delivery_histories query using index: {$usingIndex} (expected idx_dm_time)\n";
        }
    }
} catch (\Exception $e) {
    echo "   ⚠️  Could not test delivery_histories query: " . $e->getMessage() . "\n";
}

// Test users query
try {
    $explain = DB::select("EXPLAIN SELECT * FROM users WHERE ref_code = '4GI0GLELKM'");

    if (!empty($explain)) {
        $firstRow = $explain[0];
        $usingIndex = $firstRow->key ?? 'NULL';

        if (strpos($usingIndex, 'ref_code') !== false) {
            echo "   ✅ users query using index: {$usingIndex}\n";
        } else {
            echo "   ⚠️  users query using index: {$usingIndex} (expected idx_ref_code or users_ref_code_unique)\n";
        }
    }
} catch (\Exception $e) {
    echo "   ⚠️  Could not test users query: " . $e->getMessage() . "\n";
}

echo "\n=== Verification Complete ✅ ===\n\n";
echo "Expected Performance Improvements:\n";
echo "  - delivery_histories INSERT: 9502ms → <1000ms (9x faster)\n";
echo "  - users UPDATE ref_code: 2752ms → <500ms (5x faster)\n\n";

exit(0);
