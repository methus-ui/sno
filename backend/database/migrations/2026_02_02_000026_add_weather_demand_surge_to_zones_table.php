<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->boolean('weather_surge_enabled')->default(false);
            $table->boolean('demand_surge_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn(['weather_surge_enabled', 'demand_surge_enabled']);
        });
    }
};
