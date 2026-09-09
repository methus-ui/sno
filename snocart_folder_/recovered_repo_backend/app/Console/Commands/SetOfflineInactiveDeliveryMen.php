<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryMan;
use App\Models\DeliveryHistory;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SetOfflineInactiveDeliveryMen extends Command
{
    protected $signature = 'dm:set-offline-inactive {--hours=24 : Hours of no location update before marking offline}';

    protected $description = 'Mark delivery men as offline (active=0) if no location update received in the last N hours';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = Carbon::now()->subHours($hours);

        $this->info("Marking DMs offline if no location update since {$cutoff->toDateTimeString()} ({$hours}h)...");

        // delivery_histories has a unique index on delivery_man_id (upsert),
        // so updated_at = the last time a location was received for that DM.
        $recentlyActiveDmIds = DeliveryHistory::where('updated_at', '>=', $cutoff)
            ->distinct()
            ->pluck('delivery_man_id')
            ->toArray();

        // DMs currently marked online (active=1) who have NOT sent a location update
        // within the cutoff window
        $query = DeliveryMan::withoutGlobalScopes()
            ->where('active', 1)
            ->where('application_status', 'approved')
            ->whereNotIn('id', $recentlyActiveDmIds);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No stale online DMs found. All online DMs have recent location updates.');
            Log::info("dm:set-offline-inactive - No stale DMs found (cutoff: {$hours}h)");
            return 0;
        }

        $this->warn("Found {$count} online DM(s) with no location update in the last {$hours}h. Marking offline...");

        $ids = (clone $query)->pluck('id')->toArray();

        DeliveryMan::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->update(['active' => 0]);

        $this->info("Marked {$count} DM(s) as offline.");
        Log::info("dm:set-offline-inactive - Marked {$count} DM(s) offline (no location >{$hours}h). IDs: " . implode(',', $ids));

        return 0;
    }
}
