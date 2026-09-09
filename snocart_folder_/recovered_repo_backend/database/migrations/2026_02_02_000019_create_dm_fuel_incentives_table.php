<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dm_fuel_incentives', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('rate_per_km', 8, 2)->default(0);
            $table->decimal('fuel_price_reference', 8, 2)->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dm_fuel_incentives');
    }
};
