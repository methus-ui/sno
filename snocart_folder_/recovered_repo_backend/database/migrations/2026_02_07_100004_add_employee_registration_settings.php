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
        DB::table('business_settings')->insert([
            [
                'key' => 'employee_public_registration_enabled',
                'value' => '0', // Disabled by default
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'employee_registration_admin_notification',
                'value' => '1', // Email admin on new application
                'created_at' => now(),
                'updated_at' => now(),
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
                'employee_public_registration_enabled',
                'employee_registration_admin_notification'
            ])
            ->delete();
    }
};
