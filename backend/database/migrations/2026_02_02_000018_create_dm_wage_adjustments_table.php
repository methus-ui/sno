<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dm_wage_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_man_id');
            $table->date('date');
            $table->decimal('total_earned', 10, 2)->default(0);
            $table->decimal('min_guaranteed', 10, 2)->default(0);
            $table->decimal('adjustment_amount', 10, 2)->default(0);
            $table->decimal('hours_logged', 5, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('delivery_man_id')->references('id')->on('delivery_men')->onDelete('cascade');
            $table->unique(['delivery_man_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('dm_wage_adjustments');
    }
};
