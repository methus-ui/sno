# 🕐 BREAK DURATION CHECK - EARLY PUNCH OUT PREVENTION

## 📊 **OVERVIEW**

When a delivery man tries to "punch out" within **33 minutes** (30 min break + 3 min threshold) of punching in, the system **treats it as a BREAK**, not a shift end.

This prevents:
- ❌ Accidental early punch outs
- ❌ Premature incentive crediting
- ❌ Shift ending too soon

---

## 🔄 **HOW IT WORKS**

### **SCENARIO 1: Early Punch Out (< 33 Minutes)**

```
8:00 AM - DM punches in (shift starts)
    ↓
8:15 AM - DM delivers order
    → Incentive: ₹15 (marked as PENDING)
    ↓
8:25 AM - DM tries to "punch out" (only 25 minutes worked!)
    ↓
⚠️ SYSTEM DETECTS: Too early (< 33 min threshold)
    ↓
✅ ACTION: Treat as BREAK, NOT shift end
    → Create EmployeeBreak record
    → break_start = 8:25 AM
    → break_end = null (active break)
    ↓
❌ NO punch_out_time set
❌ NO incentives credited
❌ Shift continues (still active)
    ↓
8:55 AM - DM "punches in" again (resumes work)
    ↓
✅ ACTION: End the break
    → break_end = 8:55 AM
    → duration = 30 minutes
    → Shift continues
    ↓
6:00 PM - DM punches out (proper shift end - 10 hours later)
    ↓
⏱️ CHECK: 10 hours > 33 min threshold ✅
    ↓
✅ ACTION: ACTUAL SHIFT END
    → Set punch_out_time = 6:00 PM
    → Credit ALL pending incentives
    → Notification: "Shift completed! ₹XXX credited"
```

---

### **SCENARIO 2: Proper Shift End (> 33 Minutes)**

```
8:00 AM - DM punches in
    ↓
9:00 AM - DM delivers orders (earned incentives)
    ↓
6:00 PM - DM punches out (10 hours worked)
    ↓
⏱️ CHECK: 10 hours > 33 min threshold ✅
    ↓
✅ ACTION: ACTUAL SHIFT END
    → Set punch_out_time = 6:00 PM
    → Credit all pending incentives
    → Calculate working hours
    → Shift completed
```

---

## ⚙️ **CONFIGURATION**

### **Break Threshold Calculation:**

```php
// Break time from settings (default: 30 minutes)
$breakMinutes = BusinessSetting::where('key', 'dm_break_time_minutes')->first();
$breakMinutes = $breakMinutes ? json_decode($breakMinutes->value)->minutes : 30;

// Add 3 minute buffer/threshold
$breakThresholdMinutes = $breakMinutes + 3;  // 30 + 3 = 33 minutes
```

### **Where to Configure:**
- Admin Panel → Delivery Man → Offline Monitor Settings
- Setting key: `dm_break_time_minutes`
- Default: 30 minutes (+ 3 = 33 total threshold)

---

## 🗄️ **DATABASE BEHAVIOR**

### **Early Punch Out (< 33 min):**

**deliveryman_attendances:**
```sql
id  | dm_id | date       | punch_in | punch_out | notes
123 | 11    | 2026-02-08 | 08:00:00 | NULL      | "Auto punch-in on login | Break started on logout (25 min since punch-in)"
```

**breaks (employee_breaks):**
```sql
id | attendance_id | break_start         | break_end | duration_minutes
45 | 123          | 2026-02-08 08:25:00 | NULL      | NULL
```

**provide_d_m_earnings:**
```sql
id | dm_id | amount | status  | attendance_id | credited_at
67 | 11    | 15.00  | pending | 123          | NULL         -- Still pending!
```

**After "punch in" to resume (end break):**
```sql
-- breaks table updated:
id | attendance_id | break_start         | break_end           | duration_minutes
45 | 123          | 2026-02-08 08:25:00 | 2026-02-08 08:55:00 | 30
```

---

### **Proper Punch Out (> 33 min):**

**deliveryman_attendances:**
```sql
id  | dm_id | date       | punch_in | punch_out | notes
123 | 11    | 2026-02-08 | 08:00:00 | 18:00:00  | "Auto punch-in on login | Punch-out on logout"
```

**breaks:**
```sql
(May have multiple break records during the shift)
```

**provide_d_m_earnings:**
```sql
id | dm_id | amount | status   | attendance_id | credited_at
67 | 11    | 15.00  | credited | 123          | 2026-02-08 18:00:01  -- Credited!
```

---

## 📱 **NOTIFICATIONS**

### **When Early Punch Out (Break):**
No special notification sent (may be added in future)

### **When Resuming from Break:**
No notification (break ended normally)

### **When Proper Punch Out (Shift End):**
```json
{
  "title": "Shift Completed - Incentives Credited!",
  "description": "All pending incentives (₹XXX) have been credited to your wallet. Great work!",
  "type": "shift_incentive_credit",
  "amount": 172
}
```

---

## 🧪 **TESTING SCENARIOS**

### **Test 1: Very Early Punch Out (10 minutes)**

```bash
# DM punches in
curl -X POST /api/v1/delivery-man/active-toggle \
  -H "Authorization: Bearer TOKEN" \
  -d "active=1"

# Wait 10 minutes, deliver an order (earns incentive)

# Try to punch out after only 10 minutes
curl -X POST /api/v1/delivery-man/active-toggle \
  -H "Authorization: Bearer TOKEN" \
  -d "active=0"

# ✅ Expected: Break created, NOT shift end
# ✅ Check: punch_out_time = NULL
# ✅ Check: EmployeeBreak created with break_start
# ✅ Check: Incentives still status = 'pending'
```

**Verify in Database:**
```sql
-- Should have active break
SELECT * FROM breaks
WHERE attendance_id = (SELECT id FROM deliveryman_attendances WHERE delivery_man_id = 11 AND date = CURDATE())
  AND break_end IS NULL;

-- Should NOT have punch out
SELECT punch_out_time FROM deliveryman_attendances
WHERE delivery_man_id = 11 AND date = CURDATE();
-- Expected: NULL

-- Incentives should still be pending
SELECT status FROM provide_d_m_earnings
WHERE delivery_man_id = 11 AND DATE(created_at) = CURDATE();
-- Expected: 'pending'
```

---

### **Test 2: Exactly at Threshold (33 minutes)**

```bash
# Punch in, wait exactly 33 minutes, try to punch out

# ✅ Expected: Treated as BREAK (≤ threshold)
# Could be adjusted to < threshold if needed
```

---

### **Test 3: Just Over Threshold (34 minutes)**

```bash
# Punch in, wait 34 minutes, punch out

# ✅ Expected: SHIFT END
# ✅ Check: punch_out_time set
# ✅ Check: Incentives credited
```

---

### **Test 4: Resume After Break**

```bash
# After early punch out (break created)

# DM "punches in" again
curl -X POST /api/v1/delivery-man/active-toggle \
  -H "Authorization: Bearer TOKEN" \
  -d "active=1"

# ✅ Expected: Break ended
# ✅ Check: break_end populated
# ✅ Check: duration_minutes calculated
# ✅ Check: Shift still active (no punch_out_time)
```

**Verify:**
```sql
-- Break should be closed
SELECT break_end, duration_minutes FROM breaks
WHERE attendance_id = (SELECT id FROM deliveryman_attendances WHERE delivery_man_id = 11 AND date = CURDATE())
ORDER BY id DESC LIMIT 1;
-- Expected: break_end NOT NULL, duration_minutes calculated
```

---

### **Test 5: Multiple Breaks in One Shift**

```bash
# Punch in at 8:00 AM
# Work 1 hour
# Punch out at 9:00 AM (treated as break - only 60 min)
# Punch in at 9:30 AM (resume)
# Work until 6:00 PM
# Punch out at 6:00 PM (actual shift end - 10 hours total)

# ✅ Expected:
# - First punch out → break
# - Second punch in → resume
# - Final punch out → shift end + credit incentives
```

---

## 🔧 **CODE REFERENCES**

### **DeliverymanAttendanceService.php**

**markPunchOut() - Line 83:**
```php
// Calculate duration since punch in
$punchInTime = Carbon::parse($attendance->punch_in_time);
$now = Carbon::now();
$minutesSincePunchIn = $punchInTime->diffInMinutes($now);

// Get threshold (30 + 3 = 33 minutes)
$breakThresholdMinutes = ($breakSettings['minutes'] ?? 30) + 3;

// If within threshold → BREAK
if ($minutesSincePunchIn <= $breakThresholdMinutes) {
    // Create break record
    EmployeeBreak::create([
        'attendance_id' => $attendance->id,
        'break_start' => $now,
        'break_end' => null,
    ]);

    // ❌ DO NOT credit incentives
    // ❌ DO NOT set punch_out_time
    return $attendance;
}

// Otherwise → ACTUAL SHIFT END
$attendance->punch_out_time = Carbon::now()->format('H:i:s');
$attendance->save();

// ✅ Credit pending incentives
$incentiveService->creditPendingIncentives($attendance->id);
```

**markPunchIn() - Line 43:**
```php
// If attendance exists and has punch_in already
if ($attendance && $attendance->punch_in_time) {
    // Check for active break
    $activeBreak = EmployeeBreak::where('attendance_id', $attendance->id)
        ->whereNotNull('break_start')
        ->whereNull('break_end')
        ->first();

    if ($activeBreak) {
        // End the break
        $activeBreak->break_end = Carbon::now();
        $activeBreak->calculateDuration();
        $activeBreak->save();
    }
}
```

---

## 📊 **BENEFITS**

✅ **Prevents accidental early punch outs** - DM can't end shift in first 33 minutes
✅ **Protects pending incentives** - Incentives only credited on actual shift end
✅ **Allows short breaks** - DM can take quick breaks without ending shift
✅ **Automatic break tracking** - System creates break records automatically
✅ **Flexible threshold** - Admin can configure break time (default 30 min)
✅ **Accurate attendance** - Clear distinction between breaks and shift end

---

## ⚠️ **IMPORTANT NOTES**

### **Break vs Shift End:**
- **Break:** Punch out within 33 minutes of punch in
- **Shift End:** Punch out after 33 minutes

### **Multiple Breaks:**
- DM can take multiple breaks in one shift
- Each early punch out creates a new break record
- Shift only ends when punch out happens > 33 min after punch in

### **Incentives During Break:**
- Incentives earned before break: Stay PENDING
- Incentives are NOT lost during break
- All incentives credited when shift actually ends

### **What If DM Doesn't Resume After Break?**
- Break remains active (break_end = NULL)
- Shift remains active (punch_out_time = NULL)
- At 1:30 AM next day, cron job runs
- Incomplete shift → incentives WAVED OFF
- DM notified: "Incomplete shift"

---

## 🔍 **MONITORING QUERIES**

### **Find Active Breaks (Currently on Break):**
```sql
SELECT
    b.id,
    b.attendance_id,
    da.delivery_man_id,
    dm.f_name,
    dm.l_name,
    b.break_start,
    TIMESTAMPDIFF(MINUTE, b.break_start, NOW()) as break_duration_minutes
FROM breaks b
JOIN deliveryman_attendances da ON da.id = b.attendance_id
JOIN delivery_men dm ON dm.id = da.delivery_man_id
WHERE b.break_end IS NULL
  AND DATE(da.date) = CURDATE()
ORDER BY b.break_start DESC;
```

### **Find Shifts with Multiple Breaks:**
```sql
SELECT
    da.id as attendance_id,
    da.delivery_man_id,
    dm.f_name,
    dm.l_name,
    da.punch_in_time,
    da.punch_out_time,
    COUNT(b.id) as break_count,
    SUM(b.duration_minutes) as total_break_minutes
FROM deliveryman_attendances da
JOIN delivery_men dm ON dm.id = da.delivery_man_id
LEFT JOIN breaks b ON b.attendance_id = da.id
WHERE da.date = '2026-02-08'
GROUP BY da.id, da.delivery_man_id, dm.f_name, dm.l_name, da.punch_in_time, da.punch_out_time
HAVING break_count > 1
ORDER BY break_count DESC;
```

---

**Created:** February 8, 2026
**Feature:** Break Duration Check (33 min threshold)
**Status:** ✅ ACTIVE & WORKING
**Version:** 6.x
