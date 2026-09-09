<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->unsignedBigInteger('current_tier_id')->nullable()->after('vehicle_id');
            $table->integer('total_orders_accepted')->default(0)->after('current_tier_id');
            $table->integer('total_orders_rejected')->default(0)->after('total_orders_accepted');
            $table->decimal('acceptance_rate', 5, 2)->default(0)->after('total_orders_rejected');
            $table->timestamp('last_tier_calculated_at')->nullable()->after('acceptance_rate');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropColumn(['current_tier_id', 'total_orders_accepted', 'total_orders_rejected', 'acceptance_rate', 'last_tier_calculated_at']);
        });
    }
};
