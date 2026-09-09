#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

$pending = DB::table('order_details')->where('mrp_update_status', 'pending')->count();
$approved = DB::table('order_details')->where('mrp_update_status', 'approved')->where('updated_at', '>', now()->subHour())->count();

echo "Pending MRP requests: {$pending}\n";
echo "Recently approved: {$approved}\n";
