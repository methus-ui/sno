<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_shift_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_man_id');
            $table->tinyInteger('day_of_week');
            $table->unsignedBigInteger('preferred_shift_template_id')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['delivery_man_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_shift_preferences');
    }
};
