<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->integer('total_spots')->default(10)->after('is_active');
            $table->decimal('incentive_amount', 10, 2)->default(0)->after('total_spots');
            $table->integer('booking_deadline_hours')->default(12)->after('incentive_amount')->comment('Hours before shift start that booking closes');
            $table->unsignedBigInteger('zone_id')->nullable()->after('booking_deadline_hours');

            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['total_spots', 'incentive_amount', 'booking_deadline_hours', 'zone_id']);
        });
    }
};
