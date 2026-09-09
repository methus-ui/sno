<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Customer segment definitions (predefined + custom).
     * 16 predefined segments + unlimited custom segments.
     */
    public function up(): void
    {
        Schema::create('wa_customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->enum('type', ['predefined', 'custom'])->default('custom');
            $table->json('filters'); // Segment criteria
            $table->integer('customer_count')->default(0); // Cached count
            $table->timestamp('last_calculated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // Admin who created custom segment
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_customer_segments');
    }
};
