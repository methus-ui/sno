<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deliveryman_attendances', function (Blueprint $table) {
            $table->integer('offline_count')->default(0)->after('working_hours')
                ->comment('Number of times DM went offline during shift');

            $table->integer('total_offline_minutes')->default(0)->after('offline_count')
                ->comment('Total minutes DM was offline during shift');

            $table->timestamp('last_online_at')->nullable()->after('total_offline_minutes')
                ->comment('Last time DM was marked as online');

            $table->timestamp('last_offline_at')->nullable()->after('last_online_at')
                ->comment('Last time DM went offline');

            $table->boolean('is_currently_offline')->default(false)->after('last_offline_at')
                ->comment('Current offline status');

            $table->boolean('incentive_eligible')->default(true)->after('is_currently_offline')
                ->comment('Whether DM is eligible for incentives (based on offline time)');

            $table->json('offline_sessions')->nullable()->after('incentive_eligible')
                ->comment('Array of offline sessions: [{start, end, duration_mins}]');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deliveryman_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'offline_count',
                'total_offline_minutes',
                'last_online_at',
                'last_offline_at',
                'is_currently_offline',
                'incentive_eligible',
                'offline_sessions'
            ]);
        });
    }
};
