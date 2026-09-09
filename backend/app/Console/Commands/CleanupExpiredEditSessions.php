<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderEditSession;
use Carbon\Carbon;

class CleanupExpiredEditSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cleanup-edit-sessions {--hours=24 : Hours of inactivity before cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired order edit sessions older than specified hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');

        $this->info("Cleaning up order edit sessions inactive for more than {$hours} hours...");

        $expiredSessions = OrderEditSession::where('last_activity', '<', Carbon::now()->subHours($hours))
            ->get();

        $count = $expiredSessions->count();

        if ($count > 0) {
            foreach ($expiredSessions as $session) {
                $this->line("Deleting session for Order #{$session->order_id} by Admin #{$session->admin_id}");
                $session->delete();
            }

            $this->info("✓ Cleaned up {$count} expired edit session(s)");
        } else {
            $this->info("✓ No expired sessions found");
        }

        return 0;
    }
}
