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
        // Add Odoo fields to stores table
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('odoo_enabled')->default(false)->after('active');
            $table->string('odoo_api_key', 64)->nullable()->unique()->after('odoo_enabled'); // API key for local sync
            $table->string('odoo_database')->nullable()->after('odoo_api_key');
            $table->string('odoo_admin_user')->nullable()->after('odoo_database');
            $table->text('odoo_admin_password')->nullable()->after('odoo_admin_user'); // Encrypted
            $table->string('odoo_url')->nullable()->after('odoo_admin_password');
            $table->timestamp('odoo_enabled_at')->nullable()->after('odoo_url');
            $table->timestamp('odoo_disabled_at')->nullable()->after('odoo_enabled_at');
            $table->index('odoo_enabled');
            $table->index('odoo_api_key');
        });

        // Add Odoo sync fields to items table
        Schema::table('items', function (Blueprint $table) {
            $table->bigInteger('odoo_product_id')->nullable()->after('id');
            $table->timestamp('odoo_last_sync')->nullable()->after('odoo_product_id');
            $table->index('odoo_product_id');
        });

        // Add Odoo sync fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->bigInteger('odoo_order_id')->nullable()->after('id');
            $table->enum('sync_source', ['web', 'odoo', 'api', 'pos'])->default('web')->after('odoo_order_id');
            $table->timestamp('odoo_synced_at')->nullable()->after('sync_source');
            $table->index(['sync_source', 'odoo_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex(['odoo_enabled']);
            $table->dropIndex(['odoo_api_key']);
            $table->dropColumn([
                'odoo_enabled',
                'odoo_api_key',
                'odoo_database',
                'odoo_admin_user',
                'odoo_admin_password',
                'odoo_url',
                'odoo_enabled_at',
                'odoo_disabled_at'
            ]);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['odoo_product_id']);
            $table->dropColumn(['odoo_product_id', 'odoo_last_sync']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['sync_source', 'odoo_order_id']);
            $table->dropColumn(['odoo_order_id', 'sync_source', 'odoo_synced_at']);
        });
    }
};
