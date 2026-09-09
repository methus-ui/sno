<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('device_pairing_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('pairing_token', 50)->unique()->comment('One-time pairing token');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->foreignId('generated_by')->constrained('vendor_employees')->onDelete('cascade')->comment('Employee who generated token');
            $table->timestamp('expires_at')->comment('Token expiry (5 minutes)');
            $table->timestamp('used_at')->nullable()->comment('When token was used');
            $table->string('device_id', 100)->nullable()->comment('Device that used this token');
            $table->timestamp('created_at')->useCurrent();

            // Indexes for performance
            $table->index('pairing_token');
            $table->index('expires_at');
            $table->index('used_at');
            $table->index(['vendor_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_pairing_tokens');
    }
};
