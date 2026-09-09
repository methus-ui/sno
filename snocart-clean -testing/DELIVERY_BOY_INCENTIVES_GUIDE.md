# 💰 DELIVERY BOY INCENTIVES SYSTEM - COMPLETE GUIDE

## 📊 **OVERVIEW**

Incentives are **automatically added** to delivery boys' earnings when they complete deliveries. The system supports **5 types of incentives** that are calculated and awarded in real-time.

---

## 🔄 **HOW IT WORKS**

### **Trigger Point:**
Incentives are processed **when a delivery boy marks an order as "delivered"**

### **Flow:**
```
Order Delivered
    ↓
API: DeliverymanController@update_order_status
    ↓
Call: DmIncentiveService->processAllIncentives()
    ↓
Calculate & Award Incentives
    ↓
Update DM Wallet
    ↓
Create Account Transaction
    ↓
Send Push Notification (if enabled)
```

---

## 💸 **5 TYPES OF INCENTIVES**

### **1️⃣ TIME-BASED INCENTIVES (Peak Hours)**

**What:** Bonus for delivering during specific time periods

**Configuration:**
- Admin panel → Payroll → Time-Based Incentives
- Set time windows (e.g., 12:00-14:00, 19:00-21:00)
- Fixed amount or percentage of order value
- Can be zone-specific or module-specific

**Example:**
```
Lunch Rush: 12:00-14:00
  - Fixed: ₹15 per order
  - OR Percentage: 5% of order value

Dinner Rush: 19:00-21:00
  - Fixed: ₹20 per order
```

**How It's Awarded:**
- DM delivers during active time window
- System calculates bonus (fixed or percentage)
- Bonus added to wallet
- Transaction created: "Time based incentive"

**Database:**
- Table: `dm_time_based_incentives`
- Tracked in: `provide_d_m_earnings`

---

### **2️⃣ DAILY MILESTONE INCENTIVES**

**What:** Bonus for reaching delivery count targets per day

**Configuration:**
- Admin panel → Payroll → Daily Incentives
- Set delivery count milestones
- Set bonus amount for each milestone

**Example:**
```
Milestone 1: Complete 5 orders → ₹50
Milestone 2: Complete 10 orders → ₹100
Milestone 3: Complete 15 orders → ₹200
```

**How It's Awarded:**
- System counts deliveries for the day
- When target is reached, bonus is awarded **once**
- Prevents duplicate awards (checks if already given today)
- Transaction created: "Daily milestone incentive"

**Database:**
- Table: `dm_daily_incentive_rules`
- Tracked in: `provide_d_m_earnings`

---

### **3️⃣ RUSH HOUR INCENTIVES (Dynamic)**

**What:** Special bonus during manually activated rush periods

**Configuration:**
- Admin panel → Payroll → Rush Incentives
- Admin **manually activates** rush for specific zone
- Set duration and bonus amount
- Auto-deactivates after duration

**Example:**
```
Zone: Downtown
Duration: 60 minutes
Bonus: ₹25 per order
Status: ACTIVE
```

**How It's Awarded:**
- Admin activates rush for a zone
- All DMs in that zone see rush notification
- Every delivery during rush period gets bonus
- Transaction created: "Rush incentive"

**Database:**
- Table: `dm_rush_incentives`, `dm_rush_activations`
- Tracked in: `provide_d_m_earnings`

---

### **4️⃣ PERFORMANCE TIER BONUS**

**What:** Bonus per delivery based on DM's performance tier

**Configuration:**
- Admin panel → Delivery Man → Performance Tiers
- Set tier criteria (monthly deliveries, rating, etc.)
- Set bonus per delivery for each tier

**Example:**
```
Bronze Tier: 0-50 deliveries/month → ₹2/order
Silver Tier: 51-100 deliveries/month → ₹5/order
Gold Tier: 101+ deliveries/month → ₹10/order
```

**How It's Awarded:**
- System auto-assigns DM to tier based on performance
- Every delivery earns tier bonus
- Tier recalculated monthly
- Transaction created: "Tier bonus incentive"

**Database:**
- Table: `dm_performance_tiers`
- Current tier: `delivery_men.current_tier_id`

---

### **5️⃣ FUEL INCENTIVE (Distance-Based)**

**What:** Reimbursement based on distance traveled

**Configuration:**
- Admin panel → Payroll → Fuel Incentives
- Set rate per kilometer
- Can be zone-specific

**Example:**
```
Rate: ₹5 per kilometer
Order distance: 7.5 km
Fuel incentive: ₹37.50
```

**How It's Awarded:**
- System calculates distance (store → customer)
- Uses Haversine formula for accuracy
- Multiplies distance by rate
- Transaction created: "Fuel incentive"

**Database:**
- Table: `dm_fuel_incentives`
- Tracked in: `provide_d_m_earnings`

---

## 💳 **WALLET UPDATES**

### **What Gets Updated:**
```php
DeliveryManWallet {
    total_earning: +incentive_amount
    incentive_earning: +incentive_amount  // Separate tracker
    pending_withdraw: (available for withdrawal)
}
```

### **Account Transaction Created:**
```php
AccountTransaction {
    from_type: 'deliveryman'
    from_id: DM ID
    amount: incentive_amount
    method: 'incentive'
    type: 'incentive'
    ref: 'Time based incentive' (or other type)
}
```

### **Earnings Record:**
```php
ProvideDMEarning {
    delivery_man_id: DM ID
    amount: incentive_amount
    method: 'incentive'
    incentive_type: 'time_based' | 'daily_milestone' | 'rush' | 'tier_bonus' | 'fuel'
    incentive_rule_id: Rule that triggered this
    order_id: Related order
    metadata: Additional info (distance, time window, etc.)
}
```

---

## 📱 **NOTIFICATIONS**

### **Push Notification Sent:**
```json
{
  "title": "Incentive Earned!",
  "description": "You earned a bonus of ₹15. Lunch Rush Bonus",
  "type": "incentive",
  "amount": 15
}
```

### **Can Be Disabled:**
- DM settings: `notify_incentives = false`
- No notification sent, but incentive still awarded

---

## 🚫 **ELIGIBILITY CHECKS**

### **Offline Time Threshold:**
If DM exceeds configured offline time:
- ❌ **NO incentives awarded**
- Still gets commission/tips
- Prevents abuse (staying offline between orders)

### **Duplicate Prevention:**
- Each incentive checked if already awarded
- Prevents double-counting
- Based on: `(dm_id, incentive_type, rule_id, order_id, date)`

---

## 📊 **REAL EXAMPLE: DM ID 11 (Feb 8, 2026)**

### **Order #108303:**
```
Order Amount: ₹986
Delivered at: 11:10 AM

Incentives Awarded:
  ✅ Time-based: ₹8 (morning shift bonus)
  ✅ Total: ₹8
```

### **Order #108310 (5th delivery of day):**
```
Order Amount: ₹309
Delivered at: 1:14 PM
Deliveries today: 5

Incentives Awarded:
  ✅ Time-based: ₹8
  ✅ Daily Milestone: ₹50 (reached 5 deliveries!)
  ✅ Total: ₹58
```

### **Daily Summary:**
```
Total Deliveries: 14
Commission: ₹769.40
Tips: ₹50.00
Time Incentives: ₹172.00 (14 orders × avg ₹12)
Milestone Incentives: ₹350.00
  ├─ 5 orders: ₹50
  ├─ 10 orders: ₹100
  └─ 12 orders: ₹200

TOTAL EARNINGS: ₹1,341.40 ✅
```

---

## ⚙️ **ADMIN CONFIGURATION**

### **Access:**
1. Admin Panel → Payroll
2. Choose incentive type to configure

### **Settings Pages:**
- `/admin/payroll/time-incentives` - Time-based
- `/admin/payroll/daily-incentives` - Daily milestones
- `/admin/payroll/rush-incentives` - Rush hours
- `/admin/payroll/fuel-incentives` - Fuel reimbursement
- `/admin/delivery-man/performance-tiers` - Tier bonuses

---

## 🔍 **TRACKING & REPORTS**

### **View DM Earnings:**
```sql
SELECT 
    incentive_type,
    SUM(amount) as total,
    COUNT(*) as count
FROM provide_d_m_earnings
WHERE delivery_man_id = 11
  AND method = 'incentive'
  AND DATE(created_at) = '2026-02-08'
GROUP BY incentive_type;
```

### **Account Transactions:**
```sql
SELECT *
FROM account_transactions
WHERE from_type = 'deliveryman'
  AND from_id = 11
  AND type = 'incentive'
  AND DATE(created_at) = '2026-02-08';
```

---

## 🎯 **KEY POINTS**

✅ **Automatic:** No manual intervention needed
✅ **Real-time:** Added immediately on delivery
✅ **Tracked:** Full audit trail in database
✅ **Flexible:** Multiple types can combine
✅ **Notifications:** DM informed via push
✅ **Reporting:** Full breakdown available
✅ **Safe:** Duplicate prevention built-in
✅ **Fair:** Offline time eligibility checks

---

## 📝 **CODE REFERENCES**

**Service:** `app/Services/DmIncentiveService.php`
**Trigger:** `app/Http/Controllers/Api/V1/DeliverymanController.php:884`
**Models:**
- `app/Models/DmTimeBasedIncentive.php`
- `app/Models/DmDailyIncentiveRule.php`
- `app/Models/DmRushIncentive.php`
- `app/Models/DmFuelIncentive.php`
- `app/Models/ProvideDMEarning.php`

---

**Created:** February 8, 2026
**System Version:** 6.x
**Status:** ✅ ACTIVE & WORKING
