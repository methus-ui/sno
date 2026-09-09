<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SecurityDepositSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('business_settings')->updateOrInsert(
            ['key' => 'security_deposit_enabled'],
            [
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \DB::table('business_settings')->updateOrInsert(
            ['key' => 'security_deposit_amount'],
            [
                'value' => '500',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
