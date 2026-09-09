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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('instagram_username', 100)->nullable()->after('comment');
            $table->string('instagram_user_id', 50)->nullable()->after('instagram_username');
            $table->text('instagram_access_token')->nullable()->after('instagram_user_id');
            $table->timestamp('instagram_token_expires_at')->nullable()->after('instagram_access_token');

            // Add index for faster lookups
            $table->index('instagram_user_id', 'idx_instagram_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex('idx_instagram_user_id');
            $table->dropColumn([
                'instagram_username',
                'instagram_user_id',
                'instagram_access_token',
                'instagram_token_expires_at'
            ]);
        });
    }
};
