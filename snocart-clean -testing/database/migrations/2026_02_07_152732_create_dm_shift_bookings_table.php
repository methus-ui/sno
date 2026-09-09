<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_shift_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_man_id');
            $table->unsignedBigInteger('shift_template_id');
            $table->date('date');
            $table->enum('status', ['active', 'cancelled', 'completed'])->default('active');
            $table->timestamp('booked_at')->useCurrent();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->foreign('delivery_man_id')->references('id')->on('delivery_men')->onDelete('cascade');
            $table->foreign('shift_template_id')->references('id')->on('shift_templates')->onDelete('cascade');

            // Ensure a DM can only have one active booking per date
            $table->unique(['delivery_man_id', 'date', 'status'], 'dm_date_status_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_shift_bookings');
    }
};
