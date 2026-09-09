<?php

/**
 * Sound Box Installation Verification Script
 * Tests all components of the ESP32 Sound Box system
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use App\Models\VendorSoundDevice;
use App\Models\DevicePairingToken;
use App\Models\DeviceWebhookQueue;
use App\Services\DeviceWebhookService;
use App\Services\DevicePairingService;
use App\Services\DeviceManagementService;

echo "\n========================================\n";
echo "ESP32 Sound Box Installation Verification\n";
echo "========================================\n\n";

$checks = [];
$passed = 0;
$failed = 0;

// 1. Check Database Tables
echo "1. Checking database tables...\n";
$tables = ['vendor_sound_devices', 'device_pairing_tokens', 'device_webhook_queue'];
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "   ✓ Table '{$table}' exists\n";
        $checks[] = ['check' => "Table {$table}", 'status' => 'PASS'];
        $passed++;
    } else {
        echo "   ✗ Table '{$table}' missing\n";
        $checks[] = ['check' => "Table {$table}", 'status' => 'FAIL'];
        $failed++;
    }
}

// 2. Check Models
echo "\n2. Checking model classes...\n";
$models = [
    'App\Models\VendorSoundDevice',
    'App\Models\DevicePairingToken',
    'App\Models\DeviceWebhookQueue',
];
foreach ($models as $model) {
    if (class_exists($model)) {
        echo "   ✓ Model '{$model}' exists\n";
        $checks[] = ['check' => "Model {$model}", 'status' => 'PASS'];
        $passed++;
    } else {
        echo "   ✗ Model '{$model}' missing\n";
        $checks[] = ['check' => "Model {$model}", 'status' => 'FAIL'];
        $failed++;
    }
}

// 3. Check Service Classes
echo "\n3. Checking service classes...\n";
$services = [
    'App\Services\DeviceWebhookService',
    'App\Services\DevicePairingService',
    'App\Services\DeviceManagementService',
];
foreach ($services as $service) {
    if (class_exists($service)) {
        echo "   ✓ Service '{$service}' exists\n";
        $checks[] = ['check' => "Service {$service}", 'status' => 'PASS'];
        $passed++;
    } else {
        echo "   ✗ Service '{$service}' missing\n";
        $checks[] = ['check' => "Service {$service}", 'status' => 'FAIL'];
        $failed++;
    }
}

// 4. Check Controllers
echo "\n4. Checking controller classes...\n";
$controllers = [
    'App\Http\Controllers\Api\V1\Vendor\DeviceController',
    'App\Http\Controllers\Api\V1\Vendor\SoundBoxController',
    'App\Http\Controllers\Api\V1\Vendor\DeviceManagementController',
];
foreach ($controllers as $controller) {
    if (class_exists($controller)) {
        echo "   ✓ Controller '{$controller}' exists\n";
        $checks[] = ['check' => "Controller {$controller}", 'status' => 'PASS'];
        $passed++;
    } else {
        echo "   ✗ Controller '{$controller}' missing\n";
        $checks[] = ['check' => "Controller {$controller}", 'status' => 'FAIL'];
        $failed++;
    }
}

// 5. Check Middleware
echo "\n5. Checking middleware...\n";
if (class_exists('App\Http\Middleware\DeviceApiKeyAuth')) {
    echo "   ✓ DeviceApiKeyAuth middleware exists\n";
    $checks[] = ['check' => 'DeviceApiKeyAuth middleware', 'status' => 'PASS'];
    $passed++;
} else {
    echo "   ✗ DeviceApiKeyAuth middleware missing\n";
    $checks[] = ['check' => 'DeviceApiKeyAuth middleware', 'status' => 'FAIL'];
    $failed++;
}

// 6. Check Configuration
echo "\n6. Checking configuration...\n";
if (Config::has('soundbox')) {
    echo "   ✓ Sound box configuration exists\n";
    echo "   - Enabled: " . (config('soundbox.enabled') ? 'YES' : 'NO') . "\n";
    echo "   - Webhook timeout: " . config('soundbox.webhook.timeout') . "s\n";
    echo "   - Max devices per store: " . config('soundbox.device.max_per_store') . "\n";
    $checks[] = ['check' => 'Sound box configuration', 'status' => 'PASS'];
    $passed++;
} else {
    echo "   ✗ Sound box configuration missing\n";
    $checks[] = ['check' => 'Sound box configuration', 'status' => 'FAIL'];
    $failed++;
}

// 7. Check Helper Method
echo "\n7. Checking helper methods...\n";
if (method_exists('App\CentralLogics\Helpers', 'sendOrderWebhookToDevices')) {
    echo "   ✓ sendOrderWebhookToDevices helper method exists\n";
    $checks[] = ['check' => 'sendOrderWebhookToDevices helper', 'status' => 'PASS'];
    $passed++;
} else {
    echo "   ✗ sendOrderWebhookToDevices helper method missing\n";
    $checks[] = ['check' => 'sendOrderWebhookToDevices helper', 'status' => 'FAIL'];
    $failed++;
}

// 8. Check Database Data
echo "\n8. Checking database statistics...\n";
try {
    $deviceCount = VendorSoundDevice::count();
    $activeDevices = VendorSoundDevice::active()->count();
    $onlineDevices = VendorSoundDevice::online()->count();
    $pairingTokens = DevicePairingToken::valid()->count();
    $webhookQueue = DeviceWebhookQueue::pending()->count();

    echo "   - Total devices: {$deviceCount}\n";
    echo "   - Active devices: {$activeDevices}\n";
    echo "   - Online devices: {$onlineDevices}\n";
    echo "   - Valid pairing tokens: {$pairingTokens}\n";
    echo "   - Pending webhooks: {$webhookQueue}\n";

    $checks[] = ['check' => 'Database statistics', 'status' => 'PASS'];
    $passed++;
} catch (\Exception $e) {
    echo "   ✗ Error querying database: {$e->getMessage()}\n";
    $checks[] = ['check' => 'Database statistics', 'status' => 'FAIL'];
    $failed++;
}

// 9. Test Service Instantiation
echo "\n9. Testing service instantiation...\n";
try {
    $webhookService = app(DeviceWebhookService::class);
    $pairingService = app(DevicePairingService::class);
    $managementService = app(DeviceManagementService::class);

    echo "   ✓ All services can be instantiated\n";
    $checks[] = ['check' => 'Service instantiation', 'status' => 'PASS'];
    $passed++;
} catch (\Exception $e) {
    echo "   ✗ Service instantiation failed: {$e->getMessage()}\n";
    $checks[] = ['check' => 'Service instantiation', 'status' => 'FAIL'];
    $failed++;
}

// 10. Check API Routes (if artisan route:list works)
echo "\n10. Checking API routes...\n";
try {
    $routeCollection = app()->routes->getRoutes();
    $soundboxRoutes = [];

    foreach ($routeCollection as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'device') !== false || strpos($uri, 'sound') !== false) {
            $soundboxRoutes[] = $uri;
        }
    }

    if (count($soundboxRoutes) > 0) {
        echo "   ✓ Found " . count($soundboxRoutes) . " sound box related routes\n";
        foreach (array_slice($soundboxRoutes, 0, 5) as $route) {
            echo "     - {$route}\n";
        }
        if (count($soundboxRoutes) > 5) {
            echo "     ... and " . (count($soundboxRoutes) - 5) . " more\n";
        }
        $checks[] = ['check' => 'API routes registered', 'status' => 'PASS'];
        $passed++;
    } else {
        echo "   ⚠ No sound box routes found\n";
        $checks[] = ['check' => 'API routes registered', 'status' => 'WARN'];
    }
} catch (\Exception $e) {
    echo "   ⚠ Could not check routes: {$e->getMessage()}\n";
    $checks[] = ['check' => 'API routes registered', 'status' => 'WARN'];
}

// Summary
echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total checks: " . count($checks) . "\n";
echo "Passed: {$passed} ✓\n";
echo "Failed: {$failed} ✗\n";

if ($failed === 0) {
    echo "\n✅ All critical checks passed! Sound box system is ready.\n";
} else {
    echo "\n⚠️  Some checks failed. Review the output above.\n";
}

echo "\nNext Steps:\n";
echo "1. Set SOUNDBOX_ENABLED=true in .env file\n";
echo "2. Configure webhook settings in config/soundbox.php\n";
echo "3. Generate pairing QR code from vendor app\n";
echo "4. Pair your ESP32 device\n";
echo "5. Test with a real order\n";

echo "\n========================================\n\n";

exit($failed === 0 ? 0 : 1);
