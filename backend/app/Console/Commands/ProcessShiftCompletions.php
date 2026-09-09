<?php

namespace App\Console\Commands;

use App\Models\DeliverymanAttendance;
use App\Models\DmShiftBooking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Runs every 15 minutes and handles two transitions:
 *
 * 1. active / self_booked / scheduled  →  completed
 *    When shift end_time has passed AND the DM has a punch_in for today.
 *
 * 2. self_booked / scheduled  →  missed
 *    When shift end_time has passed AND the DM has NO punch_in at all (no-show).
 */
class ProcessShiftCompletions extends Command
{
    protected $signature = 'shift:process-completions {--dry-run : Show changes without saving}';
    protected $description = 'Mark finished shift bookings as completed or missed based on DM online timing';

    public function handle(): int
    {
        $now     = Carbon::now();
        $today   = $now->toDateString();
        $dryRun  = $this->option('dry-run');

        $this->info("Running at {$now->format('H:i:s')} — processing shift completions for {$today}");

        // Fetch all non-terminal bookings for today whose shift end_time has passed
        $openBookings = DmShiftBooking::where('date', $today)
            ->whereIn('status', ['active', 'self_booked', 'scheduled'])
            ->with('shiftTemplate', 'deliveryMan')
            ->get()
            ->filter(function ($booking) use ($now, $today) {
                $end = Carbon::parse($today . ' ' . $booking->shiftTemplate->end_time);
                return $now->gt($end); // Only care about shifts that have already ended
            });

        if ($openBookings->isEmpty()) {
            $this->info('No ended shifts found with open status. Nothing to do.');
            return Command::SUCCESS;
        }

        $this->info("Found {$openBookings->count()} booking(s) whose shift has ended.");

        // Pre-load today's attendance records for all relevant DMs
        $dmIds = $openBookings->pluck('delivery_man_id')->unique()->toArray();
        $attendanceByDm = DeliverymanAttendance::where('date', $today)
            ->whereIn('delivery_man_id', $dmIds)
            ->pluck('punch_in_time', 'delivery_man_id'); // dm_id => punch_in_time

        $completed = 0;
        $missed    = 0;

        foreach ($openBookings as $booking) {
            $dmId      = $booking->delivery_man_id;
            $punchIn   = $attendanceByDm->get($dmId);
            $shiftName = $booking->shiftTemplate->name;
            $dmName    = $booking->deliveryMan
                ? "{$booking->deliveryMan->f_name} {$booking->deliveryMan->l_name}"
                : "DM#{$dmId}";

            if ($punchIn) {
                // DM was online at some point during the shift → completed
                $newStatus = 'completed';
                $completed++;
                $this->line("  ✅ [{$newStatus}] DM#{$dmId} ({$dmName}) — {$shiftName} (punched in at {$punchIn})");
            } else {
                // DM never came online → no-show → missed
                $newStatus = 'missed';
                $missed++;
                $this->warn("  ❌ [{$newStatus}] DM#{$dmId} ({$dmName}) — {$shiftName} (no punch-in)");
            }

            if (!$dryRun) {
                $booking->status = $newStatus;
                $booking->save();
            }
        }

        $this->newLine();
        $this->info("📊 SUMMARY: completed={$completed}  missed={$missed}" . ($dryRun ? '  [DRY-RUN]' : ''));

        Log::info('ProcessShiftCompletions done', [
            'date'      => $today,
            'completed' => $completed,
            'missed'    => $missed,
            'dry_run'   => $dryRun,
        ]);

        return Command::SUCCESS;
    }
}
