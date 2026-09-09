# Account Deletion URLs for App Store & Play Store

This document contains the publicly accessible account deletion URLs that can be submitted to Apple App Store and Google Play Store for compliance with their account deletion requirements.

## 📱 Account Deletion URLs

### Customer Account Deletion
```
https://yourdomain.com/account-deletion/customer
```

### Store/Vendor Account Deletion
```
https://yourdomain.com/account-deletion/store
```

### Delivery Man Account Deletion
```
https://yourdomain.com/account-deletion/delivery-man
```

---

## 🔐 How It Works

Each account deletion page follows a secure 2-step verification process:

1. **Step 1: Request OTP**
   - User enters their email or phone number
   - System sends a 6-digit OTP for verification
   - OTP is valid for a limited time

2. **Step 2: Verify & Delete**
   - User enters the OTP received
   - System verifies the OTP
   - Account is permanently deleted after confirmation

---

## ✅ Safety Features

### Pre-Deletion Checks

#### For Customers:
- ❌ Cannot delete if there are ongoing orders (pending, accepted, confirmed, processing, handover, picked_up)
- ✅ All user data and user info will be permanently deleted
- ✅ All OAuth tokens are revoked

#### For Stores/Vendors:
- ❌ Cannot delete if there are ongoing orders
- ❌ Cannot delete if there is cash in hand (must settle dues first)
- ✅ All store data, logos, and cover photos are deleted
- ✅ All store employees (delivery men) are deleted
- ✅ All vendor data and user info are permanently deleted

#### For Delivery Men:
- ❌ Cannot delete if there are ongoing orders
- ❌ Cannot delete if there is cash in hand (must settle dues first)
- ✅ All delivery man data, photos, and identity images are deleted
- ✅ All user info is permanently deleted

---

## 🌐 Supported Verification Methods

- **Email Verification**: OTP sent via email
- **Phone Verification**: OTP sent via SMS

---

## 📋 App Store Submission Information

### For Apple App Store Review:

**Account Deletion URL:**
- Customer: `https://yourdomain.com/account-deletion/customer`
- Store: `https://yourdomain.com/account-deletion/store`
- Delivery Man: `https://yourdomain.com/account-deletion/delivery-man`

**Description:**
Users can delete their accounts by visiting the account deletion page, entering their email or phone number, receiving a verification code, and confirming the deletion. The process ensures security through OTP verification and checks for any pending obligations before allowing deletion.

**Account Deletion Accessibility:**
- ✅ Available without requiring app installation
- ✅ Works on all devices (mobile, tablet, desktop)
- ✅ No login required (uses OTP verification instead)
- ✅ Clear warnings about data deletion
- ✅ Complies with Apple's [App Store Review Guidelines 5.1.1(v)](https://developer.apple.com/app-store/review/guidelines/#data-collection-and-storage)

---

## 📋 Play Store Submission Information

### For Google Play Console:

**Account Deletion URL:**
- Customer: `https://yourdomain.com/account-deletion/customer`
- Store: `https://yourdomain.com/account-deletion/store`
- Delivery Man: `https://yourdomain.com/account-deletion/delivery-man`

**Description:**
Users can initiate account deletion through a web-based interface accessible without the app. The process includes OTP verification for security and validates that all obligations (ongoing orders, cash settlements) are completed before deletion.

**Data Deletion Policy:**
- ✅ Complies with Google Play's [User Data Policy](https://support.google.com/googleplay/android-developer/answer/10144311)
- ✅ Account deletion is permanent and irreversible
- ✅ All user data is deleted from the database
- ✅ Associated files (images, documents) are also deleted

---

## 🚀 Testing the Account Deletion

### Test Mode (APP_MODE=test in .env):
When `APP_MODE=test`, the OTP is always **123456** for easy testing.

### Test Flow:

1. Navigate to the account deletion URL
2. Select verification method (Email or Phone)
3. Enter your registered email/phone
4. Click "Send Verification Code"
5. Enter OTP: **123456** (in test mode) or check your email/SMS
6. Click "Permanently Delete My Account"
7. Confirm the deletion in the popup
8. Account will be deleted (if no restrictions apply)

---

## ⚠️ Important Notes

1. **Replace `yourdomain.com`** with your actual domain name before submission
2. Test the URLs thoroughly before submitting to app stores
3. Ensure your email and SMS services are properly configured
4. The deletion is **permanent and cannot be undone**
5. Users must clear all pending obligations before deletion

---

## 🔧 Technical Implementation

### API Endpoints:

#### Customer Endpoints:
- `POST /account-deletion/customer/request-otp` - Request OTP
- `POST /account-deletion/customer/delete` - Delete account

#### Store Endpoints:
- `POST /account-deletion/store/request-otp` - Request OTP
- `POST /account-deletion/store/delete` - Delete account

#### Delivery Man Endpoints:
- `POST /account-deletion/delivery-man/request-otp` - Request OTP
- `POST /account-deletion/delivery-man/delete` - Delete account

### Request/Response Format:

**Request OTP:**
```json
{
  "identifier": "user@example.com",
  "type": "email"
}
```

**Delete Account:**
```json
{
  "identifier": "user@example.com",
  "type": "email",
  "otp": "123456"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Your account has been successfully deleted"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Please complete your ongoing and accepted orders before deleting your account"
}
```

---

## 📞 Support

If users face any issues with account deletion, they can contact your support team at:
- Email: support@yourdomain.com
- Phone: Your support phone number

---

## 📝 Privacy Policy Note

Make sure to update your Privacy Policy to include information about account deletion:

```
Users can request account deletion at any time through our account deletion portal.
The deletion process is permanent and will remove all personal data from our systems.
Users must complete all ongoing transactions and settle any outstanding payments
before account deletion can be processed.
```

---

## ✅ Checklist for App Store/Play Store Submission

- [ ] Replace all instances of `yourdomain.com` with your actual domain
- [ ] Test all three account deletion URLs in production
- [ ] Verify email OTP delivery works
- [ ] Verify SMS OTP delivery works
- [ ] Test with accounts that have ongoing orders (should fail)
- [ ] Test with accounts that have cash in hand (should fail for store/delivery)
- [ ] Test successful deletion with clean accounts
- [ ] Update Privacy Policy with account deletion information
- [ ] Document the URLs in your app submission notes
- [ ] Ensure HTTPS is enabled on your domain

---

**Last Updated:** 2026-02-09
**Version:** 1.0
