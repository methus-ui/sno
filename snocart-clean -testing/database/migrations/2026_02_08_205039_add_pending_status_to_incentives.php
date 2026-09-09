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
        Schema::table('provide_d_m_earnings', function (Blueprint $table) {
            // Add status column: 'pending' or 'credited'
            $table->enum('status', ['pending', 'credited'])->default('pending')->after('metadata');

            // Add attendance_id to link incentive to shift
            $table->unsignedBigInteger('attendance_id')->nullable()->after('order_id');

            // Add credited_at timestamp for when incentive was actually paid
            $table->timestamp('credited_at')->nullable()->after('status');

            // Add index for faster queries
            $table->index(['delivery_man_id', 'status']);
            $table->index(['attendance_id']);
        });

        // Update existing records to 'credited' (they were already paid)
        DB::table('provide_d_m_earnings')->update(['status' => 'credited', 'credited_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provide_d_m_earnings', function (Blueprint $table) {
            $table->dropIndex(['delivery_man_id', 'status']);
            $table->dropIndex(['attendance_id']);
            $table->dropColumn(['status', 'attendance_id', 'credited_at']);
        });
    }
};
