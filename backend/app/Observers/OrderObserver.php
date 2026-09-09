<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderReference;
use App\Models\User;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $OrderReference = new OrderReference();
        $OrderReference->order_id = $order->id;
        $OrderReference->save();
    }

    /**
     * Handle the Order "updated" event.
     *
     * Tracks cancellations and refunds for Snoscore calculation.
     */
    public function updated(Order $order): void
    {
        // Only process if order_status changed
        if (!$order->isDirty('order_status')) {
            return;
        }

        // Skip guest orders
        if ($order->is_guest) {
            return;
        }

        $user = $order->customer;
        if (!$user) {
            return;
        }

        $newStatus = $order->order_status;
        $oldStatus = $order->getOriginal('order_status');

        // Handle cancellation
        if ($newStatus === 'canceled' && $oldStatus !== 'canceled') {
            $this->handleCancellation($order, $user);
        }

        // Handle refund (tracked separately, no score impact)
        if ($newStatus === 'refunded' && $oldStatus !== 'refunded') {
            $this->handleRefund($user);
        }
    }

    /**
     * Handle order cancellation - update user's cancellation counts and recalculate snoscore
     */
    private function handleCancellation(Order $order, User $user): void
    {
        // Determine who cancelled based on 'canceled_by' field
        $canceledBy = strtolower($order->canceled_by ?? '');
        $customerInitiated = $this->wasCustomerInitiated($canceledBy);

        if ($customerInitiated) {
            $user->increment('customer_canceled_count');
        } else {
            $user->increment('other_canceled_count');
        }

        // Recalculate snoscore
        $user->recalculateSnoscore();
    }

    /**
     * Determine if cancellation was customer-initiated
     *
     * @param string $canceledBy Value from order's canceled_by field
     * @return bool True if customer initiated, false if store/admin/deliveryman
     */
    private function wasCustomerInitiated(string $canceledBy): bool
    {
        // Customer-initiated cancellation identifiers
        $customerTypes = ['customer', 'user', 'client'];

        // Check if canceled_by matches customer types
        foreach ($customerTypes as $type) {
            if (str_contains($canceledBy, $type)) {
                return true;
            }
        }

        // If canceled_by is empty or unknown, default to customer-initiated
        // This is a conservative approach - unknown cancellations count against the customer
        // You can change this to 'false' if you want to be lenient
        if (empty($canceledBy)) {
            return true;
        }

        return false;
    }

    /**
     * Handle refund - track separately without affecting snoscore
     */
    private function handleRefund(User $user): void
    {
        $user->increment('refund_count');
        // Note: Refunds don't affect snoscore as per requirements
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
