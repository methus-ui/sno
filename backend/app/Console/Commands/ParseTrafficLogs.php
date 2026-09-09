<?php

namespace App\Console\Commands;

use App\Models\TrafficLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ParseTrafficLogs extends Command
{
    protected $signature = 'analytics:parse-traffic {--date= : Date to parse (YYYY-MM-DD), defaults to yesterday}';
    protected $description = 'Parse Apache access logs to extract app traffic metrics';

    private string $logFile = '/var/log/apache2/new_snocart_access.log';

    public function handle()
    {
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $dateStr = $targetDate->format('Y-m-d');
        $this->info("Parsing traffic for: {$dateStr}");

        if (!file_exists($this->logFile)) {
            $this->error("Log file not found: {$this->logFile}");
            return;
        }

        $uniqueIps = [];
        $totalRequests = 0;
        $customerRequests = 0;
        $dmRequests = 0;
        $vendorRequests = 0;
        $appOpens = 0;
        $endpointCounts = [];
        $hourlyDist = array_fill(0, 24, 0);

        $handle = fopen($this->logFile, 'r');
        if (!$handle) {
            $this->error("Cannot open log file");
            return;
        }

        // Also check rotated log
        $files = [$this->logFile];
        if (file_exists($this->logFile . '.1')) {
            $files[] = $this->logFile . '.1';
        }

        foreach ($files as $file) {
            $handle = fopen($file, 'r');
            if (!$handle) continue;

            while (($line = fgets($handle)) !== false) {
                // Only app traffic (Dart user-agent)
                if (strpos($line, 'Dart/') === false) continue;

                // Parse: IP - - [date:time +0000] "METHOD /path HTTP/1.1" status size
                if (!preg_match('/^(\S+) .+ \[(\d{2})\/(\w{3})\/(\d{4}):(\d{2}):\d{2}:\d{2} .+\] "(\w+) (\S+)/', $line, $m)) {
                    continue;
                }

                $ip = $m[1];
                $day = $m[2];
                $month = $m[3];
                $year = $m[4];
                $hour = (int)$m[5];
                $method = $m[6];
                $endpoint = $m[7];

                // Parse log date and compare
                $logDate = Carbon::createFromFormat('d/M/Y', "{$day}/{$month}/{$year}")->format('Y-m-d');
                if ($logDate !== $dateStr) continue;

                $totalRequests++;
                $uniqueIps[$ip] = true;
                $hourlyDist[$hour]++;

                // Normalize endpoint (strip query params and IDs)
                $cleanEndpoint = preg_replace('/\?.*/', '', $endpoint);
                $cleanEndpoint = preg_replace('/\/\d+/', '/{id}', $cleanEndpoint);

                // Count endpoints
                $endpointCounts[$cleanEndpoint] = ($endpointCounts[$cleanEndpoint] ?? 0) + 1;

                // Categorize
                if (strpos($endpoint, '/api/v1/customer') !== false || strpos($endpoint, '/api/v1/config') !== false ||
                    strpos($endpoint, '/api/v1/banners') !== false || strpos($endpoint, '/api/v1/categories') !== false ||
                    strpos($endpoint, '/api/v1/stores') !== false || strpos($endpoint, '/api/v1/items') !== false) {
                    $customerRequests++;
                } elseif (strpos($endpoint, '/api/v1/delivery-man') !== false) {
                    $dmRequests++;
                } elseif (strpos($endpoint, '/api/v1/vendor') !== false) {
                    $vendorRequests++;
                }

                // App opens = GET /api/v1/config (first call on app launch)
                if ($method === 'GET' && preg_match('#^/api/v1/config(\?|$)#', $endpoint)) {
                    $appOpens++;
                }
            }

            fclose($handle);
        }

        if ($totalRequests === 0) {
            $this->warn("No app traffic found for {$dateStr}");
            return;
        }

        // Top 20 endpoints
        arsort($endpointCounts);
        $topEndpoints = array_slice($endpointCounts, 0, 20, true);

        // Peak hour
        $peakHour = array_search(max($hourlyDist), $hourlyDist);

        TrafficLog::updateOrCreate(
            ['date' => $dateStr],
            [
                'unique_visitors' => count($uniqueIps),
                'total_requests' => $totalRequests,
                'customer_requests' => $customerRequests,
                'dm_requests' => $dmRequests,
                'vendor_requests' => $vendorRequests,
                'app_opens' => $appOpens,
                'peak_hour' => $peakHour,
                'top_endpoints' => $topEndpoints,
                'hourly_distribution' => $hourlyDist,
            ]
        );

        $this->info("Visitors: " . count($uniqueIps));
        $this->info("Total requests: {$totalRequests}");
        $this->info("Customer: {$customerRequests} | DM: {$dmRequests} | Vendor: {$vendorRequests}");
        $this->info("App opens: {$appOpens}");
        $this->info("Peak hour: {$peakHour}:00");

        Log::info("Traffic parsed for {$dateStr}", [
            'visitors' => count($uniqueIps),
            'requests' => $totalRequests,
            'app_opens' => $appOpens,
        ]);
    }
}
