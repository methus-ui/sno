<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Fix Wallet Refund Tracking System
 *
 * Changes:
 * 1. Change wallet_refund_processed from boolean to decimal to track cumulative refund amounts
 * 2. Add total_refunded_amount column to track all refunds for an order
 * 3. Add refund_history JSON column to track each refund event
 * 4. Migrate existing data (true → amount, false → 0)
 */
class FixWalletRefundTracking extends Migration
{
    public function up()
    {
        // Add new columns temporarily
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_refunded_amount', 24, 2)->default(0)->after('wallet_refund_processed');
            $table->json('refund_history')->nullable()->after('total_refunded_amount');
        });

        // Migrate existing data
        echo "Migrating existing wallet_refund_processed data...\n";

        // For orders where wallet_refund_processed = true, try to find the refund amount
        $processedOrders = DB::table('orders')
            ->where('wallet_refund_processed', true)
            ->select('id', 'user_id', 'adjustment_amount')
            ->get();

        foreach ($processedOrders as $order) {
            // Find the actual refund transaction
            $refundTransaction = DB::table('wallet_transactions')
                ->where('transaction_type', 'order_refund')
                ->where('reference', $order->id)
                ->first();

            if ($refundTransaction) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'total_refunded_amount' => $refundTransaction->credit,
                        'refund_history' => json_encode([[
                            'amount' => $refundTransaction->credit,
                            'transaction_id' => $refundTransaction->transaction_id,
                            'refunded_at' => $refundTransaction->created_at,
                            'type' => 'automatic',
                            'migrated' => true
                        ]])
                    ]);
            } else {
                // No transaction found, use adjustment_amount as estimate
                if ($order->adjustment_amount < 0) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'total_refunded_amount' => abs($order->adjustment_amount),
                            'refund_history' => json_encode([[
                                'amount' => abs($order->adjustment_amount),
                                'refunded_at' => now(),
                                'type' => 'estimated_from_migration',
                                'note' => 'No transaction found, estimated from adjustment_amount'
                            ]])
                        ]);
                }
            }
        }

        echo "Migrated " . count($processedOrders) . " orders with existing refunds.\n";

        // Drop the old boolean column
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('wallet_refund_processed');
        });

        // Rename total_refunded_amount to wallet_refund_processed to maintain compatibility
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('total_refunded_amount', 'wallet_refund_processed');
        });

        // Add comment to clarify the new column type
        DB::statement("ALTER TABLE orders MODIFY wallet_refund_processed DECIMAL(24,2) DEFAULT 0 COMMENT 'Total amount refunded to wallet (changed from boolean to decimal)'");

        echo "✅ Migration complete! wallet_refund_processed is now decimal(24,2)\n";
    }

    public function down()
    {
        // Reverse migration: Convert decimal back to boolean
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('wallet_refund_processed_temp')->default(false);
        });

        // Convert data: any amount > 0 becomes true
        DB::table('orders')
            ->where('wallet_refund_processed', '>', 0)
            ->update(['wallet_refund_processed_temp' => true]);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['wallet_refund_processed', 'refund_history']);
            $table->renameColumn('wallet_refund_processed_temp', 'wallet_refund_processed');
        });
    }
}
