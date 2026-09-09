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
        Schema::table('advertisements', function (Blueprint $table) {
            $table->string('instagram_reel_url', 500)->nullable()->after('video_attachment');
            $table->enum('video_source', ['upload', 'instagram_reel', 'instagram_url'])->default('upload')->after('instagram_reel_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropColumn(['instagram_reel_url', 'video_source']);
        });
    }
};
