# QR Payment Diagnostic Report
**Generated:** 2026-02-08 19:31 UTC

## Summary
**Issue:** QR payment received but not showing in delivery app

## Investigation Results

### Database Status
Total QR Payments: **6**

| ID | Order | Delivery Man | Amount | Status | Payment ID | Created |
|----|-------|--------------|--------|--------|------------|---------|
| 6 | 108347 | 11 | ₹303.00 | ✅ **PAID** | pay_SDfl4ROfJlcK6Z | 2026-02-08 19:14 |
| 5 | 108326 | 11 | ₹456.00 | ⚠️ ACTIVE | - | 2026-02-08 16:51 |
| 4 | 108313 | 33 | ₹588.00 | ⚠️ ACTIVE | - | 2026-02-08 14:43 |
| 3 | 108314 | 33 | ₹661.00 | ⚠️ ACTIVE | - | 2026-02-08 14:42 |
| 2 | 108315 | 33 | ₹325.00 | ⚠️ ACTIVE | - | 2026-02-08 14:42 |
| 1 | 108301 | 33 | ₹352.00 | ⚠️ ACTIVE | - | 2026-02-08 14:38 |

### Razorpay Status Check
All "ACTIVE" payments were checked against Razorpay:

- **Payment #1-5:** All showing as **CLOSED** with **NO payment received**
- **Payment #6:** Correctly marked as PAID

**Conclusion:** Payments #1-5 expired (30 min timeout) without receiving payment.

## Root Cause Analysis

### Why Payments Not Showing as Paid

The system relies on **TWO methods** to detect payments:

1. **Webhooks (Primary):** Razorpay sends `qr_code.credited` event when payment is made
2. **Polling (Fallback):** App calls status check API periodically

### Issues Found:

1. ✅ **Webhooks ARE working** - Payment #6 was updated via webhook
2. ⚠️ **QR codes expired** - Payments #1-5 expired after 30 minutes
3. ⚠️ **No polling implemented** - Delivery app not actively checking status

### Webhook Evidence
```
[2026-02-08 19:15:06] qr_code.credited event received
- QR ID: qr_SDfkoFNz9Zg3dR
- Payment ID: pay_SDfl4ROfJlcK6Z
- Amount: ₹303.00
- Status: Successfully updated ✓
```

## Solutions

### Solution 1: Manual Payment Check (If payment was made before expiry)

If you know a specific QR payment received money but wasn't updated, run:

```bash
# Check specific QR payment
php artisan qr:check-payment-status <ID>

# Example: Check payment #5
php artisan qr:check-payment-status 5

# Check all pending payments
php artisan qr:check-payment-status
```

### Solution 2: Implement Polling in Delivery App

The delivery app should poll the status endpoint every 5-10 seconds:

**API Endpoint:**
```
GET /api/v1/delivery-man/qr-payment/status/{qr_payment_id}
```

**Response:**
```json
{
  "success": true,
  "message": "Payment completed",
  "data": {
    "status": "paid",
    "is_paid": true,
    "paid_at": "2026-02-08T19:15:06.000000Z",
    "payment_id": "pay_SDfl4ROfJlcK6Z"
  }
}
```

### Solution 3: Verify Webhook Configuration

Ensure Razorpay Dashboard has webhook configured:

1. Login to Razorpay Dashboard
2. Go to **Settings** → **Webhooks**
3. Add webhook URL: `https://yourdomain.com/webhooks/razorpay-qr`
4. Enable events:
   - ✅ `qr_code.credited`
   - ✅ `payment.captured`
   - ✅ `qr_code.closed`

### Solution 4: Increase QR Expiry Time

Current: **30 minutes**
Suggested: **60-120 minutes**

**File:** `app/Services/RazorpayQRService.php:171`
```php
// Change from 30 to 60 minutes
'expires_at' => now()->addMinutes(60),
```

### Solution 5: Create Scheduled Task for Auto-Checking

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Check pending QR payments every 2 minutes
    $schedule->command('qr:check-payment-status')
        ->everyTwoMinutes()
        ->withoutOverlapping();
}
```

## Troubleshooting Guide

### If Payment is Not Showing:

1. **Check QR Payment Status**
   ```bash
   php artisan qr:check-payment-status <id>
   ```

2. **Check Laravel Logs**
   ```bash
   tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i "qr\|webhook"
   ```

3. **Verify Webhook Logs**
   ```bash
   grep "qr_code.credited\|payment.captured" storage/logs/laravel-*.log
   ```

4. **Check Database Directly**
   ```bash
   mysql -u snocart_app_user -p'Sno@$Ck42' -D snocart -e \
     "SELECT id, order_id, status, amount, paid_at FROM delivery_qr_payments WHERE order_id = <ORDER_ID>;"
   ```

### If Webhook Not Working:

1. **Test Webhook Endpoint**
   ```bash
   curl -X POST https://yourdomain.com/webhooks/razorpay-qr \
     -H "Content-Type: application/json" \
     -d '{"event":"qr_code.credited"}'
   ```

2. **Check Razorpay Webhook Logs**
   - Razorpay Dashboard → Settings → Webhooks → View Logs

3. **Verify Webhook Secret** (if configured)
   - Check `config/razor_config.php` or environment variables

## Delivery App Integration

### Recommended Flow:

```
1. Delivery man generates QR code
   ↓
2. Show QR code to customer
   ↓
3. START POLLING (every 5 seconds):
   - Call: GET /api/v1/delivery-man/qr-payment/status/{id}
   - Check: data.is_paid
   ↓
4. If is_paid = true:
   - Show success message
   - Update order status
   - Stop polling
   ↓
5. If QR expires (is_expired = true):
   - Show "QR expired" message
   - Offer to generate new QR
```

## Quick Commands

```bash
# List all QR payments
mysql -u snocart_app_user -p'Sno@$Ck42' -D snocart -e \
  "SELECT * FROM delivery_qr_payments ORDER BY created_at DESC LIMIT 10;"

# Check specific order's QR payment
mysql -u snocart_app_user -p'Sno@$Ck42' -D snocart -e \
  "SELECT * FROM delivery_qr_payments WHERE order_id = 108326;"

# View webhook logs from today
grep "qr_code.credited\|webhook" storage/logs/laravel-$(date +%Y-%m-%d).log

# Test QR payment status check
php artisan qr:check-payment-status 6

# View all routes
php artisan route:list | grep qr
```

## Support Contact

For further assistance:
1. Check Laravel logs: `storage/logs/laravel-*.log`
2. Review Razorpay dashboard for payment details
3. Verify delivery app is calling the correct API endpoints

---

**Next Steps:**
1. Identify which specific order/payment is missing
2. Run `php artisan qr:check-payment-status <id>` for that payment
3. Check Razorpay dashboard to verify if payment was actually received
4. Implement polling in delivery app as a fallback mechanism
