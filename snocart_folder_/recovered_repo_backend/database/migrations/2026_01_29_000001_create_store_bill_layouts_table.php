<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_bill_layouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('field'); // store_name, total, customer_name, etc.
            $table->enum('position', ['top', 'middle', 'bottom'])->nullable();
            $table->float('line_ratio')->nullable(); // 0.0–1.0 position as fraction of total lines
            $table->string('correction_from')->nullable(); // garbled OCR text
            $table->string('correction_to')->nullable(); // correct text
            $table->unsignedInteger('frequency')->default(1);
            $table->timestamps();

            $table->index(['store_id', 'field']);
            $table->index(['store_id', 'correction_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_bill_layouts');
    }
};
