<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Extend existing wa_campaigns table with new fields for:
     * - Segment-based targeting
     * - Scheduling
     * - Audit trail
     * - Performance tracking
     */
    public function up(): void
    {
        Schema::table('wa_campaigns', function (Blueprint $table) {
            // Segment targeting (array of segment IDs)
            $table->json('segment_ids')->nullable()->after('audience');

            // Scheduling support
            $table->timestamp('scheduled_at')->nullable()->after('finished_at');

            // Audit trail
            $table->unsignedBigInteger('created_by')->nullable()->after('status');

            // Performance tracking
            $table->integer('processing_time_seconds')->default(0)->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_campaigns', function (Blueprint $table) {
            $table->dropColumn(['segment_ids', 'scheduled_at', 'created_by', 'processing_time_seconds']);
        });
    }
};
