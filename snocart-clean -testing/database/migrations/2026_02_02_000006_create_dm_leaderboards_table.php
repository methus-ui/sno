<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_leaderboards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_man_id');
            $table->enum('period_type', ['daily', 'weekly', 'monthly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('deliveries_completed')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->decimal('acceptance_rate', 5, 2)->default(0);
            $table->integer('rank_position')->default(0);
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->timestamps();

            $table->index(['period_type', 'period_start', 'zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_leaderboards');
    }
};
