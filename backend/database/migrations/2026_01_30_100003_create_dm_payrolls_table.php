<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_man_id');
            $table->decimal('salary_amount', 10, 2)->default(0);
            $table->decimal('incentive_amount', 10, 2)->default(0);
            $table->json('incentive_breakdown')->nullable();
            $table->integer('total_deliveries')->default(0);
            $table->decimal('avg_delivery_time', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_payable', 10, 2)->default(0);
            $table->date('period_from');
            $table->date('period_to');
            $table->enum('status', ['generated', 'paid', 'cancelled'])->default('generated');
            $table->timestamp('paid_at')->nullable();
            $table->string('paid_method')->nullable();
            $table->string('paid_ref')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('delivery_man_id')->references('id')->on('delivery_men')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_payrolls');
    }
};
