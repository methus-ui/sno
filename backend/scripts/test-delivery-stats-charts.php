#!/usr/bin/env php
<?php

/**
 * Delivery Stats Charts Testing Script
 * Tests all chart APIs, performance, and data integrity
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\DeliveryStatsService;
use Carbon\Carbon;

class DeliveryStatsChartsTester
{
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;

    public function run()
    {
        $this->printHeader();

        $this->testChartAPIs();
        $this->testServiceMethods();
        $this->testPerformance();
        $this->testCaching();
        $this->testDataIntegrity();
        $this->testIndexes();

        $this->printSummary();
    }

    private function testChartAPIs()
    {
        $this->printSection("📡 Testing Chart API Endpoints");

        $endpoints = [
            '/admin/delivery-stats/chart/hourly',
            '/admin/delivery-stats/chart/delivery-time',
            '/admin/delivery-stats/chart/status',
            '/admin/delivery-stats/chart/trend',
            '/admin/delivery-stats/chart/revenue',
            '/admin/delivery-stats/chart/performers',
            '/admin/delivery-stats/chart/customers',
            '/admin/delivery-stats/chart/operations',
        ];

        foreach ($endpoints as $endpoint) {
            $this->testEndpoint($endpoint);
        }
    }

    private function testEndpoint($endpoint)
    {
        $this->totalTests++;

        try {
            $response = $this->makeRequest($endpoint);

            if ($response['status'] === 200 && isset($response['data']['success']) && $response['data']['success'] === true) {
                $this->passedTests++;
                $this->printSuccess("✓ {$endpoint} returned valid JSON");
                $this->results[] = ['test' => $endpoint, 'status' => 'PASS'];
            } else {
                $this->failedTests++;
                $this->printError("✗ {$endpoint} returned invalid response");
                $this->results[] = ['test' => $endpoint, 'status' => 'FAIL'];
            }
        } catch (\Exception $e) {
            $this->failedTests++;
            $this->printError("✗ {$endpoint} threw exception: " . $e->getMessage());
            $this->results[] = ['test' => $endpoint, 'status' => 'FAIL'];
        }
    }

    private function makeRequest($endpoint)
    {
        // Simulate HTTP request using controller methods
        $controller = new \App\Http\Controllers\Admin\DashboardController();
        $request = new \Illuminate\Http\Request([
            'zone_id' => 'all',
            'module_id' => 2,
            'date' => today()->format('Y-m-d')
        ]);

        $method = $this->getControllerMethod($endpoint);

        if (!$method) {
            throw new \Exception("Unknown endpoint: {$endpoint}");
        }

        try {
            $response = $controller->$method($request);
            $data = json_decode($response->getContent(), true);

            return [
                'status' => $response->getStatusCode(),
                'data' => $data
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getControllerMethod($endpoint)
    {
        $methods = [
            '/admin/delivery-stats/chart/hourly' => 'hourlyDistributionChart',
            '/admin/delivery-stats/chart/delivery-time' => 'deliveryTimeChart',
            '/admin/delivery-stats/chart/status' => 'statusBreakdownChart',
            '/admin/delivery-stats/chart/trend' => 'trendChart',
            '/admin/delivery-stats/chart/revenue' => 'revenueChart',
            '/admin/delivery-stats/chart/performers' => 'topPerformersChart',
            '/admin/delivery-stats/chart/customers' => 'customerInsightsChart',
            '/admin/delivery-stats/chart/operations' => 'operationalMetricsChart',
        ];

        return $methods[$endpoint] ?? null;
    }

    private function testServiceMethods()
    {
        $this->printSection("🔧 Testing Service Methods");

        $service = new DeliveryStatsService();
        $filters = ['zone_id' => 'all', 'module_id' => 2, 'date' => today()];

        $methods = [
            'getHourlyDistribution' => 'Hourly Distribution',
            'getDeliveryTimeDistribution' => 'Delivery Time Distribution',
            'getOrderStatusBreakdown' => 'Order Status Breakdown',
            'get90DayTrend' => '90-Day Trend',
            'getRevenueAnalysis' => 'Revenue Analysis',
            'getCustomerInsights' => 'Customer Insights',
            'getOperationalMetrics' => 'Operational Metrics',
        ];

        foreach ($methods as $method => $name) {
            $this->testServiceMethod($service, $method, $name, $filters);
        }

        // Test top performers with different types
        $performerTypes = ['all', 'delivery_men', 'stores', 'products'];
        foreach ($performerTypes as $type) {
            $this->testTopPerformers($service, $type, $filters);
        }
    }

    private function testServiceMethod($service, $method, $name, $filters)
    {
        $this->totalTests++;

        try {
            $data = $service->$method($filters);

            if ($data !== null) {
                $this->passedTests++;
                $this->printSuccess("✓ {$name} returned data");
                $this->results[] = ['test' => "Service::{$method}", 'status' => 'PASS'];
            } else {
                $this->failedTests++;
                $this->printError("✗ {$name} returned null");
                $this->results[] = ['test' => "Service::{$method}", 'status' => 'FAIL'];
            }
        } catch (\Exception $e) {
            $this->failedTests++;
            $this->printError("✗ {$name} threw exception: " . $e->getMessage());
            $this->results[] = ['test' => "Service::{$method}", 'status' => 'FAIL'];
        }
    }

    private function testTopPerformers($service, $type, $filters)
    {
        $this->totalTests++;

        try {
            $data = $service->getTopPerformers($filters, $type);

            if ($data !== null) {
                $this->passedTests++;
                $this->printSuccess("✓ Top Performers ({$type}) returned data");
                $this->results[] = ['test' => "TopPerformers::{$type}", 'status' => 'PASS'];
            } else {
                $this->failedTests++;
                $this->printError("✗ Top Performers ({$type}) returned null");
                $this->results[] = ['test' => "TopPerformers::{$type}", 'status' => 'FAIL'];
            }
        } catch (\Exception $e) {
            $this->failedTests++;
            $this->printError("✗ Top Performers ({$type}) threw exception: " . $e->getMessage());
            $this->results[] = ['test' => "TopPerformers::{$type}", 'status' => 'FAIL'];
        }
    }

    private function testPerformance()
    {
        $this->printSection("⚡ Testing Query Performance");

        $service = new DeliveryStatsService();
        $filters = ['zone_id' => 'all', 'module_id' => 2, 'date' => today()];

        // Test 90-day trend query (most expensive)
        $this->totalTests++;
        $start = microtime(true);
        Cache::flush(); // Clear cache to test actual query performance
        $data = $service->get90DayTrend($filters);
        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

        if ($duration < 200) {
            $this->passedTests++;
            $this->printSuccess("✓ 90-day trend query completed in {$duration}ms (< 200ms target)");
            $this->results[] = ['test' => '90-day query performance', 'status' => 'PASS'];
        } else {
            $this->failedTests++;
            $this->printError("✗ 90-day trend query took {$duration}ms (> 200ms target)");
            $this->results[] = ['test' => '90-day query performance', 'status' => 'FAIL'];
        }

        // Test hourly distribution (fast query)
        $this->totalTests++;
        $start = microtime(true);
        $data = $service->getHourlyDistribution($filters);
        $duration = (microtime(true) - $start) * 1000;

        if ($duration < 100) {
            $this->passedTests++;
            $this->printSuccess("✓ Hourly distribution query completed in {$duration}ms (< 100ms target)");
            $this->results[] = ['test' => 'Hourly query performance', 'status' => 'PASS'];
        } else {
            $this->failedTests++;
            $this->printError("✗ Hourly distribution query took {$duration}ms (> 100ms target)");
            $this->results[] = ['test' => 'Hourly query performance', 'status' => 'FAIL'];
        }
    }

    private function testCaching()
    {
        $this->printSection("💾 Testing Cache Functionality");

        $service = new DeliveryStatsService();
        $filters = ['zone_id' => 'all', 'module_id' => 2, 'date' => '2026-03-08']; // Fixed date for cache key consistency

        // Clear cache
        Cache::flush();

        // First call - should cache
        $this->totalTests++;
        DB::enableQueryLog();
        $data1 = $service->getHourlyDistribution($filters);
        $queriesFirstCall = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Second call - should use cache
        DB::enableQueryLog();
        $data2 = $service->getHourlyDistribution($filters);
        $queriesSecondCall = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Verify cache was created
        $cacheKey = 'delivery_stats_hourly_' . md5(json_encode($filters));
        $cacheExists = Cache::has($cacheKey);

        // Cache is working if:
        // 1. Cache key exists after first call
        // 2. Second call has same or fewer queries (some internal queries may still happen)
        if ($cacheExists && $queriesSecondCall <= $queriesFirstCall) {
            $this->passedTests++;
            $this->printSuccess("✓ Cache working (cache key exists, first call: {$queriesFirstCall} queries, second call: {$queriesSecondCall} queries)");
            $this->results[] = ['test' => 'Cache functionality', 'status' => 'PASS'];
        } else {
            $this->failedTests++;
            $this->printError("✗ Cache not working properly (cache exists: " . ($cacheExists ? 'YES' : 'NO') . ", first call: {$queriesFirstCall} queries, second call: {$queriesSecondCall} queries)");
            $this->results[] = ['test' => 'Cache functionality', 'status' => 'FAIL'];
        }
    }

    private function testDataIntegrity()
    {
        $this->printSection("🔍 Testing Data Integrity");

        $service = new DeliveryStatsService();
        $filters = ['zone_id' => 'all', 'module_id' => 2, 'date' => today()];

        // Test hourly distribution has 24 hours
        $this->totalTests++;
        $hourlyData = $service->getHourlyDistribution($filters);
        if (count($hourlyData) === 24) {
            $this->passedTests++;
            $this->printSuccess("✓ Hourly distribution has all 24 hours");
            $this->results[] = ['test' => 'Hourly data completeness', 'status' => 'PASS'];
        } else {
            $this->failedTests++;
            $this->printError("✗ Hourly distribution has " . count($hourlyData) . " hours instead of 24");
            $this->results[] = ['test' => 'Hourly data completeness', 'status' => 'FAIL'];
        }

        // Test delivery time buckets
        $this->totalTests++;
        $timeData = $service->getDeliveryTimeDistribution($filters);
        if (isset($timeData['buckets']) && count($timeData['buckets']) === 7) {
            $this->passedTests++;
            $this->printSuccess("✓ Delivery time has all 7 buckets");
            $this->results[] = ['test' => 'Time bucket completeness', 'status' => 'PASS'];
        } else {
            $this->failedTests++;
            $this->printError("✗ Delivery time has incorrect number of buckets");
            $this->results[] = ['test' => 'Time bucket completeness', 'status' => 'FAIL'];
        }
    }

    private function testIndexes()
    {
        $this->printSection("📊 Checking Analytics Indexes");

        $indexes = [
            ['table' => 'users', 'index' => 'idx_users_created_at'],
            ['table' => 'orders', 'index' => 'idx_orders_payment_method'],
            ['table' => 'orders', 'index' => 'idx_orders_module_status'],
            ['table' => 'order_details', 'index' => 'idx_order_details_item_id'],
        ];

        foreach ($indexes as $indexInfo) {
            $this->totalTests++;

            try {
                $indexExists = DB::select("SHOW INDEX FROM {$indexInfo['table']} WHERE Key_name = '{$indexInfo['index']}'");

                if (!empty($indexExists)) {
                    $this->passedTests++;
                    $this->printSuccess("✓ Index {$indexInfo['index']} exists on {$indexInfo['table']}");
                    $this->results[] = ['test' => $indexInfo['index'], 'status' => 'PASS'];
                } else {
                    $this->failedTests++;
                    $this->printError("✗ Index {$indexInfo['index']} missing on {$indexInfo['table']}");
                    $this->results[] = ['test' => $indexInfo['index'], 'status' => 'FAIL'];
                }
            } catch (\Exception $e) {
                $this->failedTests++;
                $this->printError("✗ Error checking index {$indexInfo['index']}: " . $e->getMessage());
                $this->results[] = ['test' => $indexInfo['index'], 'status' => 'FAIL'];
            }
        }
    }

    private function printHeader()
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║      DELIVERY STATS CHARTS - COMPREHENSIVE TEST SUITE      ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    private function printSection($title)
    {
        echo "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo " {$title}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }

    private function printSuccess($message)
    {
        echo "\033[0;32m{$message}\033[0m\n";
    }

    private function printError($message)
    {
        echo "\033[0;31m{$message}\033[0m\n";
    }

    private function printSummary()
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║                       TEST SUMMARY                          ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Total Tests:  {$this->totalTests}\n";
        echo "\033[0;32mPassed:       {$this->passedTests}\033[0m\n";
        echo "\033[0;31mFailed:       {$this->failedTests}\033[0m\n";
        echo "\n";

        if ($this->failedTests === 0) {
            echo "\033[0;32m✅ ALL TESTS PASSED! Delivery stats charts are production ready.\033[0m\n";
        } else {
            echo "\033[0;31m❌ SOME TESTS FAILED! Please review the errors above.\033[0m\n";
        }

        echo "\n";
    }
}

// Run the tester
$tester = new DeliveryStatsChartsTester();
$tester->run();
