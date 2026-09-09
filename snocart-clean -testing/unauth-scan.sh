#!/bin/bash
# ============================================================
# UNAUTHENTICATED ATTACK SURFACE SCAN
# Targets: endpoints reachable with NO login, NO approval
# Run from: /home/kamill/Desktop/snocart-clean
# Usage: bash unauth-scan.sh
# ============================================================

echo "================================================================"
echo " PART 1: MASS ASSIGNMENT — Customer Registration"
echo " Can you register with privileged fields like is_admin, role?"
echo "================================================================"
echo ""
echo "--- CustomerAuthController register method ---"
grep -n -A60 "function register" app/Http/Controllers/Api/V1/Auth/CustomerAuthController.php | head -80
echo ""

echo "================================================================"
echo " PART 2: MASS ASSIGNMENT — Vendor Registration (API)"
echo " The PUBLIC vendor registration endpoint"
echo " Can you set store_id, status, is_approved, etc?"
echo "================================================================"
echo ""
echo "--- VendorLoginController register method ---"
grep -n -A80 "function register" app/Http/Controllers/Api/V1/Auth/VendorLoginController.php | head -100
echo ""

echo "================================================================"
echo " PART 3: MASS ASSIGNMENT — Delivery Man Registration"
echo "================================================================"
echo ""
echo "--- DeliveryManLoginController store method ---"
grep -n -A80 "function store" app/Http/Controllers/Api/V1/Auth/DeliveryManLoginController.php | head -100
echo ""

echo "================================================================"
echo " PART 4: OTP / RATE LIMITING"
echo " Can you brute-force OTP on registration/password reset?"
echo "================================================================"
echo ""
echo "--- Customer verify_phone_or_email (OTP check) ---"
grep -n -A40 "function verify_phone_or_email" app/Http/Controllers/Api/V1/Auth/CustomerAuthController.php | head -50
echo ""
echo "--- Check for rate limiting / throttle middleware on auth routes ---"
grep -rn "throttle\|rate.limit\|otp_hit_count\|max_attempts\|RateLimiter" app/Http/Controllers/Api/V1/Auth/ | head -20
echo ""

echo "================================================================"
echo " PART 5: IDOR — Public Order Invoice"
echo " The signed-URL public invoice — can it be bypassed?"
echo "================================================================"
echo ""
echo "--- The public invoice route (from web.php) ---"
grep -n -B2 -A15 "public-invoice\|public.order.invoice" routes/web.php
echo ""

echo "================================================================"
echo " PART 6: IDOR — Customer Order Access"
echo " Can customer A read customer B's orders?"
echo "================================================================"
echo ""
echo "--- CustomerController order endpoints ---"
grep -n "function.*order\|->where.*user_id\|auth\|->user()" app/Http/Controllers/Api/V1/CustomerController.php | head -30
echo ""

echo "================================================================"
echo " PART 7: INFORMATION DISCLOSURE — Config API"
echo " What does the public config endpoint leak?"
echo "================================================================"
echo ""
echo "--- ConfigController — what data is exposed ---"
grep -n -A30 "function get_configuration\|function index" app/Http/Controllers/Api/V1/ConfigController.php | head -50
echo ""

echo "================================================================"
echo " PART 8: ACCOUNT DELETION ABUSE"
echo " Public endpoints — can you trigger OTP/deletion abuse?"
echo "================================================================"
echo ""
echo "--- AccountDeletionController ---"
grep -rn "AccountDeletion" routes/web.php
echo ""
ADCONT=$(find app/Http/Controllers/ -name "AccountDeletionController.php" | head -1)
if [ -n "$ADCONT" ]; then
    echo "--- $ADCONT methods ---"
    grep -n "function \|otp\|throttle\|verify\|auth" "$ADCONT" | head -30
fi
echo ""

echo "================================================================"
echo " PART 9: CUSTOMER PROFILE IMAGE UPLOAD"
echo " Does profile image update use getClientOriginalExtension?"
echo " This would be authenticated-as-CUSTOMER RCE (no approval needed)"
echo "================================================================"
echo ""
echo "--- CustomerController update_info method ---"
grep -n -A40 "function update_info" app/Http/Controllers/Api/V1/CustomerController.php | head -50
echo ""

echo "================================================================"
echo " PART 10: SOCIAL LOGIN / REGISTER"
echo " Public endpoints — mass assignment or injection?"
echo "================================================================"
echo ""
echo "--- SocialAuthController register ---"
grep -n -A50 "function social_register" app/Http/Controllers/Api/V1/Auth/SocialAuthController.php | head -60
echo ""

echo "================================================================"
echo " DONE"
echo "================================================================"
