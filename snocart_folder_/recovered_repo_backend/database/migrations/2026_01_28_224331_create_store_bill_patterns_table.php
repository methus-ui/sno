<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_bill_patterns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->enum('field', ['total', 'subtotal', 'discount', 'tax', 'customer_name']);
            $table->string('pattern', 255);
            $table->unsignedInteger('frequency')->default(1);
            $table->timestamps();

            $table->index(['store_id', 'field']);
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->unique(['store_id', 'field', 'pattern']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_bill_patterns');
    }
};
