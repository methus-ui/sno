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

class SendOrderMilestoneNotification extends Command
{
    protected $signature = 'notification:order-milestone';
    protected $description = 'Send congratulation notifications when customers hit order milestones';

    private array $milestones = [5, 10, 25, 50, 100];

    public function handle()
    {
        Log::info('SendOrderMilestoneNotification triggered at ' . now());

        $today = Carbon::today();

        // Find users whose total order count hit a milestone today
        $users = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->whereNotNull('users.cm_firebase_token')
            ->where('users.cm_firebase_token', '!=', '')
            ->where('orders.is_guest', 0)
            ->whereNotNull('orders.user_id')
            ->whereIn('orders.order_type', ['delivery', 'take_away'])
            ->where('orders.order_status', 'delivered')
            ->groupBy('orders.user_id', 'users.cm_firebase_token')
            ->havingRaw('COUNT(orders.id) IN (' . implode(',', $this->milestones) . ')')
            ->havingRaw('MAX(DATE(orders.created_at)) = ?', [$today])
            ->select('orders.user_id', 'users.cm_firebase_token', DB::raw('COUNT(orders.id) as total_orders'))
            ->get();

        $this->info("Found {$users->count()} milestone users");

        $messaging = app('firebase.messaging');
        $successCount = 0;
        $failCount = 0;
        $invalidTokenCount = 0;

        foreach ($users as $user) {
            try {
                $ordinal = $this->ordinal($user->total_orders);
                $notification = Notification::create(
                    "Congratulations! 🎉",
                    "You've completed your {$ordinal} order with us! Thank you for being a loyal customer."
                );

                $message = CloudMessage::withTarget('token', $user->cm_firebase_token)
                    ->withNotification($notification)
                    ->withData([
                        'type' => 'order_milestone',
                        'milestone' => (string)$user->total_orders,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]);

                $messaging->send($message);
                $successCount++;
            } catch (NotFound|InvalidArgument|MessagingException $e) {
                $invalidTokenCount++;
                DB::table('users')->where('id', $user->user_id)
                    ->update(['cm_firebase_token' => null]);
                Log::warning("Cleared invalid token for user {$user->user_id}");
            } catch (\Exception $e) {
                $failCount++;
                Log::error("Milestone notification failed for user {$user->user_id}: " . $e->getMessage());
            }
        }

        $this->info("Done — Sent: {$successCount}, Invalid tokens: {$invalidTokenCount}, Failed: {$failCount}");
        Log::info("Milestone notifications", compact('successCount', 'invalidTokenCount', 'failCount'));
    }

    private function ordinal(int $number): string
    {
        $suffixes = ['th', 'st', 'nd', 'rd'];
        $mod = $number % 100;
        $suffix = $suffixes[($mod >= 11 && $mod <= 13) ? 0 : min($mod % 10, 3)];
        return $number . $suffix;
    }
}
