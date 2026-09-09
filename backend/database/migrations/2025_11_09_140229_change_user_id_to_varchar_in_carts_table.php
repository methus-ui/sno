<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE carts MODIFY COLUMN user_id VARCHAR(255) NOT NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE carts MODIFY COLUMN user_id BIGINT UNSIGNED NOT NULL');
    }
};
