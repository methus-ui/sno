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
        Schema::table('delivery_men', function (Blueprint $table) {
            // Track when new joiner bonus period ends
            $table->timestamp('new_joiner_bonus_ends_at')->nullable()->after('security_deposit_status');

            // Track total completed deliveries (for milestone unlocking)
            $table->integer('total_completed_deliveries')->default(0)->after('new_joiner_bonus_ends_at');

            // Track average customer rating
            $table->decimal('average_rating', 3, 2)->default(5.00)->after('total_completed_deliveries');

            // Add index for faster queries
            $table->index(['new_joiner_bonus_ends_at']);
            $table->index(['total_completed_deliveries']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropIndex(['new_joiner_bonus_ends_at']);
            $table->dropIndex(['total_completed_deliveries']);
            $table->dropColumn(['new_joiner_bonus_ends_at', 'total_completed_deliveries', 'average_rating']);
        });
    }
};
