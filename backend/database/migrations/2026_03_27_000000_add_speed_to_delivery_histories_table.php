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
        Schema::table('delivery_histories', function (Blueprint $table) {
            // Add speed column to track delivery man's speed in km/h
            $table->decimal('speed', 8, 2)->default(0)->after('latitude')
                ->comment('Delivery man speed in km/h from GPS data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_histories', function (Blueprint $table) {
            $table->dropColumn('speed');
        });
    }
};
