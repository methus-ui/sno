<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wa_campaigns', function (Blueprint $table) {
            // Template management
            $table->unsignedBigInteger('template_id')->nullable()->after('segment_ids');

            // A/B testing
            $table->unsignedBigInteger('ab_test_id')->nullable()->after('template_id');

            // Campaign control
            $table->timestamp('paused_at')->nullable()->after('schedule_at');

            // Indexes
            $table->index('template_id', 'idx_template');
            $table->index('ab_test_id', 'idx_ab_test');

            // Foreign keys (nullable - not all campaigns use templates/AB tests)
            $table->foreign('template_id')->references('id')->on('wa_templates')->onDelete('set null');
            $table->foreign('ab_test_id')->references('id')->on('wa_ab_tests')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wa_campaigns', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['template_id']);
            $table->dropForeign(['ab_test_id']);

            // Drop indexes
            $table->dropIndex('idx_template');
            $table->dropIndex('idx_ab_test');

            // Drop columns
            $table->dropColumn(['template_id', 'ab_test_id', 'paused_at']);
        });
    }
};
