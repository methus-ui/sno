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

class SendWinbackNotification extends Command
{
    protected $signature = 'notification:winback-customer';
    protected $description = 'Send win-back notifications to customers inactive for 60+ days';

    public function handle()
    {
        Log::info('SendWinbackNotification triggered at ' . now());

        $now = Carbon::now();
        $cutoff = $now->copy()->subDays(60);

        $users = DB::table('users')
            ->whereNotNull('cm_firebase_token')
            ->where('cm_firebase_token', '!=', '')
            ->where(function ($q) use ($now) {
                $q->whereNull('last_inactive_notification_at')
                  ->orWhere('last_inactive_notification_at', '<=', $now->copy()->subDays(14));
            })
            ->whereIn('id', function ($query) use ($cutoff) {
                $query->select('user_id')
                    ->from('orders')
                    ->whereNotNull('user_id')
                    ->groupBy('user_id')
                    ->havingRaw('MAX(created_at) <= ?', [$cutoff]);
            })
            ->select('id', 'cm_firebase_token')
            ->limit(100)
            ->get();

        $this->info("Found {$users->count()} churned customers to win back");

        $messaging = app('firebase.messaging');
        $successCount = 0;
        $failCount = 0;
        $invalidTokenCount = 0;

        foreach ($users as $user) {
            try {
                $notification = Notification::create(
                    "It's been too long!",
                    "We'd love to have you back. Check out what's new and order your favourites today!"
                );

                $message = CloudMessage::withTarget('token', $user->cm_firebase_token)
                    ->withNotification($notification)
                    ->withData([
                        'type' => 'winback',
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
                Log::error("Winback notification failed for user {$user->id}: " . $e->getMessage());
            }
        }

        $this->info("Done — Sent: {$successCount}, Invalid tokens: {$invalidTokenCount}, Failed: {$failCount}");
        Log::info("Winback notifications", compact('successCount', 'invalidTokenCount', 'failCount'));
    }
}
