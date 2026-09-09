# Product Gallery - Category & Type Filters Implementation

## Feature Overview (2026-02-25)

Added comprehensive filtering options to the Product Gallery module, allowing vendors to filter products by category and type (veg/non-veg) in addition to the existing search functionality.

---

## What Was Added

### Frontend (Vendor Panel)

**File:** `resources/views/vendor-views/product/product_gallery.blade.php`

**Changes:**
1. **Category Filter Dropdown** - Shows all categories with subcategories indented
2. **Type Filter Dropdown** - Filter by All/Veg/Non-Veg
3. **Responsive Layout** - Filters arranged in 4 columns (search + category + type + button)
4. **Auto-submit on Filter Change** - Page reloads when category or type is selected
5. **Preserves Filter State** - Selected filters persist after page reload

### Backend API Support

**File:** `app/Http/Controllers/Vendor/ItemController.php`

**Method: `search()` (line 968)**
- Added `category_id` parameter support (lines 971-972)
- Added `type` parameter support
- Category filtering via relationship query (lines 983-987)
- Type filtering via scope (line 988)

**Method: `product_gallery()` (line 1853)**
- Already had category and type filtering support ✅
- Works with query parameters: `?category_id=5&type=veg`

---

## UI Layout

```
┌─────────────────────────────────────────────────────────────┐
│  Search Input (40%)  │ Category (30%) │ Type (30%) │ Search │
└─────────────────────────────────────────────────────────────┘
```

**Responsive Behavior:**
- Desktop: 4 columns side by side
- Tablet: 2 rows (search + category, then type + button)
- Mobile: 4 rows stacked vertically

---

## Filter Options

### 1. Search Filter
- **Type:** Text input with live search
- **Searches:** Product name and barcode
- **Behavior:** Auto-searches as you type (400ms debounce)

### 2. Category Filter
- **Type:** Dropdown (Select2)
- **Options:**
  - "All Categories" (default)
  - Root categories
  - └─ Subcategories (indented with arrow)
- **Behavior:** Page reloads on selection

### 3. Type Filter
- **Type:** Standard dropdown
- **Options:**
  - "All Types" (default)
  - "Veg"
  - "Non-Veg"
- **Behavior:** Page reloads on selection

---

## How It Works

### Category Filtering

**Query Parameter:** `?category_id=5`

**Backend Logic:**
```php
->when(is_numeric($category_id), function($query) use($category_id){
    return $query->whereHas('category', function($q) use($category_id){
        return $q->whereId($category_id)->orWhere('parent_id', $category_id);
    });
})
```

**Matches:**
- Products in the selected category
- Products in subcategories of the selected category

**Example:**
- Select "Groceries" → Shows all products in Groceries + all products in "Rice", "Dal", etc. (subcategories)

### Type Filtering

**Query Parameter:** `?type=veg`

**Backend Logic:**
```php
->type($type)
```

Uses the `Item` model's `type()` scope to filter by `veg` column.

### Combined Filters

**URL Example:** `?search=maggi&category_id=3&type=veg`

Filters applied:
1. ✅ Name/barcode contains "maggi"
2. ✅ Category ID is 3 or its parent is 3
3. ✅ Product is vegetarian

---

## Category Dropdown Structure

```html
<select name="category_id" id="category_id">
    <option value="all">All Categories</option>
    <option value="1">Groceries</option>
    <option value="5">  └─ Rice</option>
    <option value="6">  └─ Dal</option>
    <option value="2">Fresh Vegetables</option>
    <option value="7">  └─ Leafy Vegetables</option>
    ...
</select>
```

**Features:**
- Select2 integration for searchable dropdown
- Hierarchical display (subcategories indented)
- Only shows categories for current module
- Position 0 categories shown as root

---

## JavaScript Behavior

### Category Change Event
```javascript
$('#category_id').on('change', function() {
    // Builds URL with all current filters
    // Reloads page with new category_id parameter
});
```

### Type Change Event
```javascript
$('#type').on('change', function() {
    // Builds URL with all current filters
    // Reloads page with new type parameter
});
```

### Form Submit
```javascript
$('#search-form').on('submit', function(e) {
    e.preventDefault();
    // Combines all filters into URL
    // Reloads page with all parameters
});
```

### Why Page Reload?
- Ensures pagination works correctly
- Preserves filter state in URL (shareable links)
- Simpler than complex AJAX state management
- Standard e-commerce UX pattern

---

## API Endpoint (Mobile App)

**Endpoint:** `GET /api/v1/vendor/product-gallery/browse`

### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `search` | string | - | Search by name, description, or barcode |
| `category_id` | integer | - | Filter by category ID (includes subcategories) |
| `type` | string | 'all' | Filter by type: `all`, `veg`, `non_veg` |
| `store_id` | integer | - | Filter by source store ID |
| `exclude_my_products` | boolean | true | Exclude vendor's own products |
| `page` | integer | 1 | Page number |
| `limit` | integer | 20 | Items per page |

### Enhanced Features (2026-02-25)

**✅ Category Filter - Now includes subcategories:**
- Old: Only matched exact category ID
- New: Matches category + all its subcategories
- Example: `category_id=3` now returns products in category 3 AND all child categories

**✅ Type Filter - NEW:**
- Filter products by vegetarian/non-vegetarian
- Values: `all` (default), `veg`, `non_veg`
- Works with all other filters

**✅ Filter Options - Enhanced response:**
- Categories now include `parent_id` and `is_subcategory` flags
- New `types` array with available type options
- Each type option includes `selected` state

### Example Requests

**All vegetarian products:**
```bash
GET /api/v1/vendor/product-gallery/browse?type=veg&limit=20
```

**Search rice in category 5 (veg only):**
```bash
GET /api/v1/vendor/product-gallery/browse?search=rice&category_id=5&type=veg&page=1&limit=20
```

**All non-veg products from store 10:**
```bash
GET /api/v1/vendor/product-gallery/browse?store_id=10&type=non_veg
```

### Response Structure

```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 123,
        "name": "Basmati Rice",
        "description": "Premium quality rice",
        "image": "https://...",
        "images": [...],
        "price": 150.00,
        "discount": 10.00,
        "discount_type": "percent",
        "category_id": 5,
        "category_name": "Rice",
        "unit": "kg",
        "stock": 100,
        "source_store_id": 2,
        "source_store_name": "ABC Store",
        "source_store_logo": "https://...",
        "avg_rating": 4.5,
        "total_reviews": 120,
        "order_count": 450,
        "veg": 1,
        "recommended": 0,
        "organic": 1,
        "barcode": "1234567890",
        "can_replicate": true,
        "already_in_my_store": false
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 10,
      "total_products": 195,
      "per_page": 20,
      "has_more": true
    },
    "filters": {
      "categories": [
        {
          "id": 1,
          "name": "Groceries",
          "image": "https://...",
          "parent_id": null,
          "is_subcategory": false
        },
        {
          "id": 5,
          "name": "Rice",
          "image": "https://...",
          "parent_id": 1,
          "is_subcategory": true
        }
      ],
      "stores": [
        {
          "id": 2,
          "name": "ABC Store",
          "logo": "https://..."
        }
      ],
      "types": [
        {
          "id": "all",
          "name": "All Types",
          "selected": false
        },
        {
          "id": "veg",
          "name": "Veg",
          "selected": true
        },
        {
          "id": "non_veg",
          "name": "Non-Veg",
          "selected": false
        }
      ]
    }
  }
}
```

### Filter Logic

**Category Filter:**
```php
// Matches category + all subcategories
whereHas('category', function($q) use ($categoryId) {
    $q->where('id', $categoryId)->orWhere('parent_id', $categoryId);
})
```

**Type Filter:**
```php
// veg = 1 (vegetarian), veg = 0 (non-vegetarian)
if ($type === 'veg') {
    $query->where('veg', 1);
} elseif ($type === 'non_veg') {
    $query->where('veg', 0);
}
```

---

## Testing

### Manual Testing Steps

1. **Test Category Filter:**
   - Navigate to Product Gallery
   - Select a category from dropdown
   - Verify only products from that category are shown
   - Check URL has `?category_id=X`

2. **Test Type Filter:**
   - Select "Veg" from type dropdown
   - Verify only vegetarian products shown
   - Check URL has `?type=veg`

3. **Test Combined Filters:**
   - Search for "rice"
   - Select "Groceries" category
   - Select "Veg" type
   - Verify results match all 3 filters
   - Check URL: `?search=rice&category_id=3&type=veg`

4. **Test Filter Persistence:**
   - Apply filters
   - Reload page
   - Verify filters remain selected
   - Navigate to next page
   - Verify filters still applied

5. **Test Reset:**
   - Select "All Categories"
   - Select "All Types"
   - Clear search box
   - Verify all products shown

---

## Database Schema

### Categories Table
```sql
categories:
  - id
  - name
  - parent_id (nullable - for subcategories)
  - position (0 = root category)
  - module_id
  - status
```

### Items Table
```sql
items:
  - id
  - name
  - barcode
  - category_id (foreign key to categories)
  - veg (0 = non-veg, 1 = veg)
  - module_id
  - is_approved
  - status
```

---

## Translation Keys Used

```php
translate('messages.all_categories')
translate('messages.select_category')
translate('messages.all_types')
translate('messages.select_type')
translate('messages.veg')
translate('messages.non_veg')
translate('messages.search_name_barcode')
translate('messages.search')
```

---

## Performance Considerations

### Category Query Optimization
- Uses eager loading: `whereHas('category')` with indexed joins
- Only loads root categories (position = 0) initially
- Subcategories loaded via relationship (1 query)
- Select2 adds client-side search caching

### Search Performance
- Barcode search uses indexed column
- Name search uses full-text where available
- Limited to 100 results to prevent memory issues
- Module filtering reduces search space

### Pagination
- 32 items per page (configurable)
- Uses Laravel's efficient pagination
- Preserves query parameters in pagination links

---

## Files Modified

1. **resources/views/vendor-views/product/product_gallery.blade.php**
   - Added category filter dropdown (lines 37-47)
   - Added type filter dropdown (lines 48-53)
   - Updated layout to 4-column responsive grid
   - Updated JavaScript for filter change handlers
   - Modified form submit to include all filters

2. **app/Http/Controllers/Vendor/ItemController.php**
   - Added `category_id` parameter to `search()` method (line 971)
   - Added `type` parameter to `search()` method (line 972)
   - Added category filtering logic (lines 983-987)
   - Added type filtering scope (line 988)

---

## Backward Compatibility

✅ **100% Backward Compatible**

- Existing searches without filters continue to work
- Default values (`all`) mean no filtering applied
- URL parameters optional
- No database changes required
- No breaking changes to API

---

## Future Enhancements

Possible improvements for later:

1. **Store Filter** - Filter by source store (backend already supports it)
2. **Price Range Filter** - Min/max price sliders
3. **Rating Filter** - Show only 4+ star products
4. **Stock Filter** - Show only in-stock products
5. **AJAX Filters** - Apply filters without page reload
6. **Filter Count Badges** - Show "5 filters applied"
7. **Save Filter Presets** - Save commonly used filter combinations
8. **Export Filtered Results** - Download CSV of filtered products

---

## Result

✅ **Feature Complete**

Vendors can now:
- Filter products by category and subcategories
- Filter products by type (veg/non-veg)
- Combine filters with search
- Share filtered URLs
- Navigate pages while preserving filters

**User Experience:**
- Faster product discovery
- More relevant search results
- Industry-standard filtering UX
- Mobile-responsive design

---

**Implemented:** 2026-02-25
**Developer:** Claude Code
**Status:** Complete ✅
