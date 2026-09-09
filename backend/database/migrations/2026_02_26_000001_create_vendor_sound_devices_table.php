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
        Schema::create('vendor_sound_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('device_id', 100)->unique()->comment('ESP32 MAC address or UUID');
            $table->string('device_name', 100)->nullable()->comment('User-friendly name');
            $table->string('webhook_url', 255)->nullable()->comment('Device IP:PORT for callbacks');
            $table->string('api_key', 64)->unique()->comment('Authentication token for device');
            $table->string('api_key_hash', 255)->comment('Hashed version for security');
            $table->boolean('is_active')->default(true)->comment('Device active status');
            $table->timestamp('last_ping_at')->nullable()->comment('Last heartbeat timestamp');
            $table->string('wifi_ssid', 100)->nullable()->comment('Connected WiFi network');
            $table->string('local_ip', 45)->nullable()->comment('Device local IP address');
            $table->string('firmware_version', 50)->nullable()->comment('ESP32 firmware version');
            $table->json('settings')->nullable()->comment('Device preferences (volume, language, auto_accept)');
            $table->timestamp('paired_at')->nullable()->comment('When device was paired');
            $table->foreignId('paired_by')->nullable()->constrained('vendor_employees')->onDelete('set null')->comment('Employee who paired device');
            $table->timestamps();

            // Indexes for performance
            $table->index('device_id');
            $table->index('api_key');
            $table->index(['vendor_id', 'store_id']);
            $table->index('is_active');
            $table->index('last_ping_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vendor_sound_devices');
    }
};
