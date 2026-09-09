<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Snoscore (Customer Behavior Rating) columns to users table.
     * - Tracks customer-initiated vs store/admin-initiated cancellations
     * - Refunds tracked separately (no score impact)
     * - Snoscore: 1-5 scale behavior rating
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cancellation tracking (weighted approach)
            $table->unsignedInteger('customer_canceled_count')->default(0)->after('order_count')
                  ->comment('Customer-initiated cancellations (full weight)');
            $table->unsignedInteger('other_canceled_count')->default(0)->after('customer_canceled_count')
                  ->comment('Store/admin-initiated cancellations (0.3 weight)');

            // Refund tracking (separate, no score impact)
            $table->unsignedInteger('refund_count')->default(0)->after('other_canceled_count')
                  ->comment('Refund requests (tracked separately)');

            // Snoscore (behavior rating)
            $table->decimal('snoscore', 3, 2)->default(5.00)->after('refund_count')
                  ->comment('Behavior score 1-5 scale');
            $table->string('snoscore_status', 20)->default('excellent')->after('snoscore')
                  ->comment('Status: excellent, good, fair, poor, risky');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'customer_canceled_count',
                'other_canceled_count',
                'refund_count',
                'snoscore',
                'snoscore_status',
            ]);
        });
    }
};
