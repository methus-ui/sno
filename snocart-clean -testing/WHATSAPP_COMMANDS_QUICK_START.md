# WhatsApp Commands & Seeder - Quick Start Guide

## Files Created

### 1. Database Seeder
- **File:** `database/seeders/WhatsAppSegmentsSeeder.php`
- **Purpose:** Seeds 16 predefined customer segments into `wa_customer_segments` table
- **Segments:** inactive_7d, inactive_15d, inactive_30d, inactive_60d, vip, high_spenders, at_risk, frequent_buyers, one_time_buyers, new_customers, milestone_5th, milestone_10th, milestone_50th, cart_abandoners, first_time_discount_users, birthday_customers

### 2. Console Commands
- **WhatsAppSeedSegments.php** - `php artisan whatsapp:seed-segments`
- **WhatsAppRefreshSegments.php** - `php artisan whatsapp:refresh-segments`
- **WhatsAppProcessScheduled.php** - `php artisan whatsapp:process-scheduled`

### 3. Kernel Registration
- **File:** `app/Console/Kernel.php`
- **Commands registered:** All 3 WhatsApp commands added to $commands array
- **Scheduled tasks:**
  - Daily segment refresh at 3 AM
  - Process scheduled campaigns every minute

---

## Quick Commands

### Initial Setup
```bash
# Seed all 16 predefined segments
php artisan whatsapp:seed-segments

# Refresh all segment counts (calculate actual customer numbers)
php artisan whatsapp:refresh-segments --all
```

### Regular Operations
```bash
# Refresh all segments
php artisan whatsapp:refresh-segments --all

# Refresh specific segment by ID
php artisan whatsapp:refresh-segments --segment-id=5

# Process scheduled campaigns (manually)
php artisan whatsapp:process-scheduled
```

---

## Command Details

### 1. whatsapp:seed-segments

**Purpose:** Seeds 16 predefined customer segments

**Usage:**
```bash
php artisan whatsapp:seed-segments
```

**What it does:**
- Creates/updates 16 predefined segments in `wa_customer_segments` table
- Sets initial `customer_count` to 0
- Attempts to refresh cache for each segment
- Uses `updateOrInsert()` to prevent duplicates (safe to run multiple times)

**Output:**
```
Seeding WhatsApp customer segments...
✓ Seeded segment: Inactive 7 days (inactive_7d)
  → Cache refreshed for Inactive 7 days
✓ Seeded segment: VIP Customers (vip)
  → Cache refreshed for VIP Customers
...
✓ Successfully seeded 16 WhatsApp customer segments
```

---

### 2. whatsapp:refresh-segments

**Purpose:** Recalculate customer counts for segments and refresh cache

**Usage:**
```bash
# Refresh all segments (default)
php artisan whatsapp:refresh-segments --all

# Refresh specific segment by ID
php artisan whatsapp:refresh-segments --segment-id=3

# No options = defaults to --all
php artisan whatsapp:refresh-segments
```

**What it does:**
- Queries database to count customers matching segment filters
- Updates `customer_count` in `wa_customer_segments` table
- Refreshes Redis/cache for fast API responses
- Shows before/after counts and processing time

**Output for single segment:**
```
Refreshing segment: VIP Customers (ID: 5)
✓ Segment refreshed successfully
+------------------+--------+
| Metric           | Value  |
+------------------+--------+
| Old Count        | 0      |
| New Count        | 342    |
| Change           | +342   |
| Processing Time  | 0.58s  |
+------------------+--------+
```

**Output for all segments:**
```
Refreshing 16 segments...
[Progress bar]

+---------------------------+--------------------------+-----------+-----------+--------+-------+--------+
| Segment                   | Slug                     | Old Count | New Count | Change | Time  | Status |
+---------------------------+--------------------------+-----------+-----------+--------+-------+--------+
| Inactive 7 days           | inactive_7d              | 0         | 1,234     | +1,234 | 0.45s | ✓      |
| Inactive 15 days          | inactive_15d             | 0         | 876       | +876   | 0.38s | ✓      |
| VIP Customers             | vip                      | 0         | 342       | +342   | 0.58s | ✓      |
...
+---------------------------+--------------------------+-----------+-----------+--------+-------+--------+

Summary:
  ✓ Successful: 16
  Total customers in segments: 15,432
  Total processing time: 7.84s
```

---

### 3. whatsapp:process-scheduled

**Purpose:** Dispatch scheduled campaigns that are due to be sent

**Usage:**
```bash
php artisan whatsapp:process-scheduled
```

**What it does:**
- Finds campaigns with `status='draft'` AND `scheduled_at <= now()`
- Updates status to `queued`
- Dispatches `SendWhatsAppCampaignJob` to queue
- Logs each dispatched campaign
- If campaign dispatch fails, sets status to `failed`

**Output:**
```
Checking for scheduled WhatsApp campaigns...
Processing 3 scheduled campaigns...

✓ Dispatched campaign: Weekend Sale (ID: 12)
✓ Dispatched campaign: New Product Launch (ID: 15)
✓ Dispatched campaign: VIP Exclusive Offer (ID: 18)

Summary:
  ✓ Processed: 3
```

**When no campaigns due:**
```
Checking for scheduled WhatsApp campaigns...
No scheduled campaigns due at this time
```

---

## Scheduled Tasks (Automatic)

### In Laravel Task Scheduler (Kernel.php)

```php
// Refresh all segment counts - Daily at 3:00 AM
$schedule->command('whatsapp:refresh-segments --all')
         ->dailyAt('03:00')
         ->withoutOverlapping();

// Process scheduled campaigns - Every minute
$schedule->command('whatsapp:process-scheduled')
         ->everyMinute()
         ->withoutOverlapping();
```

**To enable scheduled tasks, add to crontab:**
```bash
* * * * * cd /var/www/html/new_public/new && php artisan schedule:run >> /dev/null 2>&1
```

---

## Segment Details

### Predefined Segments (16 total)

| Slug | Name | Filter Criteria |
|------|------|-----------------|
| `inactive_7d` | Inactive 7 days | Last order 7-14 days ago |
| `inactive_15d` | Inactive 15 days | Last order 15-29 days ago |
| `inactive_30d` | Inactive 30 days | Last order 30-59 days ago |
| `inactive_60d` | Inactive 60+ days (churned) | Last order 60+ days ago |
| `vip` | VIP Customers | Lifetime spend >₹10,000 |
| `high_spenders` | High Spenders | Lifetime spend ₹5,000-₹9,999 |
| `at_risk` | At Risk Customers | 3+ orders but inactive 14+ days |
| `frequent_buyers` | Frequent Buyers | 5+ orders in last 30 days |
| `one_time_buyers` | One-Time Buyers | Exactly 1 order ever |
| `new_customers` | New Customers | Joined in last 7 days |
| `milestone_5th` | 5th Order Milestone | Just placed 5th order (within 24h) |
| `milestone_10th` | 10th Order Milestone | Just placed 10th order (within 24h) |
| `milestone_50th` | 50th Order Milestone | Just placed 50th order (within 24h) |
| `cart_abandoners` | Cart Abandoners | Items in cart, no order in 24h |
| `first_time_discount_users` | First-Time Discount Users | First order used coupon |
| `birthday_customers` | Birthday This Month | Birthday in current month |

---

## Error Handling

All commands include:
- Try-catch blocks for error handling
- Logging to `storage/logs/laravel.log`
- Proper exit codes (0 = success, 1 = error)
- User-friendly error messages

**Check logs:**
```bash
tail -f storage/logs/laravel.log | grep -i whatsapp
```

---

## Testing Commands

```bash
# Test seeding
php artisan whatsapp:seed-segments

# Test refresh (single segment)
php artisan whatsapp:refresh-segments --segment-id=1

# Test refresh (all segments)
php artisan whatsapp:refresh-segments --all

# Test scheduled processing (should show "No campaigns due" if none scheduled)
php artisan whatsapp:process-scheduled
```

---

## Production Workflow

1. **Initial Setup (One-time):**
   ```bash
   php artisan whatsapp:seed-segments
   php artisan whatsapp:refresh-segments --all
   ```

2. **Daily Automatic Refresh (via cron):**
   - Segments refresh at 3 AM daily
   - Scheduled campaigns process every minute

3. **Manual Refresh (when needed):**
   ```bash
   # After major data changes or bulk customer imports
   php artisan whatsapp:refresh-segments --all
   ```

4. **Campaign Workflow:**
   - Admin creates campaign, sets `scheduled_at`
   - Cron runs `whatsapp:process-scheduled` every minute
   - When `scheduled_at` is reached, campaign dispatches to queue
   - Job sends messages via Gupshup API

---

## Troubleshooting

### Command not found
```bash
# Clear config cache
php artisan config:clear
php artisan cache:clear

# List all commands
php artisan list whatsapp
```

### Segment counts not updating
```bash
# Force refresh
php artisan whatsapp:refresh-segments --all

# Check logs
tail -50 storage/logs/laravel.log
```

### Scheduled campaigns not processing
```bash
# Check if cron is running
php artisan schedule:run

# Manually trigger
php artisan whatsapp:process-scheduled

# Check campaign status in database
mysql> SELECT id, name, status, scheduled_at FROM wa_campaigns WHERE status='draft' AND scheduled_at IS NOT NULL;
```

---

## Next Steps

1. ✅ Run initial seeding: `php artisan whatsapp:seed-segments`
2. ✅ Refresh segment counts: `php artisan whatsapp:refresh-segments --all`
3. ✅ Verify segments in admin panel: `/admin/whatsapp/segments`
4. ✅ Set up cron for scheduled tasks
5. ✅ Test creating a scheduled campaign
6. ✅ Monitor logs for first few campaigns

---

## Files Modified

- `database/seeders/WhatsAppSegmentsSeeder.php` - NEW
- `app/Console/Commands/WhatsAppSeedSegments.php` - NEW
- `app/Console/Commands/WhatsAppRefreshSegments.php` - NEW
- `app/Console/Commands/WhatsAppProcessScheduled.php` - NEW
- `app/Console/Kernel.php` - UPDATED (added 3 commands to $commands array, 2 scheduled tasks)

---

**Author:** Claude Code
**Date:** 2026-03-20
**Phase:** 7 - Seeder & Console Commands Complete
