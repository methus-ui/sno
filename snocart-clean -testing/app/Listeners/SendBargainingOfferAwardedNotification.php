<?php

namespace App\Listeners;

use App\Events\BargainingOfferAwarded;
use App\Models\VendorEmployee;
use App\Notifications\BargainingOfferAwardedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendBargainingOfferAwardedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\BargainingOfferAwarded  $event
     * @return void
     */
    public function handle(BargainingOfferAwarded $event)
    {
        try {
            // Check if notifications are enabled
            if (!config('bargaining.notifications.vendor.offer_awarded', true)) {
                return;
            }

            // Get store employees to notify
            $employees = VendorEmployee::where('store_id', $event->awardedOffer->store_id)
                ->where('status', 1) // Active only
                ->get();

            foreach ($employees as $employee) {
                try {
                    $employee->notify(new BargainingOfferAwardedNotification(
                        $event->bargainingRequest,
                        $event->awardedOffer
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send bargaining notification to employee', [
                        'employee_id' => $employee->id,
                        'store_id' => $event->awardedOffer->store_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Bargaining offer awarded notifications sent', [
                'store_id' => $event->awardedOffer->store_id,
                'employees_notified' => $employees->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Bargaining offer awarded notification failed', [
                'request_code' => $event->bargainingRequest->request_code,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
