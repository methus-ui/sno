<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\Messaging\InvalidArgument;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\MessagingException;

class SendInactiveCustomerNotification extends Command
{
    protected $signature = 'notification:inactive-customer';
    protected $description = 'Send push notifications to customers inactive for 7, 15, or 30 days';

    private array $tiers = [
        30 => [
            'title' => "We haven't seen you in a month!",
            'body' => "We miss you! Come back and treat yourself to something special today.",
        ],
        15 => [
            'title' => "It's been a while!",
            'body' => "Your favourite items are waiting. Come back for something delicious!",
        ],
        7 => [
            'title' => "We miss you!",
            'body' => "It's been a week since your last order. Check out what's new today!",
        ],
    ];

    public function handle()
    {
        Log::info('SendInactiveCustomerNotification triggered at ' . now());

        $messaging = app('firebase.messaging');
        $now = Carbon::now();
        $successCount = 0;
        $failCount = 0;
        $invalidTokenCount = 0;

        foreach ($this->tiers as $days => $messages) {
            $rangeStart = $now->copy()->subDays($days + 1)->endOfDay();
            $rangeEnd = $now->copy()->subDays($days)->startOfDay();

            $users = DB::table('users')
                ->whereNotNull('cm_firebase_token')
                ->where('cm_firebase_token', '!=', '')
                ->where(function ($q) use ($now) {
                    $q->whereNull('last_inactive_notification_at')
                      ->orWhere('last_inactive_notification_at', '<=', $now->copy()->subDays(7));
                })
                ->whereIn('id', function ($query) use ($rangeStart, $rangeEnd) {
                    $query->select('user_id')
                        ->from('orders')
                        ->whereNotNull('user_id')
                        ->groupBy('user_id')
                        ->havingRaw('MAX(created_at) BETWEEN ? AND ?', [$rangeStart, $rangeEnd]);
                })
                ->select('id', 'cm_firebase_token')
                ->limit(50)
                ->get();

            $this->info("Tier {$days}d: Found {$users->count()} users");

            foreach ($users as $user) {
                try {
                    $notification = Notification::create($messages['title'], $messages['body']);

                    $message = CloudMessage::withTarget('token', $user->cm_firebase_token)
                        ->withNotification($notification)
                        ->withData([
                            'type' => 'inactive_customer',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ]);

                    $messaging->send($message);

                    DB::table('users')->where('id', $user->id)
                        ->update(['last_inactive_notification_at' => $now]);

                    $successCount++;
                } catch (NotFound|InvalidArgument|MessagingException $e) {
                    $invalidTokenCount++;
                    DB::table('users')->where('id', $user->id)
                        ->update(['cm_firebase_token' => null, 'last_inactive_notification_at' => $now]);
                    Log::warning("Cleared invalid token for user {$user->id}");
                } catch (\Exception $e) {
                    $failCount++;
                    Log::error("Inactive notification failed for user {$user->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Done — Sent: {$successCount}, Invalid tokens: {$invalidTokenCount}, Failed: {$failCount}");
        Log::info("Inactive customer notifications", compact('successCount', 'invalidTokenCount', 'failCount'));
    }
}
