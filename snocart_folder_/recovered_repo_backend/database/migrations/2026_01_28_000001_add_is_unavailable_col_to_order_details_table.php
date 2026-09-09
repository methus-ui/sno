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
        Schema::table('order_details', function (Blueprint $table) {
            $table->boolean('is_unavailable')->default(false)->after('tax_amount');
            $table->string('unavailable_note')->nullable()->after('is_unavailable');
            $table->timestamp('marked_unavailable_at')->nullable()->after('unavailable_note');
            $table->decimal('requested_mrp', 24, 3)->nullable()->after('marked_unavailable_at');
            $table->string('mrp_update_status')->nullable()->after('requested_mrp')->comment('pending, approved, rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['is_unavailable', 'unavailable_note', 'marked_unavailable_at', 'requested_mrp', 'mrp_update_status']);
        });
    }
};
