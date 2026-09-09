<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_zone_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->enum('notification_type', ['new_order', 'rush_active', 'incentive_available']);
            $table->integer('dms_notified_count')->default(0);
            $table->json('notification_payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_zone_notification_logs');
    }
};
