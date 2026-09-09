# Security Deposit System - Quick Start Guide

## 🚀 Quick Deployment (5 Steps)

### Step 1: Run Migrations
```bash
# Backup database first!
php artisan migrate

# Seed default settings
php artisan db:seed --class=SecurityDepositSettingsSeeder
```

### Step 2: Configure Admin Panel
1. Navigate to: **Delivery Man → Security Deposit → Settings**
2. Toggle "Enable Security Deposit" to ON
3. Set amount (e.g., 500)
4. Click "Save Settings"

### Step 3: Verify Razorpay Configuration
Check that `addon_settings` table has Razorpay credentials:
```sql
SELECT * FROM addon_settings WHERE key_name = 'razor_pay';
```

### Step 4: Test API Endpoints
```bash
# Test profile endpoint
curl -X GET "https://new.snocart.com/api/v1/delivery-man/profile" \
  -H "Authorization: Bearer {token}"

# Should return:
{
  "security_deposit_enabled": true,
  "security_deposit_amount": 500,
  "security_deposit_status": "unpaid"
}
```

### Step 5: Update Mobile App
Add security deposit check in app startup:
```dart
if (profile.securityDepositEnabled &&
    profile.securityDepositStatus == 'unpaid' &&
    profile.applicationStatus == 'pending') {
  // Show payment screen
  Navigator.push(context, SecurityDepositPaymentScreen());
}
```

---

## 📱 Mobile App Integration (Flutter Example)

### 1. Add to Profile Model
```dart
class DeliveryManProfile {
  bool securityDepositEnabled;
  double securityDepositAmount;
  String securityDepositStatus; // 'unpaid', 'paid', 'refunded'

  factory DeliveryManProfile.fromJson(Map<String, dynamic> json) {
    return DeliveryManProfile(
      securityDepositEnabled: json['security_deposit_enabled'] ?? false,
      securityDepositAmount: double.parse(json['security_deposit_amount'].toString()),
      securityDepositStatus: json['security_deposit_status'] ?? 'unpaid',
    );
  }
}
```

### 2. Payment Initiation
```dart
Future<Map<String, dynamic>> initiateSecurityDeposit() async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/v1/delivery-man/security-deposit/pay'),
    headers: {'Authorization': 'Bearer $token'},
    body: {'payment_method': 'razorpay'},
  );
  return json.decode(response.body);
}
```

### 3. Razorpay Checkout
```dart
void openRazorpayCheckout(Map<String, dynamic> paymentData) {
  var options = {
    'key': paymentData['razorpay_key'],
    'amount': paymentData['amount'] * 100, // Convert to paise
    'order_id': paymentData['razorpay_order_id'],
    'name': 'Security Deposit',
    'description': 'Delivery Man Security Deposit',
    'prefill': {
      'contact': userPhone,
      'email': userEmail,
    },
  };

  _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _handlePaymentSuccess);
  _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _handlePaymentError);
  _razorpay.open(options);
}

void _handlePaymentSuccess(PaymentSuccessResponse response) {
  verifyPayment(
    paymentId: savedPaymentId,
    razorpayPaymentId: response.paymentId,
    razorpayOrderId: response.orderId,
    razorpaySignature: response.signature,
  );
}
```

### 4. Payment Verification
```dart
Future<void> verifyPayment({
  required int paymentId,
  required String razorpayPaymentId,
  required String razorpayOrderId,
  required String razorpaySignature,
}) async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/v1/delivery-man/security-deposit/verify'),
    headers: {'Authorization': 'Bearer $token'},
    body: {
      'payment_id': paymentId.toString(),
      'razorpay_payment_id': razorpayPaymentId,
      'razorpay_order_id': razorpayOrderId,
      'razorpay_signature': razorpaySignature,
    },
  );

  if (response.statusCode == 200) {
    // Payment successful - refresh profile and navigate
    await fetchProfile();
    Navigator.pushReplacement(context, AccountUnderReviewScreen());
  }
}
```

### 5. Status Check
```dart
Future<Map<String, dynamic>> checkDepositStatus() async {
  final response = await http.get(
    Uri.parse('$baseUrl/api/v1/delivery-man/security-deposit/status'),
    headers: {'Authorization': 'Bearer $token'},
  );
  return json.decode(response.body);
}
```

---

## 🎨 Admin Panel Views Created

### 1. Settings Page
**Location:** `resources/views/admin/security-deposit/settings.blade.php`
**Features:**
- Enable/disable toggle
- Amount configuration
- Quick stats display
- Information about how it works

**Access:** Admin Panel → Delivery Man → Security Deposit → Settings

### 2. List Page
**Location:** `resources/views/admin/security-deposit/index.blade.php`
**Features:**
- Filter tabs (All/Unpaid/Paid/Refunded)
- Search functionality
- Stats cards
- Delivery men list with status
- Refund button for paid deposits
- Export button

**Access:** Admin Panel → Delivery Man → Security Deposit

### 3. Additional Views Needed
You still need to create:
- `show.blade.php` - Individual delivery man deposit details
- `payment-details.blade.php` - Payment transaction details

---

## 🔧 API Endpoints Summary

### Delivery Man App

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/delivery-man/profile` | ✅ | Profile with deposit fields |
| POST | `/api/v1/delivery-man/security-deposit/pay` | ✅ | Initiate payment |
| GET | `/api/v1/delivery-man/security-deposit/status` | ✅ | Check status |
| POST | `/api/v1/delivery-man/security-deposit/verify` | ✅ | Verify payment |
| GET/POST | `/api/v1/delivery-man/security-deposit/callback` | ❌ | Gateway callback |

### Admin Panel

| Method | Route Name | Description |
|--------|-----------|-------------|
| GET | `admin.deliveryman.security-deposit.settings` | Settings page |
| POST | `admin.deliveryman.security-deposit.update-settings` | Save settings |
| GET | `admin.deliveryman.security-deposit.index` | List all deposits |
| GET | `admin.deliveryman.security-deposit.show` | View details |
| POST | `admin.deliveryman.security-deposit.refund` | Refund deposit |
| GET | `admin.deliveryman.security-deposit.export` | Export CSV |

---

## 🧪 Testing Checklist

### Backend Tests
- [ ] Migrations run successfully
- [ ] Settings are saved correctly
- [ ] Profile endpoint returns deposit fields
- [ ] Payment initiation creates Razorpay order
- [ ] Payment verification works
- [ ] Status endpoint returns correct data
- [ ] Refund works and calls Razorpay API

### Frontend Tests
- [ ] Admin settings page loads
- [ ] Toggle and amount input work
- [ ] List page displays delivery men
- [ ] Filters work correctly
- [ ] Search functionality works
- [ ] Refund modal opens and submits
- [ ] Export downloads CSV

### Integration Tests
- [ ] Full payment flow from app to backend
- [ ] Razorpay callback is received
- [ ] Status updates in database
- [ ] Profile reflects paid status
- [ ] Admin can see paid status
- [ ] Refund updates both systems

---

## 🐛 Troubleshooting

### Issue: "Razorpay API not initialized"
**Solution:**
1. Check `addon_settings` table for `razor_pay` entry
2. Verify `api_key` and `api_secret` are set
3. Check logs: `storage/logs/laravel.log`

### Issue: "Payment verification fails"
**Solution:**
1. Verify signature algorithm is correct
2. Check that order_id matches
3. Ensure API secret is correct
4. Check Razorpay dashboard for payment status

### Issue: "Admin routes not found"
**Solution:**
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### Issue: "Migration already ran"
**Solution:**
Check `migrations` table - if entries exist but columns missing:
```bash
# Manual rollback of specific migration
php artisan migrate:rollback --step=1
# Then re-run
php artisan migrate
```

---

## 📊 Database Queries for Manual Checks

### Check deposit settings
```sql
SELECT * FROM business_settings
WHERE key IN ('security_deposit_enabled', 'security_deposit_amount');
```

### Count deposits by status
```sql
SELECT security_deposit_status, COUNT(*)
FROM delivery_men
GROUP BY security_deposit_status;
```

### View recent payments
```sql
SELECT * FROM security_deposit_payments
ORDER BY created_at DESC
LIMIT 10;
```

### Find unpaid deposits
```sql
SELECT id, f_name, l_name, phone, email, security_deposit_status
FROM delivery_men
WHERE security_deposit_status = 'unpaid';
```

---

## 💡 Pro Tips

1. **Test in Sandbox First:** Use Razorpay test mode before going live
2. **Monitor Logs:** Keep an eye on `storage/logs/laravel.log` for payment issues
3. **Email Notifications:** Consider adding email alerts for successful payments
4. **Webhook Setup:** Implement Razorpay webhook for automatic status updates
5. **Backup First:** Always backup database before running migrations

---

## 📞 Support

For issues:
1. Check logs: `storage/logs/laravel.log`
2. Verify Razorpay dashboard for payment status
3. Test API endpoints with Postman
4. Check database tables manually

---

## ✅ Deployment Checklist

Before going live:
- [ ] Database backed up
- [ ] Migrations run successfully
- [ ] Razorpay credentials verified (LIVE mode)
- [ ] Admin can access settings page
- [ ] Admin can see delivery men list
- [ ] Test payment flow works end-to-end
- [ ] Refund functionality tested
- [ ] Mobile app updated with payment flow
- [ ] Error handling tested
- [ ] Logs are being written correctly

---

**Version:** 1.0
**Last Updated:** 2026-02-11
**Status:** ✅ Ready for Testing
