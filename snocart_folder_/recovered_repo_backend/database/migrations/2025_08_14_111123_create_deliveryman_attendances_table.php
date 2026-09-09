<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliverymanAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deliveryman_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_man_id');
            $table->date('date');
            $table->time('punch_in_time')->nullable();
            $table->time('punch_out_time')->nullable();
            $table->decimal('working_hours', 5, 2)->default(0.00);
            $table->enum('status', ['present', 'absent', 'partial'])->default('absent');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('delivery_man_id')->references('id')->on('delivery_men')->onDelete('cascade');
            $table->unique(['delivery_man_id', 'date']);
            $table->index(['delivery_man_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deliveryman_attendances');
    }
}