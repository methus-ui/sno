<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('breaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->timestamp('break_start');
            $table->timestamp('break_end')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('breaks');
    }
};
