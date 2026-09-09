<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_rush_activations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rush_incentive_id');
            $table->unsignedBigInteger('zone_id');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('pending_orders_count')->default(0);
            $table->boolean('dm_notified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_rush_activations');
    }
};
