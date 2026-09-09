<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zone_delivery_fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->string('title', 100);
            $table->tinyInteger('day')->unsigned()->comment('0=Sunday, 1=Monday...6=Saturday');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('fee_percentage', 8, 2)->comment('Positive=increase, Negative=decrease');
            $table->string('message', 255)->nullable();
            $table->boolean('status')->default(true);
            $table->tinyInteger('priority')->unsigned()->default(0)->comment('Higher value wins on overlap');
            $table->timestamps();

            // Index for efficient queries
            $table->index(['zone_id', 'day', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zone_delivery_fee_schedules');
    }
};
