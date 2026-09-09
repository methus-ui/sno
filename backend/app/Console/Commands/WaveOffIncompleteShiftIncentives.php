<?php

namespace App\Console\Commands;

use App\Models\DeliverymanAttendance;
use App\Services\DmIncentiveService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class WaveOffIncompleteShiftIncentives extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dm:waveoff-incomplete-shift-incentives {--date= : Process specific date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wave off pending incentives for delivery men who did not complete their shift (no punch out)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::yesterday()->toDateString();

        $this->info("Processing incomplete shifts for date: {$date}");

        // Find all attendance records with punch_in but NO punch_out for the specified date
        $incompleteShifts = DeliverymanAttendance::whereDate('date', $date)
            ->whereNotNull('punch_in_time')
            ->whereNull('punch_out_time')
            ->with('deliveryMan')
            ->get();

        if ($incompleteShifts->isEmpty()) {
            $this->info("✅ No incomplete shifts found for {$date}");
            return Command::SUCCESS;
        }

        $this->info("Found {$incompleteShifts->count()} incomplete shifts");

        $incentiveService = new DmIncentiveService();
        $totalWavedOff = 0;
        $totalAmount = 0;
        $processedCount = 0;

        foreach ($incompleteShifts as $attendance) {
            try {
                $result = $incentiveService->waveOffPendingIncentives($attendance->id);

                if ($result['success'] && $result['count'] > 0) {
                    $dmName = $attendance->deliveryMan->f_name . ' ' . $attendance->deliveryMan->l_name;
                    $this->warn("  ❌ DM #{$attendance->delivery_man_id} ({$dmName}): Waved off ₹{$result['total_waved_off']} ({$result['count']} incentives)");

                    $totalWavedOff += $result['count'];
                    $totalAmount += $result['total_waved_off'];
                    $processedCount++;
                }
            } catch (\Exception $e) {
                $this->error("  ⚠️  Failed to process DM #{$attendance->delivery_man_id}: " . $e->getMessage());
                \Log::error("Wave off incentives failed", [
                    'attendance_id' => $attendance->id,
                    'dm_id' => $attendance->delivery_man_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 SUMMARY:");
        $this->info("  • Total incomplete shifts: {$incompleteShifts->count()}");
        $this->info("  • Shifts with pending incentives: {$processedCount}");
        $this->info("  • Total incentives waved off: {$totalWavedOff}");
        $this->info("  • Total amount waved off: ₹{$totalAmount}");

        \Log::info("Daily wave-off of incomplete shift incentives completed", [
            'date' => $date,
            'incomplete_shifts' => $incompleteShifts->count(),
            'processed_count' => $processedCount,
            'total_waved_off' => $totalWavedOff,
            'total_amount' => $totalAmount
        ]);

        return Command::SUCCESS;
    }
}
