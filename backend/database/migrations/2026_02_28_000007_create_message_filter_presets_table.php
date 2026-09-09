<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateMessageFilterPresetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_filter_presets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // NULL = global preset
            $table->string('user_type', 50)->nullable(); // admin, vendor, etc.
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->json('filter_params'); // Stored filter configuration
            $table->boolean('is_global')->default(false); // System-wide presets
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'user_type'], 'idx_user_presets');
            $table->index(['is_global', 'sort_order'], 'idx_global_presets');
            $table->index('is_active', 'idx_active_presets');
        });

        // Add comment
        DB::statement("ALTER TABLE message_filter_presets COMMENT 'Stores saved filter combinations for quick access'");

        // Insert default global presets
        $this->insertDefaultPresets();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_filter_presets');
    }

    /**
     * Insert default global filter presets
     *
     * @return void
     */
    private function insertDefaultPresets()
    {
        $presets = [
            [
                'name' => 'Unread Messages',
                'description' => 'Show only unread messages',
                'filter_params' => json_encode(['is_read' => false]),
                'is_global' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Today\'s Messages',
                'description' => 'Messages from today',
                'filter_params' => json_encode(['date_range' => 'today']),
                'is_global' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Customer Support',
                'description' => 'Active customer conversations',
                'filter_params' => json_encode([
                    'user_type' => ['customer'],
                    'is_archived' => false,
                ]),
                'is_global' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Order Messages',
                'description' => 'Messages with order references',
                'filter_params' => json_encode(['has_order' => true]),
                'is_global' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'With Attachments',
                'description' => 'Messages containing files',
                'filter_params' => json_encode(['has_attachments' => true]),
                'is_global' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($presets as $preset) {
            DB::table('message_filter_presets')->insert(array_merge($preset, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
