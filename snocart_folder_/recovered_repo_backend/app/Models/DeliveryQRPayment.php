<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryQRPayment extends Model
{
    use HasFactory;

    protected $table = 'delivery_qr_payments';

    protected $fillable = [
        'order_id',
        'delivery_man_id',
        'razorpay_qr_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'amount',
        'currency',
        'status',
        'qr_code_url',
        'qr_code_data',
        'payment_link',
        'metadata',
        'paid_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order that owns this QR payment
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the delivery man who will receive the payment
     */
    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    /**
     * Check if QR code is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return now()->isAfter($this->expires_at);
    }

    /**
     * Check if payment is completed
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(string $paymentId): void
    {
        $this->update([
            'status' => 'paid',
            'razorpay_payment_id' => $paymentId,
            'paid_at' => now(),
        ]);
    }

    /**
     * Scope for active QR codes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'active']);
    }
}
