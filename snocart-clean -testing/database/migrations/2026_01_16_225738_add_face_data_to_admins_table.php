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
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'face_data')) {
                $table->longText('face_data')->nullable()->after('image');
            }
            if (!Schema::hasColumn('admins', 'face_registered')) {
                $table->boolean('face_registered')->default(false)->after('face_data');
            }
            if (!Schema::hasColumn('admins', 'face_registered_at')) {
                $table->timestamp('face_registered_at')->nullable()->after('face_registered');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['face_data', 'face_registered', 'face_registered_at']);
        });
    }
};
