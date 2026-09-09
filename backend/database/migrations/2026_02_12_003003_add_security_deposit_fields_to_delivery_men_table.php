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
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('delivery_men', 'security_deposit_amount')) {
                $table->decimal('security_deposit_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('delivery_men', 'security_deposit_status')) {
                $table->enum('security_deposit_status', ['unpaid', 'paid', 'refunded'])->default('unpaid');
            }
            if (!Schema::hasColumn('delivery_men', 'security_deposit_paid_at')) {
                $table->timestamp('security_deposit_paid_at')->nullable();
            }
            if (!Schema::hasColumn('delivery_men', 'security_deposit_transaction_id')) {
                $table->string('security_deposit_transaction_id')->nullable();
            }
            if (!Schema::hasColumn('delivery_men', 'security_deposit_payment_method')) {
                $table->string('security_deposit_payment_method')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropColumn([
                'security_deposit_amount',
                'security_deposit_status',
                'security_deposit_paid_at',
                'security_deposit_transaction_id',
                'security_deposit_payment_method'
            ]);
        });
    }
};
