# Store Selection Feature - Outside Purchase

## Overview
Users can now select from a list of **all active stores** (not filtered by zone) when marking items as outside purchase.

---

## 🎯 Key Features

### ✅ For Admin Panel
- **Select2 AJAX Search** - Searchable dropdown with autocomplete
- **No Zone Filtering** - Shows all active stores across all zones
- **Rich Display** - Shows store name, zone, and address
- **Optional Selection** - Can leave empty if not from a listed store
- **Performance Optimized** - AJAX loading, max 50 results per query

### ✅ For Delivery Man App
- **REST API Endpoint** - Get stores list with search
- **No Zone Restrictions** - All stores available for selection
- **Searchable** - Filter by store name or ID
- **Optional** - `outside_purchase_store_id` parameter is optional

---

## 📍 Implementation Details

### Backend Endpoints

#### 1. Admin Route
```
GET /admin/order/get-stores-for-outside-purchase?search={query}
```

**Response:**
```json
[
  {
    "id": 1,
    "text": "Store Name - Zone Name",
    "name": "Store Name",
    "address": "123 Main St",
    "zone": "North Zone"
  }
]
```

#### 2. Delivery Man API Route
```
GET /api/v1/delivery-man/get-stores-for-outside-purchase?search={query}

Headers:
  Authorization: Bearer {token}
```

**Response:**
```json
{
  "stores": [
    {
      "id": 1,
      "name": "Store Name",
      "address": "123 Main St",
      "zone": "North Zone",
      "display_name": "Store Name - North Zone"
    }
  ],
  "total": 1
}
```

---

## 🎨 Admin Panel UI

### Before (Old):
```
Purchase Cost: [_____]
Purchased From: [Dropdown with only zone stores]
```

### After (New):
```
Purchase Cost: [_____]
Purchased From: [Select2 Search - Type to search all stores...]
                ↓ (Type "Pizza")
                Pizza Palace - North Zone
                  123 Main St
                Pizza Hut - South Zone
                  456 Park Ave
                (Select store or leave empty)
```

### Features:
- Type to search by store name
- Shows store details (name, zone, address)
- Can be left empty
- AJAX-powered for fast loading

---

## 📱 Mobile App Implementation

### Store Picker UI Example

```
┌─────────────────────────────────────┐
│ Select Store (Optional)             │
├─────────────────────────────────────┤
│ [Search...                      🔍] │
├─────────────────────────────────────┤
│ ⭕ Not from a listed store          │
│                                     │
│ 🏪 Pizza Palace                     │
│    North Zone - 123 Main St         │
│                                     │
│ 🏪 Grocery Mart                     │
│    South Zone - 456 Park Ave        │
│                                     │
│ 🏪 Fresh Foods                      │
│    East Zone - 789 Oak St           │
│                                     │
│ [Cancel]               [Select]     │
└─────────────────────────────────────┘
```

### API Integration Flow

```
1. User taps "Mark as Outside Purchase"
   ↓
2. Show dialog with:
   - Purchase cost input
   - Store selection button
   ↓
3. User taps "Select Store" button
   ↓
4. App calls: GET /api/v1/delivery-man/get-stores-for-outside-purchase
   ↓
5. Display searchable list of stores
   ↓
6. User selects a store (or "None")
   ↓
7. Submit: POST /api/v1/delivery-man/mark-outside-purchase
   {
     "order_detail_id": 123,
     "outside_purchase_cost": 25.50,
     "outside_purchase_store_id": 45  // Can be null
   }
```

---

## 🔧 Technical Specifications

### Query Behavior
- **No Zone Filter** - Returns stores from ALL zones
- **Active Only** - Only stores with `status = 1`
- **Search Support** - By name or ID
- **Limit** - 50 results max per query
- **Ordering** - Alphabetical by name

### Select2 Configuration (Admin)
```javascript
$('#op-store').select2({
    dropdownParent: $('.swal2-container'),
    placeholder: 'Select store or leave empty',
    allowClear: true,
    ajax: {
        url: '/admin/order/get-stores-for-outside-purchase',
        dataType: 'json',
        delay: 250,
        data: function (params) {
            return { search: params.term };
        }
    },
    minimumInputLength: 0
});
```

---

## 📋 Testing Checklist

### Admin Panel
- [ ] Can search stores by name
- [ ] Shows all zones (not filtered)
- [ ] Can select a store
- [ ] Can leave empty (no store selected)
- [ ] Search works with partial names
- [ ] Shows store details (name, zone, address)
- [ ] Outside purchase saves with selected store ID
- [ ] Displays selected store name in order view

### Delivery Man App
- [ ] API returns all stores (not zone-filtered)
- [ ] Search parameter works correctly
- [ ] Can select a store from list
- [ ] Can choose "Not from a listed store"
- [ ] Selected store ID sent to mark-outside-purchase API
- [ ] UI shows selected store name
- [ ] Works without selecting a store (null store_id)

### API Testing
- [ ] GET stores endpoint returns correct data
- [ ] Search filters results correctly
- [ ] Returns max 50 results
- [ ] Returns empty array if no matches
- [ ] Authorization works (for delivery man API)

---

## 🎓 Examples

### Example 1: Admin selects store
```
Input:
  Cost: $15.00
  Store: "Pizza Palace - North Zone"

Result:
  outside_purchase_cost: 15.00
  outside_purchase_store_id: 42
  Display: "🔸 Outside Purchase | Cost: $15.00 | Pizza Palace"
```

### Example 2: Admin doesn't select store
```
Input:
  Cost: $15.00
  Store: (empty)

Result:
  outside_purchase_cost: 15.00
  outside_purchase_store_id: null
  Display: "🔸 Outside Purchase | Cost: $15.00"
```

### Example 3: Delivery man searches and selects
```
API Call 1:
  GET /api/v1/delivery-man/get-stores-for-outside-purchase?search=pizza

Response:
  {
    "stores": [
      {"id": 1, "name": "Pizza Palace", "display_name": "Pizza Palace - North Zone"},
      {"id": 5, "name": "Pizza Hut", "display_name": "Pizza Hut - South Zone"}
    ],
    "total": 2
  }

API Call 2:
  POST /api/v1/delivery-man/mark-outside-purchase
  {
    "order_detail_id": 123,
    "outside_purchase_cost": 20.00,
    "outside_purchase_store_id": 1
  }
```

---

## 🔄 Backward Compatibility

- Old apps that don't send `outside_purchase_store_id` will still work
- Field is optional and defaults to `null`
- No breaking changes to existing functionality
- Admin can still leave store selection empty

---

## 💡 Benefits

1. **Flexibility** - Not restricted to zone stores
2. **Accuracy** - Track exactly which store items were purchased from
3. **Reporting** - Better data for analytics and commission calculations
4. **User Experience** - Easy search and selection
5. **Optional** - Don't force selection if unknown

---

## 🆘 Troubleshooting

### Issue: Select2 dropdown not showing
**Solution:** Ensure Select2 library is loaded and `didOpen` callback initializes it

### Issue: No stores appearing in list
**Solution:** Check that stores have `status = 1` in database

### Issue: Search not working
**Solution:** Verify AJAX URL is correct and CSRF token is set

### Issue: Zone filter still applied
**Solution:** Ensure backend query doesn't include zone filtering (should only filter by status and search)

---

## 📚 Related Documentation

- `OUTSIDE_PURCHASE_APPROVAL_IMPLEMENTATION.md` - Full approval workflow
- `FRONTEND_CHANGES_GUIDE.md` - Mobile app implementation guide
