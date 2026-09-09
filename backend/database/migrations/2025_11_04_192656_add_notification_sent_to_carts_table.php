<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('updated_at');
            $table->boolean('abandoned_notification_sent')->default(false)->after('last_activity_at');
        });
    }

    public function down()
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn(['last_activity_at', 'abandoned_notification_sent']);
        });
    }
};
