<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_id');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->unsignedBigInteger('original_roster_id');
            $table->enum('request_type', ['swap', 'giveaway'])->default('swap');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_shift_swap_requests');
    }
};
