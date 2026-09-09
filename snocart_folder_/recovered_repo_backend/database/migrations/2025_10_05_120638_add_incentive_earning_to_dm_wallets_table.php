<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_man_wallets')) {
            Schema::table('delivery_man_wallets', function (Blueprint $table) {
                if (!Schema::hasColumn('delivery_man_wallets', 'incentive_earning')) {
                    $table->decimal('incentive_earning', 10, 2)
                        ->default(0)
                        ->comment('Total incentive-based earnings of delivery man')
                        ->after('pending_withdraw');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('delivery_man_wallets', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_man_wallets', 'incentive_earning')) {
                $table->dropColumn('incentive_earning');
            }
        });
    }
};
