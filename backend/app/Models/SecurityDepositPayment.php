<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityDepositPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_man_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'transaction_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'payment_url',
        'redirect_url',
        'callback_url',
        'metadata',
        'paid_at',
        'refunded_at',
        'refund_reason',
        'refunded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the delivery man that owns the security deposit payment.
     */
    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    /**
     * Get the admin who refunded the deposit.
     */
    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'refunded_by');
    }

    /**
     * Scope a query to only include successful payments.
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include refunded payments.
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Mark payment as successful.
     */
    public function markAsSuccess($transactionId = null, $razorpayPaymentId = null)
    {
        $this->status = 'success';
        $this->paid_at = now();
        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }
        if ($razorpayPaymentId) {
            $this->razorpay_payment_id = $razorpayPaymentId;
        }
        $this->save();

        // Update delivery man status
        $this->deliveryMan->update([
            'security_deposit_status' => 'paid',
            'security_deposit_paid_at' => now(),
            'security_deposit_transaction_id' => $this->transaction_id,
            'security_deposit_payment_method' => $this->payment_method,
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed()
    {
        $this->status = 'failed';
        $this->save();
    }

    /**
     * Mark payment as refunded.
     */
    public function markAsRefunded($reason = null, $refundedBy = null)
    {
        $this->status = 'refunded';
        $this->refunded_at = now();
        $this->refund_reason = $reason;
        $this->refunded_by = $refundedBy;
        $this->save();

        // Update delivery man status
        $this->deliveryMan->update([
            'security_deposit_status' => 'refunded',
        ]);
    }
}
