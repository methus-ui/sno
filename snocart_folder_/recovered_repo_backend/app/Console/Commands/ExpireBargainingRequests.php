<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BargainingRequest;
use Illuminate\Support\Facades\Log;

class ExpireBargainingRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bargaining:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire old bargaining requests that have passed their expiration time';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!config('bargaining.enabled', true)) {
            $this->info('Bargaining mode is disabled - skipping expiration check');
            return 0;
        }

        // Get all expired requests that haven't been marked as expired yet
        $expiredRequests = BargainingRequest::where('expires_at', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled', 'expired', 'accepted'])
            ->get();

        if ($expiredRequests->isEmpty()) {
            $this->info('No expired requests found');
            return 0;
        }

        $count = 0;
        foreach ($expiredRequests as $request) {
            try {
                $request->update([
                    'status' => 'expired',
                ]);

                // Update all pending offers to expired
                $request->storeOffers()
                    ->where('status', 'submitted')
                    ->update(['status' => 'expired']);

                $count++;

                Log::info('Bargaining request expired', [
                    'request_code' => $request->request_code,
                    'user_id' => $request->user_id,
                    'expired_at' => $request->expires_at,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to expire bargaining request', [
                    'request_code' => $request->request_code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Expired {$count} bargaining requests");
        Log::info("Bargaining expiration cron completed", ['expired_count' => $count]);

        return 0;
    }
}
