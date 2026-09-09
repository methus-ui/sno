<?php

namespace App\Console\Commands;

use App\CentralLogics\Helpers;
use App\Models\DmShiftBooking;
use App\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sends push-notification reminders to delivery men 1 hour before their shift starts.
 *
 * Scheduled every 30 minutes. Each run looks for shifts starting 55–85 minutes
 * from now (a 30-minute window) so every run covers a fresh batch with no overlap
 * and no double-sends — without requiring a database flag column.
 */
class SendShiftBookingReminder extends Command
{
    protected $signature = 'shift:send-booking-reminders
                            {--minutes=60 : Send reminder this many minutes before shift starts}
                            {--window=30  : Width of the time window in minutes}
                            {--dry-run    : Show what would be sent without actually sending}';

    protected $description = 'Send push notification reminders to delivery men 1 hour before their booked shift starts';

    public function handle(): int
    {
        $minutesBefore = (int) $this->option('minutes');
        $windowMinutes = (int) $this->option('window');
        $dryRun        = $this->option('dry-run');

        // Window: shifts that start between (now + minutesBefore - window/2)
        //                                and (now + minutesBefore + window/2)
        $half       = $windowMinutes / 2;
        $windowStart = now()->addMinutes($minutesBefore - $half);
        $windowEnd   = now()->addMinutes($minutesBefore + $half);

        $this->info("Checking shifts starting between {$windowStart->format('H:i')} and {$windowEnd->format('H:i')} (±{$half} min window around {$minutesBefore} min from now)");

        // Collect all active shift templates whose start_time falls in the window (time only)
        $templates = ShiftTemplate::where('is_active', 1)->get();

        $matchingTemplateIds = $templates->filter(function ($template) use ($windowStart, $windowEnd) {
            // Build a full datetime using today's date (or tomorrow if window crosses midnight)
            $startFull = Carbon::parse(today()->toDateString() . ' ' . $template->start_time);

            // If the window start is after midnight but the shift is "next day", shift by 1 day
            if ($startFull->lt(now()->subHours(12))) {
                $startFull->addDay();
            }

            return $startFull->between($windowStart, $windowEnd);
        })->pluck('id')->toArray();

        if (empty($matchingTemplateIds)) {
            $this->info('No shifts starting in this window. Nothing to send.');
            return Command::SUCCESS;
        }

        $this->info('Matching shift template IDs: ' . implode(', ', $matchingTemplateIds));

        // Find all active bookings for those templates on today's or tomorrow's date
        // (cover dates that match the window)
        $dates = array_unique([
            $windowStart->toDateString(),
            $windowEnd->toDateString(),
        ]);

        $bookings = DmShiftBooking::with(['deliveryMan', 'shiftTemplate'])
            ->whereIn('shift_template_id', $matchingTemplateIds)
            ->whereIn('date', $dates)
            ->whereIn('status', ['self_booked', 'scheduled', 'active'])
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No active bookings found for these shift slots.');
            return Command::SUCCESS;
        }

        $this->info("Found {$bookings->count()} booking(s) to remind.");

        $sent   = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($bookings as $booking) {
            $dm       = $booking->deliveryMan;
            $template = $booking->shiftTemplate;

            if (!$dm) {
                $skipped++;
                continue;
            }

            if (empty($dm->fcm_token)) {
                $this->warn("  DM #{$dm->id} has no FCM token — skipping.");
                $skipped++;
                continue;
            }

            $shiftTime = Carbon::parse($template->start_time)->format('h:i A');
            $shiftName = $template->name;

            $notificationData = [
                'title'       => 'Shift Starting Soon!',
                'description' => "Your {$shiftName} shift starts at {$shiftTime}. Please be ready on time.",
                'image'       => '',
                'order_id'    => '',
                'type'        => 'shift_reminder',
                'booking_id'  => $booking->id,
            ];

            if ($dryRun) {
                $this->line("  [DRY-RUN] Would notify DM #{$dm->id} ({$dm->f_name} {$dm->l_name}) → {$shiftName} at {$shiftTime}");
                $sent++;
                continue;
            }

            try {
                Helpers::send_push_notif_to_device($dm->fcm_token, $notificationData);

                // Log notification in user_notifications table
                DB::table('user_notifications')->insert([
                    'data'            => json_encode($notificationData),
                    'user_id'         => null,
                    'delivery_man_id' => $dm->id,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                $this->line("  ✅ Notified DM #{$dm->id} ({$dm->f_name} {$dm->l_name}) → {$shiftName} at {$shiftTime}");
                $sent++;
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to notify DM #{$dm->id}: " . $e->getMessage());
                Log::error('ShiftBookingReminder: push notification failed', [
                    'dm_id'      => $dm->id,
                    'booking_id' => $booking->id,
                    'error'      => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->newLine();
        $this->info("📊 SUMMARY: sent={$sent}  failed={$failed}  skipped={$skipped}");

        Log::info('ShiftBookingReminder completed', [
            'window_start'  => $windowStart->toIso8601String(),
            'window_end'    => $windowEnd->toIso8601String(),
            'template_ids'  => $matchingTemplateIds,
            'sent'          => $sent,
            'failed'        => $failed,
            'skipped'       => $skipped,
        ]);

        return Command::SUCCESS;
    }
}
