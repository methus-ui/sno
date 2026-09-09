<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_performance_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('rank_order')->default(0);
            $table->string('icon')->nullable();
            $table->string('color')->default('#000000');
            $table->decimal('min_rating', 3, 2)->nullable();
            $table->integer('min_deliveries')->nullable();
            $table->integer('min_acceptance_rate')->nullable();
            $table->decimal('bonus_per_delivery', 10, 2)->default(0);
            $table->integer('priority_boost')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_performance_tiers');
    }
};
