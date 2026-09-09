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
        Schema::create('wa_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->enum('category', ['marketing', 'utility', 'authentication'])->default('marketing');
            $table->string('language', 10)->default('en');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');

            // Header
            $table->enum('header_type', ['text', 'image', 'video', 'document'])->nullable();
            $table->text('header_content')->nullable();

            // Body & Footer
            $table->text('body_text');
            $table->string('footer_text', 60)->nullable();

            // Buttons
            $table->json('buttons')->nullable(); // Max 3 buttons

            // Variables
            $table->json('variables')->nullable(); // Variable examples

            // WhatsApp API
            $table->string('whatsapp_template_id', 255)->nullable();
            $table->text('rejection_reason')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['status', 'category'], 'idx_status_category');
            $table->index('whatsapp_template_id', 'idx_whatsapp_id');
            $table->index('created_by', 'idx_created_by');

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wa_templates');
    }
};
