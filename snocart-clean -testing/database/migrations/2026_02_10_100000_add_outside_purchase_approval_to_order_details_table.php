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
            // Add approval status: null (not requested), 'pending', 'approved', 'rejected'
            $table->string('outside_purchase_status')->nullable()->after('outside_purchase_store_id');
            // Track who requested (dm or admin)
            $table->string('outside_purchase_requested_by')->nullable()->after('outside_purchase_status'); // 'deliveryman' or 'admin'
            // Track approval/rejection details
            $table->unsignedBigInteger('outside_purchase_approved_by')->nullable()->after('outside_purchase_requested_by');
            $table->timestamp('outside_purchase_approved_at')->nullable()->after('outside_purchase_approved_by');
            $table->text('outside_purchase_rejection_reason')->nullable()->after('outside_purchase_approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn([
                'outside_purchase_status',
                'outside_purchase_requested_by',
                'outside_purchase_approved_by',
                'outside_purchase_approved_at',
                'outside_purchase_rejection_reason'
            ]);
        });
    }
};
