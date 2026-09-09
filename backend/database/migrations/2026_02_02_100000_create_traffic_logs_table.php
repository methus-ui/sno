<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrafficLogsTable extends Migration
{
    public function up()
    {
        Schema::create('traffic_logs', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedInteger('total_requests')->default(0);
            $table->unsignedInteger('customer_requests')->default(0);
            $table->unsignedInteger('dm_requests')->default(0);
            $table->unsignedInteger('vendor_requests')->default(0);
            $table->unsignedInteger('app_opens')->default(0);
            $table->unsignedTinyInteger('peak_hour')->nullable();
            $table->json('top_endpoints')->nullable();
            $table->json('hourly_distribution')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('traffic_logs');
    }
}
