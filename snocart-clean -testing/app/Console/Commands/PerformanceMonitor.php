<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Helpers\QueryCacheHelper;

class PerformanceMonitor extends Command
{
    protected $signature = 'performance:monitor {--output=console : Output format (console|json)}';
    protected $description = 'Monitor database and cache performance';

    public function handle()
    {
        $this->info('=== Performance Monitor ===');
        $this->info('Time: ' . now()->toDateTimeString());
        $this->newLine();

        $stats = $this->gatherStats();

        if ($this->option('output') === 'json') {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->displayStats($stats);
        return 0;
    }

    private function gatherStats(): array
    {
        return [
            'database' => $this->getDatabaseStats(),
            'cache' => $this->getCacheStats(),
            'slow_queries' => $this->getSlowQueryStats(),
            'table_sizes' => $this->getTableSizes(),
        ];
    }

    private function getDatabaseStats(): array
    {
        try {
            $status = DB::select("SHOW STATUS WHERE Variable_name IN ('Threads_connected', 'Queries', 'Slow_queries', 'Uptime')");
            $variables = DB::select("SHOW VARIABLES WHERE Variable_name IN ('max_connections', 'long_query_time')");

            $stats = [];
            foreach (array_merge($status, $variables) as $row) {
                $stats[$row->Variable_name] = $row->Value;
            }

            return $stats;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getCacheStats(): array
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Cache::getRedis();
                $info = $redis->info();
                
                return [
                    'driver' => 'redis',
                    'keys' => $redis->dbSize(),
                    'memory_used' => $info['used_memory_human'] ?? 'N/A',
                    'hit_rate' => $this->calculateHitRate($info),
                ];
            }

            return [
                'driver' => config('cache.default'),
                'note' => 'Stats only available for Redis'
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function calculateHitRate($info): string
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total == 0) {
            return 'N/A';
        }

        $rate = ($hits / $total) * 100;
        return round($rate, 2) . '%';
    }

    private function getSlowQueryStats(): array
    {
        try {
            // Read last 100 lines of slow query log
            $slowLogFile = '/var/log/mysql/slow.log';
            
            if (!file_exists($slowLogFile)) {
                return ['note' => 'Slow query log not accessible'];
            }

            $lastLines = shell_exec("sudo tail -100 {$slowLogFile} 2>/dev/null");
            $queryCount = substr_count($lastLines, 'Query_time');

            return [
                'recent_slow_queries' => $queryCount,
                'threshold' => '2 seconds',
                'log_file' => $slowLogFile,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getTableSizes(): array
    {
        try {
            $tables = DB::select("
                SELECT 
                    table_name,
                    ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                    table_rows
                FROM information_schema.tables
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ", [config('database.connections.mysql.database')]);

            return array_map(function($table) {
                return [
                    'name' => $table->table_name,
                    'size_mb' => $table->size_mb,
                    'rows' => $table->table_rows,
                ];
            }, $tables);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function displayStats(array $stats): void
    {
        // Database Stats
        $this->info('📊 Database Stats');
        $this->line('Connections: ' . ($stats['database']['Threads_connected'] ?? 'N/A'));
        $this->line('Max Connections: ' . ($stats['database']['max_connections'] ?? 'N/A'));
        $this->line('Total Queries: ' . ($stats['database']['Queries'] ?? 'N/A'));
        $this->line('Slow Queries: ' . ($stats['database']['Slow_queries'] ?? 'N/A'));
        $this->newLine();

        // Cache Stats
        $this->info('💾 Cache Stats');
        $this->line('Driver: ' . ($stats['cache']['driver'] ?? 'N/A'));
        if (isset($stats['cache']['keys'])) {
            $this->line('Cached Keys: ' . $stats['cache']['keys']);
            $this->line('Memory Used: ' . $stats['cache']['memory_used']);
            $this->line('Hit Rate: ' . $stats['cache']['hit_rate']);
        }
        $this->newLine();

        // Slow Queries
        $this->info('🐌 Slow Query Stats');
        $this->line('Recent Slow Queries: ' . ($stats['slow_queries']['recent_slow_queries'] ?? 'N/A'));
        $this->line('Threshold: ' . ($stats['slow_queries']['threshold'] ?? 'N/A'));
        $this->newLine();

        // Top Tables
        $this->info('📁 Largest Tables');
        if (isset($stats['table_sizes']) && is_array($stats['table_sizes']) && !isset($stats['table_sizes']['error'])) {
            foreach (array_slice($stats['table_sizes'], 0, 5) as $table) {
                if (is_array($table) && isset($table['name'])) {
                    $this->line(sprintf(
                        '  %s: %s MB (%s rows)',
                        $table['name'],
                        $table['size_mb'],
                        number_format($table['rows'])
                    ));
                }
            }
        } elseif (isset($stats['table_sizes']['error'])) {
            $this->line('  Error: ' . $stats['table_sizes']['error']);
        }
    }
}
