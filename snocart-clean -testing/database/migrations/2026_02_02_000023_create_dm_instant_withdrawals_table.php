<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dm_instant_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_man_id');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('method')->default('bank');
            $table->json('account_details')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->timestamps();

            $table->foreign('delivery_man_id')->references('id')->on('delivery_men')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dm_instant_withdrawals');
    }
};
