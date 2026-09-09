<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->boolean('notify_new_orders')->default(true)->after('vehicle_id');
            $table->boolean('notify_rush_active')->default(true)->after('notify_new_orders');
            $table->boolean('notify_incentives')->default(true)->after('notify_rush_active');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropColumn(['notify_new_orders', 'notify_rush_active', 'notify_incentives']);
        });
    }
};
