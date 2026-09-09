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
        Schema::create('wa_segment_filter_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('segment_id');

            // Filter field (order_count, total_spent, last_order_days_ago, etc.)
            $table->string('field', 100);

            // Operator (equals, gt, lt, between, in, not_in, contains)
            $table->string('operator', 20);

            // Value (JSON for flexibility - can store single value, array, range)
            $table->json('value');

            // Logic operator (AND, OR) - for combining with next rule
            $table->enum('logic_operator', ['AND', 'OR'])->default('AND');

            // Sort order for rule execution
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['segment_id', 'sort_order'], 'idx_segment_sort');

            // Foreign keys
            $table->foreign('segment_id')->references('id')->on('wa_customer_segments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wa_segment_filter_rules');
    }
};
