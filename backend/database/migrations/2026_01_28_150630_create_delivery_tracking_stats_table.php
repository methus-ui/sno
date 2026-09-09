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
        Schema::create('delivery_tracking_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('delivery_man_id')->index();

            // Idle tracking
            $table->timestamp('idle_start_time')->nullable();
            $table->integer('total_idle_seconds')->default(0); // Cumulative idle time
            $table->integer('idle_count')->default(0); // Number of idle periods

            // Store location tracking
            $table->timestamp('store_arrival_time')->nullable();
            $table->timestamp('store_departure_time')->nullable();
            $table->integer('store_duration_seconds')->default(0); // Total time at store

            // Customer location tracking
            $table->timestamp('customer_arrival_time')->nullable();
            $table->timestamp('customer_departure_time')->nullable();
            $table->integer('customer_duration_seconds')->default(0); // Total time at customer

            // Location state flags
            $table->boolean('is_at_store')->default(false);
            $table->boolean('is_at_customer')->default(false);
            $table->boolean('is_idle')->default(false);

            // Last known position
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->decimal('last_speed', 5, 2)->default(0); // km/h
            $table->string('movement_state', 20)->default('unknown'); // moving, slow, idle, stopped

            $table->timestamps();

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('delivery_man_id')->references('id')->on('delivery_men')->onDelete('cascade');

            // Index for ranking queries
            $table->index(['delivery_man_id', 'total_idle_seconds']);
            $table->index(['delivery_man_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_tracking_stats');
    }
};
