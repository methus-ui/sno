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
        Schema::create('learned_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 100)->unique();
            $table->integer('frequency')->default(1);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('category')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('frequency');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learned_keywords');
    }
};
