# 🧪 Order View V2 - Complete Testing Guide

**Date**: 2026-03-26
**Purpose**: Step-by-step testing instructions for Order View V2 with Bargaining Integration

---

## 📋 Table of Contents

1. [Pre-Testing Checklist](#1-pre-testing-checklist)
2. [Creating a Test Bargaining Order](#2-creating-a-test-bargaining-order)
3. [Testing Admin Panel V2](#3-testing-admin-panel-v2)
4. [Testing Vendor Panel V2](#4-testing-vendor-panel-v2)
5. [Testing Customer App API](#5-testing-customer-app-api)
6. [Testing Regular (Non-Bargaining) Orders](#6-testing-regular-orders)
7. [Testing Edge Cases](#7-testing-edge-cases)
8. [Performance Testing](#8-performance-testing)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Pre-Testing Checklist

### **Verify Database Migration**

```bash
# Check if migration ran
php artisan migrate:status | grep "add_bargaining_tracking_to_orders"

# Should show: ✓ 2026_03_27_000001_add_bargaining_tracking_to_orders

# Verify columns exist
mysql -u root -p
```

```sql
USE your_database_name;

-- Check columns
DESCRIBE orders;
-- Should show:
-- - bargaining_request_id (bigint, nullable)
-- - bargaining_accepted_offer_id (bigint, nullable)
-- - is_bargaining_order (tinyint, default 0)

-- Check indexes
SHOW INDEXES FROM orders WHERE Key_name LIKE 'idx_bargaining%';
-- Should show:
-- - idx_bargaining_request
-- - idx_is_bargaining

-- Check foreign keys
SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'orders'
  AND CONSTRAINT_NAME LIKE '%bargaining%';
-- Should show 2 FKs

EXIT;
```

### **Clear All Caches**

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Verify routes exist
php artisan route:list | grep -i "view-v2"
# Should show:
# - admin/order/view-v2/{order}
# - vendor/order/view-v2/{order}

php artisan route:list | grep -i "details-v2"
# Should show:
# - api/v1/customer/order/details-v2/{order}
```

### **Run Automated Tests**

```bash
php scripts/test-order-view-v2.php

# Expected output: 32/32 tests passed (100%)
```

---

## 2. Creating a Test Bargaining Order

### **Option A: Use Customer App (Recommended)**

1. **Login as Customer**
   - Open customer app
   - Login with valid credentials

2. **Add Items to Cart**
   - Add 3-5 items to cart
   - Note down cart total

3. **Initiate Bargaining (Instant Mode)**
   - Go to cart
   - Click "Get Best Price" or "Bargain Now"
   - Select "Instant" mode (results in 2 minutes)
   - Submit bargaining request

4. **Wait for Offers**
   - System will notify vendors
   - Wait ~2 minutes for offers
   - You'll receive offers from multiple stores

5. **Accept Best Offer**
   - Review offers
   - Accept the lowest price offer
   - Note the request code (e.g., BR-ABC123)

6. **Place Order**
   - Complete order placement
   - Pay via COD or online
   - **CRITICAL**: Note the order ID

7. **Verify Order is Linked**
   ```bash
   mysql -u root -p -e "SELECT id, bargaining_request_id, is_bargaining_order FROM orders WHERE id = YOUR_ORDER_ID"

   # Should show:
   # - bargaining_request_id: NOT NULL
   # - is_bargaining_order: 1
   ```

### **Option B: Use API (For Advanced Testing)**

**Step 1: Login and Get Token**
```bash
curl -X POST "https://new.snocart.com/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email_or_phone": "customer@example.com",
    "password": "password123"
  }'

# Save the token from response
```

**Step 2: Initiate Bargaining**
```bash
curl -X POST "https://new.snocart.com/api/v2/customer/bargaining/initiate" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "mode": "instant",
    "zone_id": 1,
    "module_id": 1,
    "cart_items": [
      {
        "item_id": 123,
        "quantity": 2,
        "original_price": 100.00
      },
      {
        "item_id": 456,
        "quantity": 1,
        "original_price": 200.00
      }
    ]
  }'

# Save the request_code from response
```

**Step 3: Wait for Offers**
```bash
# Check offers after 2 minutes
curl -X GET "https://new.snocart.com/api/v2/customer/bargaining/REQUEST_CODE/offers" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Step 4: Accept Offer**
```bash
curl -X POST "https://new.snocart.com/api/v2/customer/bargaining/REQUEST_CODE/accept-offer" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "offer_id": 789
  }'
```

**Step 5: Place Order**
```bash
curl -X POST "https://new.snocart.com/api/v1/customer/order/place" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "payment_method": "cash_on_delivery",
    "order_type": "delivery",
    "distance": 2.5,
    ...
  }'

# Save the order_id from response
```

**Step 6: Verify Linking**
```bash
mysql -u root -p -e "SELECT id, bargaining_request_id, bargaining_accepted_offer_id, is_bargaining_order FROM orders WHERE id = ORDER_ID"
```

---

## 3. Testing Admin Panel V2

### **Test 1: Access V2 View Directly**

1. **Login to Admin Panel**
   - URL: `https://new.snocart.com/admin/auth/login`
   - Login with admin credentials

2. **Navigate to V2 View**
   - Direct URL: `https://new.snocart.com/admin/order/view-v2/YOUR_ORDER_ID`
   - Replace `YOUR_ORDER_ID` with the bargaining order ID

3. **Verify Page Loads**
   - ✅ Page loads without errors (HTTP 200)
   - ✅ No console errors (F12 → Console tab)
   - ✅ No PHP errors in logs

### **Test 2: Verify Bargaining Hero Card**

**Expected:**
- 🏆 Purple gradient card at top
- **Total Savings**: Shows ₹ amount + percentage
- **Fulfillment**: Shows percentage (usually 100%)
- **Rank**: Shows "#1 of X offers"
- **Request Code**: Shows "BR-XXXXXX"

**Check:**
```
[ ] Purple gradient background visible
[ ] All 4 metrics display correctly
[ ] Savings calculation is accurate (original_cart_value - final_price)
[ ] Percentage is accurate ((savings / original_cart_value) * 100)
[ ] Request code matches bargaining_requests table
```

**Verify Data Accuracy:**
```sql
SELECT
    br.request_code,
    br.original_cart_value,
    br.final_price,
    br.total_savings,
    bso.rank,
    br.total_offers_received,
    bso.fulfillment_percentage
FROM orders o
JOIN bargaining_requests br ON o.bargaining_request_id = br.id
JOIN bargaining_store_offers bso ON o.bargaining_accepted_offer_id = bso.id
WHERE o.id = YOUR_ORDER_ID;
```

### **Test 3: Verify Items Comparison Table**

**Expected:**
- Table shows all order items
- Each row has: Item Name | Qty | Original Price | Bargained Price | Savings
- Savings = (original_price - price) * quantity

**Check:**
```
[ ] All order items displayed
[ ] Item images load correctly
[ ] Original price shown with strikethrough
[ ] Bargained price shown in bold
[ ] Savings calculated correctly per item
[ ] Total savings matches hero card
```

**Verify Item Data:**
```sql
SELECT
    od.item_name,
    od.quantity,
    od.price as bargained_price,
    boi.original_price,
    (boi.original_price - od.price) * od.quantity as savings
FROM order_details od
LEFT JOIN bargaining_offer_items boi ON od.item_id = boi.item_id
LEFT JOIN orders o ON od.order_id = o.id
WHERE od.order_id = YOUR_ORDER_ID;
```

### **Test 4: Verify Competing Offers Section**

**Expected:**
- Expandable `<details>` element
- Shows top 5 offers ranked by price
- Winning offer highlighted in green
- Each offer shows: Rank | Store Name | Total | Fulfillment %

**Check:**
```
[ ] Section collapses/expands on click
[ ] Shows max 5 offers
[ ] Rank #1 is highlighted (green border)
[ ] Store names match actual stores
[ ] Total amounts are accurate
[ ] Fulfillment percentages correct
```

**Verify Competing Offers:**
```sql
SELECT
    rank,
    s.name as store_name,
    total_amount,
    fulfillment_percentage,
    CASE WHEN id = (SELECT bargaining_accepted_offer_id FROM orders WHERE id = YOUR_ORDER_ID)
         THEN 'WINNER'
         ELSE ''
    END as is_winner
FROM bargaining_store_offers bso
JOIN stores s ON bso.store_id = s.id
WHERE bso.bargaining_request_id = (SELECT bargaining_request_id FROM orders WHERE id = YOUR_ORDER_ID)
  AND bso.status = 'submitted'
ORDER BY bso.rank ASC
LIMIT 5;
```

### **Test 5: Verify Missing Items Alert**

**If some items were unavailable:**

**Expected:**
- ⚠️ Yellow alert box
- Shows count of missing items
- Lists each missing item name

**Check:**
```
[ ] Alert only shows if items_missing > 0
[ ] Missing count is accurate
[ ] Missing item names listed correctly
```

### **Test 6: Test Toggle to V1**

**Steps:**
1. Click "View Classic (V1)" button in header
2. Should redirect to: `/admin/order/details/YOUR_ORDER_ID`
3. Page loads correctly
4. Should see "Bargaining V2" button (purple)
5. Click "Bargaining V2" → Returns to V2 view

**Check:**
```
[ ] V1 view loads without errors
[ ] V1 shows all standard order details
[ ] Purple "Bargaining V2" button visible
[ ] Clicking button returns to V2
[ ] No data loss during toggle
```

### **Test 7: Test Order Actions in V2**

**Expected:**
- All standard order actions still work
- Status updates work
- Assign delivery man works
- Print invoice works

**Check:**
```
[ ] Can change order status
[ ] Can assign delivery man
[ ] Can print invoice
[ ] Can add notes
[ ] All AJAX calls successful (check Network tab)
```

---

## 4. Testing Vendor Panel V2

### **Test 1: Access Vendor V2 View**

1. **Login to Vendor Panel**
   - URL: `https://new.snocart.com/store-panel/auth/login`
   - Login with vendor credentials (for the winning store)

2. **Navigate to V2 View**
   - Direct URL: `https://new.snocart.com/store-panel/order/view-v2/YOUR_ORDER_ID`

3. **Verify Authorization**
   - ✅ Winning vendor can access
   - ❌ Other vendors should get "Unauthorized" error

### **Test 2: Verify Success Card**

**Expected:**
- 🎉 Green gradient card with "Congratulations! You Won!" message
- Large rank badge showing "#1"
- Metrics: My Rank | Competitors | Fulfillment

**Check:**
```
[ ] Green gradient background
[ ] Large circular rank badge "#1"
[ ] "You beat X competing stores" message
[ ] Rank number correct
[ ] Competitor count accurate (total_offers - 1)
[ ] Fulfillment percentage correct
```

### **Test 3: Verify Vendor-Specific Metrics**

**Expected:**
- Items Available: X/Y
- Special Discount Given: ₹ amount
- Your Notes: (if provided during bargaining)

**Check:**
```
[ ] Items available count correct
[ ] Special discount amount accurate
[ ] Vendor notes display (if exist)
[ ] No competing offers shown (vendor perspective)
```

**Verify Vendor Data:**
```sql
SELECT
    bso.rank,
    bso.fulfillment_percentage,
    bso.items_available,
    bso.items_missing,
    bso.special_discount,
    bso.vendor_notes,
    br.total_offers_received - 1 as competitors
FROM bargaining_store_offers bso
JOIN bargaining_requests br ON bso.bargaining_request_id = br.id
WHERE bso.id = (SELECT bargaining_accepted_offer_id FROM orders WHERE id = YOUR_ORDER_ID);
```

### **Test 4: Test Toggle to V1**

**Steps:**
1. Click "View Classic (V1)" button
2. Should redirect to vendor V1 view
3. Should see green "Bargaining V2" button
4. Click button → Returns to V2

**Check:**
```
[ ] Toggle works correctly
[ ] V1 shows standard vendor order view
[ ] Green badge shows for bargaining orders
[ ] Can return to V2 seamlessly
```

---

## 5. Testing Customer App API

### **Test 1: Get Customer Token**

```bash
# Login to get token
curl -X POST "https://new.snocart.com/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email_or_phone": "CUSTOMER_PHONE_OR_EMAIL",
    "password": "PASSWORD"
  }' | jq

# Save the token
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."
```

### **Test 2: Call API Endpoint**

```bash
# Test with bargaining order
curl -X GET "https://new.snocart.com/api/v1/customer/order/details-v2/YOUR_ORDER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq

# Save response to file for inspection
curl -X GET "https://new.snocart.com/api/v1/customer/order/details-v2/YOUR_ORDER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" > response.json

# Pretty print
cat response.json | jq
```

### **Test 3: Verify Response Structure**

**Check JSON structure:**
```bash
# Check for required fields
cat response.json | jq 'keys'

# Should include:
# - id
# - order_status
# - order_amount
# - is_bargaining_order
# - customer
# - store
# - details
# - delivery_man (if assigned)

# For bargaining orders, also check:
cat response.json | jq '.bargaining_data | keys'

# Should include:
# - request_code
# - total_savings
# - savings_percentage
# - fulfillment_percentage
# - competing_offers
```

### **Test 4: Verify Authorization**

**Test 1: Valid User (Owner)**
```bash
curl -X GET "https://new.snocart.com/api/v1/customer/order/details-v2/YOUR_ORDER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Expected: HTTP 200, full order details
```

**Test 2: Invalid User (Not Owner)**
```bash
# Login as different customer
curl -X POST "https://new.snocart.com/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email_or_phone": "OTHER_CUSTOMER",
    "password": "PASSWORD"
  }' | jq

# Get token and try to access order
OTHER_TOKEN="..."

curl -X GET "https://new.snocart.com/api/v1/customer/order/details-v2/YOUR_ORDER_ID" \
  -H "Authorization: Bearer $OTHER_TOKEN" \
  -H "Accept: application/json"

# Expected: HTTP 403 Forbidden
# {"errors":[{"code":"unauthorized","message":"You are not authorized..."}]}
```

**Test 3: No Token**
```bash
curl -X GET "https://new.snocart.com/api/v1/customer/order/details-v2/YOUR_ORDER_ID" \
  -H "Accept: application/json"

# Expected: HTTP 401 Unauthorized
# {"errors":[{"code":"auth","message":"Unauthenticated"}]}
```

**Test 4: Invalid Order ID**
```bash
curl -X GET "https://new.snocart.com/api/v1/customer/order/details-v2/999999999" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Expected: HTTP 404 Not Found
# {"errors":[{"code":"not_found","message":"Order not found"}]}
```

### **Test 5: Verify Bargaining Data**

**Extract and verify bargaining data:**
```bash
cat response.json | jq '.bargaining_data'

# Check calculations
echo "Original Cart Value:" $(cat response.json | jq '.bargaining_data.original_cart_value')
echo "Final Price:" $(cat response.json | jq '.bargaining_data.final_price')
echo "Total Savings:" $(cat response.json | jq '.bargaining_data.total_savings')
echo "Savings %:" $(cat response.json | jq '.bargaining_data.savings_percentage')

# Verify: total_savings = original_cart_value - final_price
# Verify: savings_percentage = (total_savings / original_cart_value) * 100
```

### **Test 6: Verify Price Comparisons**

**Check item-level savings:**
```bash
cat response.json | jq '.details[] | {
  item_name,
  quantity,
  price,
  original_price,
  item_savings
}'

# Verify: item_savings = (original_price - price) * quantity
```

### **Test 7: Verify Competing Offers**

**Check competing offers array:**
```bash
cat response.json | jq '.bargaining_data.competing_offers'

# Should show max 5 offers
# Each should have: rank, store_name, total_amount, fulfillment_percentage, is_winner
# Rank #1 should have is_winner = true
```

### **Test 8: Performance Test**

```bash
# Test response time
time curl -X GET "https://new.snocart.com/api/v1/customer/order/details-v2/YOUR_ORDER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" -w "\nTime: %{time_total}s\n"

# Expected: < 200ms for bargaining orders
# Expected: < 100ms for regular orders
```

---

## 6. Testing Regular (Non-Bargaining) Orders

### **Test 1: Admin V2 View with Regular Order**

```bash
# Find a regular order
mysql -u root -p -e "SELECT id FROM orders WHERE is_bargaining_order = 0 LIMIT 1"

# Access V2 view
# URL: https://new.snocart.com/admin/order/view-v2/REGULAR_ORDER_ID
```

**Expected:**
```
[ ] Page loads without errors
[ ] NO bargaining hero card shown
[ ] Items table shows normal pricing (no original_price column)
[ ] NO competing offers section
[ ] All standard order info displays correctly
```

### **Test 2: V1 View with Regular Order**

```bash
# URL: https://new.snocart.com/admin/order/details/REGULAR_ORDER_ID
```

**Expected:**
```
[ ] Page loads normally
[ ] NO "Bargaining V2" button shown
[ ] Standard order view only
```

### **Test 3: API with Regular Order**

```bash
curl -X GET "https://new.snocart.com/api/v1/customer/order/details-v2/REGULAR_ORDER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq

# Check response
cat response.json | jq '{
  id,
  is_bargaining_order,
  bargaining_data
}'
```

**Expected:**
```json
{
  "id": 100001,
  "is_bargaining_order": false,
  "bargaining_data": null
}
```

---

## 7. Testing Edge Cases

### **Edge Case 1: Partial Fulfillment**

**Create scenario:**
- Bargaining order where vendor couldn't provide all items
- Some items marked as unavailable

**Test:**
```
[ ] Missing items alert shows in admin V2
[ ] Items missing count accurate
[ ] Fulfillment % < 100
[ ] Vendor V2 shows items_missing count
```

### **Edge Case 2: Only 1 Offer Received**

**Create scenario:**
- Bargaining request with only 1 vendor offer

**Test:**
```
[ ] Hero card shows "Rank #1 of 1 offers"
[ ] Competing offers section shows only 1 entry
[ ] No errors in display
```

### **Edge Case 3: Guest Order**

**Create scenario:**
- Place bargaining order as guest (no login)

**API Test:**
```bash
# Try to access as authenticated user (should fail)
curl -X GET "https://new.snocart.com/api/v1/customer/order/details-v2/GUEST_ORDER_ID" \
  -H "Authorization: Bearer $TOKEN"

# Expected: 403 Forbidden (guest_id doesn't match)
```

### **Edge Case 4: Order with Null Bargaining Fields**

**Create scenario:**
- Old order created before migration (all bargaining fields NULL)

**Test:**
```
[ ] V2 view loads without errors
[ ] No bargaining card shown
[ ] No PHP errors about null values
[ ] isset() checks prevent errors
```

### **Edge Case 5: Very Large Order (50+ items)**

**Test:**
```
[ ] Page loads in < 2 seconds
[ ] All items display correctly
[ ] Scrolling works smoothly
[ ] No memory errors
```

---

## 8. Performance Testing

### **Test 1: Database Query Count**

**Enable query logging:**
```php
// Add to AppServiceProvider.php boot()
DB::listen(function($query) {
    Log::info('Query: ' . $query->sql);
});
```

**Access V2 view and check logs:**
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "Query:"

# Count queries
grep "Query:" storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l

# Expected: < 15 queries for bargaining order (with eager loading)
# Expected: < 10 queries for regular order
```

### **Test 2: Page Load Time**

**Use browser DevTools:**
1. Open V2 view
2. F12 → Network tab
3. Reload page (Ctrl+R)
4. Check "Load" time

**Expected:**
- Bargaining order: < 1.5 seconds
- Regular order: < 1 second

### **Test 3: API Response Time**

```bash
# Test 10 times and average
for i in {1..10}; do
  curl -X GET "https://new.snocart.com/api/v1/customer/order/details-v2/YOUR_ORDER_ID" \
    -H "Authorization: Bearer $TOKEN" \
    -o /dev/null -s -w "Time: %{time_total}s\n"
done

# Expected average: < 200ms
```

### **Test 4: Concurrent Requests**

```bash
# Install Apache Bench
sudo apt-get install apache2-utils

# Test 100 requests, 10 concurrent
ab -n 100 -c 10 \
  -H "Authorization: Bearer $TOKEN" \
  "https://new.snocart.com/api/v1/customer/order/details-v2/YOUR_ORDER_ID"

# Check results:
# - Requests per second > 50
# - Failed requests = 0
# - Mean time < 200ms
```

---

## 9. Troubleshooting

### **Issue: 500 Error on V2 View**

**Check:**
```bash
# Check Laravel logs
tail -100 storage/logs/laravel-$(date +%Y-%m-%d).log

# Check PHP errors
tail -100 /var/log/php8.2-fpm.log

# Common causes:
# - Missing relationships
# - Null pointer errors
# - isset() check missing
```

**Fix:**
- Add `isset()` checks before accessing array keys
- Use null coalescing operator `??`
- Ensure eager loading includes all relationships

### **Issue: Toggle Button Not Showing**

**Check:**
```sql
SELECT id, is_bargaining_order FROM orders WHERE id = YOUR_ORDER_ID;

# If is_bargaining_order = 0 → Button won't show (correct)
# If is_bargaining_order = 1 → Button should show
```

**Fix:**
```bash
# Clear view cache
php artisan view:clear

# Check blade file for isset() check
grep "is_bargaining_order" resources/views/admin-views/order/order-view.blade.php
```

### **Issue: API Returns 403 Forbidden**

**Check:**
```bash
# Verify order ownership
mysql -u root -p

SELECT
    o.id,
    o.user_id,
    u.phone,
    u.email
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
WHERE o.id = YOUR_ORDER_ID;

# Compare with authenticated user ID from token
```

### **Issue: Missing Competing Offers**

**Check:**
```sql
SELECT
    id,
    rank,
    total_amount,
    status
FROM bargaining_store_offers
WHERE bargaining_request_id = (
    SELECT bargaining_request_id
    FROM orders
    WHERE id = YOUR_ORDER_ID
)
ORDER BY rank ASC;

# If status != 'submitted' → Won't show
```

**Fix:**
- Ensure offers have `status = 'submitted'`
- Check controller loads offers correctly

### **Issue: Incorrect Savings Calculation**

**Verify:**
```sql
SELECT
    br.original_cart_value,
    br.final_price,
    br.total_savings,
    br.original_cart_value - br.final_price as calculated_savings
FROM bargaining_requests br
JOIN orders o ON o.bargaining_request_id = br.id
WHERE o.id = YOUR_ORDER_ID;

# If calculated_savings != total_savings → Data issue
```

---

## ✅ Final Testing Checklist

### **Admin Panel**
```
[ ] V2 view loads for bargaining orders
[ ] V2 view loads for regular orders
[ ] Bargaining hero card displays correctly
[ ] Items comparison table accurate
[ ] Competing offers section works
[ ] Missing items alert (if applicable)
[ ] Toggle to/from V1 works
[ ] All order actions functional
[ ] No console errors
[ ] No PHP errors
```

### **Vendor Panel**
```
[ ] V2 view loads for winning vendor
[ ] Unauthorized for other vendors
[ ] Success card displays
[ ] Vendor metrics accurate
[ ] Toggle to/from V1 works
[ ] Order actions functional
[ ] No errors
```

### **Customer API**
```
[ ] API endpoint accessible
[ ] Authorization checks work
[ ] Returns correct data for bargaining orders
[ ] Returns correct data for regular orders
[ ] Response structure matches docs
[ ] Competing offers included
[ ] Price comparisons accurate
[ ] Performance < 200ms
[ ] Error responses correct (401, 403, 404)
```

### **Database**
```
[ ] Migration ran successfully
[ ] Columns exist and nullable
[ ] Foreign keys created
[ ] Indexes created
[ ] Auto-linking works in place_order
[ ] No data corruption
```

### **Backward Compatibility**
```
[ ] V1 views unchanged
[ ] Regular orders unaffected
[ ] Existing APIs work
[ ] No breaking changes
[ ] Old orders work in V2
```

---

## 📊 Expected Test Results

**Success Criteria:**
- ✅ All automated tests pass (32/32)
- ✅ All manual tests pass
- ✅ No console errors
- ✅ No PHP errors
- ✅ Performance metrics met
- ✅ Authorization working
- ✅ Data accuracy verified

**If all tests pass:** ✅ **READY FOR PRODUCTION**

**If any tests fail:** ⚠️ **Review troubleshooting section and fix issues**

---

**Testing Duration**: ~2-3 hours (complete testing)
**Minimum Testing**: ~30 minutes (critical path only)

Good luck with testing! 🚀
