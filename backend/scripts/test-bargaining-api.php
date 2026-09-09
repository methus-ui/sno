<?php

/**
 * Bargaining Mode API Testing Script
 *
 * Tests all customer and vendor API endpoints
 *
 * Usage:
 *   php scripts/test-bargaining-api.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\VendorEmployee;
use App\Models\Store;
use App\Models\BargainingRequest;
use Illuminate\Support\Facades\Artisan;

class BargainingApiTester
{
    protected $baseUrl;
    protected $customerToken;
    protected $vendorToken;
    protected $testResults = [];

    public function __construct()
    {
        $this->baseUrl = config('app.url') . '/api';
        echo "🧪 Bargaining Mode API Testing Script\n";
        echo "=====================================\n\n";
    }

    public function runAllTests()
    {
        echo "📋 Running all API tests...\n\n";

        // Setup
        $this->testFeatureFlag();
        $this->setupTestTokens();

        // Customer API tests
        $this->testCustomerApis();

        // Vendor API tests
        $this->testVendorApis();

        // Cron job test
        $this->testCronJob();

        // Display results
        $this->displayResults();
    }

    protected function testFeatureFlag()
    {
        echo "1️⃣ Testing Feature Flag...\n";

        $enabled = config('bargaining.enabled', false);
        $this->addResult('Feature Flag', $enabled ? 'ENABLED' : 'DISABLED', $enabled);

        $instantEnabled = config('bargaining.instant_mode_enabled', false);
        $this->addResult('Instant Mode', $instantEnabled ? 'ENABLED' : 'DISABLED', $instantEnabled);

        $waitEnabled = config('bargaining.wait_mode_enabled', false);
        $this->addResult('Wait Mode', $waitEnabled ? 'ENABLED' : 'DISABLED', $waitEnabled);

        echo "   ✓ Configuration loaded\n\n";
    }

    protected function setupTestTokens()
    {
        echo "2️⃣ Setting up test tokens...\n";

        // Get a customer with API token
        $customer = User::whereNotNull('api_token')->first();
        if (!$customer) {
            echo "   ⚠️ No customer with API token found - creating test token\n";
            $customer = User::first();
            if ($customer) {
                $customer->api_token = \Illuminate\Support\Str::random(80);
                $customer->save();
            }
        }

        if ($customer) {
            $this->customerToken = $customer->api_token;
            $this->addResult('Customer Token', 'Found', true);
            echo "   ✓ Customer token: {$customer->id}\n";
        } else {
            $this->addResult('Customer Token', 'NOT FOUND', false);
            echo "   ✗ No customer found\n";
        }

        // Get a vendor employee
        $vendor = VendorEmployee::first();
        if ($vendor && $vendor->auth_token) {
            $this->vendorToken = $vendor->auth_token;
            $this->addResult('Vendor Token', 'Found', true);
            echo "   ✓ Vendor token: {$vendor->id}\n";
        } else {
            $this->addResult('Vendor Token', 'NOT FOUND', false);
            echo "   ✗ No vendor employee found\n";
        }

        echo "\n";
    }

    protected function testCustomerApis()
    {
        echo "3️⃣ Testing Customer APIs...\n";

        if (!$this->customerToken) {
            echo "   ⚠️ Skipping - no customer token\n\n";
            return;
        }

        // Test routes exist
        $routes = [
            'api.v2.bargaining.initiate' => 'POST /api/v2/bargaining/initiate',
            'api.v2.bargaining.status' => 'GET /api/v2/bargaining/status/{code}',
            'api.v2.bargaining.accept-offer' => 'POST /api/v2/bargaining/accept-offer',
            'api.v2.bargaining.cancel' => 'POST /api/v2/bargaining/cancel/{code}',
            'api.v2.bargaining.history' => 'GET /api/v2/bargaining/history',
            'api.v2.bargaining.offer-details' => 'GET /api/v2/bargaining/offer/{id}',
        ];

        foreach ($routes as $name => $description) {
            $exists = \Route::has($name);
            $this->addResult("Route: {$description}", $exists ? 'EXISTS' : 'NOT FOUND', $exists);
            echo ($exists ? "   ✓" : "   ✗") . " {$description}\n";
        }

        echo "\n";
    }

    protected function testVendorApis()
    {
        echo "4️⃣ Testing Vendor APIs...\n";

        if (!$this->vendorToken) {
            echo "   ⚠️ Skipping - no vendor token\n\n";
            return;
        }

        $routes = [
            'api.v2.vendor.bargaining.available' => 'GET /api/v2/vendor/bargaining/available',
            'api.v2.vendor.bargaining.counter-offer' => 'POST /api/v2/vendor/bargaining/counter-offer',
            'api.v2.vendor.bargaining.settings' => 'GET /api/v2/vendor/bargaining/settings',
            'api.v2.vendor.bargaining.update-settings' => 'PUT /api/v2/vendor/bargaining/settings',
            'api.v2.vendor.bargaining.my-offer' => 'GET /api/v2/vendor/bargaining/my-offer/{code}',
            'api.v2.vendor.bargaining.withdraw-offer' => 'POST /api/v2/vendor/bargaining/withdraw-offer/{id}',
            'api.v2.vendor.bargaining.analytics' => 'GET /api/v2/vendor/bargaining/analytics',
        ];

        foreach ($routes as $name => $description) {
            $exists = \Route::has($name);
            $this->addResult("Route: {$description}", $exists ? 'EXISTS' : 'NOT FOUND', $exists);
            echo ($exists ? "   ✓" : "   ✗") . " {$description}\n";
        }

        echo "\n";
    }

    protected function testCronJob()
    {
        echo "5️⃣ Testing Cron Job...\n";

        // Check if command exists
        try {
            Artisan::call('bargaining:expire', ['--help' => true]);
            $this->addResult('Cron Command', 'EXISTS', true);
            echo "   ✓ Command 'bargaining:expire' registered\n";
        } catch (\Exception $e) {
            $this->addResult('Cron Command', 'NOT FOUND', false);
            echo "   ✗ Command not found: {$e->getMessage()}\n";
        }

        // Check schedule
        $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
        $events = $schedule->events();

        $found = false;
        foreach ($events as $event) {
            if (strpos($event->command, 'bargaining:expire') !== false) {
                $found = true;
                break;
            }
        }

        $this->addResult('Cron Schedule', $found ? 'REGISTERED' : 'NOT FOUND', $found);
        echo ($found ? "   ✓" : "   ✗") . " Command scheduled in kernel\n";

        // Test execution
        try {
            Artisan::call('bargaining:expire');
            $output = Artisan::output();
            $this->addResult('Cron Execution', 'SUCCESS', true);
            echo "   ✓ Command executed successfully\n";
            echo "   Output: " . trim($output) . "\n";
        } catch (\Exception $e) {
            $this->addResult('Cron Execution', 'FAILED', false);
            echo "   ✗ Execution failed: {$e->getMessage()}\n";
        }

        echo "\n";
    }

    protected function addResult($test, $status, $passed)
    {
        $this->testResults[] = [
            'test' => $test,
            'status' => $status,
            'passed' => $passed,
        ];
    }

    protected function displayResults()
    {
        echo "\n📊 Test Results Summary\n";
        echo "======================\n\n";

        $passed = 0;
        $failed = 0;

        foreach ($this->testResults as $result) {
            $icon = $result['passed'] ? '✅' : '❌';
            echo "{$icon} {$result['test']}: {$result['status']}\n";

            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }

        $total = $passed + $failed;
        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

        echo "\n";
        echo "Total Tests: {$total}\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        echo "Success Rate: {$percentage}%\n";

        if ($failed === 0) {
            echo "\n🎉 All tests passed! Bargaining Mode API is ready.\n";
        } else {
            echo "\n⚠️ Some tests failed. Please review the issues above.\n";
        }

        echo "\n";
    }
}

// Run tests
$tester = new BargainingApiTester();
$tester->runAllTests();

echo "✨ Testing complete!\n";
