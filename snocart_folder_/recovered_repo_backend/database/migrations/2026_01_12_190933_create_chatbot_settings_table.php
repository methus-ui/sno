<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chatbot_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('chatbot_settings')->insert([
            ['key' => 'chatbot_enabled', 'value' => 'true', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'ai_fallback_enabled', 'value' => 'false', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'ai_provider', 'value' => 'openai', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'ai_api_key', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'ai_model', 'value' => 'gpt-4o-mini', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'confidence_threshold', 'value' => '0.7', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'auto_response_delay', 'value' => '2', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_settings');
    }
};
