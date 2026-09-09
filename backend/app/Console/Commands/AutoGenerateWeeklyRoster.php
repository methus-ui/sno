<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShiftRoster;
use Carbon\Carbon;

class AutoGenerateWeeklyRoster extends Command
{
    protected $signature = 'roster:auto-generate-weekly';
    protected $description = 'Copy current week roster to next week if next week has no roster';

    public function handle()
    {
        $currentWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $nextWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek()->format('Y-m-d');

        if (ShiftRoster::forWeek($nextWeekStart)->count() > 0) {
            $this->info('Next week roster already exists. Skipping.');
            return 0;
        }

        $sourceRosters = ShiftRoster::forWeek($currentWeekStart)->get();

        if ($sourceRosters->isEmpty()) {
            $this->warn('No roster found for current week.');
            return 0;
        }

        foreach ($sourceRosters as $roster) {
            ShiftRoster::create([
                'admin_id' => $roster->admin_id,
                'day_of_week' => $roster->day_of_week,
                'week_start_date' => $nextWeekStart,
                'shift_start' => $roster->shift_start,
                'shift_end' => $roster->shift_end,
                'is_off_day' => $roster->is_off_day,
                'shift_template_id' => $roster->shift_template_id,
                'roster_role_id' => $roster->roster_role_id,
                'created_by' => $roster->created_by,
            ]);
        }

        $this->info("Generated {$sourceRosters->count()} roster entries for next week ({$nextWeekStart}).");
        return 0;
    }
}
