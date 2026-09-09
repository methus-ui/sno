# WhatsApp Dashboard - Deployment Guide

**Version:** 1.0
**Date:** 2026-03-20
**Environment:** Production
**Status:** Ready for Deployment

---

## 📋 Pre-Deployment Checklist

### 1. Security Verification ✅
- [x] No hardcoded credentials in codebase
- [x] SSL verification enabled on all API calls
- [x] Standalone dashboard deleted (`/public/wa-dashboard/`)
- [x] Race conditions prevented with pessimistic locking
- [x] Command injection vulnerability fixed (exec() replaced with queues)

### 2. Database Verification ✅
- [x] All 6 tables created (`wa_campaign_recipients`, `wa_customer_segments`, `wa_segment_customers`, `wa_campaign_analytics`, `wa_customer_journey`, `wa_campaigns` extended)
- [x] All indexes created for performance
- [x] Foreign keys properly configured

### 3. Service Layer Verification ✅
- [x] WhatsAppApiService (SSL + rate limiting)
- [x] CustomerSegmentationService (16 predefined segments)
- [x] CampaignBuilderService (validation + scheduling)
- [x] CampaignAnalyticsService (ROI calculation)
- [x] CustomerJourneyService (behavior tracking)

### 4. Queue Jobs Verification ✅
- [x] SendWhatsAppCampaignJob (campaign orchestration)
- [x] SendWhatsAppMessageJob (individual messages)
- [x] CalculateCampaignAnalyticsJob (ROI metrics)

### 5. Controllers & Routes Verification ✅
- [x] DashboardController (main dashboard)
- [x] CustomerController (19k+ customers)
- [x] SegmentController (segment manager)
- [x] CampaignController (campaign builder)
- [x] Routes added to `routes/admin.php`

### 6. Views & UI Verification ✅
- [x] Dashboard view (stats + charts)
- [x] Campaign list view (DataTables)
- [x] Campaign create view (3-step wizard)
- [x] Campaign analytics view (Chart.js)
- [x] Customer list view (filters)
- [x] Segment manager view (16 predefined + custom)

### 7. Seeder & Commands Verification ✅
- [x] WhatsAppSegmentsSeeder (16 predefined segments)
- [x] whatsapp:seed-segments command
- [x] whatsapp:refresh-segments command
- [x] whatsapp:process-scheduled command
- [x] Scheduled tasks added to Kernel.php

---

## 🚀 Deployment Steps

### Step 1: Backup Current System

```bash
# Backup database
mysqldump -u root -p snocart > /var/backups/snocart_before_whatsapp_$(date +%Y%m%d_%H%M%S).sql

# Backup .env file
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Backup codebase
tar -czf /var/backups/codebase_before_whatsapp_$(date +%Y%m%d_%H%M%S).tar.gz \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='storage/logs' \
  /var/www/html/new_public/new/
```

### Step 2: Run Pre-Deployment Tests

```bash
cd /var/www/html/new_public/new

# Run comprehensive test suite
php scripts/test-whatsapp-dashboard-complete.php

# Expected output: "SUCCESS: All tests passed! System is production-ready."
```

If tests fail, fix issues before proceeding.

### Step 3: Configure Environment Variables

Ensure `.env` has WhatsApp configuration:

```bash
# WhatsApp Business API Configuration
WHATSAPP_ENABLED=true
WHATSAPP_API_TOKEN=your_token_here
WHATSAPP_PHONE_NUMBER_ID=886857197839727
WHATSAPP_API_VERSION=v19.0
WHATSAPP_API_BASE_URL=https://graph.facebook.com
WHATSAPP_RATE_LIMIT=5
WHATSAPP_BURST_LIMIT=50
```

**CRITICAL:** Replace `your_token_here` with actual WhatsApp API token.

### Step 4: Clear All Caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear
php artisan optimize
```

### Step 5: Seed Predefined Segments

```bash
# Seed 16 predefined customer segments
php artisan whatsapp:seed-segments

# Expected output: "16 predefined segments seeded successfully"

# Refresh segment caches
php artisan whatsapp:refresh-segments --all

# Expected output: Shows customer count for each segment
```

### Step 6: Configure Queue Workers

**Option A: Supervisor (Recommended for Production)**

Create supervisor config: `/etc/supervisor/conf.d/laravel-whatsapp-worker.conf`

```ini
[program:laravel-whatsapp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/new_public/new/artisan queue:work --queue=whatsapp,whatsapp-messages,whatsapp-analytics --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/log/supervisor/laravel-whatsapp-worker.log
stopwaitsecs=3600
```

Reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-whatsapp-worker:*
sudo supervisorctl status
```

**Option B: Screen (Development/Testing)**

```bash
screen -S whatsapp-queue
php artisan queue:work --queue=whatsapp,whatsapp-messages,whatsapp-analytics --sleep=3 --tries=3
# Press Ctrl+A then D to detach

# Reattach later with:
screen -r whatsapp-queue
```

### Step 7: Setup Scheduled Tasks

Verify cron entry exists:

```bash
crontab -e

# Add this line if not present:
* * * * * cd /var/www/html/new_public/new && php artisan schedule:run >> /dev/null 2>&1
```

Verify scheduled tasks:
```bash
php artisan schedule:list | grep whatsapp

# Expected output:
# 0 3 * * * whatsapp:refresh-segments --all
# * * * * * whatsapp:process-scheduled
```

### Step 8: Test with Small Campaign

```bash
# Send test campaign to 5 phones
# 1. Go to admin panel: https://new.snocart.com/admin/whatsapp
# 2. Click "Create Campaign"
# 3. Select a small segment (e.g., "New Customers" with <10 people)
# 4. Upload test image
# 5. Add caption
# 6. Click "Launch Campaign"

# Monitor queue processing:
php artisan queue:monitor

# Check campaign status:
mysql -u root -p snocart -e "SELECT * FROM wa_campaigns ORDER BY id DESC LIMIT 1\G"

# Check recipient status:
mysql -u root -p snocart -e "SELECT status, COUNT(*) FROM wa_campaign_recipients GROUP BY status"
```

### Step 9: Verify Analytics Calculation

```bash
# Wait 5 minutes after campaign completes
sleep 300

# Check analytics table
mysql -u root -p snocart -e "SELECT * FROM wa_campaign_analytics ORDER BY campaign_id DESC LIMIT 1\G"

# Should show:
# - delivery_rate (sent/total * 100)
# - read_rate (read/delivered * 100)
# - orders_placed_24h (if any)
# - revenue_generated_24h
# - roi
```

### Step 10: Production Deployment Verification

```bash
# Run comprehensive test again
php scripts/test-whatsapp-dashboard-complete.php

# Check queue workers running
sudo supervisorctl status | grep whatsapp

# Check cron is working
grep CRON /var/log/syslog | grep schedule:run | tail -5

# Check no errors in Laravel logs
tail -50 storage/logs/laravel.log | grep ERROR

# Check admin panel accessible
curl -I https://new.snocart.com/admin/whatsapp
# Expected: HTTP 200 or 302 (redirect to login)
```

---

## 🔍 Monitoring & Maintenance

### Daily Monitoring

```bash
# Check failed queue jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Check segment refresh
mysql -u root -p snocart -e "SELECT name, customer_count, last_calculated_at FROM wa_customer_segments ORDER BY last_calculated_at DESC LIMIT 10"

# Check campaign stats
mysql -u root -p snocart -e "SELECT COUNT(*) as total, status, SUM(sent) as total_sent FROM wa_campaigns GROUP BY status"
```

### Weekly Maintenance

```bash
# Refresh all segments manually
php artisan whatsapp:refresh-segments --all

# Clear old failed jobs (older than 7 days)
php artisan queue:prune-failed --hours=168

# Check disk space
df -h | grep storage

# Optimize database
php artisan optimize:clear
```

### Monthly Maintenance

```bash
# Archive old campaigns (optional)
mysql -u root -p snocart -e "
CREATE TABLE wa_campaigns_archive LIKE wa_campaigns;
INSERT INTO wa_campaigns_archive SELECT * FROM wa_campaigns WHERE finished_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
DELETE FROM wa_campaigns WHERE finished_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
"

# Clean up old journey records (optional, keep last 3 months)
mysql -u root -p snocart -e "DELETE FROM wa_customer_journey WHERE event_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
```

---

## 🛠️ Troubleshooting

### Issue: Campaigns not sending

**Symptoms:** Campaign stuck in "running" status, no messages sent

**Solutions:**
1. Check queue workers running:
   ```bash
   sudo supervisorctl status | grep whatsapp
   ```

2. Check failed jobs:
   ```bash
   php artisan queue:failed
   ```

3. Check rate limiting:
   ```bash
   redis-cli GET whatsapp_rate_limit
   ```

4. Check WhatsApp API status:
   ```bash
   curl -H "Authorization: Bearer $WHATSAPP_API_TOKEN" \
     "https://graph.facebook.com/v19.0/$WHATSAPP_PHONE_NUMBER_ID"
   ```

---

### Issue: Segments showing 0 customers

**Symptoms:** All segments show `customer_count = 0`

**Solutions:**
1. Refresh segments:
   ```bash
   php artisan whatsapp:refresh-segments --all
   ```

2. Check users have valid phone numbers:
   ```bash
   mysql -u root -p snocart -e "SELECT COUNT(*) FROM users WHERE phone IS NOT NULL AND phone != '' AND status = 1"
   ```

3. Check segment filters are correct:
   ```bash
   mysql -u root -p snocart -e "SELECT slug, filters FROM wa_customer_segments WHERE type='predefined' LIMIT 5"
   ```

---

### Issue: Analytics not calculating

**Symptoms:** `wa_campaign_analytics` table empty or ROI = 0

**Solutions:**
1. Check job ran:
   ```bash
   php artisan queue:monitor | grep CalculateCampaignAnalyticsJob
   ```

2. Manually trigger analytics:
   ```bash
   php artisan tinker
   >>> dispatch(new \App\Jobs\CalculateCampaignAnalyticsJob(1));
   ```

3. Check campaign finished:
   ```bash
   mysql -u root -p snocart -e "SELECT id, status, finished_at FROM wa_campaigns ORDER BY id DESC LIMIT 5"
   ```

---

### Issue: Queue workers dying

**Symptoms:** Supervisor shows "FATAL" or "STOPPED"

**Solutions:**
1. Check supervisor logs:
   ```bash
   tail -100 /var/log/supervisor/laravel-whatsapp-worker.log
   ```

2. Check Laravel logs:
   ```bash
   tail -100 storage/logs/laravel.log
   ```

3. Restart workers:
   ```bash
   sudo supervisorctl restart laravel-whatsapp-worker:*
   ```

4. Check memory usage:
   ```bash
   free -h
   top -u www-data
   ```

---

## 📊 Performance Optimization

### Database Indexing

All required indexes already created during migration. Verify:

```bash
mysql -u root -p snocart -e "SHOW INDEX FROM wa_campaign_recipients"
mysql -u root -p snocart -e "SHOW INDEX FROM wa_customer_journey"
mysql -u root -p snocart -e "SHOW INDEX FROM wa_segment_customers"
```

### Queue Optimization

Increase queue worker count if processing is slow:

```ini
# In supervisor config: /etc/supervisor/conf.d/laravel-whatsapp-worker.conf
numprocs=5  # Increase from 3 to 5
```

### Cache Optimization

Use Redis for caching (if not already):

```bash
# .env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

---

## 🔒 Security Best Practices

### 1. Protect WhatsApp API Token

```bash
# Ensure .env is not accessible
chmod 600 .env
chown www-data:www-data .env

# Add to .gitignore
echo ".env" >> .gitignore
```

### 2. Rate Limiting Protection

Current limits:
- 5 messages per second (configurable in `config/whatsapp.php`)
- Burst limit: 50 messages

WhatsApp Business API has daily limits:
- Tier 1: 1,000 messages/day
- Tier 2: 10,000 messages/day
- Tier 3: 100,000 messages/day

Monitor daily usage:
```bash
mysql -u root -p snocart -e "
SELECT DATE(created_at) as date, COUNT(*) as messages_sent
FROM wa_campaign_recipients
WHERE status IN ('sent', 'delivered', 'read')
GROUP BY DATE(created_at)
ORDER BY date DESC
LIMIT 7
"
```

### 3. Customer Privacy

Only send to customers who opted in:
```php
// In segment filters, always include:
'opted_in_marketing' => true
```

### 4. Regular Security Audits

```bash
# Run security verification monthly
php scripts/verify-whatsapp-security-fixes.php

# Check for hardcoded secrets
grep -r "EAAR" app/ --exclude-dir=vendor
grep -r "886857197839727" app/ --exclude-dir=vendor
```

---

## 📈 Success Metrics

### Track These KPIs

1. **Delivery Rate:** (delivered / sent) * 100
   - Target: >95%

2. **Read Rate:** (read / delivered) * 100
   - Target: >60%

3. **Conversion Rate (24h):** (orders / sent) * 100
   - Target: >5%

4. **ROI:** (revenue - cost) / cost * 100
   - Target: >300%

5. **Queue Processing Time:** Average time to send 1000 messages
   - Target: <10 minutes

6. **Failed Rate:** (failed / sent) * 100
   - Target: <2%

### Monthly Reporting

```bash
# Generate monthly report
mysql -u root -p snocart -e "
SELECT
  DATE_FORMAT(started_at, '%Y-%m') as month,
  COUNT(*) as campaigns,
  SUM(total) as total_recipients,
  SUM(sent) as total_sent,
  SUM(failed) as total_failed,
  ROUND(SUM(sent) / SUM(total) * 100, 2) as delivery_rate_pct
FROM wa_campaigns
WHERE started_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(started_at, '%Y-%m')
ORDER BY month DESC
"
```

---

## 🎯 Next Steps

1. **Monitor First Week:**
   - Check queue processing daily
   - Monitor failed jobs
   - Review analytics accuracy

2. **Optimize Segments:**
   - Analyze which segments perform best
   - Create custom segments based on performance

3. **A/B Testing:**
   - Test different message formats
   - Test different send times
   - Test different segment combinations

4. **Scale Up:**
   - Increase queue workers if needed
   - Request higher WhatsApp API tier if hitting limits
   - Consider geographic segmentation

5. **Feature Enhancements:**
   - Add scheduled campaigns UI
   - Add campaign templates
   - Add automated workflows
   - Add customer journey visualizations

---

## 📞 Support

For issues or questions:
- Check troubleshooting section above
- Review logs: `storage/logs/laravel.log`
- Run test suite: `php scripts/test-whatsapp-dashboard-complete.php`
- Check queue status: `php artisan queue:monitor`

---

**Deployment Completed:** _______________
**Deployed By:** _______________
**Verification Status:** _______________

---

## ✅ Post-Deployment Checklist

- [ ] All tests passing (100%)
- [ ] Queue workers running (3+ workers)
- [ ] Cron scheduled tasks active
- [ ] Segments seeded and refreshed
- [ ] Test campaign sent successfully
- [ ] Analytics calculating correctly
- [ ] No errors in logs
- [ ] Admin panel accessible
- [ ] Security verification passed
- [ ] Backup completed
- [ ] Team trained on new features

---

**Document Version:** 1.0
**Last Updated:** 2026-03-20
**Status:** Production Ready ✅
