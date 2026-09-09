# 🎯 SHIFT-BASED INCENTIVES SYSTEM - COMPLETE GUIDE

## 📊 **OVERVIEW**

Incentives are now **shift-based** - they are marked as **PENDING** during the shift and only **CREDITED** when the delivery man properly **punches out** at the end of their shift.

---

## 🔄 **HOW IT WORKS NOW**

### **NEW FLOW:**

```
DM Punches In (Start Shift)
    ↓
Delivers Orders → Earns PENDING Incentives
    ↓
Incentives Marked as "pending" (NOT credited to wallet yet)
    ↓
DM Notified: "Bonus accrued, will credit at shift end"
    ↓
DM Punches Out (End Shift) ✅
    ↓
All PENDING Incentives → CREDITED to Wallet
    ↓
DM Notified: "Shift completed! ₹XXX credited"
```

### **INCOMPLETE SHIFT SCENARIO:**

```
DM Punches In
    ↓
Delivers Orders → Earns PENDING Incentives
    ↓
DM DOESN'T Punch Out ❌
    ↓
Next Day 1:30 AM - Cron Job Runs
    ↓
ALL PENDING Incentives → WAVED OFF (Deleted)
    ↓
DM Notified: "Incentives not credited - incomplete shift"
```

---

## 💸 **INCENTIVE STATUSES**

### **1️⃣ PENDING**
- **When:** DM delivers order during an ACTIVE shift (has punch_in, no punch_out yet)
- **Wallet:** NOT added to wallet yet
- **Transaction:** NO account transaction created yet
- **Notification:** "Bonus accrued! Will be credited when shift ends"
- **Database:** `status = 'pending'`, `credited_at = null`, `attendance_id` set

### **2️⃣ CREDITED**
- **When:**
  - DM punches out (shift completed) ✅
  - OR incentive earned when NO active shift
- **Wallet:** Added to `incentive_earning` and `total_earning`
- **Transaction:** Account transaction created
- **Notification:** "Incentive Earned! ₹XX added to wallet"
- **Database:** `status = 'credited'`, `credited_at` set

### **3️⃣ WAVED OFF (Deleted)**
- **When:** Shift not completed (no punch out) by next day
- **Wallet:** NEVER added to wallet
- **Transaction:** NEVER created
- **Notification:** "Incentives not credited - incomplete shift"
- **Database:** Record deleted from `provide_d_m_earnings`

---

## 🗄️ **DATABASE CHANGES**

### **New Columns in `provide_d_m_earnings` Table:**

```sql
-- Status: pending or credited
status ENUM('pending', 'credited') DEFAULT 'pending'

-- Links incentive to shift
attendance_id BIGINT UNSIGNED NULL

-- Timestamp when credited to wallet
credited_at TIMESTAMP NULL

-- Indexes for performance
INDEX (delivery_man_id, status)
INDEX (attendance_id)
```

---

## 📋 **COMPLETE FLOW EXAMPLES**

### **Example 1: Complete Shift (Normal)**

```
8:00 AM - DM #11 punches in
  → Attendance record created (punch_in_time = 08:00)

10:15 AM - DM delivers Order #108303 (₹986)
  → Time-based incentive: ₹8
  → STATUS: pending
  → attendance_id: 1234
  → Notification: "Bonus accrued! ₹8 will credit at shift end"
  → Wallet: NO CHANGE YET

12:30 PM - DM delivers Order #108310 (5th delivery)
  → Time-based: ₹8 (pending)
  → Daily milestone: ₹50 (pending)
  → Total pending: ₹58
  → Wallet: NO CHANGE YET

6:00 PM - DM punches out
  → Attendance updated (punch_out_time = 18:00)
  → creditPendingIncentives() called
  → All pending incentives → credited
  → Total credited: ₹58 (from 2 deliveries)
  → Wallet updated: +₹58 to incentive_earning
  → Account transactions created (2 entries)
  → Notification: "Shift completed! ₹58 credited. Great work!"
```

**Database State After:**
```sql
-- provide_d_m_earnings
id | dm_id | amount | type        | status   | attendance_id | credited_at
1  | 11    | 8.00   | time_based  | credited | 1234          | 2026-02-08 18:00:01
2  | 11    | 8.00   | time_based  | credited | 1234          | 2026-02-08 18:00:01
3  | 11    | 50.00  | daily_mile  | credited | 1234          | 2026-02-08 18:00:01

-- delivery_man_wallets
dm_id | total_earning | incentive_earning
11    | 1399.40      | 58.00

-- account_transactions (3 new rows created at 18:00)
```

---

### **Example 2: Incomplete Shift (No Punch Out)**

```
8:00 AM - DM #6 punches in
  → Attendance record created (punch_in_time = 08:00)

11:00 AM - DM delivers Order #108350 (₹500)
  → Time-based: ₹15 (pending)
  → STATUS: pending
  → Wallet: NO CHANGE

3:00 PM - DM delivers Order #108365 (₹800)
  → Time-based: ₹15 (pending)
  → Rush bonus: ₹25 (pending)
  → Total pending: ₹55
  → Wallet: NO CHANGE

❌ DM forgets to punch out and goes offline
  → Attendance: punch_out_time = NULL

NEXT DAY 1:30 AM - Cron Job Runs
  → Command: dm:waveoff-incomplete-shift-incentives
  → Finds attendance #1235 (no punch out)
  → waveOffPendingIncentives(1235) called
  → All 3 pending incentives → DELETED
  → Wallet: NO CHANGE (never credited)
  → Notification: "Pending ₹55 not credited - shift incomplete"
```

**Database State After:**
```sql
-- provide_d_m_earnings
(All 3 pending records DELETED)

-- delivery_man_wallets
dm_id | total_earning | incentive_earning
6     | 450.00       | 0.00              -- NO incentives credited

-- account_transactions
(NO new incentive transactions)

-- deliveryman_attendances
id   | dm_id | punch_in | punch_out | date
1235 | 6     | 08:00:00 | NULL      | 2026-02-08  -- Incomplete!
```

---

## 🔧 **CODE REFERENCES**

### **1. DmIncentiveService.php**

**Location:** `app/Services/DmIncentiveService.php`

**Key Methods:**

```php
// Awards incentive - marks as pending if shift active
awardIncentive($dm, $amount, $type, $ruleId, $orderId, $metadata)

// Credits all pending incentives when shift completes
creditPendingIncentives($attendanceId)

// Waves off (deletes) pending incentives for incomplete shifts
waveOffPendingIncentives($attendanceId)
```

**Logic:**
```php
public function awardIncentive(...)
{
    // Check if DM has active shift today
    $todayAttendance = DeliverymanAttendance::where('delivery_man_id', $dm->id)
        ->whereDate('date', now()->toDateString())
        ->whereNotNull('punch_in_time')
        ->first();

    // If shift active (has punch_in, no punch_out) → PENDING
    $isPending = $todayAttendance && !$todayAttendance->punch_out_time;

    // Create earning record
    ProvideDMEarning::create([
        'status' => $isPending ? 'pending' : 'credited',
        'attendance_id' => $todayAttendance ? $todayAttendance->id : null,
        'credited_at' => $isPending ? null : now(),
        // ... other fields
    ]);

    // Only credit wallet if NOT pending
    if (!$isPending) {
        $wallet->increment('incentive_earning', $amount);
        // Create account transaction
    }
}
```

---

### **2. DeliverymanAttendanceService.php**

**Location:** `app/Services/DeliverymanAttendanceService.php`

**Hook on Punch Out (Line 100):**

```php
public function markPunchOut(int $deliveryManId, string $source = 'active_toggle'): ?DeliverymanAttendance
{
    // ... existing code ...

    // Set punch_out time
    $attendance->punch_out_time = Carbon::now()->format('H:i:s');
    $attendance->save();

    // 💰 Credit pending incentives when shift is completed
    try {
        $incentiveService = new DmIncentiveService();
        $result = $incentiveService->creditPendingIncentives($attendance->id);

        \Log::info("Punch out - Incentive crediting result", [
            'dm_id' => $deliveryManId,
            'attendance_id' => $attendance->id,
            'result' => $result
        ]);
    } catch (\Exception $e) {
        \Log::error("Failed to credit incentives on punch out: " . $e->getMessage());
    }

    return $attendance;
}
```

---

### **3. WaveOffIncompleteShiftIncentives Command**

**Location:** `app/Console/Commands/WaveOffIncompleteShiftIncentives.php`

**Scheduled:** Daily at 1:30 AM (processes previous day)

**What It Does:**
1. Finds all attendance records from yesterday with `punch_in` but NO `punch_out`
2. For each incomplete shift, calls `waveOffPendingIncentives()`
3. Deletes all pending incentives for that shift
4. Sends notification to DM about waved-off incentives
5. Logs summary report

**Run Manually:**
```bash
# Process yesterday's incomplete shifts
php artisan dm:waveoff-incomplete-shift-incentives

# Process specific date
php artisan dm:waveoff-incomplete-shift-incentives --date=2026-02-07
```

---

### **4. Scheduled in Kernel.php**

**Location:** `app/Console/Kernel.php` (Line 89)

```php
// Wave off pending incentives for incomplete shifts - daily at 1:30 AM
$schedule->command('dm:waveoff-incomplete-shift-incentives')
         ->dailyAt('01:30')
         ->withoutOverlapping();
```

---

## 📱 **NOTIFICATIONS**

### **When Incentive Accrued (Pending):**
```json
{
  "title": "Incentive Accrued!",
  "description": "Bonus of ₹15 will be credited when shift ends. Lunch Rush Bonus",
  "type": "incentive",
  "status": "pending",
  "amount": 15
}
```

### **When Shift Completed (Credited):**
```json
{
  "title": "Shift Completed - Incentives Credited!",
  "description": "All pending incentives (₹172) have been credited to your wallet. Great work!",
  "type": "shift_incentive_credit",
  "amount": 172,
  "count": 14
}
```

### **When Shift Incomplete (Waved Off):**
```json
{
  "title": "Shift Incomplete - Incentives Not Credited",
  "description": "Pending incentives (₹55) were not credited because the shift was not completed properly. Please remember to punch out at the end of your shift.",
  "type": "shift_incentive_waved_off",
  "amount": 55,
  "count": 3
}
```

---

## 🚫 **IMPORTANT NOTES**

### **What Happens to Commission & Tips?**
✅ **Commission and tips are STILL credited immediately** (not affected by this change)
- Commission: Credited when order delivered
- Tips: Credited when order delivered
- **Only INCENTIVES are pending until punch out**

### **What If DM Has No Shift Today?**
✅ **Incentives are credited immediately** (marked as 'credited')
- If DM never punched in today
- Incentive awarded instantly (backward compatible)
- No pending status

### **What About Offline Time Eligibility?**
✅ **Still applies**
- If DM exceeds offline threshold → `incentive_eligible = false`
- No incentives awarded (neither pending nor credited)
- Existing behavior preserved

### **Can Admin Force Credit Pending Incentives?**
📝 **Manual crediting possible via tinker:**
```php
// Credit all pending incentives for a specific attendance
$incentiveService = new App\Services\DmIncentiveService();
$result = $incentiveService->creditPendingIncentives(1234);
```

---

## 🎯 **BENEFITS OF SHIFT-BASED INCENTIVES**

✅ **Encourages proper shift completion** - DMs must punch out to get bonuses
✅ **Accurate attendance tracking** - Clear start/end times for shifts
✅ **Prevents abuse** - Can't collect bonuses without completing shift
✅ **Better payroll management** - Incentives tied to verified working hours
✅ **Accountability** - DMs responsible for their shift completion
✅ **Fair system** - Only completed shifts earn incentives

---

## 📊 **REPORTS & QUERIES**

### **Check Pending Incentives for Today:**
```sql
SELECT
    de.delivery_man_id,
    dm.f_name,
    dm.l_name,
    COUNT(*) as pending_count,
    SUM(de.amount) as pending_amount
FROM provide_d_m_earnings de
JOIN delivery_men dm ON dm.id = de.delivery_man_id
WHERE de.status = 'pending'
  AND DATE(de.created_at) = CURDATE()
GROUP BY de.delivery_man_id, dm.f_name, dm.l_name;
```

### **Check Credited Incentives on Shift Completion:**
```sql
SELECT
    de.*,
    da.punch_in_time,
    da.punch_out_time
FROM provide_d_m_earnings de
JOIN deliveryman_attendances da ON da.id = de.attendance_id
WHERE de.status = 'credited'
  AND de.credited_at IS NOT NULL
  AND DATE(de.credited_at) = '2026-02-08'
ORDER BY de.credited_at DESC;
```

### **Find Incomplete Shifts (No Punch Out):**
```sql
SELECT
    da.id as attendance_id,
    da.delivery_man_id,
    dm.f_name,
    dm.l_name,
    da.date,
    da.punch_in_time,
    COUNT(de.id) as pending_incentives,
    COALESCE(SUM(de.amount), 0) as pending_amount
FROM deliveryman_attendances da
JOIN delivery_men dm ON dm.id = da.delivery_man_id
LEFT JOIN provide_d_m_earnings de ON de.attendance_id = da.id AND de.status = 'pending'
WHERE da.punch_in_time IS NOT NULL
  AND da.punch_out_time IS NULL
  AND da.date = '2026-02-08'
GROUP BY da.id, da.delivery_man_id, dm.f_name, dm.l_name, da.date, da.punch_in_time;
```

---

## 🧪 **TESTING**

### **Test 1: Normal Shift Completion**
1. DM punches in
2. DM delivers 3 orders → earns 3 pending incentives
3. Check `provide_d_m_earnings` → all status = 'pending'
4. Check wallet → incentive_earning = 0 (not credited yet)
5. DM punches out
6. Check `provide_d_m_earnings` → all status = 'credited', credited_at set
7. Check wallet → incentive_earning = sum of all incentives
8. Check `account_transactions` → 3 new incentive transactions

### **Test 2: Incomplete Shift Wave-Off**
1. DM punches in
2. DM delivers 2 orders → earns 2 pending incentives
3. DM goes offline (doesn't punch out)
4. Wait until next day 1:30 AM (or run command manually)
5. Run: `php artisan dm:waveoff-incomplete-shift-incentives --date=2026-02-08`
6. Check `provide_d_m_earnings` → pending records deleted
7. Check wallet → incentive_earning = 0 (never credited)
8. Check notification → DM received wave-off message

### **Test 3: No Active Shift (Backward Compatible)**
1. DM doesn't punch in today
2. DM delivers order (maybe forgot to punch in)
3. Incentive awarded → status = 'credited' immediately
4. Wallet updated immediately
5. No pending status (backward compatible)

---

**Created:** February 8, 2026
**Migration:** `2026_02_08_205039_add_pending_status_to_incentives.php`
**Status:** ✅ ACTIVE & WORKING
**Version:** 6.x
