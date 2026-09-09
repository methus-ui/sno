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
            // Approval workflow fields
            $table->tinyInteger('status')->nullable()->after('password')
                ->comment('null=pending, 1=approved, 0=denied');
            $table->uuid('application_id')->unique()->nullable()->after('status');
            $table->text('rejection_reason')->nullable()->after('application_id');
            $table->unsignedBigInteger('approved_by')->nullable()->after('rejection_reason');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('applied_at')->nullable()->after('approved_at');

            // Document paths (JSON array)
            $table->json('documents')->nullable()->after('image')
                ->comment('{"resume":"path","id_proof":"path","certificates":["path1"]}');

            // Employment details
            $table->text('address')->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('address');
            $table->string('emergency_contact_name', 100)->nullable()->after('date_of_birth');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');

            // Foreign key
            $table->foreign('approved_by')->references('id')->on('admins')->onDelete('set null');

            // Indexes
            $table->index(['status', 'applied_at']);
            $table->index('application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['status', 'applied_at']);
            $table->dropIndex(['application_id']);

            $table->dropColumn([
                'status',
                'application_id',
                'rejection_reason',
                'approved_by',
                'approved_at',
                'applied_at',
                'documents',
                'address',
                'date_of_birth',
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        });
    }
};
