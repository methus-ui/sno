<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('email', 100);
            $table->enum('employee_type', ['admin', 'vendor'])->comment('admin or vendor employee');
            $table->unsignedBigInteger('role_id')->nullable()->comment('Pre-assigned role');
            $table->unsignedBigInteger('zone_id')->nullable()->comment('Pre-assigned zone (admin only)');
            $table->unsignedBigInteger('store_id')->nullable()->comment('Pre-assigned store (vendor only)');
            $table->unsignedBigInteger('created_by');
            $table->boolean('is_used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('admins')->onDelete('cascade');
            $table->index(['token', 'expires_at']);
            $table->index(['email', 'employee_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_invitations');
    }
};
