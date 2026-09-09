<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->string('ref_code', 10)->nullable()->unique()->after('current_tier_id');
            $table->unsignedBigInteger('referred_by')->nullable()->after('ref_code');
            $table->foreign('referred_by')->references('id')->on('delivery_men')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn(['ref_code', 'referred_by']);
        });
    }
};
