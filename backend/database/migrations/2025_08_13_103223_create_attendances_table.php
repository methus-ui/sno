<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->datetime('punch_in')->nullable();
            $table->datetime('punch_out')->nullable();
            $table->date('attendance_date');
            $table->decimal('total_hours', 8, 2)->nullable();
            $table->enum('status', ['present', 'absent', 'partial'])->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->index(['admin_id', 'attendance_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}