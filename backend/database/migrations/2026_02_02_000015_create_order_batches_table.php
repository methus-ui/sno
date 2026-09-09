<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->unsignedBigInteger('delivery_man_id')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->enum('status', ['pending', 'assigned', 'in_progress', 'completed'])->default('pending');
            $table->integer('total_orders')->default(0);
            $table->timestamps();

            $table->foreign('delivery_man_id')->references('id')->on('delivery_men')->onDelete('set null');
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_batches');
    }
};
