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
        Schema::create('security_deposit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_man_id')->constrained('delivery_men')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('INR');
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable(); // razorpay, paytm, etc.
            $table->string('transaction_id')->nullable();
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->text('payment_url')->nullable();
            $table->text('redirect_url')->nullable();
            $table->text('callback_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamps();

            $table->index('delivery_man_id');
            $table->index('status');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_deposit_payments');
    }
};
