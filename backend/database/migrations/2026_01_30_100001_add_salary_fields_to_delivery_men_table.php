<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->decimal('salary_amount', 10, 2)->default(0)->after('earning');
            $table->string('salary_payment_cycle', 20)->default('monthly')->after('salary_amount');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropColumn(['salary_amount', 'salary_payment_cycle']);
        });
    }
};
