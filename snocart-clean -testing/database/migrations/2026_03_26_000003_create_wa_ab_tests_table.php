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
        Schema::create('wa_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->unsignedBigInteger('campaign_id_a');
            $table->unsignedBigInteger('campaign_id_b');
            $table->decimal('split_ratio', 5, 2)->default(50.00); // Percentage for variant A (0-100)
            $table->enum('winning_metric', ['delivery_rate', 'read_rate', 'conversion_rate'])->default('read_rate');
            $table->enum('status', ['draft', 'running', 'completed', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('winner_campaign_id')->nullable();
            $table->decimal('confidence_level', 5, 2)->nullable(); // Statistical significance (95%, 99%)
            $table->integer('test_duration_days')->default(7);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            // Indexes
            $table->index(['status', 'started_at'], 'idx_status_started');
            $table->index('created_by', 'idx_created_by');

            // Foreign keys
            $table->foreign('campaign_id_a')->references('id')->on('wa_campaigns')->onDelete('cascade');
            $table->foreign('campaign_id_b')->references('id')->on('wa_campaigns')->onDelete('cascade');
            $table->foreign('winner_campaign_id')->references('id')->on('wa_campaigns')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wa_ab_tests');
    }
};
