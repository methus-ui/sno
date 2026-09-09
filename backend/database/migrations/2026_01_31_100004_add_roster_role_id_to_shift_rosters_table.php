<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shift_rosters', function (Blueprint $table) {
            $table->unsignedBigInteger('roster_role_id')->nullable()->after('created_by');
            $table->foreign('roster_role_id')->references('id')->on('roster_roles')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('shift_rosters', function (Blueprint $table) {
            $table->dropForeign(['roster_role_id']);
            $table->dropColumn('roster_role_id');
        });
    }
};
