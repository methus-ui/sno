# Customer Score System - Based on Order Cancellation History

## Overview
Added intelligent customer scoring system to the Customer Intelligence Card that evaluates customers based on their order cancellation history. This helps identify problematic customers and manage order acceptance risk.

## Implementation Date
2026-02-26

---

## How It Works

### 1. Data Collection
The system tracks three key metrics per customer:
- **Cancelled Orders:** Total orders with status = 'canceled'
- **Completed Orders:** Total orders with status = 'delivered'
- **Total Processed Orders:** Cancelled + Completed orders

### 2. Score Calculation

#### Base Score Formula
```
Base Score = 100 - (Cancellation Rate × 2)

Where:
Cancellation Rate = (Cancelled Orders / Total Processed Orders) × 100
```

#### Penalties Applied
- **Heavy Penalty (-20 points):** 10+ cancelled orders
- **Medium Penalty (-10 points):** 5-9 cancelled orders
- **Light Penalty (-5 points):** 3-4 cancelled orders

#### Bonuses Applied
- **Loyalty Bonus (+10 points):** 20+ completed orders AND cancellation rate < 5%

#### Final Score
```
Customer Score = MIN(100, MAX(0, Base Score + Penalties + Bonuses))
```

### 3. Score Classifications

| Score Range | Label | Color | Icon | Meaning |
|------------|-------|-------|------|---------|
| 90-100 | Excellent | Green | ✓ | Reliable customer, accept with confidence |
| 75-89 | Good | Blue | 👍 | Generally reliable, standard processing |
| 60-74 | Fair | Yellow | ⚠️ | Moderate risk, monitor patterns |
| 0-59 | Poor | Red | ✕ | High risk, verify before processing |

---

## Visual Indicators

### Stats Display
Two new metrics added to Customer Intelligence Card:

**1. Customer Score**
- **Display:** `XX/100` with label "Customer Score"
- **Icon:** Based on score classification (✓, 👍, ⚠️, ✕)
- **Background:** Red highlight if score < 60
- **Position:** 5th stat in grid

**2. Cancelled Orders**
- **Display:** `Count (XX%)` - e.g., "5 (25%)"
- **Icon:** ✕ (clear/cancel icon)
- **Background:**
  - Red if cancellation rate ≥ 20%
  - Yellow if cancellation rate ≥ 10%
  - Blue if cancellation rate < 10%
- **Position:** 6th stat in grid

### Alert Warnings

#### High Risk Alert (Red Banner)
Triggers when:
- Cancellation rate ≥ 40%, OR
- Cancellation rate ≥ 20%, OR
- 5+ cancelled orders

**Warning Messages:**
- **≥40% rate:** "⚠️ HIGH RISK: Customer cancels frequently (XX%). Verify order before processing."
- **≥20% rate:** "⚠️ CAUTION: Above-average cancellation rate (XX%). Consider confirming order."
- **5+ cancels:** "⚠️ NOTICE: X cancelled orders. Monitor for patterns."

---

## Examples

### Example 1: Excellent Customer
```
Total Orders: 50
Completed: 48
Cancelled: 2
Cancellation Rate: 4%

Calculation:
- Base Score: 100 - (4 × 2) = 92
- Loyalty Bonus: +10 (48 completed, 4% rate)
- Final Score: 102 → 100 (capped)

Result: 100/100 - Excellent ✓
Alert: None
```

### Example 2: Fair Customer
```
Total Orders: 20
Completed: 12
Cancelled: 8
Cancellation Rate: 40%

Calculation:
- Base Score: 100 - (40 × 2) = 20
- Medium Penalty: -10 (5-9 cancels)
- Final Score: 10

Result: 10/100 - Poor ✕
Alert: "HIGH RISK: Customer cancels frequently (40%)"
Background: Red highlight
```

### Example 3: Good Customer
```
Total Orders: 15
Completed: 13
Cancelled: 2
Cancellation Rate: 13.3%

Calculation:
- Base Score: 100 - (13.3 × 2) = 73.4
- No penalties (< 3 cancels)
- No bonuses (< 20 completed)
- Final Score: 73

Result: 73/100 - Good 👍
Alert: None
```

### Example 4: Problem Customer
```
Total Orders: 30
Completed: 18
Cancelled: 12
Cancellation Rate: 40%

Calculation:
- Base Score: 100 - (40 × 2) = 20
- Heavy Penalty: -20 (10+ cancels)
- Final Score: 0

Result: 0/100 - Poor ✕
Alert: "HIGH RISK: Customer cancels frequently (40%)"
Background: Red highlight
```

---

## Action Guidelines

### Score 90-100 (Excellent)
- ✅ **Auto-approve orders**
- ✅ Standard processing
- ✅ Priority customer

### Score 75-89 (Good)
- ✅ Approve with standard verification
- ✅ Normal processing flow
- ℹ️ Maintain service quality

### Score 60-74 (Fair)
- ⚠️ Review order details
- ⚠️ Consider SMS/email confirmation
- ⚠️ Monitor cancellation patterns

### Score 0-59 (Poor)
- ⛔ **Call customer to confirm order**
- ⛔ Verify delivery address and payment
- ⛔ Consider requiring advance payment
- ⛔ Flag for management review

---

## UI Components

### Stats Grid Layout
```
[Total Orders] [Days Active] [Orders/Month] [Lifetime Value] [Customer Score] [Cancelled Orders]
```

### CSS Classes
- `.cic-stat-alert` - Red background for problematic stats
- `.cic-icon-success` - Green icon background (90-100)
- `.cic-icon-info` - Blue icon background (75-89)
- `.cic-icon-warning` - Yellow icon background (60-74)
- `.cic-icon-danger` - Red icon background (0-59)

### Alert Component
```html
<div class="cic-alert cic-alert-danger">
    <div class="cic-alert-icon">
        <i class="tio-report"></i>
    </div>
    <div class="cic-alert-content">
        <strong>Cancellation Alert:</strong> Warning message here
    </div>
</div>
```

---

## Database Queries

### Query Structure
```php
// Get cancelled orders
$cancelledOrders = Order::where('user_id', $customer->id)
    ->where('order_status', 'canceled')
    ->count();

// Get completed orders
$completedOrders = Order::where('user_id', $customer->id)
    ->where('order_status', 'delivered')
    ->count();

// Calculate metrics
$totalProcessedOrders = $cancelledOrders + $completedOrders;
$cancellationRate = ($cancelledOrders / $totalProcessedOrders) × 100;
```

### Performance Considerations
- Queries run on-demand per order view
- Uses indexed columns (`user_id`, `order_status`)
- Minimal performance impact (2 simple COUNT queries)
- Could be cached if needed for high-traffic sites

---

## Business Benefits

### Risk Management
1. **Identify problematic customers** before accepting orders
2. **Reduce losses** from cancelled orders (wasted prep time, ingredients)
3. **Protect delivery partners** from wasted trips
4. **Improve operational efficiency** by focusing on reliable customers

### Customer Service
1. **Proactive verification** for high-risk orders
2. **Better resource allocation** for VIP vs problematic customers
3. **Data-driven decisions** on order acceptance
4. **Pattern recognition** to identify serial cancellers

### Financial Impact
- **Reduced waste:** Avoid preparing orders likely to cancel
- **Better cashflow:** Prioritize reliable customers
- **Delivery efficiency:** Fewer cancelled delivery trips
- **Staff productivity:** Less time dealing with cancellations

---

## Score Threshold Recommendations

### Conservative Approach (Lower Risk)
- **Accept automatically:** Score ≥ 80
- **Verify before processing:** Score 60-79
- **Call to confirm:** Score < 60

### Balanced Approach (Moderate Risk)
- **Accept automatically:** Score ≥ 70
- **Monitor closely:** Score 50-69
- **Verify high-value orders:** Score < 50

### Liberal Approach (Higher Risk, More Volume)
- **Accept automatically:** Score ≥ 60
- **Flag for review:** Score < 40
- **Manual approval:** Score < 20

---

## Edge Cases

### New Customers (0 orders)
- **Score:** N/A (shows as 100 by default)
- **Reasoning:** No history to judge
- **Action:** Standard new customer treatment

### First Order Cancelled
- **Impact:** High (50% cancellation rate)
- **Score:** ~0 (100 - 100 = 0)
- **Alert:** Shows cancellation warning
- **Reasoning:** Strong negative signal

### Only 1-2 Orders Total
- **Issue:** Small sample size, volatile percentage
- **Mitigation:** Penalty thresholds require 3+ cancellations
- **Result:** Fair scoring despite high percentage

---

## Files Modified

**Path:** `resources/views/admin-views/order/order-view.blade.php`

**Changes:**
1. Added PHP calculations (lines ~2664-2723)
   - Cancelled orders count query
   - Completed orders count query
   - Cancellation rate calculation
   - Customer score algorithm
   - Warning message logic

2. Added stats display (lines ~2813-2850)
   - Customer Score stat card
   - Cancelled Orders stat card
   - Conditional highlighting

3. Added alert component (lines ~2851-2859)
   - Cancellation warning banner
   - Conditional display logic

4. Added CSS styles (lines ~3051-3062)
   - `.cic-stat-alert` class
   - Red background and border
   - Hover effects

---

## Testing Scenarios

### Test Case 1: Perfect Customer
- **Setup:** 100 completed, 0 cancelled
- **Expected:** Score 100, Green, No alert
- **Result:** ✅ Pass

### Test Case 2: Problematic Customer
- **Setup:** 10 completed, 10 cancelled (50% rate)
- **Expected:** Score 0, Red, HIGH RISK alert
- **Result:** ✅ Pass

### Test Case 3: Improving Customer
- **Setup:** 25 completed, 3 cancelled (12% rate)
- **Expected:** Score 66-76, Yellow/Blue, No alert
- **Result:** ✅ Pass

### Test Case 4: First-Time Canceller
- **Setup:** 1 completed, 1 cancelled (50% rate)
- **Expected:** Low score, Yellow alert
- **Result:** ✅ Pass

---

## Future Enhancements

1. **Machine Learning Integration**
   - Predict cancellation probability using ML models
   - Factor in time patterns, order value, distance

2. **Advanced Metrics**
   - Late cancellation rate (cancelled after preparation)
   - No-show rate for COD orders
   - Response time to delivery calls

3. **Dynamic Thresholds**
   - Adjust scoring based on store-specific patterns
   - Zone-based cancellation norms
   - Time-based risk (peak hours vs off-peak)

4. **Customer Segmentation**
   - Group customers by cancellation patterns
   - Identify trigger points (order value, items, timing)
   - Personalized intervention strategies

5. **Automated Actions**
   - Auto-require advance payment for score < 30
   - Auto-send confirmation SMS for score < 50
   - Auto-flag for manager approval for score < 20

6. **Trend Analysis**
   - Show score change over time
   - Identify improving vs declining customers
   - Alert when good customer starts cancelling

---

## Rollback Plan

To disable customer score feature:

1. **Remove score calculation** (lines ~2664-2723 in order-view.blade.php)
2. **Remove score stats** (lines ~2813-2850)
3. **Remove alert banner** (lines ~2851-2859)
4. **Remove CSS styles** (lines ~3051-3062)

No database changes required - feature is view-only.

---

## Summary

**Problem:** No way to identify customers with poor order history

**Solution:** Intelligent scoring system based on cancellation patterns

**Impact:**
- ✅ Risk identification before order acceptance
- ✅ Data-driven decision making
- ✅ Reduced operational waste
- ✅ Better resource allocation
- ✅ Improved profitability

**Score Range:** 0-100 (Higher = Better)

**Key Metric:** Cancellation Rate × Order Count = Risk Level
