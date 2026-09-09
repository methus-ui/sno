<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Allow delivery men to book multiple shifts per day
     * Example: Morning Peak (7am-12pm) + Evening Peak (3pm-8pm) on same day
     */
    public function up(): void
    {
        // Step 1: Add a regular index on delivery_man_id (for foreign key)
        DB::statement('
            ALTER TABLE dm_shift_bookings
            ADD INDEX idx_delivery_man_id (delivery_man_id)
        ');

        // Step 2: Drop the foreign key temporarily
        DB::statement('
            ALTER TABLE dm_shift_bookings
            DROP FOREIGN KEY dm_shift_bookings_delivery_man_id_foreign
        ');

        // Step 3: Drop the old unique constraint
        DB::statement('
            ALTER TABLE dm_shift_bookings
            DROP INDEX dm_date_status_unique
        ');

        // Step 4: Add new unique constraint (allows multiple shifts per day)
        DB::statement('
            ALTER TABLE dm_shift_bookings
            ADD UNIQUE KEY dm_shift_date_status_unique (delivery_man_id, shift_template_id, date, status)
        ');

        // Step 5: Recreate the foreign key
        DB::statement('
            ALTER TABLE dm_shift_bookings
            ADD CONSTRAINT dm_shift_bookings_delivery_man_id_foreign
            FOREIGN KEY (delivery_man_id) REFERENCES delivery_men(id) ON DELETE CASCADE
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the changes

        // Drop foreign key
        DB::statement('
            ALTER TABLE dm_shift_bookings
            DROP FOREIGN KEY dm_shift_bookings_delivery_man_id_foreign
        ');

        // Drop new unique constraint
        DB::statement('
            ALTER TABLE dm_shift_bookings
            DROP INDEX dm_shift_date_status_unique
        ');

        // Restore old unique constraint
        DB::statement('
            ALTER TABLE dm_shift_bookings
            ADD UNIQUE KEY dm_date_status_unique (delivery_man_id, date, status)
        ');

        // Recreate foreign key
        DB::statement('
            ALTER TABLE dm_shift_bookings
            ADD CONSTRAINT dm_shift_bookings_delivery_man_id_foreign
            FOREIGN KEY (delivery_man_id) REFERENCES delivery_men(id) ON DELETE CASCADE
        ');

        // Drop the regular index we added
        DB::statement('
            ALTER TABLE dm_shift_bookings
            DROP INDEX idx_delivery_man_id
        ');
    }
};
