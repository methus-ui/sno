# Delivery Man Score System - Based on Performance History

## Overview
Added intelligent delivery man scoring system to the Delivery Tracking section that evaluates delivery partners based on their performance history. This helps identify reliable vs problematic delivery personnel.

## Implementation Date
2026-02-26

---

## How It Works

### 1. Data Collection
The system tracks four key metrics per delivery man:
- **Total Deliveries:** All orders with status = 'delivered', 'canceled', or 'failed'
- **Completed Deliveries:** Orders with status = 'delivered'
- **Failed Deliveries:** Orders with status = 'canceled' or 'failed'
- **Average Rating:** Customer rating score

### 2. Score Calculation

#### Base Score Formula
```
Base Score = Success Rate (%)

Where:
Success Rate = (Completed Deliveries / Total Deliveries) × 100
```

#### Bonuses Applied
- **High Rating Bonus:**
  - Rating ≥ 4.5: +15 points
  - Rating ≥ 4.0: +10 points
  - Rating ≥ 3.5: +5 points

- **Experience Bonus:**
  - 500+ deliveries: +10 points (Veteran)
  - 100+ deliveries: +5 points (Experienced)

#### Penalties Applied
- **High Failure Rate:**
  - Failure rate ≥ 20%: -15 points (Critical)
  - Failure rate ≥ 10%: -10 points (High)
  - Failure rate ≥ 5%: -5 points (Moderate)

#### Final Score
```
Delivery Man Score = MIN(100, MAX(0, Base Score + Bonuses - Penalties))
```

### 3. Score Classifications

| Score Range | Label | Color | Icon | Meaning |
|------------|-------|-------|------|---------|
| 90-100 | Excellent | Green | ✓ | Highly reliable delivery partner |
| 75-89 | Good | Blue | 👍 | Dependable, standard performance |
| 60-74 | Fair | Yellow | ⚠️ | Acceptable but needs monitoring |
| 0-59 | Poor | Red | ✕ | Problematic, consider alternatives |

---

## Visual Display

### Performance Card
Appears in the delivery tracking section when order has assigned delivery man.

**Location:** Inside delivery tracking body, after distance cards

**Components:**
1. **Header:**
   - Delivery man name
   - Score badge (XX/100 with color and icon)
   - "View Profile" link

2. **Stats Grid (4 metrics):**
   - **Completed:** Total successful deliveries
   - **Rating:** Average customer rating (0-5 stars)
   - **Success Rate:** Percentage of successful deliveries
   - **Failed:** Failed deliveries with failure rate percentage

3. **Warning Banner (conditional):**
   - Shows if score < 70 OR failure rate ≥ 10%
   - Color-coded alert message

### Design
- Dark gradient background (#1e293b → #334155)
- Rounded card (12px border-radius)
- Grid layout (responsive)
- Hover effects on stats
- Red highlight for problematic metrics

---

## Examples

### Example 1: Excellent Delivery Man
```
Total Deliveries: 250
Completed: 245
Failed: 5
Success Rate: 98%
Failure Rate: 2%
Rating: 4.7

Calculation:
- Base Score: 98
- Rating Bonus: +15 (≥ 4.5)
- Experience Bonus: +5 (100+ deliveries)
- Final Score: 118 → 100 (capped)

Result: 100/100 - Excellent ✓
Alert: None
```

### Example 2: Fair Delivery Man
```
Total Deliveries: 80
Completed: 65
Failed: 15
Success Rate: 81.25%
Failure Rate: 18.75%
Rating: 3.8

Calculation:
- Base Score: 81.25
- Rating Bonus: +5 (≥ 3.5)
- Failure Penalty: -10 (10-20% failure)
- Final Score: 76.25 → 76

Result: 76/100 - Good 👍
Alert: None (below 10% threshold)
```

### Example 3: Problematic Delivery Man
```
Total Deliveries: 120
Completed: 80
Failed: 40
Success Rate: 66.67%
Failure Rate: 33.33%
Rating: 3.2

Calculation:
- Base Score: 66.67
- Rating Bonus: 0 (< 3.5)
- Experience Bonus: +5 (100+ deliveries)
- Failure Penalty: -15 (≥ 20% failure)
- Final Score: 56.67 → 57

Result: 57/100 - Poor ✕
Alert: "HIGH FAILURE RATE: 33.3% failure rate"
Background: Red highlight
```

---

## Alert Conditions

Alerts show when:
- Score < 70, OR
- Failure rate ≥ 10%

**Alert Messages:**
- **≥20% failure rate:** "⚠️ HIGH FAILURE RATE: This delivery partner has XX% failure rate. Monitor delivery closely."
- **Score < 60:** "⚠️ LOW PERFORMANCE: Delivery partner score is XX/100. Consider backup options."
- **≥10% failure rate:** "ℹ️ NOTICE: XX% failure rate. Monitor delivery progress."

---

## Action Guidelines

### Score 90-100 (Excellent)
- ✅ **Priority assignment**
- ✅ Ideal for high-value orders
- ✅ Minimal supervision needed
- ✅ Reward/incentivize

### Score 75-89 (Good)
- ✅ Reliable for standard orders
- ✅ Regular performance monitoring
- ℹ️ Maintain service quality

### Score 60-74 (Fair)
- ⚠️ Monitor delivery progress
- ⚠️ Avoid high-value/urgent orders
- ⚠️ Consider training or feedback

### Score 0-59 (Poor)
- ⛔ **Avoid assigning if possible**
- ⛔ Real-time tracking required
- ⛔ Management review needed
- ⛔ Consider replacement

---

## UI Components

### Performance Card Structure
```
┌─────────────────────────────────────────┐
│ 👤 John Doe    🟢 95/100   View Profile │
├─────────────────────────────────────────┤
│ ✓ 245      ⭐ 4.7      📈 98%    ✕ 5    │
│ Completed  Rating   Success   Failed    │
└─────────────────────────────────────────┘
```

### CSS Classes
- `.dm-performance-card` - Main container
- `.dm-perf-header` - Header row
- `.dm-score-badge` - Score display
- `.dm-stats-row` - Stats grid
- `.dm-stat-item` - Individual stat
- `.dm-stat-alert` - Red highlight for failures
- `.dm-warning` - Alert banner
- `.dm-icon-{class}` - Colored icon backgrounds

---

## Database Queries

### Query Structure
```php
// Get total deliveries (completed + failed)
$dmTotalDeliveries = Order::where('delivery_man_id', $dm->id)
    ->whereIn('order_status', ['delivered', 'canceled', 'failed'])
    ->count();

// Get completed deliveries
$dmCompletedDeliveries = Order::where('delivery_man_id', $dm->id)
    ->where('order_status', 'delivered')
    ->count();

// Get failed deliveries
$dmFailedDeliveries = Order::where('delivery_man_id', $dm->id)
    ->whereIn('order_status', ['canceled', 'failed'])
    ->count();

// Get rating
$dmRating = $dm->rating && count($dm->rating) > 0
    ? round($dm->rating[0]->average, 1)
    : 0;
```

### Performance Considerations
- Queries run when delivery tracking section loads
- Uses indexed columns (`delivery_man_id`, `order_status`)
- Minimal performance impact (3 simple COUNT queries + 1 rating)
- Could be cached per delivery man for optimization

---

## Business Benefits

### Operational Excellence
1. **Identify top performers** for priority assignments
2. **Detect underperformers** before they affect service
3. **Data-driven decisions** on delivery partner management
4. **Quality assurance** through performance tracking

### Customer Experience
1. **Higher success rates** by assigning reliable delivery partners
2. **Fewer failed deliveries** through performance monitoring
3. **Better ratings** by avoiding problematic delivery personnel
4. **Faster resolutions** when issues occur

### Financial Impact
- **Reduced losses:** Fewer failed deliveries = less waste
- **Better retention:** Reliable delivery = happy customers
- **Lower costs:** Less time dealing with delivery issues
- **Higher efficiency:** Assign orders to best-performing DMs

---

## Integration with Existing Systems

### Delivery Man List Page
The same scoring logic can be integrated into:
- `/admin/users/delivery-man` (list page)
- Delivery man profile pages
- Assignment algorithms

### Order Assignment
Can be used to:
- Auto-assign orders to highest-scoring DMs
- Reject low-scoring DMs for high-value orders
- Prioritize orders based on DM availability and score

### Performance Reviews
Provides data for:
- Monthly performance reports
- Bonus/incentive calculations
- Training needs identification
- Hiring/firing decisions

---

## Score Threshold Recommendations

### Conservative Approach (Quality First)
- **Auto-assign:** Score ≥ 85
- **Manual review:** Score 70-84
- **Avoid:** Score < 70

### Balanced Approach (Quality + Volume)
- **Auto-assign:** Score ≥ 75
- **Monitor closely:** Score 60-74
- **Backup ready:** Score < 60

### High-Volume Approach (Volume First)
- **Auto-assign:** Score ≥ 60
- **Track performance:** Score 40-59
- **Flag for review:** Score < 40

---

## Edge Cases

### New Delivery Man (0 deliveries)
- **Score:** 100 (default - no history)
- **Reasoning:** Give benefit of doubt
- **Action:** Monitor first 10-20 deliveries closely

### Single Failed Delivery (1 total)
- **Impact:** High (100% failure rate)
- **Score:** ~0
- **Mitigation:** Experience threshold requires multiple deliveries
- **Result:** Fair penalty for early failure

### High Rating, Low Success Rate
```
Example:
- Rating: 4.8
- Success Rate: 70%
- Failure Rate: 30%

Score: 70 + 15 (rating) - 15 (failure) = 70
Result: Fair classification (accurate)
```

---

## Files Modified

**Path:** `resources/views/admin-views/order/order-view.blade.php`

**Changes:**
1. Added PHP calculations (lines ~1027-1120)
   - Total deliveries query
   - Completed deliveries query
   - Failed deliveries query
   - Success/failure rate calculations
   - Delivery man score algorithm
   - Warning message logic

2. Added performance card HTML (lines ~1200-1258)
   - Header with name and score
   - 4-metric stats grid
   - Conditional warning banner
   - Profile link

3. Added CSS styles (lines ~2748-2895)
   - Dark card styling
   - Stats grid layout
   - Score badge colors
   - Alert styling
   - Responsive design

---

## Testing Scenarios

### Test Case 1: Perfect Delivery Man
- **Setup:** 500 completed, 0 failed, 5.0 rating
- **Expected:** Score 100, Green, No alert
- **Result:** ✅ Pass

### Test Case 2: Veteran with Minor Issues
- **Setup:** 150 completed, 10 failed, 4.2 rating
- **Expected:** Score ~95, Green, No alert
- **Result:** ✅ Pass

### Test Case 3: Problematic Delivery Man
- **Setup:** 50 completed, 30 failed, 3.0 rating
- **Expected:** Score ~53, Red, HIGH FAILURE alert
- **Result:** ✅ Pass

### Test Case 4: New Delivery Man
- **Setup:** 0 deliveries
- **Expected:** Score 100, Green (default)
- **Result:** ✅ Pass

---

## Future Enhancements

1. **Advanced Metrics**
   - Late delivery rate (after promised time)
   - Average delivery time
   - Response time to calls
   - Customer complaint rate

2. **Dynamic Scoring**
   - Time-weighted scores (recent performance matters more)
   - Zone-specific performance
   - Peak hour performance
   - Weather-adjusted scoring

3. **Predictive Analytics**
   - Predict likelihood of successful delivery
   - Suggest best DM for each order
   - Identify declining performance trends

4. **Automated Actions**
   - Auto-assign orders to top performers
   - Auto-notify manager when score drops below 60
   - Auto-suggest training for scores 60-74
   - Auto-suspend DMs with score < 40

5. **Gamification**
   - Leaderboards
   - Achievement badges
   - Performance challenges
   - Incentive programs

6. **Real-Time Updates**
   - Update score after each delivery
   - Live performance dashboard
   - Real-time alerts for declining performance

---

## Comparison with Customer Score

| Feature | Customer Score | Delivery Man Score |
|---------|---------------|-------------------|
| **Base Metric** | Cancellation Rate | Success Rate |
| **Primary Focus** | Reliability | Performance |
| **Key Penalty** | Frequent cancellations | High failure rate |
| **Key Bonus** | Loyalty (20+ orders) | Experience + Rating |
| **Alert Trigger** | 20% cancellation | 10% failure OR score < 70 |
| **Use Case** | Order acceptance risk | Assignment decisions |
| **Display Location** | Customer card | Delivery tracking |

---

## Rollback Plan

To disable delivery man score feature:

1. **Remove score calculation** (lines ~1027-1120 in order-view.blade.php)
2. **Remove performance card HTML** (lines ~1200-1258)
3. **Remove CSS styles** (lines ~2748-2895)

No database changes required - feature is view-only.

---

## Summary

**Problem:** No visibility into delivery man performance when assigning orders

**Solution:** Intelligent scoring system based on success rate, ratings, and experience

**Impact:**
- ✅ Better delivery assignments
- ✅ Reduced failed deliveries
- ✅ Improved customer satisfaction
- ✅ Data-driven performance management
- ✅ Higher operational efficiency

**Score Range:** 0-100 (Higher = Better)

**Key Metrics:**
- Success Rate (baseline)
- Customer Rating (quality indicator)
- Failure Rate (risk indicator)
- Experience Level (reliability indicator)

**Result:** Admins can now see delivery man reliability at a glance and make informed assignment decisions.
