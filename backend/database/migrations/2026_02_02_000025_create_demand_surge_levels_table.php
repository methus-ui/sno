<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demand_surge_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->integer('min_pending_orders');
            $table->integer('max_available_dms');
            $table->decimal('surge_percentage', 8, 2);
            $table->boolean('is_enabled')->default(true);
            $table->string('message', 255)->nullable();
            $table->timestamps();

            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('cascade');
            $table->index(['zone_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_surge_levels');
    }
};
