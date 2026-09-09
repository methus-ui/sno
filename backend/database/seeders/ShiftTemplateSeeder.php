<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftTemplateSeeder extends Seeder
{
    /**
     * Seed shift templates for Kashmir Grocery Delivery.
     *
     * Shifts designed for:
     * - Kashmir weather conditions (shorter winter hours)
     * - Grocery delivery patterns (morning & evening peaks)
     * - Local culture and shopping habits
     *
     * @return void
     */
    public function run()
    {
        $shifts = [
            // Peak shifts
            [
                'name' => 'Morning Peak',
                'start_time' => '07:00:00',
                'end_time' => '12:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Evening Peak',
                'start_time' => '15:00:00',
                'end_time' => '20:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Bridge/Regular shifts
            [
                'name' => 'Mid Day',
                'start_time' => '11:00:00',
                'end_time' => '16:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Late Evening',
                'start_time' => '18:00:00',
                'end_time' => '21:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Full day shifts
            [
                'name' => 'Full Day',
                'start_time' => '09:00:00',
                'end_time' => '19:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Full Day Extended',
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Winter specific (shorter daylight)
            [
                'name' => 'Winter Morning',
                'start_time' => '08:00:00',
                'end_time' => '13:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Winter Afternoon',
                'start_time' => '12:00:00',
                'end_time' => '17:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Winter Full Day',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Weekend special
            [
                'name' => 'Weekend Special',
                'start_time' => '10:00:00',
                'end_time' => '20:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($shifts as $shift) {
            DB::table('shift_templates')->updateOrInsert(
                ['name' => $shift['name']],
                $shift
            );
        }
    }
}
