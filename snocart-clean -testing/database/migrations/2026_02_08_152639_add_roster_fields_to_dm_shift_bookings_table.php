<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dm_shift_bookings', function (Blueprint $table) {
            // Add fields from DmShiftRoster to support unified system
            $table->unsignedBigInteger('assigned_by')->nullable()->after('shift_template_id');
            $table->boolean('is_off_day')->default(false)->after('cancellation_reason');
            $table->text('notes')->nullable()->after('is_off_day');

            // Update status enum to include all possible states
            // Note: In MySQL, this requires dropping and recreating the column
            // Current: 'active', 'cancelled', 'completed'
            // New: 'active', 'scheduled', 'self_booked', 'cancelled', 'completed', 'missed'
            DB::statement("ALTER TABLE dm_shift_bookings MODIFY COLUMN status ENUM('active', 'scheduled', 'self_booked', 'cancelled', 'completed', 'missed') DEFAULT 'active'");

            // Add foreign key for assigned_by
            $table->foreign('assigned_by')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dm_shift_bookings', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['assigned_by']);

            // Drop added columns
            $table->dropColumn(['assigned_by', 'is_off_day', 'notes']);

            // Revert status enum
            DB::statement("ALTER TABLE dm_shift_bookings MODIFY COLUMN status ENUM('active', 'cancelled', 'completed') DEFAULT 'active'");
        });
    }
};
