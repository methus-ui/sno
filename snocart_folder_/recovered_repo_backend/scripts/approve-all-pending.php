#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking pending requests...\n";

// Count pending requests
$pendingMrp = DB::table('order_details')->where('mrp_update_status', 'pending')->count();
$pendingOP = DB::table('order_details')->where('outside_purchase_status', 'pending')->count();

echo "Found {$pendingMrp} pending MRP requests\n";
echo "Found {$pendingOP} pending Outside Purchase requests\n\n";

if ($pendingMrp === 0 && $pendingOP === 0) {
    echo "No pending requests to approve!\n";
    exit(0);
}

// Approve MRP requests
if ($pendingMrp > 0) {
    echo "Approving {$pendingMrp} MRP price change requests...\n";

    $updated = DB::table('order_details')
        ->where('mrp_update_status', 'pending')
        ->update([
            'price' => DB::raw('requested_mrp'),
            'mrp_update_status' => 'approved',
            'updated_at' => now()
        ]);

    echo "✅ Approved {$updated} MRP requests!\n";
}

// Approve Outside Purchase requests
if ($pendingOP > 0) {
    echo "Approving {$pendingOP} Outside Purchase requests...\n";

    $updated = DB::table('order_details')
        ->where('outside_purchase_status', 'pending')
        ->update([
            'outside_purchase_status' => 'approved',
            'updated_at' => now()
        ]);

    echo "✅ Approved {$updated} Outside Purchase requests!\n";
}

echo "\n✅ All pending requests approved successfully!\n";
