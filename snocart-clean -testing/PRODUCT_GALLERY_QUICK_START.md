# Product Gallery Feature - Quick Start Guide

## 📱 For Mobile App Developers

### Overview
The Product Gallery feature allows vendors to browse and replicate products from other successful stores in the marketplace. This drastically reduces the time needed to populate a new store's inventory.

---

## 🔑 Authentication

All endpoints require Bearer token authentication:

```http
Authorization: Bearer {vendor_access_token}
```

Get the token from the login endpoint:
```
POST /api/v1/auth/vendor/login
```

---

## 📋 API Endpoints

### 1. Browse Product Gallery

**Endpoint:** `GET /api/v1/vendor/product-gallery/browse`

**Query Parameters:**
- `search` (optional): Search term
- `category_id` (optional): Filter by category
- `store_id` (optional): Filter by specific store
- `page` (default: 1): Page number
- `limit` (default: 20): Items per page
- `exclude_my_products` (default: true): Hide your own products

**Example Request:**
```bash
curl -X GET "https://your-domain.com/api/v1/vendor/product-gallery/browse?search=apple&page=1&limit=20" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 123,
        "name": "Organic Apples",
        "description": "Fresh organic apples",
        "image": "https://...",
        "price": 5.99,
        "discount": 10,
        "discount_type": "percent",
        "source_store_name": "Green Grocers",
        "avg_rating": 4.5,
        "already_in_my_store": false
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 25,
      "total_products": 500
    }
  }
}
```

---

### 2. Get Product Details

**Endpoint:** `GET /api/v1/vendor/product-gallery/details/{product_id}`

**Example Request:**
```bash
curl -X GET "https://your-domain.com/api/v1/vendor/product-gallery/details/123" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "Organic Apples",
    "description": "Full description...",
    "images": ["img1.jpg", "img2.jpg"],
    "price": 5.99,
    "variations": [...],
    "addons": [...],
    "source_store": {
      "name": "Green Grocers",
      "rating": 4.8
    }
  }
}
```

---

### 3. Replicate Product

**Endpoint:** `POST /api/v1/vendor/product-gallery/replicate`

**Request Body:**
```json
{
  "source_product_id": 123,
  "customize": {
    "price": 6.99,
    "discount": 5,
    "stock": 50,
    "copy_images": true,
    "copy_variations": true,
    "copy_addons": true
  }
}
```

**Example Request:**
```bash
curl -X POST "https://your-domain.com/api/v1/vendor/product-gallery/replicate" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "source_product_id": 123,
    "customize": {
      "price": 6.99,
      "stock": 50
    }
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Product replicated successfully",
  "data": {
    "new_product_id": 789,
    "name": "Organic Apples",
    "items_copied": {
      "images": 3,
      "variations": 2,
      "addons": 5
    }
  }
}
```

---

### 4. Batch Replicate

**Endpoint:** `POST /api/v1/vendor/product-gallery/batch-replicate`

**Request Body:**
```json
{
  "product_ids": [123, 124, 125],
  "default_settings": {
    "discount": 0,
    "stock": 100,
    "copy_images": true
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "3 products replicated successfully",
  "data": {
    "successful": 3,
    "failed": 0,
    "results": [...]
  }
}
```

---

### 5. Get Trending Products

**Endpoint:** `GET /api/v1/vendor/product-gallery/trending`

**Response:**
```json
{
  "success": true,
  "data": {
    "most_ordered": [...],
    "highest_rated": [...],
    "newest_additions": [...]
  }
}
```

---

### 6. My Replication History

**Endpoint:** `GET /api/v1/vendor/product-gallery/my-replications`

**Response:**
```json
{
  "success": true,
  "data": {
    "replications": [...],
    "total_products": 45
  }
}
```

---

## 🎨 UI/UX Implementation Guide

### Screen 1: Product Gallery Browse

**Components Needed:**
- Search bar with debouncing
- Category filter chips
- Product grid/list
- "Add to Store" button on each card
- Infinite scroll or pagination
- Loading states

**Sample Layout:**
```
┌─────────────────────────────────────┐
│  🔍 Search products...              │
├─────────────────────────────────────┤
│  [All] [Fruits] [Vegetables]        │
├─────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐        │
│  │  🍎      │  │  🍊      │        │
│  │  Apple   │  │  Orange  │        │
│  │  $5.99   │  │  $3.99   │        │
│  │ [+ Add]  │  │ [+ Add]  │        │
│  └──────────┘  └──────────┘        │
└─────────────────────────────────────┘
```

---

### Screen 2: Product Details

**Components Needed:**
- Image carousel
- Product info (name, description, price)
- Variations preview
- Source store badge
- "Replicate to My Store" button
- "Customize" toggle

**Sample Layout:**
```
┌─────────────────────────────────────┐
│  ← Back                             │
├─────────────────────────────────────┤
│         📷 Image Carousel            │
├─────────────────────────────────────┤
│  Organic Apples                     │
│  $5.99  ⭐ 4.5 (150 reviews)        │
│                                      │
│  From: Green Grocers 🏪             │
│                                      │
│  Description...                     │
│                                      │
│  Variations: 2                      │
│  Addons: 5                          │
│                                      │
│  [🎨 Customize] [✅ Add to Store]   │
└─────────────────────────────────────┘
```

---

### Screen 3: Customization Sheet

**Components Needed:**
- Price input with currency
- Stock quantity input
- Discount input
- Toggle switches for:
  - Copy images
  - Copy variations
  - Copy addons
- "Add to Store" submit button

---

## ⚡ Performance Optimization

### 1. Caching Strategy
```dart
// Cache product gallery for 5 minutes
final cachedGallery = await cache.get('product_gallery_page_1');
if (cachedGallery != null && !forceRefresh) {
  return cachedGallery;
}
```

### 2. Image Loading
```dart
// Use placeholder while loading
CachedNetworkImage(
  imageUrl: product.image,
  placeholder: (context, url) => ShimmerWidget(),
  errorWidget: (context, url, error) => Icon(Icons.error),
)
```

### 3. Pagination
```dart
// Load more on scroll
scrollController.addListener(() {
  if (scrollController.position.pixels ==
      scrollController.position.maxScrollExtent) {
    loadMoreProducts();
  }
});
```

---

## 🔒 Error Handling

### Common Error Codes

**403 Forbidden**
- Store doesn't have permission to add items
- Subscription limit reached

**404 Not Found**
- Product doesn't exist

**409 Conflict**
- Product already exists in vendor's store

**422 Validation Error**
- Invalid input data

**Example Error Response:**
```json
{
  "success": false,
  "message": "You have reached your product limit"
}
```

---

## ✅ Testing Checklist

### Backend Testing
- [ ] Run `php scripts/test-product-gallery-api.php`
- [ ] Test with Postman collection
- [ ] Verify image copying works
- [ ] Test subscription limits
- [ ] Test duplicate prevention

### Mobile App Testing
- [ ] Gallery loads and displays products
- [ ] Search functionality works
- [ ] Filters apply correctly
- [ ] Product details page loads
- [ ] Single replication works
- [ ] Batch replication works
- [ ] Error messages display properly
- [ ] Loading states show correctly
- [ ] Images load properly
- [ ] Pagination works smoothly

---

## 🐛 Troubleshooting

### Issue: No products showing in gallery
**Solution:** Check if there are products in other stores with `status=1` and `is_approved=1`

### Issue: Replication fails
**Solution:** Check vendor's subscription limits and ensure `item_section=1` in store settings

### Issue: Images not copying
**Solution:** Verify storage permissions and that source images exist in storage

### Issue: "Product already exists" error
**Solution:** Product with same name exists - suggest vendor to modify name or check existing products

---

## 📞 Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Run test script: `php scripts/test-product-gallery-api.php`
3. Verify routes: `php artisan route:list | grep product-gallery`

---

## 🎉 Success Metrics

Track these metrics to measure feature success:
- Number of products replicated per day
- Most replicated products (trending)
- Time saved per vendor (vs manual entry)
- Vendor satisfaction score
- Product catalog growth rate

---

## 📄 API Response Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 403 | Forbidden (no permission) |
| 404 | Not Found |
| 409 | Conflict (duplicate) |
| 422 | Validation Error |
| 500 | Server Error |

---

**Last Updated:** 2026-02-25
**Version:** 1.0.0
