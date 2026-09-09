# Security Deposit System Implementation

## Overview
Complete implementation of the security deposit system for delivery men with Razorpay integration.

---

## ✅ Completed Backend Implementation

### 1. Database Migrations

#### a) **Delivery Men Table** (`2026_02_12_003003_add_security_deposit_fields_to_delivery_men_table.php`)
Added fields:
- `security_deposit_amount` (decimal)
- `security_deposit_status` (enum: unpaid/paid/refunded)
- `security_deposit_paid_at` (timestamp)
- `security_deposit_transaction_id` (string)
- `security_deposit_payment_method` (string)

#### b) **Security Deposit Payments Table** (`2026_02_12_003024_create_security_deposit_payments_table.php`)
Complete tracking table with:
- Delivery man relationship
- Payment details (amount, currency, status)
- Razorpay integration fields
- Refund tracking
- Metadata storage

### 2. Models

#### **SecurityDepositPayment Model** (`app/Models/SecurityDepositPayment.php`)
Features:
- Full relationship with DeliveryMan and Admin
- Status scopes (success, pending, refunded)
- Helper methods: `markAsSuccess()`, `markAsFailed()`, `markAsRefunded()`
- Automatic delivery man status updates on payment success

#### **DeliveryMan Model** (Updated)
Added relationships:
- `securityDepositPayments()` - All payments
- `latestSecurityDepositPayment()` - Most recent payment

### 3. Service Layer

#### **SecurityDepositService** (`app/Services/SecurityDepositService.php`)
Complete payment processing service:
- Razorpay API integration
- Payment initiation
- Payment verification with signature validation
- Status tracking
- Refund processing
- Settings management

Key methods:
- `initiatePayment()` - Create new payment
- `verifyPayment()` - Verify Razorpay callback
- `getPaymentStatus()` - Check payment status
- `refund()` - Process refunds with Razorpay API
- `isEnabled()` - Check if deposits are enabled
- `getAmount()` - Get configured deposit amount

### 4. API Endpoints (Delivery Man App)

#### Profile Endpoint (Modified)
**GET** `/api/v1/delivery-man/profile`

Added fields to response:
```json
{
  "security_deposit_enabled": true,
  "security_deposit_amount": 500,
  "security_deposit_status": "unpaid"
}
```

#### New Endpoints

**1. Initiate Payment**
```
POST /api/v1/delivery-man/security-deposit/pay
```
Request:
```json
{
  "payment_method": "razorpay"
}
```
Response:
```json
{
  "payment_id": 1,
  "amount": 500,
  "razorpay_order_id": "order_xxx",
  "razorpay_key": "rzp_xxx",
  "payment_url": "https://...",
  "redirect_url": "https://...",
  "callback_url": "https://..."
}
```

**2. Get Status**
```
GET /api/v1/delivery-man/security-deposit/status
```
Response:
```json
{
  "security_deposit_enabled": true,
  "security_deposit_amount": 500,
  "security_deposit_status": "paid",
  "security_deposit_paid_at": "2026-02-11 14:30:00",
  "security_deposit_transaction_id": "pay_xxx"
}
```

**3. Verify Payment**
```
POST /api/v1/delivery-man/security-deposit/verify
```
Request:
```json
{
  "payment_id": 1,
  "razorpay_payment_id": "pay_xxx",
  "razorpay_order_id": "order_xxx",
  "razorpay_signature": "signature_xxx"
}
```

**4. Payment Callback** (For gateway redirects)
```
GET/POST /api/v1/delivery-man/security-deposit/callback
```

### 5. Admin Panel Controllers

#### **SecurityDepositController** (`app/Http/Controllers/Admin/SecurityDepositController.php`)

Methods:
- `settings()` - View settings page
- `updateSettings()` - Update enable/disable and amount
- `index()` - List all delivery men with deposit status
- `show()` - View individual deposit details
- `refund()` - Process refund
- `paymentDetails()` - View payment transaction details
- `export()` - Export CSV of deposit statuses

### 6. Admin Routes

```php
Route::group(['prefix' => 'deliveryman/security-deposit'], function () {
    Route::get('settings', 'SecurityDepositController@settings');
    Route::post('settings', 'SecurityDepositController@updateSettings');
    Route::get('/', 'SecurityDepositController@index');
    Route::get('show/{id}', 'SecurityDepositController@show');
    Route::post('refund/{id}', 'SecurityDepositController@refund');
    Route::get('payment/{paymentId}', 'SecurityDepositController@paymentDetails');
    Route::get('export', 'SecurityDepositController@export');
});
```

### 7. Database Seeder

**SecurityDepositSettingsSeeder** - Adds default business settings:
- `security_deposit_enabled` = 0 (disabled by default)
- `security_deposit_amount` = 500

---

## 📋 Next Steps - Frontend Implementation

### Admin Panel Views Needed

Create these Blade files:

#### 1. **Settings Page**
`resources/views/admin/security-deposit/settings.blade.php`

Features:
- Toggle to enable/disable security deposit
- Input field for deposit amount
- Save button

#### 2. **List Page**
`resources/views/admin/security-deposit/index.blade.php`

Features:
- Filter tabs: All / Unpaid / Paid / Refunded
- Search by name, phone, email
- Table columns:
  - ID
  - Name
  - Phone
  - Email
  - Deposit Amount
  - Status (badge)
  - Paid At
  - Actions (View, Refund)
- Pagination
- Export button
- Stats cards showing counts

#### 3. **Detail Page**
`resources/views/admin/security-deposit/show.blade.php`

Features:
- Delivery man information
- Payment history table
- Refund form (if status = paid)
- Transaction details

#### 4. **Payment Details Modal/Page**
`resources/views/admin/security-deposit/payment-details.blade.php`

Features:
- Full payment transaction details
- Razorpay IDs
- Timestamps
- Metadata

### Mobile App Implementation

The mobile app needs to:

1. **Check Profile on Startup**
   - Call `/api/v1/delivery-man/profile`
   - Check `security_deposit_enabled`, `security_deposit_amount`, `security_deposit_status`

2. **Block Flow if Unpaid**
   - If `enabled=true` AND `status=unpaid` AND `application_status=pending`
   - Show "Pay Security Deposit" screen
   - Block access to other features

3. **Payment Screen**
   - Display deposit amount
   - "Pay Now" button
   - On click: Call `/api/v1/delivery-man/security-deposit/pay`
   - Open Razorpay checkout with returned `razorpay_order_id` and `razorpay_key`

4. **Razorpay Integration**
```dart
// Pseudo code
Razorpay razorpay = Razorpay();
var options = {
  'key': response.razorpay_key,
  'amount': response.amount * 100,
  'order_id': response.razorpay_order_id,
  'name': 'Security Deposit',
  'description': 'Delivery Man Security Deposit',
  'prefill': {
    'contact': user.phone,
    'email': user.email
  },
  'callback_url': response.callback_url,
};
razorpay.open(options);
```

5. **Payment Success**
   - On success callback, call `/api/v1/delivery-man/security-deposit/verify`
   - Show success message
   - Navigate to "Account Under Review" screen

6. **Payment Status Check**
   - Call `/api/v1/delivery-man/security-deposit/status` to verify
   - Update local state

---

## 🚀 Deployment Steps

### 1. Run Migrations
```bash
# ⚠️ IMPORTANT: Backup database first!
php artisan migrate
```

### 2. Seed Settings
```bash
php artisan db:seed --class=SecurityDepositSettingsSeeder
```

### 3. Configure Razorpay
Make sure Razorpay credentials are configured in `addon_settings` table with:
- `key_name` = 'razor_pay'
- `mode` = 'live' or 'test'
- `live_values` or `test_values` with `api_key` and `api_secret`

### 4. Test Flow
1. Enable security deposit in admin settings
2. Set amount (e.g., 500)
3. Create test delivery man account
4. Try payment flow from mobile app
5. Verify payment in admin panel
6. Test refund functionality

---

## 🔒 Security Considerations

✅ **Implemented:**
- Razorpay signature verification
- Transaction logging
- Admin authentication for refunds
- Status validation (can't refund unpaid deposits)
- Duplicate payment prevention

⚠️ **Additional Recommendations:**
- Add rate limiting to payment endpoints
- Implement webhook for automatic payment updates
- Add email notifications on payment success/refund
- Log all security deposit actions for audit trail

---

## 📊 Database Structure

### delivery_men table (new columns)
```sql
security_deposit_amount           DECIMAL(10,2) DEFAULT 0
security_deposit_status           ENUM('unpaid','paid','refunded') DEFAULT 'unpaid'
security_deposit_paid_at          TIMESTAMP NULL
security_deposit_transaction_id   VARCHAR(255) NULL
security_deposit_payment_method   VARCHAR(255) NULL
```

### security_deposit_payments table (new)
```sql
id                        BIGINT UNSIGNED PRIMARY KEY
delivery_man_id           BIGINT UNSIGNED (FK to delivery_men)
amount                    DECIMAL(10,2)
currency                  VARCHAR(255) DEFAULT 'INR'
status                    ENUM('pending','success','failed','refunded')
payment_method            VARCHAR(255)
transaction_id            VARCHAR(255)
razorpay_order_id         VARCHAR(255)
razorpay_payment_id       VARCHAR(255)
razorpay_signature        VARCHAR(255)
payment_url               TEXT
redirect_url              TEXT
callback_url              TEXT
metadata                  JSON
paid_at                   TIMESTAMP NULL
refunded_at               TIMESTAMP NULL
refund_reason             TEXT NULL
refunded_by               BIGINT UNSIGNED NULL (FK to admins)
created_at                TIMESTAMP
updated_at                TIMESTAMP
```

### business_settings table (new rows)
```sql
key: 'security_deposit_enabled'  value: '0' or '1'
key: 'security_deposit_amount'   value: '500' (example)
```

---

## 🧪 Testing Checklist

### API Tests
- [ ] Profile endpoint returns security deposit fields
- [ ] Payment initiation creates Razorpay order
- [ ] Payment verification validates signature
- [ ] Status endpoint returns correct data
- [ ] Already paid users can't pay again
- [ ] Disabled deposits prevent payment

### Admin Tests
- [ ] Settings page saves correctly
- [ ] List page shows all delivery men
- [ ] Filters work (paid/unpaid/refunded)
- [ ] Search functionality works
- [ ] Refund creates Razorpay refund
- [ ] Export generates CSV correctly
- [ ] Permissions work (only admins can refund)

### Integration Tests
- [ ] Full payment flow with Razorpay sandbox
- [ ] Webhook handling (if implemented)
- [ ] Concurrent payment attempts handled correctly
- [ ] Refund reflects in delivery man profile

---

## 📞 Support & Troubleshooting

### Common Issues

**1. Migration fails**
- Check if columns already exist
- Verify database connection
- Check for conflicting migrations

**2. Razorpay API not initialized**
- Verify credentials in `addon_settings` table
- Check Razorpay PHP SDK is installed: `composer require razorpay/razorpay`
- Check logs: `storage/logs/laravel.log`

**3. Payment verification fails**
- Ensure correct signature algorithm
- Check Razorpay webhook secret matches
- Verify payment ID is correct

**4. Admin routes not working**
- Clear route cache: `php artisan route:clear`
- Check middleware permissions
- Verify admin is logged in

### Logs
All payment activities are logged to:
```
storage/logs/laravel.log
```

Search for:
- "Security deposit payment initiation"
- "Razorpay security deposit creation"
- "Payment verification"
- "Refund"

---

## 🎯 Summary

This implementation provides:
✅ Complete backend API for security deposit payments
✅ Razorpay integration with signature verification
✅ Admin panel controllers and routes
✅ Payment tracking and audit trail
✅ Refund functionality with Razorpay API
✅ Status checking and validation
✅ Export functionality for reporting

**What's Missing:**
- Admin panel Blade views (templates provided above)
- Mobile app implementation (flow provided above)
- Email notifications (optional)
- Webhook handler for automatic updates (optional)

---

## 📝 Files Created/Modified

### New Files
1. `database/migrations/2026_02_12_003003_add_security_deposit_fields_to_delivery_men_table.php`
2. `database/migrations/2026_02_12_003024_create_security_deposit_payments_table.php`
3. `database/seeders/SecurityDepositSettingsSeeder.php`
4. `app/Models/SecurityDepositPayment.php`
5. `app/Services/SecurityDepositService.php`
6. `app/Http/Controllers/Api/V1/SecurityDepositController.php`
7. `app/Http/Controllers/Admin/SecurityDepositController.php`

### Modified Files
1. `app/Models/DeliveryMan.php` - Added relationships
2. `app/Http/Controllers/Api/V1/DeliverymanController.php` - Added deposit fields to profile
3. `routes/api/v1/api.php` - Added API routes
4. `routes/admin.php` - Added admin routes

---

**Implementation Date:** 2026-02-11
**Version:** 1.0
**Status:** ✅ Backend Complete | ⏳ Frontend Pending
