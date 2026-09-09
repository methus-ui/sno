# Quick Start - Razorpay QR Payment System

## ✅ Installation Complete!

All components have been successfully installed and configured.

---

## 🚀 Quick Start (5 Minutes)

### Step 1: Test the API (Backend)

```bash
# Test QR generation (replace YOUR_TOKEN with actual delivery man token)
curl -X POST https://new.snocart.com/api/v1/delivery/qr-payment/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 108315,
    "amount": 100.00
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "QR code generated successfully",
  "data": {
    "id": 1,
    "qr_code_url": "https://api.razorpay.com/.../qr.png",
    "qr_code_data": "upi://pay?...",
    "amount": 100.00,
    "status": "active"
  }
}
```

### Step 2: Check Payment Status

```bash
curl -X GET https://new.snocart.com/api/v1/delivery/qr-payment/status/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Step 3: Integrate in Flutter App

Copy the Flutter example from `DELIVERY_QR_PAYMENT_API_DOCUMENTATION.md` section "Flutter Integration"

---

## 📱 Flutter Integration (Copy & Paste)

### 1. Add to `pubspec.yaml`

```yaml
dependencies:
  http: ^1.2.0
  qr_flutter: ^4.1.0
```

### 2. Create Service File

Create `lib/services/delivery_qr_payment_service.dart` and copy the service code from the documentation.

### 3. Use in Delivery Screen

```dart
// When delivery boy clicks "Confirm Delivery"
Navigator.push(
  context,
  MaterialPageRoute(
    builder: (context) => DeliveryQRPaymentScreen(
      orderId: order.id,
      amount: order.orderAmount,
    ),
  ),
);
```

---

## 🔧 Configure Razorpay Webhook

1. Go to https://dashboard.razorpay.com/app/webhooks
2. Create webhook with URL: `https://new.snocart.com/webhooks/razorpay-qr`
3. Select events: `qr_code.credited`, `qr_code.closed`, `payment.captured`
4. Save webhook secret to database (see Setup Guide)

---

## 📊 Test Payment Flow

1. **Backend generates QR** → Flutter app calls `/generate` API
2. **QR displayed to customer** → Flutter shows QR using `qr_flutter`
3. **Customer pays** → Scans QR with Google Pay/PhonePe/etc.
4. **Payment detected** → Webhook notifies backend OR app polls `/status`
5. **Delivery confirmed** → App shows success message

---

## 📁 Files Created

| File | Purpose |
|------|---------|
| `app/Models/DeliveryQRPayment.php` | Database model |
| `app/Services/RazorpayQRService.php` | QR code generation logic |
| `app/Http/Controllers/Api/V1/DeliveryQRPaymentController.php` | API endpoints |
| `app/Http/Controllers/Api/V1/RazorpayQRWebhookController.php` | Webhook handler |
| `routes/delivery_qr_api.php` | API routes |
| `database/migrations/2026_02_08_000002_create_delivery_qr_payments_table.php` | Database table |

---

## 🎯 API Endpoints Summary

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/v1/delivery/qr-payment/generate` | POST | Generate QR code |
| `/api/v1/delivery/qr-payment/status/{id}` | GET | Check if paid |
| `/api/v1/delivery/qr-payment/{id}` | GET | Get details |
| `/api/v1/delivery/qr-payment/cancel/{id}` | POST | Cancel QR |
| `/api/v1/delivery/qr-payment/my-qr-payments` | GET | List payments |
| `/webhooks/razorpay-qr` | POST | Webhook |

---

## 🔍 Verify Installation

```bash
# 1. Check database
mysql -u snocart_app_user -p'Sno@$Ck42' snocart \
  -e "DESCRIBE delivery_qr_payments"

# 2. Check routes
php artisan route:list | grep qr-payment

# 3. Test API
curl -X POST https://new.snocart.com/api/v1/delivery/qr-payment/generate \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"order_id": 1, "amount": 100}'
```

---

## 📖 Full Documentation

- **API Documentation:** `DELIVERY_QR_PAYMENT_API_DOCUMENTATION.md`
- **Setup Guide:** `RAZORPAY_QR_SETUP_GUIDE.md`
- **This Quick Start:** `QUICK_START_QR_PAYMENT.md`

---

## 🆘 Need Help?

1. **API not working?** → Check `storage/logs/laravel-YYYY-MM-DD.log`
2. **Routes not found?** → Run `php artisan route:clear`
3. **Webhook not triggering?** → Check Razorpay dashboard webhook logs
4. **Payment not detected?** → Check database: `SELECT * FROM delivery_qr_payments`

---

## ✨ What's Next?

1. ✅ Test in staging environment
2. ✅ Integrate Flutter app
3. ✅ Configure Razorpay webhook
4. ✅ Test end-to-end flow
5. ✅ Deploy to production
6. ✅ Monitor first real payments

---

**Happy Coding! 🚀**

If you need help, refer to the full documentation files.
