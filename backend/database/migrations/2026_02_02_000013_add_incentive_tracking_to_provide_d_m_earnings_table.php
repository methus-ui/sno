<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provide_d_m_earnings', function (Blueprint $table) {
            $table->enum('incentive_type', ['daily_milestone', 'time_based', 'rush', 'tier_bonus', 'manual'])->default('manual')->after('amount');
            $table->unsignedBigInteger('incentive_rule_id')->nullable()->after('incentive_type');
            $table->unsignedBigInteger('order_id')->nullable()->after('incentive_rule_id');
            $table->json('metadata')->nullable()->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('provide_d_m_earnings', function (Blueprint $table) {
            $table->dropColumn(['incentive_type', 'incentive_rule_id', 'order_id', 'metadata']);
        });
    }
};
