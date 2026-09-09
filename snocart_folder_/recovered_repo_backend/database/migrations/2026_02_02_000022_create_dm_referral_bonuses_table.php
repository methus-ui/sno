<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dm_referral_bonuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id');
            $table->unsignedBigInteger('referred_id');
            $table->decimal('bonus_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->boolean('condition_met')->default(false);
            $table->timestamps();

            $table->foreign('referrer_id')->references('id')->on('delivery_men')->onDelete('cascade');
            $table->foreign('referred_id')->references('id')->on('delivery_men')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dm_referral_bonuses');
    }
};
