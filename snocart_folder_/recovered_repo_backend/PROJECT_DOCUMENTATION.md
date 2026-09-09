# 6ammart / Snocart - Project Documentation

> **Version:** 2.12
> **Framework:** Laravel 10
> **URL:** https://new.snocart.com
> **Last Updated:** January 2026

---

## Table of Contents
1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [Directory Structure](#directory-structure)
4. [Database Architecture](#database-architecture)
5. [API Architecture](#api-architecture)
6. [Authentication](#authentication)
7. [WebSocket Configuration](#websocket-configuration)
8. [Performance Optimization](#performance-optimization)
9. [Deployment & Maintenance](#deployment--maintenance)
10. [Common Issues & Solutions](#common-issues--solutions)

---

## Project Overview

**6ammart (Snocart)** is a multi-vendor e-commerce and delivery platform supporting 5 business modules:

| Module | Description | Features |
|--------|-------------|----------|
| **Food** | Restaurant ordering | Add-ons, availability times, cutlery |
| **Grocery** | Stock-based delivery | Stock management, units |
| **Pharmacy** | Medical products | Prescriptions, conditions, generic names |
| **E-commerce** | General products | Brands, 24/7 availability |
| **Parcel** | Delivery service | Package delivery only |

### Key Statistics
- **123 Models** - Eloquent models for all entities
- **163 Controllers** - Admin (56), Vendor (20+), API (54+)
- **190 Migrations** - Database schema (2022-2024)
- **15+ Payment Gateways** - Stripe, PayPal, Razorpay, BKash, etc.

---

## Technology Stack

```
Backend:
├── Laravel 10 (PHP 7.3/8.1)
├── MySQL Database
├── Redis (Cache, Queue, Session)
├── Laravel Passport (OAuth2)
└── Laravel WebSockets (Real-time)

Frontend Assets:
├── Laravel Mix
├── Laravel Echo + Pusher.js
└── Bootstrap 4/5

External Services:
├── Firebase (Auth, Push Notifications)
├── 15+ Payment Gateways
├── SMS Gateways
└── AWS S3 (Optional Storage)
```

---

## Directory Structure

```
/var/www/html/new_public/new/
├── app/
│   ├── Models/                    # 123 Eloquent models
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/             # 56 admin controllers
│   │   │   ├── Api/V1/            # 54 REST API controllers
│   │   │   ├── Api/V2/            # V2 API controllers
│   │   │   └── Vendor/            # Vendor portal
│   │   ├── Middleware/
│   │   └── Resources/             # API transformers
│   ├── Services/                  # 20+ business services
│   ├── Repositories/              # Data access layer
│   ├── CentralLogics/             # Shared helpers
│   │   ├── helpers.php            # General utilities
│   │   ├── order.php              # Order calculations
│   │   ├── store.php              # Store operations
│   │   ├── item.php               # Product search/filter
│   │   └── sms_module.php         # SMS sending
│   ├── Events/                    # Broadcast events
│   ├── WebSockets/Handler/        # WebSocket handlers
│   └── Mail/                      # 36 email templates
├── routes/
│   ├── admin.php                  # Admin routes (863 lines)
│   ├── vendor.php                 # Vendor routes
│   ├── api/v1/api.php             # API V1 (514 lines)
│   ├── api/v2/api.php             # API V2
│   └── channels.php               # WebSocket channels
├── config/
│   ├── websockets.php             # WebSocket config
│   ├── broadcasting.php           # Broadcasting config
│   ├── module.php                 # Module types
│   └── auth.php                   # Multi-guard auth
├── database/migrations/           # 190 migration files
└── public/                        # Web root
```

---

## Database Architecture

### Core Tables

**Users & Authentication:**
- `users` - Customers (wallet_balance, loyalty_point, referral)
- `admins` - Platform administrators
- `delivery_men` - Delivery partners
- `vendor_employees` - Store staff

**E-commerce:**
- `stores` - Vendor profiles (module_id, delivery settings)
- `items` - Products/menu items (variations, stock)
- `categories` - Product categorization
- `orders` - Order management
- `order_details` - Order line items
- `carts` - Shopping cart

**Financial:**
- `wallet_transactions` - Customer wallet
- `order_transactions` - Order breakdowns
- `admin_wallets` - Admin commissions
- `store_wallets` - Store earnings
- `disbursements` - Payouts

### Key Relationships
```
Order -> belongsTo -> User (customer)
Order -> belongsTo -> Store
Order -> belongsTo -> DeliveryMan
Order -> hasMany -> OrderDetail
OrderDetail -> belongsTo -> Item
Item -> belongsTo -> Store
Item -> belongsTo -> Category
Store -> belongsTo -> Zone
```

---

## API Architecture

### REST API V1 Endpoints

**Base URL:** `/api/v1/`

**Authentication:**
```
POST /auth/sign-up              # Customer registration
POST /auth/login                # Customer login
POST /auth/delivery-man/login   # Delivery partner login
POST /auth/vendor/login         # Vendor login
POST /auth/forgot-password      # Password reset
POST /auth/social-login         # Social authentication
```

**Customer Endpoints:**
```
GET  /module                    # Available modules
GET  /zone/list                 # Delivery zones
GET  /category/*                # Categories
GET  /store/*                   # Store details
GET  /search                    # Product search
POST /cart/*                    # Cart operations
POST /order/*                   # Order placement
GET  /delivery-man/*            # Delivery tracking
```

**Vendor Endpoints:**
```
GET  /vendor/item/*             # Product management
GET  /vendor/order/*            # Order management
GET  /vendor/report/*           # Sales reports
POST /vendor/business_plan      # Business model
```

### API Authentication
- **Method:** Bearer Token (Laravel Passport)
- **Header:** `Authorization: Bearer {token}`

---

## Authentication

### Multi-Guard System

| Guard | Provider | Use Case |
|-------|----------|----------|
| `web` | Session | Web dashboard |
| `admin` | Session | Admin panel |
| `vendor` | Session | Vendor portal |
| `api` | Passport | Mobile/API |
| `delivery_men` | Session | Delivery app |

### Middleware Stack
```php
'AdminMiddleware'        // Admin access
'VendorMiddleware'       // Vendor access
'ModulePermissionMiddleware'  // Module-based access
'LocalizationMiddleware' // Language switching
'auth:api'               // API authentication
```

---

## WebSocket Configuration

### Current Setup (Self-Hosted)

**Package:** `beyondcode/laravel-websockets`

**Config Files:**
- `/config/websockets.php` - WebSocket server config
- `/config/broadcasting.php` - Broadcasting config
- `.env` - Environment variables

### Required .env Variables

```env
# Broadcasting Driver
BROADCAST_DRIVER=pusher

# Pusher/WebSocket Settings (Self-Hosted)
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_HOST=new.snocart.com
PUSHER_PORT=6001
PUSHER_SCHEME=https
PUSHER_USE_TLS=true

# SSL Certificates
LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT="/etc/letsencrypt/live/new.snocart.com/fullchain.pem"
LARAVEL_WEBSOCKETS_SSL_LOCAL_PK="/etc/letsencrypt/live/new.snocart.com/privkey.pem"
```

### Supervisor Configuration

**WebSocket Server:** `/etc/supervisor/conf.d/websockets.conf`
```ini
[program:websockets]
command=/usr/bin/php /var/www/html/new_public/new/artisan websockets:serve --host=0.0.0.0 --port=6001
directory=/var/www/html/new_public/new
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/websockets.log
stopwaitsecs=10
```

**Queue Worker:** `/etc/supervisor/conf.d/laravel-worker.conf`
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/new_public/new/artisan queue:work redis --sleep=3 --tries=5 --timeout=120
directory=/var/www/html/new_public/new
autostart=true
autorestart=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
```

### Starting WebSocket Services
```bash
# Update supervisor configs
sudo supervisorctl reread
sudo supervisorctl update

# Start services
sudo supervisorctl start websockets
sudo supervisorctl start laravel-worker:*

# Check status
sudo supervisorctl status
```

### WebSocket Event Example
```php
// app/Events/DeliveryManLocationUpdated.php
class DeliveryManLocationUpdated implements ShouldBroadcast
{
    public $deliveryManId;
    public $location;

    public function __construct($deliveryManId, array $location)
    {
        $this->deliveryManId = $deliveryManId;
        $this->location = $location;
    }

    public function broadcastOn()
    {
        return [new Channel('delivery-man.' . $this->deliveryManId)];
    }

    public function broadcastAs()
    {
        return 'location.updated';
    }
}
```

---

## Performance Optimization

### Critical Issues Found

#### 1. N+1 Query Problems

**Location:** `app/CentralLogics/helpers.php`

| Line | Issue | Impact |
|------|-------|--------|
| 262-264 | Category query in loop | 30+ queries per product list |
| 309-311 | Nutrition/Allergy queries in loop | 2 queries per item |
| 268 | AddOn query per item | N queries for N items |
| 124 | Cart category lookup in loop | Multiple queries |

**Fix Example:**
```php
// BEFORE (N+1)
foreach ($category_ids as $value) {
    $category_name = Category::where('id', $value->id)->pluck('name');
}

// AFTER (Single Query)
$categoryNames = Category::whereIn('id', collect($category_ids)->pluck('id'))
    ->pluck('name', 'id');
foreach ($category_ids as $value) {
    $category_name = $categoryNames[$value->id] ?? null;
}
```

#### 2. Missing Eager Loading

**Files affected:**
- `ItemController.php:570` - Reviews need `with('item.translations')`
- `helpers.php:799` - Store config lazy loaded in loop
- `store.php:149-180` - Category queries inside `each()` callback

**Fix Example:**
```php
// BEFORE
Review::with(['customer', 'item'])->where(['item_id' => $item_id])

// AFTER
Review::with(['customer', 'item.translations'])->where(['item_id' => $item_id])
```

#### 3. Missing Route & Config Caching

```bash
# Run in production (after code changes):
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear caches when needed:
php artisan optimize:clear
```

### Performance Checklist

- [ ] Enable OPcache (currently enabled)
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Use Redis for cache, session, queue (currently configured)
- [ ] Enable queue workers for async processing
- [ ] Add database indexes (already in place)
- [ ] Fix N+1 queries in helpers.php
- [ ] Implement response caching for static data

### Quick Performance Wins

```bash
# 1. Cache configuration
php artisan config:cache

# 2. Cache routes
php artisan route:cache

# 3. Cache views
php artisan view:cache

# 4. Optimize autoloader
composer dump-autoload --optimize

# 5. Start queue workers
sudo supervisorctl start laravel-worker:*
```

---

## Deployment & Maintenance

### Production Deployment Checklist

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Run migrations
php artisan migrate --force

# 4. Clear and rebuild caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart services
sudo supervisorctl restart all
sudo systemctl reload php8.1-fpm
sudo systemctl reload nginx
```

### Log Locations

```
Laravel Logs:     /var/www/html/new_public/new/storage/logs/
WebSocket Logs:   /var/log/supervisor/websockets.log
Queue Worker:     /var/log/laravel-worker.log
Nginx Logs:       /var/log/nginx/
PHP-FPM Logs:     /var/log/php8.1-fpm.log
```

### Health Check Commands

```bash
# Check Laravel status
php artisan about

# Check database connection
php artisan tinker --execute="DB::connection()->getPdo();"

# Check Redis connection
php artisan tinker --execute="Redis::ping();"

# Check queue status
php artisan queue:monitor redis:default

# Check supervisor status
sudo supervisorctl status
```

---

## Common Issues & Solutions

### Issue 1: WebSocket Not Connecting

**Symptoms:** Real-time updates not working, location tracking fails

**Solution:**
```bash
# 1. Check if WebSocket server is running
sudo supervisorctl status websockets

# 2. Check logs
tail -f /var/log/supervisor/websockets.log

# 3. Restart WebSocket server
sudo supervisorctl restart websockets

# 4. Verify SSL certificates
ls -la /etc/letsencrypt/live/new.snocart.com/

# 5. Check port availability
netstat -tlnp | grep 6001
```

### Issue 2: Queue Jobs Not Processing

**Symptoms:** Emails not sending, broadcasts delayed

**Solution:**
```bash
# 1. Check queue worker status
sudo supervisorctl status laravel-worker:*

# 2. Start workers
sudo supervisorctl start laravel-worker:*

# 3. Check failed jobs
php artisan queue:failed

# 4. Retry failed jobs
php artisan queue:retry all
```

### Issue 3: Slow API Responses

**Symptoms:** API endpoints taking >2 seconds

**Solution:**
```bash
# 1. Enable caching
php artisan config:cache
php artisan route:cache

# 2. Check for N+1 queries (enable debug bar locally)
# Add to .env.local: DEBUGBAR_ENABLED=true

# 3. Check Redis connection
redis-cli ping

# 4. Check database slow queries
# Enable slow query log in MySQL
```

### Issue 4: Out of Memory Errors

**Symptoms:** 500 errors on large data exports

**Solution:**
```php
// In controller methods handling large data:
ini_set('memory_limit', '512M');
set_time_limit(300);

// Use chunking for large queries:
Order::chunk(100, function ($orders) {
    // Process orders
});
```

### Issue 5: Session Expiring Too Fast

**Symptoms:** Users logged out unexpectedly

**Solution:**
```env
# In .env, increase session lifetime (minutes)
SESSION_LIFETIME=120

# Ensure Redis is working for sessions
SESSION_DRIVER=redis
```

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `app/CentralLogics/helpers.php` | Core utility functions |
| `app/CentralLogics/order.php` | Order calculation logic |
| `config/module.php` | Module type definitions |
| `config/auth.php` | Authentication guards |
| `config/websockets.php` | WebSocket configuration |
| `routes/api/v1/api.php` | API V1 routes |
| `routes/admin.php` | Admin panel routes |

---

## Environment Variables Reference

```env
# Application
APP_NAME=Snocart
APP_ENV=production
APP_DEBUG=false
APP_URL=https://new.snocart.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache & Queue
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Broadcasting (Self-Hosted WebSockets)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-secret
PUSHER_HOST=new.snocart.com
PUSHER_PORT=6001
PUSHER_SCHEME=https

# SSL for WebSockets
LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT=/etc/letsencrypt/live/new.snocart.com/fullchain.pem
LARAVEL_WEBSOCKETS_SSL_LOCAL_PK=/etc/letsencrypt/live/new.snocart.com/privkey.pem
```

---

## Contact & Support

For issues with this codebase, check:
1. Laravel logs: `storage/logs/laravel.log`
2. WebSocket logs: `/var/log/supervisor/websockets.log`
3. Nginx error logs: `/var/log/nginx/error.log`

---

*Documentation generated: January 2026*
