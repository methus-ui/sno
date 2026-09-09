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
        // Add proximity enforcement setting
        DB::table('business_settings')->insertOrIgnore([
            [
                'key' => 'delivery_proximity_enforcement',
                'value' => '1', // Enabled by default
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'delivery_proximity_radius',
                'value' => '0.2', // 200 meters in kilometers
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
            ->whereIn('key', ['delivery_proximity_enforcement', 'delivery_proximity_radius'])
            ->delete();
    }
};
