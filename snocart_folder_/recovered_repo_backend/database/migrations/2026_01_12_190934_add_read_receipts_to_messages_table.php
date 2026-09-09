<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('is_seen');
            $table->unsignedBigInteger('read_by_admin_id')->nullable()->after('read_at');

            $table->index('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['read_at']);
            $table->dropColumn(['read_at', 'read_by_admin_id']);
        });
    }
};
