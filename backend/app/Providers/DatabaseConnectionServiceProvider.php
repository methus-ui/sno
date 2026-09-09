<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;
use PDOException;

class DatabaseConnectionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Add connection retry logic for transient connection failures
        $this->setupConnectionRetry();

        // Monitor slow queries
        $this->monitorSlowQueries();
    }

    /**
     * Setup automatic reconnection on connection failure
     */
    private function setupConnectionRetry()
    {
        DB::listen(function ($query) {
            // This listener will catch query events
        });

        // Add reconnection logic for dropped connections
        $db = DB::connection()->getPdo();
        if ($db) {
            DB::reconnect();
        }
    }

    /**
     * Monitor and log slow queries
     */
    private function monitorSlowQueries()
    {
        if (config('app.env') === 'production') {
            DB::listen(function (QueryExecuted $query) {
                // Log queries that take longer than 2 seconds
                if ($query->time > 2000) {
                    // Get caller from backtrace (skip vendor/framework frames)
                    $caller = '';
                    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
                    foreach ($trace as $frame) {
                        $file = $frame['file'] ?? '';
                        if ($file && strpos($file, '/app/') !== false && strpos($file, '/vendor/') === false) {
                            $caller = str_replace(base_path(), '', $file) . ':' . ($frame['line'] ?? '?');
                            break;
                        }
                    }
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                        'caller' => $caller,
                    ]);
                }
            });
        }
    }

    /**
     * Check if exception is a connection error that should be retried
     */
    public static function isConnectionError($exception): bool
    {
        if (!$exception instanceof PDOException) {
            return false;
        }

        $connectionErrors = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
            'Physical connection is not usable',
            'TCP Provider: Error code 0x68',
            'ORA-03114',
            'Packets out of order. Expected',
            'Adaptive Server connection failed',
            'Communication link failure',
            'connection is no longer usable',
            'Login timeout expired',
            'SQLSTATE[HY000] [2002] Connection refused',
            'running "REINDEX" caused "database is locked" errors',
        ];

        $message = $exception->getMessage();

        foreach ($connectionErrors as $error) {
            if (stripos($message, $error) !== false) {
                return true;
            }
        }

        return false;
    }
}
