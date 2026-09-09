<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DmMinWageSettingSeeder extends Seeder
{
    /**
     * Seed minimum wage settings for delivery men.
     *
     * Minimum wage guarantee for Kashmir:
     * - Based on J&K minimum wage standards
     * - Ensures delivery boys earn minimum even on slow days
     * - Encourages login during slow hours
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            // Default setting for all zones
            [
                'zone_id' => null,              // Applies to all zones
                'dm_type' => 'all',
                'min_daily_amount' => 400.00,   // ₹400 minimum per day
                'min_hours_required' => 8.0,    // Must work 8 hours to qualify
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // If you have specific zones, add zone-specific settings
        // Example: Higher min wage for difficult terrain zones
        // [
        //     'zone_id' => 1,  // Downtown Srinagar
        //     'dm_type' => 'zone_wise',
        //     'min_daily_amount' => 450.00,
        //     'min_hours_required' => 8.0,
        //     'status' => true,
        // ],

        foreach ($settings as $setting) {
            DB::table('dm_minimum_wage_settings')->updateOrInsert(
                ['zone_id' => $setting['zone_id'], 'dm_type' => $setting['dm_type']],
                $setting
            );
        }
    }
}
