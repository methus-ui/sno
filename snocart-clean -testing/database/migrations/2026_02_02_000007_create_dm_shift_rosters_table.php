<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_shift_rosters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_man_id');
            $table->unsignedBigInteger('shift_template_id')->nullable();
            $table->date('date');
            $table->time('shift_start');
            $table->time('shift_end');
            $table->enum('status', ['scheduled', 'self_assigned', 'completed', 'missed', 'cancelled'])->default('scheduled');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->boolean('is_off_day')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['delivery_man_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_shift_rosters');
    }
};
