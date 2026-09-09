<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageSearchHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_search_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type', 50); // admin, vendor, customer, delivery_man
            $table->string('search_query', 255);
            $table->integer('result_count')->default(0);
            $table->json('filter_params')->nullable(); // Store applied filters
            $table->timestamp('created_at')->useCurrent();

            // Indexes for fast retrieval
            $table->index(['user_id', 'user_type', 'created_at'], 'idx_user_searches');
            $table->index('search_query', 'idx_search_query');
            $table->index('created_at', 'idx_created_at');
        });

        // Add comment to table
        DB::statement("ALTER TABLE message_search_history COMMENT 'Stores user search history for quick repeat searches'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_search_history');
    }
}
