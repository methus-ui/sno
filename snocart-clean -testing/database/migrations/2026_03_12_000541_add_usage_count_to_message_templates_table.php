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
        Schema::table('message_templates', function (Blueprint $table) {
            $table->unsignedInteger('usage_count')->default(0)->after('content');
            $table->timestamp('last_used_at')->nullable()->after('usage_count');
            $table->index('usage_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropIndex(['usage_count']);
            $table->dropColumn(['usage_count', 'last_used_at']);
        });
    }
};
