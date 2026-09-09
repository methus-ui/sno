<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('business_settings')->insertOrIgnore([
            [
                'key' => 'dm_cancellation_compensation_enabled',
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'dm_cancellation_compensation_percentage',
                'value' => '50', // 50% of delivery charge
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'dm_cancellation_compensation_proximity',
                'value' => '0.2', // 200 meters in km
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('business_settings')
            ->whereIn('key', [
                'dm_cancellation_compensation_enabled',
                'dm_cancellation_compensation_percentage',
                'dm_cancellation_compensation_proximity'
            ])
            ->delete();
    }
};
