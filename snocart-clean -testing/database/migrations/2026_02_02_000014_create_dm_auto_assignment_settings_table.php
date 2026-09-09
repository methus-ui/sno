<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dm_auto_assignment_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->decimal('max_radius_km', 5, 2)->default(5.00);
            $table->json('priority_factors')->nullable()->comment('distance_weight, rating_weight, acceptance_rate_weight, tier_weight');
            $table->integer('fallback_to_manual_after_seconds')->default(120);
            $table->integer('max_orders_per_dm')->default(3);
            $table->timestamps();

            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dm_auto_assignment_settings');
    }
};
