<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('total_break_minutes')->default(0)->after('notes');
            $table->integer('allocated_break_minutes')->default(30)->after('total_break_minutes');
            $table->integer('extra_break_minutes')->default(0)->after('allocated_break_minutes');
            $table->timestamp('expected_shift_end')->nullable()->after('extra_break_minutes');
            $table->boolean('shift_completed')->default(false)->after('expected_shift_end');
            $table->boolean('early_departure')->default(false)->after('shift_completed');
            $table->decimal('actual_work_hours', 5, 2)->nullable()->after('early_departure');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'total_break_minutes', 'allocated_break_minutes', 'extra_break_minutes',
                'expected_shift_end', 'shift_completed', 'early_departure', 'actual_work_hours',
            ]);
        });
    }
};
