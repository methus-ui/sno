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
        Schema::table('delivery_men', function (Blueprint $table) {
            // Change identity_image from VARCHAR(255) to TEXT to support JSON arrays with multiple images
            $table->text('identity_image')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            // Revert back to VARCHAR(255) if needed
            $table->string('identity_image', 255)->change();
        });
    }
};
