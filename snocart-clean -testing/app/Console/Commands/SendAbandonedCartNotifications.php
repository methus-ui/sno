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

class SendAbandonedCartNotifications extends Command
{
    protected $signature = 'cart:send-abandoned-notifications';
    protected $description = 'Send push notifications for carts abandoned for more than 5 minutes';

    public function handle()
    {
        Log::info('SendAbandonedCartNotifications command triggered at ' . now());

        $now = Carbon::now();
        $threshold = $now->copy()->subMinutes(5);

        $abandonedCarts = DB::table('carts')
            ->join('users', 'carts.user_id', '=', 'users.id')
            ->where('carts.is_guest', 0)
            ->where('carts.abandoned_notification_sent', false)
            ->whereNotNull('users.cm_firebase_token')
            ->where('users.cm_firebase_token', '!=', '')
            ->where(function ($query) use ($threshold) {
                $query->where('carts.last_activity_at', '<=', $threshold)
                    ->orWhere(function ($q) use ($threshold) {
                        $q->whereNull('carts.last_activity_at')
                            ->where('carts.updated_at', '<=', $threshold);
                    });
            })
            ->groupBy('carts.user_id', 'users.cm_firebase_token')
            ->select(
                'carts.user_id',
                'users.cm_firebase_token',
                DB::raw('COUNT(*) as items_count'),
                DB::raw('SUM(carts.price * carts.quantity) as total_value'),
                DB::raw('MAX(COALESCE(carts.last_activity_at, carts.updated_at)) as last_activity')
            )
            ->orderBy('last_activity', 'desc')
            ->limit(10)
            ->get();

        if ($abandonedCarts->isEmpty()) {
            $this->info('No abandoned carts found.');
            Log::info('No abandoned carts found.');
            return;
        }

        $this->info('Found ' . $abandonedCarts->count() . ' abandoned carts.');
        Log::info('Found ' . $abandonedCarts->count() . ' abandoned carts.');

        $messaging = app('firebase.messaging');
        $successCount = 0;
        $failCount = 0;
        $invalidTokenCount = 0;

        foreach ($abandonedCarts as $cart) {
            try {
                $totalFormatted = '₹' . number_format($cart->total_value, 2);

                // Custom message for free delivery
                if ($cart->total_value >= 599) {
                    $description = "🎉 You got free delivery! Complete your order worth $totalFormatted now!";
                } else {
                    $description = "🛒 You left {$cart->items_count} items worth $totalFormatted in your cart!";
                }

                $notification = Notification::create(
                    "Hey! You forgot something...",
                    $description
                )->withImageUrl('https://new.snocart.com/storage/app/public/cart-reminder.png');

                $message = CloudMessage::withTarget('token', $cart->cm_firebase_token)
                    ->withNotification($notification)
                    ->withData([
                        'type' => 'abandoned_cart',
                        'user_id' => (string)$cart->user_id,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]);

                $messaging->send($message);

                // ✅ Mark this user's cart as notified
                DB::table('carts')
                    ->where('user_id', $cart->user_id)
                    ->update(['abandoned_notification_sent' => true]);

                $successCount++;
                $this->info("✅ Notification sent for user {$cart->user_id}");
                Log::info("Notification sent successfully for user {$cart->user_id}");

            } catch (NotFound $e) {
                // Token not found - user probably uninstalled app
                $invalidTokenCount++;
                $this->warn("⚠️  Invalid token for user {$cart->user_id} - clearing token");
                
                // Clear invalid token from database
                DB::table('users')
                    ->where('id', $cart->user_id)
                    ->update(['cm_firebase_token' => null]);
                
                // Mark cart as notified to avoid retrying
                DB::table('carts')
                    ->where('user_id', $cart->user_id)
                    ->update(['abandoned_notification_sent' => true]);
                
                Log::warning("Cleared invalid token for user {$cart->user_id}");

            } catch (InvalidArgument $e) {
                // Invalid token format
                $invalidTokenCount++;
                $this->warn("⚠️  Malformed token for user {$cart->user_id} - clearing token");
                
                // Clear invalid token from database
                DB::table('users')
                    ->where('id', $cart->user_id)
                    ->update(['cm_firebase_token' => null]);
                
                // Mark cart as notified to avoid retrying
                DB::table('carts')
                    ->where('user_id', $cart->user_id)
                    ->update(['abandoned_notification_sent' => true]);
                
                Log::warning("Cleared malformed token for user {$cart->user_id}");

            } catch (\Exception $e) {
                $failCount++;
                Log::error('Failed to send abandoned cart notification', [
                    'user_id' => $cart->user_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("❌ Error sending notification for user {$cart->user_id}: " . $e->getMessage());
            }
        }

        // Summary
        $this->info("=====================================");
        $this->info("✅ Successfully sent: {$successCount}");
        $this->warn("⚠️  Invalid tokens cleaned: {$invalidTokenCount}");
        $this->error("❌ Failed: {$failCount}");
        $this->info("=====================================");

        Log::info("Abandoned cart notifications summary", [
            'success' => $successCount,
            'invalid_tokens' => $invalidTokenCount,
            'failed' => $failCount,
        ]);
    }
}
