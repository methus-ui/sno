<?php

// Test script to debug Odoo API key generation
// Access via: https://new.snocart.com/test-odoo-api.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();

$response = $kernel->handle($request);

// Test authentication
echo "=== Authentication Debug ===\n";
echo "vendor_employee authenticated: " . (auth('vendor_employee')->check() ? 'YES' : 'NO') . "\n";
echo "vendor authenticated: " . (auth('vendor')->check() ? 'YES' : 'NO') . "\n";

if (auth('vendor_employee')->check()) {
    $user = auth('vendor_employee')->user();
    echo "vendor_employee ID: " . $user->id . "\n";
    echo "vendor_employee name: " . $user->f_name . " " . $user->l_name . "\n";
    if ($user->store) {
        echo "Store ID: " . $user->store->id . "\n";
        echo "Store Name: " . $user->store->name . "\n";
        echo "odoo_api_key: " . ($user->store->odoo_api_key ?? 'NULL') . "\n";
        echo "odoo_enabled: " . ($user->store->odoo_enabled ? 'true' : 'false') . "\n";
    } else {
        echo "ERROR: Store not found for vendor_employee!\n";
    }
}

if (auth('vendor')->check()) {
    $user = auth('vendor')->user();
    echo "vendor ID: " . $user->id . "\n";
    echo "vendor name: " . $user->f_name . " " . $user->l_name . "\n";
    if ($user->stores && $user->stores->count() > 0) {
        $store = $user->stores[0];
        echo "Store ID: " . $store->id . "\n";
        echo "Store Name: " . $store->name . "\n";
        echo "odoo_api_key: " . ($store->odoo_api_key ?? 'NULL') . "\n";
        echo "odoo_enabled: " . ($store->odoo_enabled ? 'true' : 'false') . "\n";
    } else {
        echo "ERROR: No stores found for vendor!\n";
    }
}

if (!auth('vendor_employee')->check() && !auth('vendor')->check()) {
    echo "ERROR: No authentication found! Please log in first.\n";
}
