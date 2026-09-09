# Vendor App API Endpoints Documentation & Product Gallery Feature

## 📋 Complete List of Vendor App Endpoints

### Base URL
```
https://your-domain.com/api/v1/vendor/
```

### Authentication Required
All endpoints below require `vendor.api` middleware (Bearer token authentication)

---

## 🔐 Authentication Endpoints (No Auth Required)

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| POST | `/api/v1/auth/vendor/login` | `VendorLoginController@login` | Login vendor |
| POST | `/api/v1/auth/vendor/register` | `VendorLoginController@register` | Register new vendor |
| POST | `/api/v1/auth/vendor/forgot-password` | `VendorPasswordResetController@reset_password_request` | Request password reset |
| POST | `/api/v1/auth/vendor/verify-token` | `VendorPasswordResetController@verify_token` | Verify reset token |
| PUT | `/api/v1/auth/vendor/reset-password` | `VendorPasswordResetController@reset_password_submit` | Reset password |

---

## 👤 Profile & Settings

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `notifications` | `VendorController@get_notifications` | Get all notifications |
| GET | `profile` | `VendorController@get_profile` | Get vendor profile |
| PUT | `update-profile` | `VendorController@update_profile` | Update vendor profile |
| PUT | `update-announcment` | `VendorController@update_announcment` | Update announcement |
| POST | `update-active-status` | `VendorController@active_status` | Toggle store active/inactive |
| PUT | `update-fcm-token` | `VendorController@update_fcm_token` | Update Firebase token |
| DELETE | `remove-account` | `VendorController@remove_account` | Delete vendor account |

---

## 💰 Earnings & Financial

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `earning-info` | `VendorController@get_earning_data` | Get earnings data |
| GET | `get-withdraw-list` | `VendorController@withdraw_list` | List withdrawal requests |
| PUT | `update-bank-info` | `VendorController@update_bank_info` | Update bank information |
| POST | `request-withdraw` | `VendorController@request_withdraw` | Request withdrawal |
| POST | `make-collected-cash-payment` | `VendorController@make_payment` | Make cash payment |
| POST | `make-wallet-adjustment` | `VendorController@make_wallet_adjustment` | Adjust wallet |
| GET | `wallet-payment-list` | `VendorController@wallet_payment_list` | Get wallet payment list |
| GET | `get-expense` | `ReportController@expense_report` | Get expense report |
| GET | `get-disbursement-report` | `ReportController@disbursement_report` | Get disbursement report |

---

## 📦 Order Management

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `current-orders` | `VendorController@get_current_orders` | Get current/pending orders |
| GET | `completed-orders` | `VendorController@get_completed_orders` | Get completed orders |
| GET | `canceled-orders` | `VendorController@get_canceled_orders` | Get canceled orders |
| GET | `all-orders` | `VendorController@get_all_orders` | Get all orders |
| GET | `order-details` | `VendorController@get_order_details` | Get order details |
| GET | `order` | `VendorController@get_order` | Get single order |
| PUT | `update-order-status` | `VendorController@update_order_status` | Update order status |
| PUT | `update-order-amount` | `VendorController@edit_order_amount` | Edit order amount |
| PUT | `send-order-otp` | `VendorController@send_order_otp` | Send OTP for order |
| PUT | `order/mark-item-unavailable` | `VendorController@mark_item_unavailable` | Mark item as unavailable |

---

## 🛍️ Product/Item Management

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `get-items-list` | `VendorController@get_items` | Get vendor's items list |
| POST | `item/store` | `ItemController@store` | Create new item |
| PUT | `item/update` | `ItemController@update` | Update item |
| DELETE | `item/delete` | `ItemController@delete` | Delete item |
| GET | `item/status` | `ItemController@status` | Toggle item status |
| GET | `item/details/{id}` | `ItemController@get_item` | Get item details |
| POST | `item/search` | `ItemController@search` | Search items |
| POST | `item/toggle-stock-status` | `ItemController@toggle_stock_status` | Toggle stock status |
| POST | `item/quick-stock-update` | `ItemController@quick_stock_update` | Quick stock update |
| GET | `item/reviews` | `ItemController@reviews` | Get item reviews |
| PUT | `item/reply-update` | `ItemController@update_reply` | Update review reply |
| GET | `item/recommended` | `ItemController@recommended` | Get recommended items |
| GET | `item/organic` | `ItemController@organic` | Get organic items |
| GET | `item/pending/item/list` | `ItemController@pending_item_list` | Get pending items |
| GET | `item/requested/item/view/{id}` | `ItemController@requested_item_view` | View requested item |
| PUT | `item/stock-update` | `ItemController@stock_update` | Update stock |
| GET | `item/stock-limit-list` | `ItemController@stock_limit_list` | Get stock limit list |

---

## 🎯 Campaigns & Marketing

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `get-basic-campaigns` | `VendorController@get_basic_campaigns` | Get basic campaigns |
| PUT | `campaign-leave` | `VendorController@remove_store` | Leave campaign |
| PUT | `campaign-join` | `VendorController@addstore` | Join campaign |

---

## 🎟️ Coupons

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `coupon/list` | `CouponController@list` | List all coupons |
| GET | `coupon/view` | `CouponController@view` | View coupon details |
| GET | `coupon/view-without-translate` | `CouponController@view_without_translate` | View raw coupon |
| POST | `coupon/store` | `CouponController@store` | Create coupon |
| POST | `coupon/update` | `CouponController@update` | Update coupon |
| POST | `coupon/status` | `CouponController@status` | Toggle coupon status |
| POST | `coupon/delete` | `CouponController@delete` | Delete coupon |
| POST | `coupon/search` | `CouponController@search` | Search coupons |

---

## 📢 Advertisements

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `advertisement/` | `AdvertisementController@index` | List advertisements |
| GET | `advertisement/details/{id}` | `AdvertisementController@show` | Get ad details |
| DELETE | `advertisement/delete/{id}` | `AdvertisementController@destroy` | Delete advertisement |
| POST | `advertisement/store` | `AdvertisementController@store` | Create advertisement |
| POST | `advertisement/update/{id}` | `AdvertisementController@update` | Update advertisement |
| PUT | `advertisement/status` | `AdvertisementController@status` | Toggle ad status |
| POST | `advertisement/copy-add-post` | `AdvertisementController@copyAddPost` | Copy advertisement |

---

## ➕ Addons

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `addon/` | `AddOnController@list` | List addons |
| POST | `addon/store` | `AddOnController@store` | Create addon |
| PUT | `addon/update` | `AddOnController@update` | Update addon |
| GET | `addon/status` | `AddOnController@status` | Toggle addon status |
| DELETE | `addon/delete` | `AddOnController@delete` | Delete addon |

---

## 🎨 Banners

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `banner/` | `BannerController@list` | List banners |
| POST | `banner/store` | `BannerController@store` | Create banner |
| PUT | `banner/update` | `BannerController@update` | Update banner |
| GET | `banner/status` | `BannerController@status` | Toggle banner status |
| DELETE | `banner/delete` | `BannerController@delete` | Delete banner |

---

## 📁 Categories

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `categories/` | `CategoryController@get_categories` | Get all categories |
| GET | `categories/childes/{category_id}` | `CategoryController@get_childes` | Get child categories |

---

## 🚴 Delivery Men

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| POST | `delivery-man/store` | `DeliveryManController@store` | Create delivery man |
| GET | `delivery-man/list` | `DeliveryManController@list` | List delivery men |
| GET | `delivery-man/preview` | `DeliveryManController@preview` | Preview delivery man |
| GET | `delivery-man/status` | `DeliveryManController@status` | Toggle DM status |
| POST | `delivery-man/update/{id}` | `DeliveryManController@update` | Update delivery man |
| DELETE | `delivery-man/delete` | `DeliveryManController@delete` | Delete delivery man |
| POST | `delivery-man/search` | `DeliveryManController@search` | Search delivery men |

---

## 🏪 POS System

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `pos/orders` | `POSController@order_list` | Get POS orders |
| POST | `pos/place-order` | `POSController@place_order` | Place POS order |
| GET | `pos/customers` | `POSController@get_customers` | Get customers for POS |

---

## 💬 Messaging/Chat

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `message/list` | `ConversationController@conversations` | List conversations |
| GET | `message/search-list` | `ConversationController@search_conversations` | Search conversations |
| GET | `message/details` | `ConversationController@messages` | Get messages |
| POST | `message/send` | `ConversationController@messages_store` | Send message |

---

## ⚙️ Business Settings

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| PUT | `update-business-setup` | `BusinessSettingsController@update_store_setup` | Update store setup |
| POST | `schedule/store` | `BusinessSettingsController@add_schedule` | Add schedule |
| DELETE | `schedule/{store_schedule}` | `BusinessSettingsController@remove_schedule` | Remove schedule |

---

## 📊 Subscription Management

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `/api/v1/vendor/package-view` | `SubscriptionController@package_view` | View packages |
| POST | `/api/v1/vendor/business_plan` | `SubscriptionController@business_plan` | Get business plan |
| POST | `/api/v1/vendor/subscription/payment/api` | `SubscriptionController@subscription_payment_api` | Process subscription payment |
| POST | `/api/v1/vendor/package-renew` | `SubscriptionController@package_renew_change_update_api` | Renew/change package |
| POST | `/api/v1/vendor/cancel-subscription` | `SubscriptionController@cancelSubscription` | Cancel subscription |
| GET | `/api/v1/vendor/check-product-limits` | `SubscriptionController@checkProductLimits` | Check product limits |
| GET | `subscription-transaction` | `SubscriptionController@transaction` | Get subscription transactions |

---

## 🔧 Utilities

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `unit` | `UnitController@index` | Get units |
| GET | `attributes` | `AttributeController@list` | Get attributes |
| GET | `get-withdraw-method-list` | `WithdrawMethodController@withdraw_method_list` | Get withdrawal methods |
| GET | `withdraw-method/list` | `WithdrawMethodController@get_disbursement_withdrawal_methods` | Get disbursement methods |
| POST | `withdraw-method/store` | `WithdrawMethodController@disbursement_withdrawal_method_store` | Store withdrawal method |
| POST | `withdraw-method/make-default` | `WithdrawMethodController@disbursement_withdrawal_method_default` | Set default method |
| DELETE | `withdraw-method/delete` | `WithdrawMethodController@disbursement_withdrawal_method_delete` | Delete withdrawal method |

---

## 🔄 Odoo/POS Sync (No Auth Required)

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| POST | `/api/v1/vendor/sync-orders` | `VendorSyncController@syncOrders` | Sync orders from POS |
| GET | `/api/v1/vendor/sync-products` | `VendorSyncController@syncProducts` | Sync products to POS |
| GET | `/api/v1/vendor/sync-inventory` | `VendorSyncController@syncInventory` | Sync inventory |
| POST | `/api/v1/vendor/update-inventory` | `VendorSyncController@updateInventory` | Update inventory from POS |
| GET | `/api/v1/vendor/sync-status` | `VendorSyncController@syncStatus` | Get sync status |

---

# 🖼️ NEW FEATURE: Product Gallery for Vendors

## Overview
This feature allows vendors to browse a centralized product gallery containing all products from all stores in the system, and replicate (copy) products to their own store with one tap.

## Business Benefits
- **Time Saving**: Vendors don't need to manually create common products
- **Standardization**: Ensures consistent product information across stores
- **Quick Onboarding**: New vendors can quickly populate their inventory
- **Quality**: Products come with pre-verified images, descriptions, and details

---

## Implementation Plan

### 1. Database Schema (No Changes Needed)
The existing `items` table already supports this feature:
- Products from different stores are differentiated by `store_id`
- All product data (images, descriptions, prices) is stored per item

### 2. New API Endpoints

#### A. Get Product Gallery (Browse All Products)
```
GET /api/v1/vendor/product-gallery/browse
```

**Query Parameters:**
- `search` (optional): Search by product name
- `category_id` (optional): Filter by category
- `store_id` (optional): Filter by specific store
- `page` (default: 1): Pagination
- `limit` (default: 20): Items per page
- `exclude_my_products` (boolean, default: true): Hide products already in vendor's store

**Response:**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 123,
        "name": "Organic Apples",
        "description": "Fresh organic apples from local farms",
        "image": "https://domain.com/storage/product/apple.jpg",
        "images": ["image1.jpg", "image2.jpg"],
        "price": 5.99,
        "discount": 10,
        "discount_type": "percent",
        "category_id": 5,
        "category_name": "Fruits",
        "unit": "kg",
        "stock": 100,
        "source_store_id": 456,
        "source_store_name": "Green Grocers",
        "avg_rating": 4.5,
        "total_reviews": 150,
        "can_replicate": true,
        "already_in_my_store": false
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 25,
      "total_products": 500,
      "per_page": 20
    },
    "filters": {
      "categories": [...],
      "stores": [...]
    }
  }
}
```

---

#### B. Get Product Gallery Details
```
GET /api/v1/vendor/product-gallery/details/{product_id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "Organic Apples",
    "description": "Full detailed description...",
    "image": "main-image.jpg",
    "images": ["image1.jpg", "image2.jpg", "image3.jpg"],
    "price": 5.99,
    "discount": 10,
    "discount_type": "percent",
    "category_id": 5,
    "category_name": "Fruits",
    "category_path": "Food > Fruits > Apples",
    "unit": "kg",
    "stock": 100,
    "available_time_starts": "00:00:00",
    "available_time_ends": "23:59:59",
    "variations": [...],
    "addons": [...],
    "attributes": [...],
    "tags": ["organic", "fresh", "local"],
    "source_store": {
      "id": 456,
      "name": "Green Grocers",
      "logo": "logo.jpg",
      "rating": 4.8
    },
    "nutrition_info": [...],
    "allergen_info": [...],
    "reviews": {
      "avg_rating": 4.5,
      "total_reviews": 150,
      "recent_reviews": [...]
    }
  }
}
```

---

#### C. Replicate Product to My Store
```
POST /api/v1/vendor/product-gallery/replicate
```

**Request Body:**
```json
{
  "source_product_id": 123,
  "customize": {
    "price": 6.99,           // Optional: Override price
    "discount": 5,           // Optional: Override discount
    "stock": 50,             // Optional: Set initial stock
    "available_time_starts": "09:00:00",  // Optional
    "available_time_ends": "21:00:00",    // Optional
    "copy_images": true,     // Default: true
    "copy_variations": true, // Default: true
    "copy_addons": true,     // Default: true
    "copy_attributes": true, // Default: true
    "is_recommended": false, // Default: false
    "veg": null              // Optional: Override veg/non-veg
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Product replicated successfully",
  "data": {
    "new_product_id": 789,
    "name": "Organic Apples",
    "status": "active",
    "items_copied": {
      "images": 3,
      "variations": 2,
      "addons": 5,
      "attributes": 3
    }
  }
}
```

---

#### D. Batch Replicate Multiple Products
```
POST /api/v1/vendor/product-gallery/batch-replicate
```

**Request Body:**
```json
{
  "product_ids": [123, 124, 125, 126],
  "default_settings": {
    "discount": 0,
    "stock": 100,
    "copy_images": true,
    "copy_variations": true,
    "copy_addons": true
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "4 products replicated successfully",
  "data": {
    "successful": 3,
    "failed": 1,
    "results": [
      {
        "source_id": 123,
        "new_id": 789,
        "status": "success"
      },
      {
        "source_id": 124,
        "new_id": null,
        "status": "failed",
        "error": "Product already exists in your store"
      }
    ]
  }
}
```

---

#### E. Get Replication History
```
GET /api/v1/vendor/product-gallery/my-replications
```

**Response:**
```json
{
  "success": true,
  "data": {
    "replications": [
      {
        "my_product_id": 789,
        "source_product_id": 123,
        "source_store_name": "Green Grocers",
        "replicated_at": "2026-02-25 10:30:00",
        "product_name": "Organic Apples",
        "customizations_applied": ["price", "stock"]
      }
    ],
    "total_replicated": 15
  }
}
```

---

#### F. Popular/Trending Products in Gallery
```
GET /api/v1/vendor/product-gallery/trending
```

**Response:**
```json
{
  "success": true,
  "data": {
    "most_replicated": [...],
    "highest_rated": [...],
    "newest_additions": [...]
  }
}
```

---

## 3. Controller Implementation

### File: `app/Http/Controllers/Api/V1/Vendor/ProductGalleryController.php`

**Key Methods:**
- `browse()` - Browse product gallery with filters
- `details($id)` - Get detailed product information
- `replicate(Request $request)` - Replicate single product
- `batchReplicate(Request $request)` - Replicate multiple products
- `myReplications()` - Get vendor's replication history
- `trending()` - Get trending/popular products

---

## 4. Routes Addition

**File: `routes/api/v1/api.php`**

Add inside the vendor middleware group (around line 225):

```php
// Product Gallery
Route::group(['prefix'=>'product-gallery'], function(){
    Route::get('browse', 'ProductGalleryController@browse');
    Route::get('details/{id}', 'ProductGalleryController@details');
    Route::post('replicate', 'ProductGalleryController@replicate');
    Route::post('batch-replicate', 'ProductGalleryController@batchReplicate');
    Route::get('my-replications', 'ProductGalleryController@myReplications');
    Route::get('trending', 'ProductGalleryController@trending');
});
```

---

## 5. Business Logic

### Replication Process:
1. **Fetch Source Product**: Get all data from source item
2. **Duplicate Check**: Ensure product doesn't already exist in vendor's store
3. **Copy Core Data**:
   - Name, description, category
   - Price (can be customized)
   - Unit, veg/non-veg status
   - Available times
4. **Copy Related Data**:
   - Images (copy files to new location)
   - Variations (create new variation records)
   - Addons (link or copy addon data)
   - Attributes
   - Tags
   - Nutrition info
   - Allergen info
5. **Set Vendor-Specific Data**:
   - `store_id` = Current vendor's store
   - `stock` = Initial stock (customizable)
   - `status` = Active by default
6. **Track Replication**: Optionally log source product ID for tracking

---

## 6. Database Tracking (Optional Enhancement)

### New Migration: `product_replications` table
```sql
CREATE TABLE product_replications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    vendor_store_id BIGINT,
    source_product_id BIGINT,
    replicated_product_id BIGINT,
    customizations JSON,
    replicated_at TIMESTAMP,
    FOREIGN KEY (vendor_store_id) REFERENCES stores(id),
    FOREIGN KEY (source_product_id) REFERENCES items(id),
    FOREIGN KEY (replicated_product_id) REFERENCES items(id)
);
```

---

## 7. Permission & Security

### Checks:
- ✅ Vendor must be authenticated
- ✅ Vendor's store must have `item_section` enabled
- ✅ Check subscription limits (max products)
- ✅ Prevent replicating own products
- ✅ Prevent duplicate replication (same product twice)
- ✅ Validate all customization inputs

---

## 8. UI/UX Flow (Mobile App)

### Screen 1: Product Gallery Browse
- Grid/List view of products
- Search bar at top
- Category filters
- Store filter
- "Add to My Store" button on each product card
- Floating action button for "Trending Products"

### Screen 2: Product Details
- Full product information
- Image gallery
- Variations, addons preview
- Source store badge
- "Replicate to My Store" primary action button
- "Customize Before Adding" secondary button

### Screen 3: Customization Screen (Optional)
- Price override
- Stock quantity input
- Discount override
- Toggle switches for:
  - Copy images
  - Copy variations
  - Copy addons
- "Add to My Store" submit button

### Screen 4: Replication Success
- Success animation
- "View in My Products" button
- "Add More Products" button

---

## 9. Testing Checklist

- [ ] Gallery loads with pagination
- [ ] Search works correctly
- [ ] Category filter works
- [ ] Store filter works
- [ ] Product details display correctly
- [ ] Single replication works
- [ ] Batch replication works
- [ ] Images copy correctly
- [ ] Variations copy correctly
- [ ] Addons copy correctly
- [ ] Subscription limits enforced
- [ ] Duplicate prevention works
- [ ] Customization applies correctly
- [ ] Replication history tracks correctly

---

## 10. Performance Considerations

- **Caching**: Cache popular products list (1 hour TTL)
- **Image Optimization**: Copy images asynchronously via queue
- **Pagination**: Limit to 20-50 products per page
- **Indexing**: Add indexes on `items.store_id`, `items.category_id`
- **Lazy Loading**: Load product details only when needed

---

## Summary

This product gallery feature will significantly improve vendor onboarding and product management efficiency. Vendors can:

1. **Browse** thousands of products from successful stores
2. **Preview** complete product details before replicating
3. **Replicate** products with one tap
4. **Customize** prices, stock, and settings
5. **Batch add** multiple products at once
6. **Track** which products they've replicated

The implementation leverages existing database structure, requires minimal schema changes, and follows Laravel best practices.
