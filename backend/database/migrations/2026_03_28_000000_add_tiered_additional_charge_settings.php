<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BusinessSetting;

class AddTieredAdditionalChargeSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add new business settings for tiered additional charges

        // Enable/disable tiered system (if disabled, uses old flat charge)
        BusinessSetting::updateOrInsert(
            ['key' => 'additional_charge_tiered_enabled'],
            ['value' => '0'] // Disabled by default
        );

        // Store tier configuration as JSON
        // Default tiers: <500 = 25, 500-999 = 30, >=1000 = 35
        $default_tiers = json_encode([
            ['min' => 0, 'max' => 499.99, 'charge' => 25],
            ['min' => 500, 'max' => 999.99, 'charge' => 30],
            ['min' => 1000, 'max' => null, 'charge' => 35], // null means no upper limit
        ]);

        BusinessSetting::updateOrInsert(
            ['key' => 'additional_charge_tiers'],
            ['value' => $default_tiers]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        BusinessSetting::where('key', 'additional_charge_tiered_enabled')->delete();
        BusinessSetting::where('key', 'additional_charge_tiers')->delete();
    }
}
