<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_time_based_incentives', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->time('time_from');
            $table->time('time_to');
            $table->json('days_of_week')->nullable();
            $table->enum('bonus_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('bonus_value', 10, 2);
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->unsignedBigInteger('module_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_time_based_incentives');
    }
};
