<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryMan;
use App\Models\DeliveryHistory;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DeactivateInactiveDeliveryMen extends Command
{
    protected $signature = 'dm:deactivate-inactive {--days=7 : Number of days of inactivity before deactivation}';

    protected $description = 'Deactivate delivery men who have not sent live location updates in the last N days';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $this->info("Checking for delivery men inactive since {$cutoff->toDateTimeString()} ({$days} days)...");

        // Get IDs of delivery men who HAVE sent location updates within the cutoff
        $activeDeliveryManIds = DeliveryHistory::where('created_at', '>=', $cutoff)
            ->distinct()
            ->pluck('delivery_man_id')
            ->toArray();

        // Find currently active & approved delivery men who are NOT in the active list
        $inactiveQuery = DeliveryMan::withoutGlobalScopes()
            ->where('status', 1)
            ->where('application_status', 'approved')
            ->whereNotIn('id', $activeDeliveryManIds);

        $inactiveCount = $inactiveQuery->count();

        if ($inactiveCount === 0) {
            $this->info('All active delivery men have sent location updates within the last ' . $days . ' days. No action needed.');
            Log::info("dm:deactivate-inactive - No inactive delivery men found (cutoff: {$days} days)");
            return 0;
        }

        $this->warn("Found {$inactiveCount} delivery men with no location updates in the last {$days} days.");

        // Get their details for logging
        $inactiveList = $inactiveQuery->select('id', 'f_name', 'l_name', 'phone')->get();

        $this->table(
            ['ID', 'Name', 'Phone'],
            $inactiveList->map(fn($dm) => [$dm->id, $dm->f_name . ' ' . $dm->l_name, $dm->phone])->toArray()
        );

        // Deactivate them (set status = 0)
        $updated = DeliveryMan::withoutGlobalScopes()
            ->where('status', 1)
            ->where('application_status', 'approved')
            ->whereNotIn('id', $activeDeliveryManIds)
            ->update(['status' => 0]);

        $this->info("Deactivated {$updated} delivery men.");

        // Log the deactivation
        $ids = $inactiveList->pluck('id')->toArray();
        Log::warning("dm:deactivate-inactive - Deactivated {$updated} delivery men with no location updates in {$days} days. IDs: " . implode(',', $ids));

        // Send push notification to deactivated delivery men
        foreach ($inactiveList as $dm) {
            $fullDm = DeliveryMan::withoutGlobalScopes()->find($dm->id);
            if ($fullDm && $fullDm->fcm_token) {
                try {
                    \App\CentralLogics\Helpers::send_push_notif_to_device($fullDm->fcm_token, [
                        'title' => 'Account Deactivated',
                        'description' => 'Your account has been deactivated due to no live location updates in the last ' . $days . ' days. Please contact admin to reactivate.',
                        'image' => '',
                        'order_id' => '',
                        'type' => 'account_status',
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to notify deactivated DM#{$dm->id}: " . $e->getMessage());
                }
            }
        }

        return 0;
    }
}
