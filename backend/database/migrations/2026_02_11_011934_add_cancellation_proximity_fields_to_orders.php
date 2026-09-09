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
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('dm_was_at_location_on_cancel')->default(0)->after('canceled_by');
            $table->decimal('dm_distance_on_cancel', 10, 2)->nullable()->after('dm_was_at_location_on_cancel')->comment('Distance in meters when cancelled');
            $table->decimal('dm_cancellation_compensation', 10, 2)->default(0)->after('dm_distance_on_cancel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['dm_was_at_location_on_cancel', 'dm_distance_on_cancel', 'dm_cancellation_compensation']);
        });
    }
};
