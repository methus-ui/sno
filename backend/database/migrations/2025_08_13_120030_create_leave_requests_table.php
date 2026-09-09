<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->enum('leave_type', ['sick', 'personal', 'vacation', 'emergency', 'other']);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->timestamps();
            
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('admins')->onDelete('set null');
            $table->index(['admin_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_requests');
    }
}