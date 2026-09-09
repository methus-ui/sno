<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dm_minimum_wage_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->enum('dm_type', ['zone_wise', 'all'])->default('all');
            $table->decimal('min_daily_amount', 10, 2)->default(0);
            $table->decimal('min_hours_required', 4, 1)->default(8.0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dm_minimum_wage_settings');
    }
};
