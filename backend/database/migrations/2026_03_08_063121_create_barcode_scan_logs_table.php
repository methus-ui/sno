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
        Schema::create('barcode_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_man_id')->constrained('delivery_men')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->string('barcode', 255);
            $table->decimal('earning', 8, 2)->default(5.00);
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('delivery_man_id');
            $table->index('item_id');
            $table->index('created_at');
            
            // Prevent duplicate submissions for same item by same DM
            $table->unique(['delivery_man_id', 'item_id'], 'unique_dm_item_scan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcode_scan_logs');
    }
};
