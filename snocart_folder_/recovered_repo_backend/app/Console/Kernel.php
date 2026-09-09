<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CompressImages::class,
        Commands\CleanupUnusedImages::class,
        Commands\OptimizeImages::class,
        Commands\ConvertImagesToWebpSafe::class,
        Commands\SendAbandonedCartNotifications::class,
        Commands\CalculateSnoscore::class, // Snoscore backfill command
        Commands\SendDeliveryBoyOfDayNotification::class, // Every 2 hours
        Commands\SendRushTimeNotification::class, // Rush time alerts
        Commands\DeactivateInactiveDeliveryMen::class, // Deactivate DMs with no location updates
        Commands\SetOfflineInactiveDeliveryMen::class, // Mark DMs offline if no location update in 24h
        Commands\AutoGenerateWeeklyRoster::class,
        Commands\CheckAttendanceViolations::class,
        Commands\CheckRushConditions::class,
        Commands\DeactivateExpiredRush::class,
        Commands\UpdateDmPerformanceTiers::class,
        Commands\GenerateLeaderboard::class,
        Commands\AutoAssignOrders::class,
        Commands\DmMinWageCheck::class,
        Commands\SendInactiveCustomerNotification::class,
        Commands\SendWinbackNotification::class,
        Commands\SendOrderMilestoneNotification::class,
        Commands\SendWeeklyBroadcastNotification::class,
        Commands\ParseTrafficLogs::class,
        Commands\CleanupExpiredEditSessions::class,
        Commands\WarmCaches::class, // Performance optimization
        Commands\ClearModuleCache::class, // Module-specific cache clearing
        Commands\WaveOffIncompleteShiftIncentives::class, // Wave off incentives for incomplete shifts
        Commands\SendShiftBookingReminder::class,         // Shift start reminders for booked DMs
        Commands\ProcessShiftCompletions::class,          // Auto-complete / miss shift slots after end_time
        Commands\ExpireBargainingRequests::class,         // Expire old bargaining requests
        Commands\WaBlast::class,                          // WhatsApp campaign blast
        Commands\WhatsAppSeedSegments::class,             // Seed WhatsApp customer segments
        Commands\WhatsAppRefreshSegments::class,          // Refresh segment counts and cache
        Commands\WhatsAppProcessScheduled::class,         // Process scheduled campaigns
        Commands\ExpireStuckWalletPayments::class,        // Expire stuck wallet payments
        Commands\ReconcileWalletPayments::class,          // Reconcile wallet payments with Razorpay
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // Optional: Schedule automatic cleanup monthly
        // $schedule->command('images:cleanup')->monthly();

        // Optional: Schedule automatic image compression weekly
        // $schedule->command('images:compress')->weekly();

        // ✅ Add abandoned cart notifications - runs every 5 minutes
        $schedule->command('cart:send-abandoned-notifications')
                 ->everyFiveMinutes()
                 ->withoutOverlapping();

        // ✅ Delivery Boy of the Day notification - runs every 2 hours
        $schedule->command('notification:delivery-boy-of-day')
                 ->everyTwoHours()
                 ->withoutOverlapping();

        // ✅ Rush Time Alert - checks every 5 minutes for hot carts
        $schedule->command('notification:rush-time')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->between('8:00', '23:00'); // Only during business hours

        // ✅ Deactivate delivery men with no live location updates in last 7 days - runs daily at midnight
        $schedule->command('dm:deactivate-inactive')
                 ->dailyAt('00:00')
                 ->withoutOverlapping();

        // ✅ Mark delivery men offline (active=0) if no location update in last 24h - runs every hour
        $schedule->command('dm:set-offline-inactive')
                 ->hourly()
                 ->withoutOverlapping();

        // Auto-generate next week's roster every Friday at 11 PM
        $schedule->command('roster:auto-generate-weekly')
                 ->weeklyOn(5, '23:00')
                 ->withoutOverlapping();

        // Check attendance violations daily at 1 AM
        $schedule->command('attendance:check-violations')
                 ->dailyAt('01:00')
                 ->withoutOverlapping();

        // ✅ Shift booking reminders — every 30 minutes (window=30 so no double-sends)
        $schedule->command('shift:send-booking-reminders --minutes=60 --window=30')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping();

        // ✅ Auto-complete / mark missed shift slots — every 15 minutes
        $schedule->command('shift:process-completions')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping();

        // ✅ Wave off pending incentives for incomplete shifts - daily at 1:30 AM
        $schedule->command('dm:waveoff-incomplete-shift-incentives')
                 ->dailyAt('01:30')
                 ->withoutOverlapping();

        // ✅ Rush condition check - every 5 minutes
        $schedule->command('dm:check-rush-conditions')
                 ->everyFiveMinutes()
                 ->withoutOverlapping();

        // ✅ Deactivate expired rush incentives - every minute
        $schedule->command('dm:deactivate-expired-rush')
                 ->everyMinute()
                 ->withoutOverlapping();

        // ✅ Update DM performance tiers - daily at midnight
        $schedule->command('dm:update-tiers')
                 ->dailyAt('00:05')
                 ->withoutOverlapping();

        // ✅ Generate daily leaderboard - daily at 23:59
        $schedule->command('dm:generate-leaderboard --type=daily')
                 ->dailyAt('23:59')
                 ->withoutOverlapping();

        // ✅ Generate weekly leaderboard - Sunday at 23:58
        $schedule->command('dm:generate-leaderboard --type=weekly')
                 ->weeklyOn(0, '23:58')
                 ->withoutOverlapping();

        // ✅ Generate monthly leaderboard - last day of month at 23:57
        $schedule->command('dm:generate-leaderboard --type=monthly')
                 ->monthlyOn(28, '23:57')
                 ->withoutOverlapping();

        // ✅ Auto-assign orders - every 30 seconds (runs every minute with 2 passes)
        $schedule->command('dm:auto-assign-orders')
                 ->everyMinute()
                 ->withoutOverlapping();

        // ✅ Min wage check - daily at 1 AM for previous day
        $schedule->command('dm:min-wage-check')
                 ->dailyAt('01:00')
                 ->withoutOverlapping();

        // ✅ Inactive customer re-engagement - daily at 10 AM
        $schedule->command('notification:inactive-customer')
                 ->dailyAt('10:00')
                 ->withoutOverlapping();

        // ✅ Win-back churned customers - every Monday at 11 AM
        $schedule->command('notification:winback-customer')
                 ->weeklyOn(1, '11:00')
                 ->withoutOverlapping();

        // ✅ Order milestone congratulations - daily at 9 PM
        $schedule->command('notification:order-milestone')
                 ->dailyAt('21:00')
                 ->withoutOverlapping();

        // ✅ Weekly broadcast - every Friday at 11 AM
        $schedule->command('notification:weekly-broadcast')
                 ->weeklyOn(5, '11:00')
                 ->withoutOverlapping();

        // ✅ Parse traffic logs - daily at 00:30 (for previous day)
        $schedule->command('analytics:parse-traffic')
                 ->dailyAt('00:30')
                 ->withoutOverlapping();

        // ✅ Clean up expired order edit sessions - daily at 02:00
        $schedule->call(function () {
            \App\Models\OrderEditSession::expired()->delete();
        })->dailyAt('02:00');

        // ✅ Clear old log files weekly (keeps logs under control)
        $schedule->call(function () {
            $logPath = storage_path('logs');
            $files = glob($logPath . '/laravel-*.log');
            $daysToKeep = 7;
            foreach ($files as $file) {
                if (filemtime($file) < strtotime("-{$daysToKeep} days")) {
                    unlink($file);
                }
            }
        })->weeklyOn(0, '23:30'); // Sunday at 11:30 PM

        // ✅ Warm application caches - every 10 minutes for optimal performance
        $schedule->command('cache:warm')
                 ->everyTenMinutes()
                 ->withoutOverlapping();

        // ✅ Expire old bargaining requests - every minute
        $schedule->command('bargaining:expire')
                 ->everyMinute()
                 ->withoutOverlapping();

        // ✅ Refresh WhatsApp customer segment counts - daily at 3 AM
        $schedule->command('whatsapp:refresh-segments --all')
                 ->dailyAt('03:00')
                 ->withoutOverlapping();

        // ✅ Process scheduled WhatsApp campaigns - every minute
        $schedule->command('whatsapp:process-scheduled')
                 ->everyMinute()
                 ->withoutOverlapping();

        // ✅ Auto-expire stuck wallet payments - every 15 minutes (timeout=30 minutes)
        $schedule->command('wallet:expire-stuck-payments --timeout=30')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping();

        // ✅ Reconcile wallet payments with Razorpay API - daily at 2 AM
        $schedule->command('wallet:reconcile-payments --days=7 --limit=100')
                 ->dailyAt('02:00')
                 ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
