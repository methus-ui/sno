<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DmFuelIncentiveSeeder extends Seeder
{
    /**
     * Seed fuel incentives for delivery men.
     *
     * Fuel compensation for Kashmir:
     * - Hilly terrain = more fuel consumption
     * - Current petrol prices ~₹100/liter in Kashmir
     * - Average mileage: 40-50 km/liter for bikes
     *
     * @return void
     */
    public function run()
    {
        $incentives = [
            // Standard fuel incentive (all zones)
            [
                'title' => 'Standard Fuel Allowance',
                'rate_per_km' => 3.00,          // ₹3 per km
                'fuel_price_reference' => 100.00, // Based on ₹100/liter petrol
                'status' => 1,
                'zone_id' => null,              // All zones
                'effective_from' => now(),
                'effective_to' => null,         // No end date
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Zone-specific rates can be added for hilly areas
        // Example:
        // [
        //     'title' => 'Hilly Area Fuel Allowance',
        //     'rate_per_km' => 4.00,          // ₹4 per km (higher for hills)
        //     'fuel_price_reference' => 100.00,
        //     'status' => 1,
        //     'zone_id' => 2,                 // Specific hilly zone
        //     'effective_from' => now(),
        //     'effective_to' => null,
        // ],

        foreach ($incentives as $incentive) {
            DB::table('dm_fuel_incentives')->updateOrInsert(
                ['title' => $incentive['title']],
                $incentive
            );
        }
    }
}
