<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_rush_incentives', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('trigger_type', ['order_count', 'pending_time'])->default('order_count');
            $table->integer('min_threshold');
            $table->integer('max_threshold')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->enum('bonus_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('bonus_value', 10, 2);
            $table->integer('duration_minutes')->default(60);
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_rush_incentives');
    }
};
