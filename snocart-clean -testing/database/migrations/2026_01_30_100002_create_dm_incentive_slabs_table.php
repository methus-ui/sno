<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_incentive_slabs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['delivery_count', 'avg_delivery_time']);
            $table->decimal('min_value', 10, 2);
            $table->decimal('max_value', 10, 2)->nullable();
            $table->decimal('bonus_amount', 10, 2);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_incentive_slabs');
    }
};
