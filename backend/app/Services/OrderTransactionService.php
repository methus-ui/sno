<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\CentralLogics\OrderLogic;
use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\AdminWallet;
use App\Models\StoreWallet;
use App\Models\DeliveryManWallet;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderTransactionService
{
    /**
     * Update transaction when order is edited
     * Preserves original amounts and tracks all changes
     *
     * @param Order $order The order that was edited
     * @param string $editedBy 'admin' or 'vendor'
     * @param int $userId The ID of the user who made the edit
     * @param array $changes Additional context about what was changed
     * @return OrderTransaction|null
     */
    public static function updateFromOrderEdit(
        Order $order,
        string $editedBy,
        int $userId,
        array $changes = []
    ) {
        $transaction = OrderTransaction::where('order_id', $order->id)->first();

        if (!$transaction) {
            // Only auto-create transactions for delivered orders
            // For active orders, transaction will be created at delivery time
            if ($order->order_status === 'delivered') {
                Log::warning("Transaction missing for delivered order {$order->id}, creating new one");
                $received_by = $order->delivery_man_id ? 'deliveryman' : 'admin';
                OrderLogic::create_transaction($order, $received_by, null);
                return OrderTransaction::where('order_id', $order->id)->first();
            }
            // For non-delivered orders, just log and return null (edit amounts update order, not transaction)
            Log::info("Transaction not yet created for order {$order->id} (status: {$order->order_status}) — will be created at delivery");
            return null;
        }

        DB::beginTransaction();
        try {
            // First edit - preserve originals
            if (!$transaction->is_edited) {
                $transaction->original_order_amount = $transaction->order_amount;
                $transaction->original_store_amount = $transaction->store_amount;
                $transaction->original_admin_commission = $transaction->admin_commission;
                $transaction->is_edited = true;
            }

            // Calculate adjustment
            $oldAmount = $transaction->order_amount;
            $adjustment = $order->order_amount - $oldAmount;

            // Recalculate ALL transaction amounts based on new order state
            $newAmounts = self::recalculateTransactionAmounts($order, $transaction);

            // Update transaction with recalculated values
            $transaction->order_amount = $newAmounts['order_amount'];
            $transaction->store_amount = $newAmounts['store_amount'];
            $transaction->admin_commission = $newAmounts['admin_commission'];
            $transaction->tax = $newAmounts['tax'];
            $transaction->delivery_charge = $newAmounts['delivery_charge'];
            $transaction->original_delivery_charge = $newAmounts['original_delivery_charge'];
            $transaction->admin_expense = $newAmounts['admin_expense'];
            $transaction->store_expense = $newAmounts['store_expense'];
            $transaction->discount_amount_by_store = $newAmounts['discount_amount_by_store'];
            $transaction->delivery_fee_comission = $newAmounts['delivery_fee_comission'];
            $transaction->additional_charge = $newAmounts['additional_charge'];
            $transaction->extra_packaging_amount = $newAmounts['extra_packaging_amount'];
            $transaction->ref_bonus_amount = $newAmounts['ref_bonus_amount'];
            $transaction->outside_purchase_amount = $newAmounts['outside_purchase_amount'];

            // Update tracking fields
            $transaction->total_adjustment += $adjustment;
            $transaction->edit_count++;
            $transaction->last_edited_at = now();
            $transaction->last_edited_by = $userId;

            // Append to edit history (audit trail)
            $history = $transaction->edit_history ? json_decode($transaction->edit_history, true) : [];
            $history[] = [
                'edited_at' => now()->toDateTimeString(),
                'edited_by' => $editedBy,
                'user_id' => $userId,
                'adjustment' => $adjustment,
                'old_order_amount' => $oldAmount,
                'new_order_amount' => $order->order_amount,
                'changes' => $changes,
            ];
            $transaction->edit_history = json_encode($history);

            $transaction->save();

            // 🔧 FIX: Handle outside purchase wallet adjustments
            if ($order->order_type != 'parcel') {
                self::updateOutsidePurchaseWallets($order, $transaction, $newAmounts);
            }

            DB::commit();

            Log::info("Transaction updated for edited order", [
                'order_id' => $order->id,
                'edited_by' => $editedBy,
                'adjustment' => $adjustment,
                'edit_count' => $transaction->edit_count,
                'outside_purchase_handled' => $newAmounts['outside_purchase_amount'] > 0,
            ]);

            return $transaction;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction update failed for order ' . $order->id . ': ' . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Recalculate transaction amounts based on current order state
     * This mirrors the logic from OrderLogic::create_transaction
     *
     * @param Order $order
     * @param OrderTransaction $existingTransaction
     * @return array
     */
    private static function recalculateTransactionAmounts(Order $order, OrderTransaction $existingTransaction)
    {
        $type = $order->order_type;
        $dm_tips_manage_status = BusinessSetting::where('key', 'dm_tips_status')->first()->value;
        $admin_subsidy = 0;
        $amount_admin = 0;
        $store_d_amount = 0;
        $admin_coupon_discount_subsidy = 0;
        $store_subsidy = 0;
        $store_coupon_discount_subsidy = 0;
        $store_discount_amount = 0;
        $flash_admin_discount_amount = 0;
        $flash_store_discount_amount = 0;
        $comission_on_store_amount = 0;
        $ref_bonus_amount = 0;
        $subscription_mode = 0;
        $commission_percentage = 0;

        $store = $order?->store;
        $store_sub = $order?->store?->store_sub;

        // Free delivery by admin
        if ($order->free_delivery_by == 'admin') {
            $admin_subsidy = $order->original_delivery_charge;
        }

        // Free delivery by store
        if ($order->free_delivery_by == 'vendor') {
            $store_subsidy = $order->original_delivery_charge;
        }

        // Coupon discount by Admin
        if ($order->coupon_created_by == 'admin') {
            $admin_coupon_discount_subsidy = $order->coupon_discount_amount;
        }

        // 1st order discount by Admin
        if ($order->ref_bonus_amount > 0) {
            $ref_bonus_amount = $order->ref_bonus_amount;
        }

        // Coupon discount by store
        if ($order->coupon_created_by == 'vendor') {
            $store_coupon_discount_subsidy = $order->coupon_discount_amount;
        }

        if ($type == 'parcel') {
            $comission = \App\Models\BusinessSetting::where('key', 'parcel_commission_dm')->first();
            $dm_tips = $dm_tips_manage_status ? $order->dm_tips : 0;
            $comission = isset($comission) ? $comission->value : 0;
            $order_amount = $order->order_amount - $dm_tips - $order->additional_charge - $order->extra_packaging_amount;
            $dm_commission = $comission ? ($order_amount / 100) * $comission : 0;
            $comission_amount = $order_amount - $dm_commission;
        } else {
            $comission = isset($order->store->comission) == null ? \App\Models\BusinessSetting::where('key', 'admin_commission')->first()->value : $order->store->comission;
            $dm_tips = $dm_tips_manage_status ? $order->dm_tips : 0;

            if ($order->store_discount_amount > 0 && $order->discount_on_product_by == 'vendor') {
                if ($store->store_business_model == 'subscription' && isset($store_sub)) {
                    $store_d_amount = $order->store_discount_amount;
                } else {
                    $amount_admin = $comission ? ($order->store_discount_amount / 100) * $comission : 0;
                    $store_d_amount = $order->store_discount_amount - $amount_admin;
                }
            }

            if ($order->store_discount_amount > 0 && $order->discount_on_product_by == 'admin') {
                $store_discount_amount = $order->store_discount_amount;
            }

            if ($order->flash_admin_discount_amount > 0) {
                $flash_admin_discount_amount = $order->flash_admin_discount_amount;
            }

            if ($order->flash_store_discount_amount > 0) {
                $flash_store_discount_amount = $order->flash_store_discount_amount;
            }

            $order_amount = $order->order_amount - $order->additional_charge - $order->extra_packaging_amount - $order->delivery_charge - $order->total_tax_amount - $dm_tips + $flash_admin_discount_amount + $order->coupon_discount_amount + $store_discount_amount + $flash_store_discount_amount + $ref_bonus_amount;

            // Commission on delivery charge
            $delivery_charge_comission = BusinessSetting::where('key', 'delivery_charge_comission')->first();
            $delivery_charge_comission_percentage = $delivery_charge_comission ? $delivery_charge_comission->value : 0;
            $comission_on_delivery = $delivery_charge_comission_percentage * ($order->original_delivery_charge / 100);

            if ($order->store->sub_self_delivery) {
                $comission_on_actual_delivery_fee = 0;
            } else {
                $comission_on_actual_delivery_fee = ($order->delivery_charge > 0) ? $comission_on_delivery : 0;
            }

            if ($order->free_delivery_by == 'admin') {
                if ($order->store->sub_self_delivery) {
                    $comission_on_actual_delivery_fee = 0;
                } else {
                    $comission_on_actual_delivery_fee = ($order->original_delivery_charge > 0) ? $comission_on_delivery : 0;
                }
            }

            // Final commission
            if ($store->store_business_model == 'subscription' && isset($store_sub)) {
                $comission_on_store_amount = 0;
                $subscription_mode = 1;
                $commission_percentage = 0;
            } else {
                $comission_on_store_amount = ($comission ? ($order_amount / 100) * $comission : 0);
                $subscription_mode = 0;
                $commission_percentage = $comission;
            }

            $comission_amount = $comission_on_store_amount + $comission_on_actual_delivery_fee;
            $dm_commission = $order->original_delivery_charge - $comission_on_actual_delivery_fee;
        }

        $store_amount = $order_amount + $order->total_tax_amount + $order->extra_packaging_amount - $comission_on_store_amount - $store_coupon_discount_subsidy - $flash_store_discount_amount;

        // Outside Purchase calculation
        $outside_purchase_customer_total = 0;
        $outside_purchase_cost_total = $order->outside_purchase_amount ?? 0;
        if ($type != 'parcel' && $outside_purchase_cost_total > 0) {
            foreach ($order->details as $detail) {
                if ($detail->is_outside_purchase) {
                    $outside_purchase_customer_total += $detail->price * $detail->quantity;
                }
            }
            $op_commission = $comission ? ($outside_purchase_customer_total / 100) * $comission : 0;
            $op_vendor_portion = $outside_purchase_customer_total - $op_commission;
            $store_amount = $store_amount - $op_vendor_portion;
        }

        return [
            'order_amount' => $order->order_amount,
            'store_amount' => $type == 'parcel' ? 0 : $store_amount,
            'admin_commission' => $comission_amount + $order->additional_charge - $admin_subsidy - $admin_coupon_discount_subsidy - $ref_bonus_amount,
            'delivery_charge' => $order->delivery_charge,
            'original_delivery_charge' => $dm_commission,
            'tax' => $order->total_tax_amount,
            'admin_expense' => $admin_subsidy + $admin_coupon_discount_subsidy + $store_discount_amount + $flash_admin_discount_amount + $amount_admin + $ref_bonus_amount,
            'store_expense' => $store_subsidy + $store_coupon_discount_subsidy + $flash_store_discount_amount,
            'discount_amount_by_store' => $store_coupon_discount_subsidy + $store_d_amount + $store_subsidy,
            'delivery_fee_comission' => isset($comission_on_actual_delivery_fee) ? $comission_on_actual_delivery_fee : 0,
            'additional_charge' => $order->additional_charge,
            'extra_packaging_amount' => $order->extra_packaging_amount,
            'ref_bonus_amount' => $order->ref_bonus_amount,
            'outside_purchase_amount' => $outside_purchase_cost_total,
        ];
    }

    /**
     * Update wallets for outside purchase items when order is edited
     * This handles cash deductions from delivery man and store credits
     *
     * @param Order $order
     * @param OrderTransaction $transaction
     * @param array $newAmounts
     * @return void
     */
    private static function updateOutsidePurchaseWallets($order, $transaction, $newAmounts)
    {
        try {
            $type = $order->order_type;
            if ($type == 'parcel') return;

            // Get old outside purchase amount from transaction
            $oldOutsidePurchaseAmount = $transaction->getOriginal('outside_purchase_amount') ?? 0;
            $newOutsidePurchaseAmount = $newAmounts['outside_purchase_amount'] ?? 0;

            // If no outside purchase changes, skip
            if ($oldOutsidePurchaseAmount == $newOutsidePurchaseAmount && $newOutsidePurchaseAmount == 0) {
                return;
            }

            // Calculate new outside purchase details
            $newOutsidePurchaseDetails = self::calculateOutsidePurchaseDetails($order);

            // Get delivery man wallet if exists
            $dmWallet = null;
            if ($order->delivery_man_id) {
                $dmWallet = \App\Models\DeliveryManWallet::firstOrNew([
                    'delivery_man_id' => $order->delivery_man_id
                ]);
            }

            // Step 1: Reverse old outside purchase deductions (if any)
            if ($oldOutsidePurchaseAmount > 0 && $dmWallet) {
                // Add back the old deduction (reverse it)
                $dmWallet->collected_cash = $dmWallet->collected_cash + $oldOutsidePurchaseAmount;
                Log::info("Reversed old outside purchase deduction from DM wallet", [
                    'order_id' => $order->id,
                    'dm_id' => $order->delivery_man_id,
                    'amount_added_back' => $oldOutsidePurchaseAmount,
                ]);
            }

            // Step 2: Apply new outside purchase deductions
            if ($newOutsidePurchaseAmount > 0) {
                // Deduct random store costs from delivery man (items purchased from unlisted stores)
                $randomCost = $newOutsidePurchaseDetails['random_cost'];
                if ($randomCost > 0 && $dmWallet) {
                    $dmWallet->collected_cash = $dmWallet->collected_cash - $randomCost;
                    Log::info("Applied new outside purchase deduction to DM wallet", [
                        'order_id' => $order->id,
                        'dm_id' => $order->delivery_man_id,
                        'amount_deducted' => $randomCost,
                    ]);
                }

                // Credit listed stores (items purchased from specific stores)
                $storeCredits = $newOutsidePurchaseDetails['store_credits'];
                if (!empty($storeCredits)) {
                    $comission = $order->store->comission ?? \App\Models\BusinessSetting::where('key', 'admin_commission')->first()->value ?? 0;

                    foreach ($storeCredits as $storeId => $costAmount) {
                        $opStore = \App\Models\Store::with('vendor')->find($storeId);
                        if ($opStore && $opStore->vendor) {
                            $opCommission = $comission ? ($costAmount / 100) * $comission : 0;
                            $opStoreEarning = $costAmount - $opCommission;

                            $opStoreWallet = \App\Models\StoreWallet::firstOrNew([
                                'vendor_id' => $opStore->vendor->id
                            ]);
                            $opStoreWallet->total_earning = $opStoreWallet->total_earning + $opStoreEarning;
                            $opStoreWallet->save();

                            Log::info("Credited outside purchase store", [
                                'order_id' => $order->id,
                                'store_id' => $storeId,
                                'cost' => $costAmount,
                                'commission' => $opCommission,
                                'earning' => $opStoreEarning,
                            ]);
                        }
                    }
                }
            }

            // Step 3: Update main store amount (exclude outside purchase earnings)
            $mainStoreWallet = \App\Models\StoreWallet::firstOrNew([
                'vendor_id' => $order->store->vendor->id
            ]);

            // Adjust store's total earning based on new store_amount from transaction
            $oldStoreAmount = $transaction->getOriginal('store_amount') ?? 0;
            $newStoreAmount = $newAmounts['store_amount'] ?? 0;
            $storeAmountDiff = $newStoreAmount - $oldStoreAmount;

            if (abs($storeAmountDiff) > 0.01) {
                $mainStoreWallet->total_earning = $mainStoreWallet->total_earning + $storeAmountDiff;
                Log::info("Adjusted main store wallet for outside purchase changes", [
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'old_amount' => $oldStoreAmount,
                    'new_amount' => $newStoreAmount,
                    'adjustment' => $storeAmountDiff,
                ]);
            }

            // Save wallets
            if ($dmWallet && $dmWallet->exists) {
                $dmWallet->save();
            }
            $mainStoreWallet->save();

        } catch (\Exception $e) {
            Log::error('Outside purchase wallet update failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e->getMessage(),
            ]);
            // Don't throw - allow transaction update to complete
        }
    }

    /**
     * Calculate outside purchase details from order
     *
     * @param Order $order
     * @return array
     */
    private static function calculateOutsidePurchaseDetails($order)
    {
        $customerTotal = 0;
        $costTotal = 0;
        $storeCredits = []; // store_id => cost amount
        $randomCost = 0; // cost from unlisted stores

        foreach ($order->details as $detail) {
            if ($detail->is_outside_purchase) {
                $customerTotal += $detail->price * $detail->quantity;
                $detailCost = $detail->outside_purchase_cost * $detail->quantity;
                $costTotal += $detailCost;

                // Track credits for listed stores, otherwise count as random
                if ($detail->outside_purchase_store_id) {
                    if (!isset($storeCredits[$detail->outside_purchase_store_id])) {
                        $storeCredits[$detail->outside_purchase_store_id] = 0;
                    }
                    $storeCredits[$detail->outside_purchase_store_id] += $detailCost;
                } else {
                    $randomCost += $detailCost;
                }
            }
        }

        return [
            'customer_total' => $customerTotal,
            'cost_total' => $costTotal,
            'store_credits' => $storeCredits,
            'random_cost' => $randomCost,
        ];
    }
}
