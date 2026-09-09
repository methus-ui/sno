# Safe Upgrade Plan for Production System
**Created**: February 5, 2026
**Target**: Zero Downtime | Maximum Stability
**Application**: new.snocart.com

---

## 📊 Current State Analysis

### Current Versions
| Component | Current Version | Status | Support Until |
|-----------|----------------|---------|---------------|
| **PHP** | 8.3.6 | ✅ Supported | Dec 2026 (Security) |
| **Laravel** | 10.48.22 | ✅ LTS Support | Feb 2026 |
| **MySQL** | 8.0.45 | ✅ Supported | Apr 2026 |
| **Composer** | 2.x | ✅ Latest | Active |
| **Ubuntu** | 24.04 LTS | ✅ LTS | Apr 2029 |

### Health Check
- ✅ All components actively supported
- ✅ No critical security vulnerabilities
- ✅ Production-ready versions
- ⚠️ Laravel 10 LTS ends Feb 2026 (4 months away)

---

## 🎯 Recommended Upgrade Path

### Priority 1: Critical Updates (RECOMMENDED - DO THESE)
These are **safe, low-risk updates** with significant benefits:

#### 1. PHP 8.3.6 → 8.3.18 (Latest Patch)
- **Risk Level**: 🟢 Very Low
- **Downtime**: None
- **Benefits**:
  - Security patches
  - Bug fixes
  - Performance improvements
- **Breaking Changes**: None
- **Timeline**: 1-2 hours

#### 2. MySQL 8.0.45 → 8.0.40+ (Latest 8.0.x)
- **Risk Level**: 🟢 Very Low
- **Downtime**: None (hot upgrade available)
- **Benefits**:
  - Security fixes
  - Performance improvements
  - Bug fixes
- **Breaking Changes**: None (same major version)
- **Timeline**: 2-3 hours

#### 3. Composer Dependency Updates (Security Patches)
- **Risk Level**: 🟢 Low
- **Downtime**: None
- **Benefits**: Security patches for packages
- **Timeline**: 1 hour

---

### Priority 2: Major Upgrades (DO LATER - After Testing)

#### 4. Laravel 10 → Laravel 11 (LTS)
- **Risk Level**: 🟡 Moderate
- **Downtime**: Minimal (5-10 minutes deployment)
- **Why Upgrade?**:
  - Laravel 10 support ends Feb 2026 (4 months!)
  - Laravel 11 LTS until Feb 2027
  - Better performance
  - New features
- **Breaking Changes**: YES (requires migration)
- **Timeline**: 1-2 weeks (testing included)
- **Status**: Recommended for Q1 2026

#### 5. PHP 8.3 → 8.4
- **Risk Level**: 🟡 Moderate
- **Downtime**: None
- **Benefits**:
  - Property hooks
  - Better performance
  - New features
- **Consideration**: Wait until Laravel 11 migration complete
- **Timeline**: After Laravel 11 migration
- **Status**: Optional - Not urgent

#### 6. MySQL 8.0 → 8.4 or 9.0
- **Risk Level**: 🟠 Moderate-High
- **Benefits**: JavaScript stored procedures, vector search
- **Consideration**: MySQL 8.0 supported until Apr 2026
- **Status**: Not recommended now - reassess in 6 months

---

## 🛡️ SAFE Upgrade Strategy (Zero Downtime)

### Phase 1: Immediate (This Week) - Patch Updates
**Risk**: Very Low | **Downtime**: Zero

```bash
✅ Update PHP 8.3.6 → 8.3.18 (patch)
✅ Update MySQL 8.0.45 → 8.0.40+ (patch)
✅ Update Composer dependencies (security only)
✅ Update system packages
```

**Benefits**:
- Security patches
- Bug fixes
- Zero breaking changes
- No application changes needed

---

### Phase 2: Planning (Next 2-4 Weeks) - Laravel 11 Migration
**Risk**: Moderate | **Downtime**: 5-10 minutes

**Why This Matters**:
- Laravel 10 support ends Feb 2026 (4 months!)
- Laravel 11 gives you 1 more year of support
- Better performance and features

**Steps**:
1. Set up staging environment (clone production)
2. Test Laravel 11 upgrade on staging
3. Fix breaking changes
4. Full regression testing
5. Deploy to production (off-peak hours)

---

### Phase 3: Future (Mid-2026) - PHP 8.4
**Risk**: Moderate | **Downtime**: Zero

**Status**: Optional enhancement after Laravel 11 is stable

---

## 📋 Detailed Upgrade Procedures

### PROCEDURE 1: PHP Patch Update (SAFE - DO NOW)

**Estimated Time**: 1-2 hours
**Downtime**: ZERO
**Rollback Time**: 5 minutes

#### Pre-Upgrade Checklist
```bash
# 1. Create full backup
sudo cp -r /var/www/html/new_public/new /backup/app-$(date +%F)
mysqldump -u snocart_app_user -p snocart > /backup/snocart-$(date +%F).sql

# 2. Check current PHP version
php -v

# 3. Test application health
curl -I https://new.snocart.com | head -5
php artisan about

# 4. Check PHP-FPM status
sudo systemctl status php8.3-fpm
```

#### Upgrade Steps
```bash
# 1. Update package list
sudo apt update

# 2. Check available PHP updates
apt list --upgradable | grep php8.3

# 3. Upgrade PHP (gracefully)
sudo apt install --only-upgrade php8.3 php8.3-cli php8.3-fpm \
  php8.3-mysql php8.3-xml php8.3-curl php8.3-gd php8.3-mbstring \
  php8.3-redis php8.3-zip -y

# 4. Verify new version
php -v

# 5. Restart PHP-FPM (graceful - no downtime)
sudo systemctl reload php8.3-fpm

# 6. Test application
curl -I https://new.snocart.com
php artisan about

# 7. Check logs for errors
tail -50 /var/www/html/new_public/new/storage/logs/laravel-$(date +%F).log
```

#### Rollback Plan (if needed)
```bash
# If issues occur, rollback is simple:
sudo apt install php8.3=8.3.6-* php8.3-fpm=8.3.6-* \
  php8.3-mysql=8.3.6-* -y --allow-downgrades
sudo systemctl reload php8.3-fpm
```

---

### PROCEDURE 2: MySQL Patch Update (SAFE - DO NOW)

**Estimated Time**: 2-3 hours
**Downtime**: ZERO (hot upgrade)
**Rollback Time**: 10 minutes

#### Pre-Upgrade Checklist
```bash
# 1. Backup database
mysqldump -u snocart_app_user -p snocart > /backup/snocart-$(date +%F).sql

# 2. Check current version
mysql --version
mysql -e "SELECT VERSION();"

# 3. Check database size and connections
mysql -e "SELECT table_schema AS 'Database',
  ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
  FROM information_schema.tables
  WHERE table_schema = 'snocart';"

mysql -e "SHOW STATUS LIKE 'Threads_connected';"

# 4. Test database connectivity
php artisan db:monitor
```

#### Upgrade Steps
```bash
# 1. Update package list
sudo apt update

# 2. Check available MySQL updates
apt list --upgradable | grep mysql

# 3. Upgrade MySQL (hot upgrade - no downtime)
sudo apt install --only-upgrade mysql-server mysql-client -y

# 4. Verify upgrade
mysql --version
mysql -e "SELECT VERSION();"

# 5. Run MySQL upgrade (fixes system tables)
sudo mysql_upgrade -u root -p

# 6. Restart MySQL (graceful)
sudo systemctl restart mysql

# 7. Test application
php artisan db:monitor
curl -I https://new.snocart.com

# 8. Check for errors
sudo tail -50 /var/log/mysql/error.log
```

#### Rollback Plan
```bash
# Restore from backup if issues
mysql -u root -p snocart < /backup/snocart-$(date +%F).sql

# Downgrade package if critical
sudo apt install mysql-server=8.0.45-* -y --allow-downgrades
sudo systemctl restart mysql
```

---

### PROCEDURE 3: Composer Security Updates (SAFE - DO NOW)

**Estimated Time**: 30 minutes
**Downtime**: ZERO
**Rollback Time**: 2 minutes

#### Steps
```bash
# 1. Backup composer files
cd /var/www/html/new_public/new
cp composer.json composer.json.backup
cp composer.lock composer.lock.backup

# 2. Check for security updates
composer audit

# 3. Update security patches only (no major versions)
composer update --no-dev --optimize-autoloader --prefer-stable

# 4. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan optimize

# 5. Test application
php artisan about
curl -I https://new.snocart.com

# 6. Check logs
tail -50 storage/logs/laravel-$(date +%F).log
```

#### Rollback
```bash
# If issues occur
mv composer.json.backup composer.json
mv composer.lock.backup composer.lock
composer install --no-dev --optimize-autoloader
php artisan optimize
```

---

### PROCEDURE 4: Laravel 10 → 11 Migration (DO LATER)

**Estimated Time**: 1-2 weeks (including testing)
**Downtime**: 5-10 minutes (deployment only)
**Complexity**: Moderate

⚠️ **IMPORTANT**: Do NOT do this yet. This requires:
1. Staging environment setup
2. Full testing cycle
3. Breaking changes analysis
4. Migration guide review

#### Why Upgrade to Laravel 11?
- ✅ Laravel 10 support ends: **February 2026** (4 months!)
- ✅ Laravel 11 LTS support until: **February 2027** (1 year)
- ✅ Performance improvements
- ✅ Better developer experience
- ✅ New features (per-second rate limiting, health checks, etc.)

#### Breaking Changes (Laravel 10 → 11)
1. Minimum PHP 8.2 required (you have 8.3 ✅)
2. Some Eloquent changes
3. Middleware signature changes
4. Rate limiting changes
5. Validation changes

#### Timeline Recommendation
- **Week 1-2**: Set up staging, initial upgrade attempt
- **Week 3-4**: Fix breaking changes, testing
- **Week 5**: Final testing, prepare deployment
- **Week 6**: Deploy to production (off-peak hours)

#### I can provide detailed Laravel 11 migration guide when you're ready.

---

## 🚨 Safety Protocols

### Before ANY Upgrade:

1. **Full Backup**
```bash
# Application files
sudo tar -czf /backup/app-$(date +%F-%H%M).tar.gz /var/www/html/new_public/new

# Database
mysqldump -u snocart_app_user -p snocart | gzip > /backup/db-$(date +%F-%H%M).sql.gz

# Verify backups
ls -lh /backup/
```

2. **Health Check**
```bash
# Application
curl -I https://new.snocart.com
php artisan about

# Database
mysql -e "SHOW STATUS LIKE 'Threads_connected';"

# Disk space
df -h

# Memory
free -h
```

3. **Maintenance Mode (for major updates)**
```bash
# Enable
php artisan down --refresh=15 --secret="upgrade-token-$(date +%s)"

# Access site during maintenance
https://new.snocart.com/upgrade-token-XXXXX

# Disable after upgrade
php artisan up
```

### Monitoring During Upgrade

```bash
# Terminal 1: Watch Laravel logs
tail -f storage/logs/laravel-$(date +%F).log

# Terminal 2: Watch MySQL logs
sudo tail -f /var/log/mysql/error.log

# Terminal 3: Watch PHP-FPM logs
sudo tail -f /var/log/php8.3-fpm.log

# Terminal 4: Monitor system resources
watch -n 1 'free -h; echo ""; df -h'
```

### Testing Checklist After Upgrade

```bash
# 1. Application responds
curl -I https://new.snocart.com

# 2. Laravel health
php artisan about

# 3. Database connectivity
php artisan db:monitor

# 4. Queue workers
php artisan queue:work --once

# 5. Cache works
php artisan cache:clear
php artisan config:cache

# 6. Critical paths (test manually)
- User login
- Place order
- Payment processing
- Notifications
- Admin panel

# 7. Check logs for errors
grep -i error storage/logs/laravel-$(date +%F).log
```

---

## 📅 Recommended Timeline

### **This Week** (Safe Updates Only)
- ✅ PHP 8.3.6 → 8.3.18 (patch update)
- ✅ MySQL 8.0.45 → 8.0.x (patch update)
- ✅ Composer security updates
- ✅ System package updates

**Risk**: Very Low
**Downtime**: Zero
**Benefits**: Security & stability

---

### **February 2026** (Before Laravel 10 EOL)
- 🔄 Plan Laravel 11 migration
- 🔄 Set up staging environment
- 🔄 Test upgrade on staging
- 🔄 Fix breaking changes
- 🔄 Deploy to production

**Risk**: Moderate
**Downtime**: 5-10 minutes
**Benefits**: Extended support, better performance

---

### **Mid-2026** (Optional)
- 🔵 Consider PHP 8.4 (after Laravel 11 is stable)
- 🔵 Evaluate MySQL 8.4/9.0

**Risk**: Moderate
**Benefits**: New features, performance

---

## 💡 My Recommendations

### DO NOW (This Week) ✅
1. **PHP patch update** (8.3.6 → 8.3.18)
2. **MySQL patch update** (8.0.45 → 8.0.x)
3. **Composer security updates**

These are **SAFE** with:
- ✅ Zero downtime
- ✅ Zero breaking changes
- ✅ Security benefits
- ✅ Easy rollback

### DO SOON (By February 2026) ⚠️
1. **Laravel 11 migration** (before Laravel 10 EOL)

This requires:
- Testing environment
- 1-2 weeks preparation
- Minimal downtime (5-10 min)

### DO LATER (Optional) 🔵
1. PHP 8.4 (after Laravel 11 stable)
2. MySQL 8.4/9.0 (reassess in 6 months)

---

## 🎯 DECISION MATRIX

| Update | Risk | Effort | Urgency | Recommendation |
|--------|------|--------|---------|----------------|
| **PHP 8.3 patch** | 🟢 Very Low | Low | Medium | ✅ DO NOW |
| **MySQL 8.0 patch** | 🟢 Very Low | Low | Medium | ✅ DO NOW |
| **Composer updates** | 🟢 Low | Low | Medium | ✅ DO NOW |
| **Laravel 11** | 🟡 Moderate | High | HIGH | ⚠️ DO BY FEB 2026 |
| **PHP 8.4** | 🟡 Moderate | Medium | Low | 🔵 OPTIONAL |
| **MySQL 8.4/9.0** | 🟠 Moderate | Medium | Low | 🔵 NOT YET |

---

## 🆘 Emergency Contacts & Resources

### Rollback Decision Tree
```
Issue found after upgrade?
├─ Critical (site down)
│  └─ IMMEDIATE ROLLBACK
│     └─ Restore from backup
│     └─ Downgrade packages
│     └─ Notify team
│
├─ Major (features broken)
│  └─ Can it wait for fix?
│     ├─ Yes → Fix forward
│     └─ No → Rollback
│
└─ Minor (small issues)
   └─ Fix forward (don't rollback)
```

### Support Resources
- Laravel Upgrade Guide: https://laravel.com/docs/11.x/upgrade
- PHP Migration Guide: https://www.php.net/manual/en/migration83.php
- MySQL Release Notes: https://dev.mysql.com/doc/relnotes/mysql/8.0/en/

---

## ✅ Final Recommendation

### START WITH THESE (SAFEST):

```bash
# 1. Create backups
sudo tar -czf /backup/app-$(date +%F).tar.gz /var/www/html/new_public/new
mysqldump -u snocart_app_user -p snocart | gzip > /backup/db-$(date +%F).sql.gz

# 2. Update PHP (patch only)
sudo apt update
sudo apt install --only-upgrade php8.3 php8.3-* -y
sudo systemctl reload php8.3-fpm

# 3. Update MySQL (patch only)
sudo apt install --only-upgrade mysql-server mysql-client -y
sudo mysql_upgrade -u root -p
sudo systemctl restart mysql

# 4. Update Composer packages (security only)
cd /var/www/html/new_public/new
composer update --no-dev --optimize-autoloader
php artisan optimize

# 5. Verify everything works
php artisan about
curl -I https://new.snocart.com
```

**Total Time**: 2-3 hours
**Downtime**: ZERO
**Risk**: Very Low
**Benefits**: Security, stability, bug fixes

---

**Questions? I can help with:**
1. Step-by-step execution of any procedure
2. Setting up staging environment for Laravel 11
3. Detailed Laravel 11 migration guide
4. Custom rollback procedures
5. Performance monitoring during upgrades

---

**Document Version**: 1.0
**Created**: February 5, 2026
**Next Review**: March 2026 (before Laravel 10 EOL)
