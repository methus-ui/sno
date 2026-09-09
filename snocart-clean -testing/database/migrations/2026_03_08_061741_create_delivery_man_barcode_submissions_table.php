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
        Schema::create('delivery_man_barcode_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_man_id')->constrained('delivery_men')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('order_detail_id')->constrained('order_details')->onDelete('cascade');
            $table->foreignId('item_id')->nullable()->constrained('items')->onDelete('set null');
            $table->string('submitted_barcode', 100);
            $table->string('expected_barcode', 100)->nullable()->comment('Barcode from item master');
            $table->boolean('is_match')->default(false)->comment('Whether submitted barcode matches expected');
            $table->timestamp('submitted_at')->useCurrent();
            $table->string('submission_type', 20)->default('manual')->comment('manual, scanned, etc');
            $table->json('metadata')->nullable()->comment('Additional data like GPS coords, photo, etc');
            $table->timestamps();

            // Indexes
            $table->index('delivery_man_id');
            $table->index('order_id');
            $table->index(['order_id', 'item_id']);
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_man_barcode_submissions');
    }
};
