# Module Filter for Outside Purchase Stores

## Overview
Store selection is now filtered by **module** - only stores from the same module as the order are shown.

---

## 🎯 Why Module Filtering?

### Problem (Before):
- Order is for **Food** module
- Showed ALL stores (Food, Grocery, Pharmacy, etc.)
- User could accidentally select wrong module store
- Confusing and error-prone

### Solution (After):
- Order is for **Food** module
- Shows ONLY **Food** stores
- Prevents cross-module errors
- Cleaner, more relevant selection

---

## 📊 Module Distribution

Based on current database:

| Module | Store Count |
|--------|-------------|
| Food | 55 stores |
| Clothing & Personal Care | 8 stores |
| Electronics | 7 stores |
| Skin & Hair care | 12 stores |
| Grocery | 6 stores |
| Gifts & Goodies | 3 stores |
| Bakery | 2 stores |
| Nuts & Dry Fruits | 2 stores |
| Demo Module | 1 store |
| SnoFresh | 1 store |

---

## 🔧 Implementation Details

### Admin Side

#### 1. Button Data Attribute
```html
<button class="mark-outside-purchase"
        data-order-detail-id="123"
        data-item-price="25.00"
        data-module-id="4">  ← Module ID from order
    Mark Outside Purchase
</button>
```

#### 2. JavaScript AJAX Call
```javascript
$.ajax({
    url: '/admin/order/get-stores-for-outside-purchase',
    data: {
        search: '',
        limit: 200,
        module_id: moduleId  // ← Filters by module
    }
});
```

#### 3. Controller Query
```php
$stores = Store::where('status', 1)
    ->when($moduleId, function($query) use ($moduleId) {
        $query->where('module_id', $moduleId);  // ← Module filter
    })
    ->get();
```

---

### Delivery Man API

#### Method 1: Pass Order ID (Recommended)
```
GET /api/v1/delivery-man/get-stores-for-outside-purchase?order_id=123

Response:
{
  "stores": [...],  // Only stores from order's module
  "total": 55,
  "module_filtered": true,
  "module_id": 4
}
```

#### Method 2: Pass Module ID Directly
```
GET /api/v1/delivery-man/get-stores-for-outside-purchase?module_id=4

Response:
{
  "stores": [...],  // Only stores from module 4
  "total": 55,
  "module_filtered": true,
  "module_id": 4
}
```

#### How It Works:
```php
// If order_id provided, get module from order
if ($orderId && !$moduleId) {
    $order = Order::with('store:id,module_id')->find($orderId);
    $moduleId = $order->store->module_id;
}

// Filter stores by module
$stores = Store::where('status', 1)
    ->when($moduleId, function($query) use ($moduleId) {
        $query->where('module_id', $moduleId);
    })
    ->get();
```

---

## 🎨 User Experience

### Example: Food Order

**Before (Without Module Filter):**
```
Purchased From Store:
├─ Pizza Palace (Food) ✓
├─ Burger Joint (Food) ✓
├─ Medicine Shop (Pharmacy) ✗ Wrong module!
├─ Grocery Mart (Grocery) ✗ Wrong module!
└─ Electronics Hub (Electronics) ✗ Wrong module!
```

**After (With Module Filter):**
```
Purchased From Store:
├─ Pizza Palace (Food) ✓
├─ Burger Joint (Food) ✓
├─ Sushi Bar (Food) ✓
├─ Cafe Coffee (Food) ✓
└─ (Only 55 Food stores shown)
```

---

## 📱 Mobile App Integration

### Update API Call

**Before:**
```dart
final response = await http.get(
  Uri.parse('$baseUrl/api/v1/delivery-man/get-stores-for-outside-purchase'),
);
```

**After (Option 1 - Pass Order ID):**
```dart
final response = await http.get(
  Uri.parse('$baseUrl/api/v1/delivery-man/get-stores-for-outside-purchase?order_id=${order.id}'),
);
```

**After (Option 2 - Pass Module ID):**
```dart
final response = await http.get(
  Uri.parse('$baseUrl/api/v1/delivery-man/get-stores-for-outside-purchase?module_id=${order.module_id}'),
);
```

### Response Handling
```dart
final data = json.decode(response.body);
final stores = data['stores']; // Filtered by module
final isFiltered = data['module_filtered']; // true if filtered
final moduleId = data['module_id']; // Module used for filtering

if (isFiltered) {
  print('Showing only ${stores.length} stores from module $moduleId');
}
```

---

## 🧪 Testing

### Test Case 1: Food Order
```
1. Open Food order
2. Click "Mark Outside Purchase"
3. Open store dropdown
4. Should see ONLY Food stores (55 stores)
5. Should NOT see Grocery/Pharmacy stores
```

### Test Case 2: Grocery Order
```
1. Open Grocery order
2. Click "Mark Outside Purchase"
3. Open store dropdown
4. Should see ONLY Grocery stores (6 stores)
5. Should NOT see Food/Pharmacy stores
```

### Test Case 3: Search Within Module
```
1. Open Food order (55 stores)
2. Type "pizza" in search
3. Should show only Food stores with "pizza" in name
4. Should NOT show pizza stores from other modules
```

### API Testing

**Test Admin Endpoint:**
```bash
# Get Food stores (module_id = 4)
curl "http://your-domain/admin/order/get-stores-for-outside-purchase?module_id=4"

# Should return ~55 stores

# Get Grocery stores (module_id = 2)
curl "http://your-domain/admin/order/get-stores-for-outside-purchase?module_id=2"

# Should return ~6 stores
```

**Test Delivery Man API:**
```bash
# Method 1: By order ID
curl -H "Authorization: Bearer {token}" \
  "http://your-domain/api/v1/delivery-man/get-stores-for-outside-purchase?order_id=123"

# Method 2: By module ID
curl -H "Authorization: Bearer {token}" \
  "http://your-domain/api/v1/delivery-man/get-stores-for-outside-purchase?module_id=4"
```

---

## 📋 API Parameters

### Admin Endpoint
```
GET /admin/order/get-stores-for-outside-purchase

Parameters:
├─ search (string, optional) - Search by store name
├─ limit (integer, optional) - Max results (default: 100)
└─ module_id (integer, optional) - Filter by module

Response: Array of stores
```

### Delivery Man API
```
GET /api/v1/delivery-man/get-stores-for-outside-purchase

Parameters:
├─ search (string, optional) - Search by store name
├─ limit (integer, optional) - Max results (default: 100)
├─ module_id (integer, optional) - Filter by module
└─ order_id (integer, optional) - Auto-detect module from order

Response:
{
  "stores": [...],
  "total": 55,
  "module_filtered": true,
  "module_id": 4
}
```

---

## 🎯 Benefits

✅ **Accuracy** - Only relevant stores shown
✅ **Speed** - Fewer stores = faster loading
✅ **User Experience** - No confusion with wrong module stores
✅ **Error Prevention** - Can't select Pharmacy store for Food order
✅ **Performance** - Smaller result sets

---

## 🔄 Backward Compatibility

### Without Module Filter (Still Works)
```
GET /admin/order/get-stores-for-outside-purchase
# Returns ALL active stores (like before)
```

### With Module Filter (New)
```
GET /admin/order/get-stores-for-outside-purchase?module_id=4
# Returns ONLY module 4 stores
```

**Note:** Module filtering is **optional**. If `module_id` is not provided, all stores are returned.

---

## 🐛 Troubleshooting

### Issue: No stores showing
**Cause:** Module might have no active stores
**Solution:** Check database:
```sql
SELECT COUNT(*) FROM stores
WHERE status = 1 AND module_id = 4;
```

### Issue: Wrong stores showing
**Cause:** Module ID not being passed correctly
**Solution:**
1. Check browser console for AJAX request
2. Verify `data-module-id` attribute on button
3. Check if `$order->store->module_id` exists

### Issue: All stores showing (not filtered)
**Cause:** Module ID is null/empty
**Solution:**
1. Verify order has a store
2. Verify store has a module_id
3. Check JavaScript is reading `data-module-id` correctly

---

## 📊 Query Examples

### Get Food Stores Only
```sql
SELECT s.id, s.name, m.module_name
FROM stores s
JOIN modules m ON s.module_id = m.id
WHERE s.status = 1
AND s.module_id = 4;
```

### Count Stores Per Module
```sql
SELECT
  m.module_name,
  COUNT(s.id) as store_count
FROM modules m
LEFT JOIN stores s ON m.id = s.module_id AND s.status = 1
GROUP BY m.id, m.module_name
ORDER BY store_count DESC;
```

---

## ✅ Summary

| Feature | Before | After |
|---------|--------|-------|
| **Filtering** | None | By Module |
| **Food Order Shows** | All 97 stores | Only 55 Food stores |
| **Grocery Order Shows** | All 97 stores | Only 6 Grocery stores |
| **Error Risk** | High (wrong module) | Low (filtered) |
| **Performance** | Slower (more data) | Faster (less data) |

---

## 🚀 Status

✅ **Admin Panel** - Module filtering active
✅ **Delivery Man API** - Supports order_id and module_id
✅ **Backward Compatible** - Works with/without filter
✅ **Tested** - Query verified with database

**Ready to use!** Clear cache and test:
```bash
php artisan cache:clear
php artisan view:clear
```
