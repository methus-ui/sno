#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🔍 Testing Dashboard Direct Access...\n\n";

try {
    echo "1. Creating controller instance...\n";

    // Try to instantiate the controller
    $apiService = app()->make('App\Services\WhatsAppApiService');
    $segmentService = app()->make('App\Services\CustomerSegmentationService');

    $controller = new App\Http\Controllers\Admin\WhatsApp\DashboardController(
        $apiService,
        $segmentService
    );

    echo "   ✅ Controller created successfully\n\n";

    echo "2. Calling index method...\n";

    $response = $controller->index();

    echo "   ✅ Index method executed\n";
    echo "   Response type: " . get_class($response) . "\n\n";

    if ($response instanceof Illuminate\View\View) {
        echo "3. View details:\n";
        echo "   View name: " . $response->name() . "\n";
        echo "   Data keys: " . implode(', ', array_keys($response->getData())) . "\n\n";

        $data = $response->getData();
        if (isset($data['stats'])) {
            echo "4. Stats data:\n";
            foreach ($data['stats'] as $key => $value) {
                echo "   {$key}: {$value}\n";
            }
        }
    }

    echo "\n✅ Dashboard is working correctly!\n";
    echo "\nYou can now visit: https://new.snocart.com/admin/whatsapp\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR FOUND:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n   Stack trace:\n";

    $trace = $e->getTrace();
    foreach (array_slice($trace, 0, 5) as $i => $t) {
        echo "   #{$i} ";
        if (isset($t['file'])) {
            echo basename($t['file']) . ":" . ($t['line'] ?? '?');
        }
        if (isset($t['class'])) {
            echo " {$t['class']}{$t['type']}{$t['function']}()";
        }
        echo "\n";
    }

    echo "\n🔧 How to fix:\n";

    if (strpos($e->getMessage(), 'Class') !== false && strpos($e->getMessage(), 'not found') !== false) {
        echo "   - Run: composer dump-autoload\n";
    }

    if (strpos($e->getMessage(), 'Table') !== false || strpos($e->getMessage(), 'Column') !== false) {
        echo "   - Run: php artisan migrate\n";
    }

    if (strpos($e->getMessage(), 'binding') !== false || strpos($e->getMessage(), 'resolve') !== false) {
        echo "   - Service not registered in container\n";
        echo "   - Check app/Providers/AppServiceProvider.php\n";
    }
}
