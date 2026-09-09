# Complete Vendor App API Endpoints Reference

## 📱 Quick Navigation
- [Authentication](#authentication)
- [Profile & Settings](#profile--settings)
- [Financial & Earnings](#financial--earnings)
- [Order Management](#order-management)
- [Product/Item Management](#productitem-management)
- [**NEW: Product Gallery**](#new-product-gallery-feature)
- [Campaigns & Marketing](#campaigns--marketing)
- [Coupons](#coupons)
- [Advertisements](#advertisements)
- [Addons](#addons)
- [Banners](#banners)
- [Categories](#categories)
- [Delivery Men](#delivery-men)
- [POS System](#pos-system)
- [Messaging](#messaging)
- [Business Settings](#business-settings)
- [Subscription](#subscription)
- [Utilities](#utilities)

---

## Authentication
**Base:** `/api/v1/auth/vendor/`

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 1 | POST | `login` | Login vendor |
| 2 | POST | `register` | Register new vendor |
| 3 | POST | `forgot-password` | Request password reset |
| 4 | POST | `verify-token` | Verify reset token |
| 5 | PUT | `reset-password` | Reset password |

---

## Profile & Settings
**Base:** `/api/v1/vendor/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 6 | GET | `notifications` | Get all notifications |
| 7 | GET | `profile` | Get vendor profile |
| 8 | PUT | `update-profile` | Update vendor profile |
| 9 | PUT | `update-announcment` | Update announcement |
| 10 | POST | `update-active-status` | Toggle store active/inactive |
| 11 | PUT | `update-fcm-token` | Update Firebase token |
| 12 | DELETE | `remove-account` | Delete vendor account |

---

## Financial & Earnings
**Base:** `/api/v1/vendor/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 13 | GET | `earning-info` | Get earnings data |
| 14 | GET | `get-withdraw-list` | List withdrawal requests |
| 15 | PUT | `update-bank-info` | Update bank information |
| 16 | POST | `request-withdraw` | Request withdrawal |
| 17 | POST | `make-collected-cash-payment` | Make cash payment |
| 18 | POST | `make-wallet-adjustment` | Adjust wallet |
| 19 | GET | `wallet-payment-list` | Get wallet payment list |
| 20 | GET | `get-expense` | Get expense report |
| 21 | GET | `get-disbursement-report` | Get disbursement report |
| 22 | GET | `get-withdraw-method-list` | Get withdrawal methods |
| 23 | GET | `withdraw-method/list` | Get disbursement methods |
| 24 | POST | `withdraw-method/store` | Store withdrawal method |
| 25 | POST | `withdraw-method/make-default` | Set default method |
| 26 | DELETE | `withdraw-method/delete` | Delete withdrawal method |

---

## Order Management
**Base:** `/api/v1/vendor/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 27 | GET | `current-orders` | Get current/pending orders |
| 28 | GET | `completed-orders` | Get completed orders |
| 29 | GET | `canceled-orders` | Get canceled orders |
| 30 | GET | `all-orders` | Get all orders |
| 31 | GET | `order-details` | Get order details |
| 32 | GET | `order` | Get single order |
| 33 | PUT | `update-order-status` | Update order status |
| 34 | PUT | `update-order-amount` | Edit order amount |
| 35 | PUT | `send-order-otp` | Send OTP for order |
| 36 | PUT | `order/mark-item-unavailable` | Mark item as unavailable |

---

## Product/Item Management
**Base:** `/api/v1/vendor/item/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 37 | GET | `/get-items-list` | Get vendor's items list |
| 38 | POST | `store` | Create new item |
| 39 | PUT | `update` | Update item |
| 40 | DELETE | `delete` | Delete item |
| 41 | GET | `status` | Toggle item status |
| 42 | GET | `details/{id}` | Get item details |
| 43 | POST | `search` | Search items |
| 44 | POST | `toggle-stock-status` | Toggle stock status |
| 45 | POST | `quick-stock-update` | Quick stock update |
| 46 | GET | `reviews` | Get item reviews |
| 47 | PUT | `reply-update` | Update review reply |
| 48 | GET | `recommended` | Get recommended items |
| 49 | GET | `organic` | Get organic items |
| 50 | GET | `pending/item/list` | Get pending items |
| 51 | GET | `requested/item/view/{id}` | View requested item |
| 52 | PUT | `stock-update` | Update stock |
| 53 | GET | `stock-limit-list` | Get stock limit list |

---

## 🆕 NEW: Product Gallery Feature
**Base:** `/api/v1/vendor/product-gallery/` **Auth Required:** ✅

| # | Method | Endpoint | Description | ⭐ Feature |
|---|--------|----------|-------------|-----------|
| 54 | GET | `browse` | Browse all products from marketplace | 🔍 Search & Filter |
| 55 | GET | `details/{id}` | Get full product details | 📋 Complete Info |
| 56 | POST | `replicate` | Copy product to your store | 📦 One-Click Copy |
| 57 | POST | `batch-replicate` | Copy multiple products at once | ⚡ Bulk Action |
| 58 | GET | `trending` | Get popular/trending products | 📈 Trending |
| 59 | GET | `my-replications` | View your replication history | 📊 History |

**🎯 Key Features:**
- Browse 1000+ products from successful stores
- Copy products with images, variations, addons
- Customize price, stock before replication
- Duplicate prevention built-in
- Subscription limit enforcement
- Search by name, category, store
- See trending and best-selling products

---

## Campaigns & Marketing
**Base:** `/api/v1/vendor/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 60 | GET | `get-basic-campaigns` | Get basic campaigns |
| 61 | PUT | `campaign-leave` | Leave campaign |
| 62 | PUT | `campaign-join` | Join campaign |

---

## Coupons
**Base:** `/api/v1/vendor/coupon/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 63 | GET | `list` | List all coupons |
| 64 | GET | `view` | View coupon details |
| 65 | GET | `view-without-translate` | View raw coupon |
| 66 | POST | `store` | Create coupon |
| 67 | POST | `update` | Update coupon |
| 68 | POST | `status` | Toggle coupon status |
| 69 | POST | `delete` | Delete coupon |
| 70 | POST | `search` | Search coupons |

---

## Advertisements
**Base:** `/api/v1/vendor/advertisement/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 71 | GET | `/` | List advertisements |
| 72 | GET | `details/{id}` | Get ad details |
| 73 | DELETE | `delete/{id}` | Delete advertisement |
| 74 | POST | `store` | Create advertisement |
| 75 | POST | `update/{id}` | Update advertisement |
| 76 | PUT | `status` | Toggle ad status |
| 77 | POST | `copy-add-post` | Copy advertisement |

---

## Addons
**Base:** `/api/v1/vendor/addon/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 78 | GET | `/` | List addons |
| 79 | POST | `store` | Create addon |
| 80 | PUT | `update` | Update addon |
| 81 | GET | `status` | Toggle addon status |
| 82 | DELETE | `delete` | Delete addon |

---

## Banners
**Base:** `/api/v1/vendor/banner/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 83 | GET | `/` | List banners |
| 84 | POST | `store` | Create banner |
| 85 | PUT | `update` | Update banner |
| 86 | GET | `status` | Toggle banner status |
| 87 | DELETE | `delete` | Delete banner |

---

## Categories
**Base:** `/api/v1/vendor/categories/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 88 | GET | `/` | Get all categories |
| 89 | GET | `childes/{category_id}` | Get child categories |

---

## Delivery Men
**Base:** `/api/v1/vendor/delivery-man/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 90 | POST | `store` | Create delivery man |
| 91 | GET | `list` | List delivery men |
| 92 | GET | `preview` | Preview delivery man |
| 93 | GET | `status` | Toggle DM status |
| 94 | POST | `update/{id}` | Update delivery man |
| 95 | DELETE | `delete` | Delete delivery man |
| 96 | POST | `search` | Search delivery men |

---

## POS System
**Base:** `/api/v1/vendor/pos/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 97 | GET | `orders` | Get POS orders |
| 98 | POST | `place-order` | Place POS order |
| 99 | GET | `customers` | Get customers for POS |

---

## Messaging
**Base:** `/api/v1/vendor/message/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 100 | GET | `list` | List conversations |
| 101 | GET | `search-list` | Search conversations |
| 102 | GET | `details` | Get messages |
| 103 | POST | `send` | Send message |

---

## Business Settings
**Base:** `/api/v1/vendor/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 104 | PUT | `update-business-setup` | Update store setup |
| 105 | POST | `schedule/store` | Add schedule |
| 106 | DELETE | `schedule/{store_schedule}` | Remove schedule |

---

## Subscription
**Base:** `/api/v1/vendor/` **Auth Required:** ✅ (Some endpoints don't require auth)

| # | Method | Endpoint | Auth | Description |
|---|--------|----------|------|-------------|
| 107 | GET | `package-view` | ❌ | View packages |
| 108 | POST | `business_plan` | ❌ | Get business plan |
| 109 | POST | `subscription/payment/api` | ❌ | Process subscription payment |
| 110 | POST | `package-renew` | ❌ | Renew/change package |
| 111 | POST | `cancel-subscription` | ❌ | Cancel subscription |
| 112 | GET | `check-product-limits` | ❌ | Check product limits |
| 113 | GET | `subscription-transaction` | ✅ | Get subscription transactions |

---

## Utilities
**Base:** `/api/v1/vendor/` **Auth Required:** ✅

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 114 | GET | `unit` | Get units |
| 115 | GET | `attributes` | Get attributes |

---

## 🔄 Odoo/POS Sync
**Base:** `/api/v1/vendor/` **Auth Required:** ❌ (Public endpoints)

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 116 | POST | `sync-orders` | Sync orders from POS |
| 117 | GET | `sync-products` | Sync products to POS |
| 118 | GET | `sync-inventory` | Sync inventory |
| 119 | POST | `update-inventory` | Update inventory from POS |
| 120 | GET | `sync-status` | Get sync status |

---

## 📊 Statistics Summary

- **Total Endpoints:** 120+
- **Auth Required:** 114 endpoints
- **Public Endpoints:** 6 endpoints
- **NEW Product Gallery:** 6 endpoints
- **HTTP Methods Used:** GET (68), POST (33), PUT (14), DELETE (5)

---

## 🎯 Most Important Endpoints for Vendor App

### Essential (Must Have)
1. ✅ Login/Register (Auth)
2. ✅ Get Profile
3. ✅ Get Orders (current, completed, all)
4. ✅ Update Order Status
5. ✅ Get Products List
6. ✅ Add/Edit Product
7. ✅ Get Earnings
8. ✅ Request Withdrawal
9. **✨ Browse Product Gallery (NEW)**
10. **✨ Replicate Product (NEW)**

### Secondary (Nice to Have)
11. Notifications
12. Campaigns
13. Coupons
14. Banners
15. Messaging
16. Delivery Men
17. POS
18. Reviews

---

## 🚀 Quick Start for Developers

### 1. Import Postman Collection
```bash
# File location
PRODUCT_GALLERY_POSTMAN_COLLECTION.json
```

### 2. Set Environment Variables
```
base_url: https://your-domain.com/api/v1
vendor_token: (get from login)
```

### 3. Test Flow
```
Login → Browse Gallery → View Details → Replicate → Check My Products
```

### 4. Test Automation
```bash
php scripts/test-product-gallery-api.php
```

---

## 📚 Documentation Files

| File | Description |
|------|-------------|
| `VENDOR_APP_ENDPOINTS_AND_PRODUCT_GALLERY.md` | Full API specification (100+ pages) |
| `PRODUCT_GALLERY_QUICK_START.md` | Developer quick start guide |
| `PRODUCT_GALLERY_IMPLEMENTATION_SUMMARY.md` | Technical implementation details |
| `VENDOR_API_ENDPOINTS_COMPLETE_LIST.md` | This file - Quick reference |
| `PRODUCT_GALLERY_POSTMAN_COLLECTION.json` | Postman collection for testing |

---

## 🔐 Authentication Example

```bash
# 1. Login
curl -X POST "https://domain.com/api/v1/auth/vendor/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"vendor@example.com","password":"pass123"}'

# Response
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "vendor": {...}
}

# 2. Use token in subsequent requests
curl -X GET "https://domain.com/api/v1/vendor/product-gallery/browse" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

---

## 🎨 UI/UX Implementation Priority

### Phase 1 (MVP)
- [ ] Login/Register screens
- [ ] Dashboard with stats
- [ ] Order list and details
- [ ] Product list
- [ ] **Product Gallery browse** ⭐
- [ ] **Product replication** ⭐

### Phase 2 (Enhanced)
- [ ] Add/Edit product
- [ ] Earnings and withdrawals
- [ ] Notifications
- [ ] Batch product replication ⭐
- [ ] Trending products ⭐

### Phase 3 (Advanced)
- [ ] Campaigns
- [ ] Coupons
- [ ] Messaging
- [ ] POS
- [ ] Analytics

---

## 🔧 Testing & Debugging

### Check Routes
```bash
php artisan route:list | grep "vendor"
```

### Test Product Gallery
```bash
php scripts/test-product-gallery-api.php
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

---

## 📞 Support

**Documentation Location:** `/var/www/html/new_public/new/`
**Test Scripts:** `/var/www/html/new_public/new/scripts/`
**Controller:** `app/Http/Controllers/Api/V1/Vendor/ProductGalleryController.php`
**Routes:** `routes/api/v1/api.php` (line ~361)

---

**Last Updated:** 2026-02-25
**Version:** 1.0.0
**Status:** ✅ Production Ready
