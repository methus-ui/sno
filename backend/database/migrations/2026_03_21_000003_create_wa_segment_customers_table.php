<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cached segment membership for performance.
     * Pre-calculated which customers belong to which segments.
     */
    public function up(): void
    {
        Schema::create('wa_segment_customers', function (Blueprint $table) {
            $table->unsignedBigInteger('segment_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('added_at')->useCurrent();

            // Composite primary key
            $table->primary(['segment_id', 'user_id']);

            // Foreign keys
            $table->foreign('segment_id')->references('id')->on('wa_customer_segments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Index for reverse lookup
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_segment_customers');
    }
};
