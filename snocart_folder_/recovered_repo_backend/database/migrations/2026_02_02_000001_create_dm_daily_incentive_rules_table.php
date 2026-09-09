<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_daily_incentive_rules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('delivery_count');
            $table->decimal('bonus_amount', 10, 2);
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_daily_incentive_rules');
    }
};
