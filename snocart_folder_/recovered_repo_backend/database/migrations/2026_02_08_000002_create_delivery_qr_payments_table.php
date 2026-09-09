<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates table to track Razorpay QR code payments for delivery confirmation
     */
    public function up(): void
    {
        Schema::create('delivery_qr_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('delivery_man_id')->constrained('delivery_men')->onDelete('cascade');

            // Razorpay details
            $table->string('razorpay_qr_id')->unique(); // Razorpay QR ID
            $table->string('razorpay_order_id')->nullable(); // Razorpay Order ID
            $table->string('razorpay_payment_id')->nullable(); // Razorpay Payment ID after payment

            // Payment details
            $table->decimal('amount', 24, 2); // Amount to collect
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['pending', 'active', 'paid', 'expired', 'cancelled'])->default('pending');

            // QR code data
            $table->text('qr_code_url')->nullable(); // URL to QR code image
            $table->text('qr_code_data')->nullable(); // QR code string data
            $table->text('payment_link')->nullable(); // UPI payment link

            // Metadata
            $table->json('metadata')->nullable(); // Additional data (customer info, etc.)
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['order_id', 'delivery_man_id']);
            $table->index(['razorpay_qr_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_qr_payments');
    }
};
