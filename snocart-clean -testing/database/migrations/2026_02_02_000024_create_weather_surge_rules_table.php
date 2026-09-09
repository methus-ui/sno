<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_surge_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->enum('weather_condition', ['rain', 'heavy_rain', 'storm', 'snow', 'extreme_heat', 'fog']);
            $table->decimal('surge_percentage', 8, 2);
            $table->decimal('min_temp', 5, 2)->nullable();
            $table->decimal('max_temp', 5, 2)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->string('message', 255)->nullable();
            $table->timestamps();

            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('cascade');
            $table->index(['zone_id', 'weather_condition', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_surge_rules');
    }
};
