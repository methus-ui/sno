# Razorpay QR Payment System - Setup Guide

## 📋 Overview

This guide will help you set up the Razorpay Dynamic QR Code payment system for delivery confirmation.

---

## ✅ Installation Checklist

### 1. Database Setup ✅ COMPLETED

The migration has been run successfully:
```bash
✅ delivery_qr_payments table created
✅ Indexes added for performance
```

**Table structure:**
- `id` - Primary key
- `order_id` - Foreign key to orders
- `delivery_man_id` - Foreign key to delivery_men
- `razorpay_qr_id` - Razorpay QR code ID
- `razorpay_order_id` - Razorpay order ID
- `razorpay_payment_id` - Payment ID (after payment)
- `amount`, `currency` - Payment amount
- `status` - Payment status (pending/active/paid/expired/cancelled)
- `qr_code_url` - URL to QR image
- `qr_code_data` - UPI payment string
- `payment_link` - UPI deep link
- `metadata` - JSON metadata
- Timestamps and expiry

### 2. Routes Setup ✅ COMPLETED

Routes have been registered in `RouteServiceProvider.php`

**Available endpoints:**
- `POST /api/v1/delivery/qr-payment/generate` - Generate QR code
- `GET /api/v1/delivery/qr-payment/status/{id}` - Check payment status
- `GET /api/v1/delivery/qr-payment/{id}` - Get QR details
- `POST /api/v1/delivery/qr-payment/cancel/{id}` - Cancel QR
- `GET /api/v1/delivery/qr-payment/my-qr-payments` - List QR payments
- `POST /webhooks/razorpay-qr` - Webhook endpoint

### 3. Code Files Created

**Models:**
- ✅ `app/Models/DeliveryQRPayment.php`

**Services:**
- ✅ `app/Services/RazorpayQRService.php`

**Controllers:**
- ✅ `app/Http/Controllers/Api/V1/DeliveryQRPaymentController.php`
- ✅ `app/Http/Controllers/Api/V1/RazorpayQRWebhookController.php`

**Routes:**
- ✅ `routes/delivery_qr_api.php`

**Documentation:**
- ✅ `DELIVERY_QR_PAYMENT_API_DOCUMENTATION.md`
- ✅ `RAZORPAY_QR_SETUP_GUIDE.md` (this file)

---

## 🔧 Configuration Steps

### Step 1: Razorpay API Keys

1. **Get your Razorpay credentials:**
   - Go to https://dashboard.razorpay.com/
   - Navigate to Settings → API Keys
   - Generate API keys (Test & Live)

2. **Update in database:**
   Your Razorpay credentials are stored in the `business_settings` table.

   ```sql
   -- Check current configuration
   SELECT * FROM business_settings
   WHERE key_name = 'razor_pay'
   AND settings_type = 'payment_config';
   ```

   Make sure the JSON in `live_values` or `test_values` contains:
   ```json
   {
     "api_key": "rzp_live_xxxxx",
     "api_secret": "your_secret_key",
     "webhook_secret": "your_webhook_secret"
   }
   ```

### Step 2: Enable Razorpay QR Codes

1. Go to Razorpay Dashboard → Payment Methods
2. Enable "Bharat QR" or "UPI QR"
3. QR codes should now be available via API

### Step 3: Configure Webhooks

1. Go to Razorpay Dashboard → Settings → Webhooks
2. Click "Create New Webhook"
3. Enter URL: `https://new.snocart.com/webhooks/razorpay-qr`
4. Select events:
   - ✅ `qr_code.credited`
   - ✅ `qr_code.closed`
   - ✅ `payment.captured` (optional backup)
5. Click "Create Webhook"
6. Copy the "Webhook Secret"
7. Add to your `.env` file:

   ```env
   RAZORPAY_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
   ```

   Or update in database:
   ```sql
   UPDATE business_settings
   SET live_values = JSON_SET(live_values, '$.webhook_secret', 'whsec_xxxxxxxxxxxxx')
   WHERE key_name = 'razor_pay'
   AND settings_type = 'payment_config';
   ```

### Step 4: Clear Cache

```bash
cd /var/www/html/new_public/new
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Step 5: Verify Installation

Run this command to check if routes are registered:

```bash
php artisan route:list | grep "qr-payment"
```

Expected output:
```
POST   | api/v1/delivery/qr-payment/generate         | delivery.qr.generate
GET    | api/v1/delivery/qr-payment/status/{id}      | delivery.qr.status
GET    | api/v1/delivery/qr-payment/{id}             | delivery.qr.details
POST   | api/v1/delivery/qr-payment/cancel/{id}      | delivery.qr.cancel
GET    | api/v1/delivery/qr-payment/my-qr-payments   | delivery.qr.my-payments
POST   | webhooks/razorpay-qr                        | webhooks.razorpay-qr
```

---

## 🧪 Testing

### Test in Development

1. **Switch to Razorpay Test Mode:**
   ```sql
   UPDATE business_settings
   SET mode = 'test'
   WHERE key_name = 'razor_pay';
   ```

2. **Test API with Postman/cURL:**

   ```bash
   # Generate QR Code
   curl -X POST https://new.snocart.com/api/v1/delivery/qr-payment/generate \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
       "order_id": 108315,
       "amount": 100.00,
       "delivery_man_id": 244
     }'
   ```

   Expected response:
   ```json
   {
     "success": true,
     "message": "QR code generated successfully",
     "data": {
       "id": 1,
       "qr_code_url": "https://...",
       "qr_code_data": "upi://pay?...",
       "amount": 100.00,
       "status": "active"
     }
   }
   ```

3. **Check Status:**
   ```bash
   curl -X GET https://new.snocart.com/api/v1/delivery/qr-payment/status/1 \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

4. **Test Payment:**
   - Use Razorpay test dashboard to simulate payment
   - Or use test UPI ID: `success@razorpay`

5. **Check Webhook:**
   ```bash
   tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "Razorpay QR"
   ```

---

## 🚀 Go Live Checklist

### Before Production:

- [ ] **Switch to Live Mode**
  ```sql
  UPDATE business_settings
  SET mode = 'live'
  WHERE key_name = 'razor_pay';
  ```

- [ ] **Verify Live API Keys**
  ```sql
  SELECT JSON_EXTRACT(live_values, '$.api_key') as api_key
  FROM business_settings
  WHERE key_name = 'razor_pay';
  ```

- [ ] **Test Webhook in Production**
  - Make a small test payment
  - Check webhook logs
  - Verify payment is recorded

- [ ] **Update Flutter App**
  - Deploy Flutter app with QR payment integration
  - Test on real devices
  - Test end-to-end flow

- [ ] **Monitor First Payments**
  - Watch logs: `tail -f storage/logs/laravel-$(date +%Y-%m-%d).log`
  - Check Razorpay dashboard
  - Verify database records

---

## 📊 Monitoring & Maintenance

### Check Payment Records

```sql
-- Recent QR payments
SELECT * FROM delivery_qr_payments
ORDER BY created_at DESC
LIMIT 10;

-- Successful payments today
SELECT COUNT(*) as paid_today, SUM(amount) as total_amount
FROM delivery_qr_payments
WHERE status = 'paid'
AND DATE(paid_at) = CURDATE();

-- Delivery man earnings
SELECT
  dm.f_name, dm.l_name,
  COUNT(*) as qr_payments,
  SUM(amount) as total_collected
FROM delivery_qr_payments dqp
JOIN delivery_men dm ON dqp.delivery_man_id = dm.id
WHERE dqp.status = 'paid'
GROUP BY dqp.delivery_man_id
ORDER BY total_collected DESC;
```

### Check Webhook Logs

```bash
# Today's webhook events
grep "Razorpay QR webhook" storage/logs/laravel-$(date +%Y-%m-%d).log

# Payment confirmations
grep "QR payment marked as paid" storage/logs/laravel-$(date +%Y-%m-%d).log
```

### Performance Monitoring

```bash
# Count QR payments by status
mysql -u snocart_app_user -p'Sno@$Ck42' snocart -e "
SELECT status, COUNT(*) as count
FROM delivery_qr_payments
GROUP BY status
"
```

---

## 🔒 Security Best Practices

1. **Never expose API secrets in client apps**
   - Backend handles all Razorpay API calls
   - Flutter app only receives QR code data

2. **Verify webhook signatures**
   - Webhook controller validates Razorpay signatures
   - Prevents fake payment notifications

3. **Use HTTPS only**
   - Ensure SSL certificate is valid
   - Webhook URL must be HTTPS

4. **Rate limiting**
   - API routes have rate limiting enabled
   - 600 requests per minute per user

5. **Authentication**
   - All endpoints require Bearer token (except webhook)
   - Delivery man can only access their own payments

---

## ❓ Troubleshooting

### Issue: QR code generation fails

**Check:**
1. Razorpay API keys are correct
2. Razorpay account is activated
3. QR code feature is enabled in Razorpay dashboard

**Debug:**
```bash
# Check logs
tail -100 storage/logs/laravel-$(date +%Y-%m-%d).log | grep "QR"
```

### Issue: Webhook not working

**Check:**
1. Webhook URL is accessible (not localhost)
2. Webhook secret is configured
3. Selected correct events in Razorpay dashboard

**Test webhook manually:**
```bash
# Razorpay Dashboard → Webhooks → Test Webhook
```

### Issue: Payment not detected

**Check:**
1. Webhook is configured correctly
2. Status polling is working in Flutter app
3. Check Razorpay dashboard for payment

**Manual status check:**
```bash
curl -X GET https://new.snocart.com/api/v1/delivery/qr-payment/status/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📞 Support

### Razorpay Support
- Documentation: https://razorpay.com/docs/payments/qr-codes/
- Support: support@razorpay.com
- Dashboard: https://dashboard.razorpay.com/

### Internal Support
- Backend Team: backend@snocart.com
- API Documentation: `DELIVERY_QR_PAYMENT_API_DOCUMENTATION.md`

---

## 📝 Additional Notes

### QR Code Expiry
- Default expiry: 30 minutes
- Can be changed in `RazorpayQRService.php` (line ~149)

### Amount Limits
- Minimum: ₹1
- Maximum: As per Razorpay limits (usually ₹1,00,000 per transaction)

### Supported Payment Methods
- All UPI apps (Google Pay, PhonePe, Paytm, BHIM, etc.)
- Any app that can scan UPI QR codes

---

## ✅ Post-Installation Verification

Run these checks after setup:

```bash
# 1. Check database table
mysql -u snocart_app_user -p'Sno@$Ck42' snocart -e "SHOW TABLES LIKE 'delivery_qr_payments'"

# 2. Check routes
php artisan route:list | grep qr-payment

# 3. Check model
php artisan tinker --execute="dd(App\Models\DeliveryQRPayment::class)"

# 4. Test service (in tinker)
# php artisan tinker
# >>> $service = app(App\Services\RazorpayQRService::class);
# >>> dump($service);
```

All checks should pass without errors.

---

**Setup completed successfully! 🎉**

You can now start using the Razorpay QR payment system in your Flutter app.

Refer to `DELIVERY_QR_PAYMENT_API_DOCUMENTATION.md` for API details and Flutter integration examples.
