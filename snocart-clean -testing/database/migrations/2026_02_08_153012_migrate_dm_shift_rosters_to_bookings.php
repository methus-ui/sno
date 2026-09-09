<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrate all DmShiftRoster records to DmShiftBooking table
     * This consolidates the two parallel shift systems into one unified system
     */
    public function up(): void
    {
        Log::info('Starting DmShiftRoster to DmShiftBooking migration');

        // Get all roster records
        $rosters = DB::table('dm_shift_rosters')->get();

        $migratedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($rosters as $roster) {
            try {
                // Check if a booking already exists for this DM and date
                $existingBooking = DB::table('dm_shift_bookings')
                    ->where('delivery_man_id', $roster->delivery_man_id)
                    ->where('date', $roster->date)
                    ->whereIn('status', ['active', 'scheduled', 'self_booked', 'completed'])
                    ->first();

                if ($existingBooking) {
                    $skippedCount++;
                    Log::warning("Skipped roster {$roster->id} - booking already exists", [
                        'roster_id' => $roster->id,
                        'booking_id' => $existingBooking->id,
                        'dm_id' => $roster->delivery_man_id,
                        'date' => $roster->date,
                    ]);
                    continue;
                }

                // Map roster status to booking status
                $bookingStatus = $this->mapStatus($roster->status);

                // Create booking from roster
                DB::table('dm_shift_bookings')->insert([
                    'delivery_man_id' => $roster->delivery_man_id,
                    'shift_template_id' => $roster->shift_template_id,
                    'date' => $roster->date,
                    'status' => $bookingStatus,
                    'booked_at' => $roster->status === 'self_assigned' ? $roster->created_at : null,
                    'cancelled_at' => $roster->status === 'cancelled' ? $roster->updated_at : null,
                    'cancellation_reason' => null,
                    'assigned_by' => $roster->assigned_by,
                    'is_off_day' => $roster->is_off_day ?? false,
                    'notes' => $roster->notes,
                    'created_at' => $roster->created_at,
                    'updated_at' => $roster->updated_at,
                ]);

                $migratedCount++;

            } catch (\Exception $e) {
                $errors[] = [
                    'roster_id' => $roster->id,
                    'error' => $e->getMessage(),
                ];
                Log::error("Failed to migrate roster {$roster->id}", [
                    'roster_id' => $roster->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('DmShiftRoster migration completed', [
            'total_rosters' => $rosters->count(),
            'migrated' => $migratedCount,
            'skipped' => $skippedCount,
            'errors' => count($errors),
        ]);

        if (!empty($errors)) {
            Log::error('Migration errors:', ['errors' => $errors]);
        }

        // Rename the old table to archive it (don't drop immediately)
        if (Schema::hasTable('dm_shift_rosters')) {
            Schema::rename('dm_shift_rosters', 'dm_shift_rosters_archived');
            Log::info('Renamed dm_shift_rosters to dm_shift_rosters_archived');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Rolling back DmShiftRoster migration');

        // Restore archived table
        if (Schema::hasTable('dm_shift_rosters_archived')) {
            Schema::rename('dm_shift_rosters_archived', 'dm_shift_rosters');
            Log::info('Restored dm_shift_rosters from archive');
        }

        // Note: We don't delete the migrated bookings as they may have been updated
        // Manual cleanup may be needed if you want to restore the exact previous state
        Log::warning('Migrated bookings were NOT deleted. Manual cleanup may be needed.');
    }

    /**
     * Map roster status to booking status
     */
    private function mapStatus(string $rosterStatus): string
    {
        return match ($rosterStatus) {
            'scheduled' => 'scheduled',
            'self_assigned' => 'self_booked',
            'completed' => 'completed',
            'missed' => 'missed',
            'cancelled' => 'cancelled',
            default => 'active',
        };
    }
};
