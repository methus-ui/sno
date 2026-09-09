<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftRostersTable extends Migration
{
    public function up()
    {
        Schema::create('shift_rosters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->tinyInteger('day_of_week');
            $table->time('shift_start')->nullable();
            $table->time('shift_end')->nullable();
            $table->boolean('is_off_day')->default(false);
            $table->date('week_start_date');
            $table->unsignedBigInteger('shift_template_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('shift_template_id')->references('id')->on('shift_templates')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('set null');
            $table->unique(['admin_id', 'day_of_week', 'week_start_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_rosters');
    }
}
