# Frontend Changes Guide - Outside Purchase Approval

## Overview
The outside purchase feature now requires admin approval. Delivery men submit requests that admins must approve/reject.

---

## 🚨 REQUIRED CHANGES

### 1. Delivery Man Mobile App

#### A. API Response Changes

**Endpoint:** `POST /api/v1/delivery-man/mark-outside-purchase`

**OLD Response (Before):**
```json
{
    "message": "Outside purchase marked successfully"
}
```

**NEW Response (After):**
```json
{
    "message": "Outside purchase request submitted for approval",
    "status": "pending"
}
```

#### B. Update Success Handler

**Before:**
```javascript
// Show: "Outside purchase marked successfully"
showToast(response.message);
```

**After:**
```javascript
// Show: "Request submitted for approval"
showToast(response.message);
// Optional: Show status badge
showStatusBadge(response.status); // 'pending'
```

---

#### C. Update Order Detail Model

Add new fields to your OrderDetail model:

```dart
// Example for Flutter
class OrderDetail {
  // Existing fields
  bool? isOutsidePurchase;
  double? outsidePurchaseCost;
  int? outsidePurchaseStoreId;

  // NEW FIELDS - Add these
  String? outsidePurchaseStatus;        // 'pending', 'approved', 'rejected', or null
  String? outsidePurchaseRequestedBy;   // 'deliveryman' or 'admin'
  String? outsidePurchaseRejectionReason; // Reason if rejected
  DateTime? outsidePurchaseApprovedAt;  // Approval timestamp

  // ... rest of model
}
```

```kotlin
// Example for Kotlin (Android)
data class OrderDetail(
    // Existing fields
    val isOutsidePurchase: Boolean?,
    val outsidePurchaseCost: Double?,
    val outsidePurchaseStoreId: Int?,

    // NEW FIELDS - Add these
    val outsidePurchaseStatus: String?,        // 'pending', 'approved', 'rejected', or null
    val outsidePurchaseRequestedBy: String?,   // 'deliveryman' or 'admin'
    val outsidePurchaseRejectionReason: String?, // Reason if rejected
    val outsidePurchaseApprovedAt: String?     // Approval timestamp
)
```

```swift
// Example for Swift (iOS)
struct OrderDetail {
    // Existing fields
    var isOutsidePurchase: Bool?
    var outsidePurchaseCost: Double?
    var outsidePurchaseStoreId: Int?

    // NEW FIELDS - Add these
    var outsidePurchaseStatus: String?        // 'pending', 'approved', 'rejected', or nil
    var outsidePurchaseRequestedBy: String?   // 'deliveryman' or 'admin'
    var outsidePurchaseRejectionReason: String? // Reason if rejected
    var outsidePurchaseApprovedAt: String?    // Approval timestamp
}
```

---

#### D. Update UI to Show Status

**Order Item View - Add Status Badges:**

```dart
// Flutter Example
Widget buildOutsidePurchaseStatus(OrderDetail item) {
  if (item.outsidePurchaseStatus == null) {
    return SizedBox.shrink(); // No status
  }

  Color badgeColor;
  String badgeText;
  IconData badgeIcon;

  switch (item.outsidePurchaseStatus) {
    case 'pending':
      badgeColor = Colors.orange;
      badgeText = 'Pending Approval';
      badgeIcon = Icons.hourglass_empty;
      break;
    case 'approved':
      badgeColor = Colors.green;
      badgeText = 'Approved';
      badgeIcon = Icons.check_circle;
      break;
    case 'rejected':
      badgeColor = Colors.red;
      badgeText = 'Rejected';
      badgeIcon = Icons.cancel;
      break;
    default:
      return SizedBox.shrink();
  }

  return Container(
    padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
    decoration: BoxDecoration(
      color: badgeColor.withOpacity(0.1),
      borderRadius: BorderRadius.circular(4),
      border: Border.all(color: badgeColor),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(badgeIcon, size: 14, color: badgeColor),
        SizedBox(width: 4),
        Text(
          badgeText,
          style: TextStyle(color: badgeColor, fontSize: 12),
        ),
      ],
    ),
  );
}

// Show rejection reason if rejected
Widget buildRejectionReason(OrderDetail item) {
  if (item.outsidePurchaseStatus == 'rejected' &&
      item.outsidePurchaseRejectionReason != null &&
      item.outsidePurchaseRejectionReason!.isNotEmpty) {
    return Padding(
      padding: EdgeInsets.only(top: 4),
      child: Text(
        'Reason: ${item.outsidePurchaseRejectionReason}',
        style: TextStyle(color: Colors.red[700], fontSize: 11, fontStyle: FontStyle.italic),
      ),
    );
  }
  return SizedBox.shrink();
}
```

---

#### E. Update Order List View

Show visual indicator for orders with pending outside purchases:

```dart
// Flutter Example
Widget buildOrderCard(Order order) {
  bool hasPendingOutsidePurchase = order.details?.any(
    (detail) => detail.outsidePurchaseStatus == 'pending'
  ) ?? false;

  return Card(
    child: Column(
      children: [
        // Order header
        ListTile(
          title: Text('Order #${order.id}'),
          trailing: hasPendingOutsidePurchase
            ? Chip(
                label: Text('Pending Approval', style: TextStyle(fontSize: 10)),
                backgroundColor: Colors.orange[100],
                avatar: Icon(Icons.pending, size: 14, color: Colors.orange),
              )
            : null,
        ),
        // Order items...
      ],
    ),
  );
}
```

---

#### F. Add Store Selection Feature (NEW)

When marking an item as outside purchase, delivery men should be able to select which store they purchased from.

**Step 1: Fetch Stores List**

```dart
// Flutter Example - API Service
class DeliveryManService {
  Future<List<Store>> getStoresForOutsidePurchase({String? search}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/v1/delivery-man/get-stores-for-outside-purchase?search=${search ?? ""}'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return (data['stores'] as List)
          .map((store) => Store.fromJson(store))
          .toList();
    }
    throw Exception('Failed to load stores');
  }
}

// Store Model
class Store {
  final int id;
  final String name;
  final String address;
  final String? zone;
  final String displayName;

  Store({
    required this.id,
    required this.name,
    required this.address,
    this.zone,
    required this.displayName,
  });

  factory Store.fromJson(Map<String, dynamic> json) {
    return Store(
      id: json['id'],
      name: json['name'],
      address: json['address'],
      zone: json['zone'],
      displayName: json['display_name'],
    );
  }
}
```

**Step 2: Create Store Picker UI**

```dart
// Flutter Example - Store Selection Dialog
Future<Store?> showStorePickerDialog(BuildContext context) async {
  List<Store> stores = [];
  String searchQuery = '';
  bool isLoading = true;

  return showDialog<Store>(
    context: context,
    builder: (context) => StatefulBuilder(
      builder: (context, setState) {
        // Load stores on first render or search change
        if (isLoading) {
          DeliveryManService().getStoresForOutsidePurchase(search: searchQuery).then((data) {
            setState(() {
              stores = data;
              isLoading = false;
            });
          });
        }

        return AlertDialog(
          title: Text('Select Store'),
          content: Container(
            width: double.maxFinite,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Search field
                TextField(
                  decoration: InputDecoration(
                    hintText: 'Search by store name...',
                    prefixIcon: Icon(Icons.search),
                  ),
                  onChanged: (value) {
                    setState(() {
                      searchQuery = value;
                      isLoading = true;
                    });
                  },
                ),
                SizedBox(height: 16),
                // Store list
                Expanded(
                  child: isLoading
                      ? Center(child: CircularProgressIndicator())
                      : ListView.builder(
                          shrinkWrap: true,
                          itemCount: stores.length + 1, // +1 for "None" option
                          itemBuilder: (context, index) {
                            if (index == 0) {
                              // "No store selected" option
                              return ListTile(
                                leading: Icon(Icons.clear, color: Colors.grey),
                                title: Text('Not from a listed store'),
                                onTap: () => Navigator.pop(context, null),
                              );
                            }

                            final store = stores[index - 1];
                            return ListTile(
                              leading: Icon(Icons.store, color: Colors.blue),
                              title: Text(store.name),
                              subtitle: Text('${store.zone ?? "N/A"} - ${store.address}'),
                              onTap: () => Navigator.pop(context, store),
                            );
                          },
                        ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text('Cancel'),
            ),
          ],
        );
      },
    ),
  );
}
```

**Step 3: Use Store Picker in Outside Purchase Flow**

```dart
// Flutter Example - Mark Outside Purchase Dialog
Future<void> markAsOutsidePurchase(BuildContext context, OrderDetail item) async {
  final costController = TextEditingController(text: item.price.toString());
  Store? selectedStore;

  showDialog(
    context: context,
    builder: (context) => AlertDialog(
      title: Text('Outside Purchase'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Purchase cost input
          TextField(
            controller: costController,
            keyboardType: TextInputType.numberWithOptions(decimal: true),
            decoration: InputDecoration(
              labelText: 'Purchase Cost',
              prefixText: '\$',
            ),
          ),
          SizedBox(height: 16),

          // Store selection button
          InkWell(
            onTap: () async {
              final store = await showStorePickerDialog(context);
              setState(() {
                selectedStore = store;
              });
            },
            child: InputDecorator(
              decoration: InputDecoration(
                labelText: 'Purchased From Store (Optional)',
                suffixIcon: Icon(Icons.arrow_drop_down),
              ),
              child: Text(
                selectedStore?.displayName ?? 'Not from a listed store',
                style: TextStyle(fontSize: 14),
              ),
            ),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text('Cancel'),
        ),
        ElevatedButton(
          onPressed: () async {
            final cost = double.tryParse(costController.text);
            if (cost == null || cost <= 0) {
              // Show error
              return;
            }

            // Submit outside purchase request
            await DeliveryManService().markOutsidePurchase(
              orderDetailId: item.id,
              cost: cost,
              storeId: selectedStore?.id, // Can be null
            );

            Navigator.pop(context);
            // Show success message
          },
          child: Text('Submit'),
        ),
      ],
    ),
  );
}
```

**Features:**
- ✅ Searchable store list (not filtered by zone)
- ✅ Optional selection - can choose "Not from a listed store"
- ✅ Shows store name, zone, and address
- ✅ Live search with debouncing
- ✅ Limit 50 results per query

---

#### G. Update String Resources

Add new translations:

**English (`strings_en.json` or similar):**
```json
{
  "outside_purchase_pending": "Pending Admin Approval",
  "outside_purchase_approved": "Approved",
  "outside_purchase_rejected": "Rejected",
  "outside_purchase_request_submitted": "Request submitted for approval",
  "rejection_reason": "Rejection Reason",
  "waiting_approval": "Waiting for admin approval",
  "admin_approved": "Admin approved this purchase",
  "admin_rejected": "Admin rejected this request"
}
```

---

### 2. Admin Web Panel (Already Done ✅)

The admin web panel has been updated. No additional changes needed.

---

### 3. Customer App (Optional - If Applicable)

If customers can see outside purchase information:

#### Update Order Detail View
- Show status badges similar to delivery man app
- Show "Pending" for items awaiting approval
- Don't show cost details until approved

---

## 🎨 UI/UX Recommendations

### Status Color Scheme
```
Pending:  🟠 Orange/Yellow (#FF9800)
Approved: 🟢 Green (#4CAF50)
Rejected: 🔴 Red (#F44336)
```

### Icons Recommendation
```
Pending:  ⏳ hourglass, clock, pending
Approved: ✓  checkmark, check_circle
Rejected: ✕  cancel, close, error
```

### User Messages

**When Submitting Request:**
```
Success: "Outside purchase request submitted! Waiting for admin approval."
```

**In Order Details:**
```
Pending:  "🟠 Pending admin approval"
Approved: "✓ Approved by admin"
Rejected: "✕ Rejected: [reason]"
```

---

## 📋 Testing Checklist

### Delivery Man App
- [ ] Submit outside purchase request
- [ ] Verify "pending" status shows in UI
- [ ] Check success message displays correctly
- [ ] Verify pending badge appears on order item
- [ ] Test viewing rejected request with reason
- [ ] Test viewing approved request
- [ ] Check order list shows pending indicator
- [ ] Verify all translations work in all languages

### API Integration
- [ ] Test API response parsing
- [ ] Handle new status field correctly
- [ ] Handle null/missing status field (backward compatibility)
- [ ] Test error scenarios

---

## 🔄 Backward Compatibility

**Important:** The API will return new fields, but old app versions should still work:

```dart
// Safe parsing example
outsidePurchaseStatus: json['outside_purchase_status'] ?? null,
```

**Migration Strategy:**
1. Update backend first (already done)
2. Update mobile apps
3. Force update or gradual rollout
4. Old apps will work but won't show status (degraded experience)

---

## 📱 API Endpoints Reference

### Get Stores List (NEW - Delivery Man)
```
GET /api/v1/delivery-man/get-stores-for-outside-purchase

Headers:
  Authorization: Bearer {token}

Query Parameters:
  search (optional): Search by store name or ID
  order_id (optional): Auto-filter by order's module
  module_id (optional): Filter by specific module ID
  limit (optional): Max results (default: 100)

Response (Success):
{
  "stores": [
    {
      "id": 1,
      "name": "Store Name",
      "address": "123 Main St",
      "zone": "North Zone",
      "module": "Food",
      "display_name": "Store Name - North Zone"
    },
    {
      "id": 2,
      "name": "Another Store",
      "address": "456 Park Ave",
      "zone": "South Zone",
      "module": "Food",
      "display_name": "Another Store - South Zone"
    }
  ],
  "total": 2,
  "module_filtered": true,
  "module_id": 4
}

Features:
- Filters by order's module (recommended: pass order_id)
- Not filtered by zone (all zones shown)
- Supports search by name or ID
- Limited to 100 results per query (configurable)
- Can be used to populate searchable store picker
```

### Mark Outside Purchase (Delivery Man)
```
POST /api/v1/delivery-man/mark-outside-purchase

Headers:
  Authorization: Bearer {token}
  Content-Type: application/json

Request Body:
{
  "order_detail_id": 123,
  "outside_purchase_cost": 25.50,
  "outside_purchase_store_id": 45  // OPTIONAL - Can be null or omitted
}

Response (Success):
{
  "message": "Outside purchase request submitted for approval",
  "status": "pending"
}
```

### Get Order Details (Delivery Man)
```
GET /api/v1/delivery-man/order-details?order_id={id}

Response includes new fields in order_details:
{
  "order_details": [
    {
      "id": 123,
      "is_outside_purchase": false,  // false until approved
      "outside_purchase_cost": 25.50,
      "outside_purchase_store_id": 45,
      "outside_purchase_status": "pending",  // NEW
      "outside_purchase_requested_by": "deliveryman",  // NEW
      "outside_purchase_rejection_reason": null,  // NEW
      "outside_purchase_approved_at": null  // NEW
    }
  ]
}
```

---

## 💡 Example Implementations

### Complete Flutter Widget Example

```dart
class OrderItemCard extends StatelessWidget {
  final OrderDetail item;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Item name and details
            Text(item.itemName, style: TextStyle(fontWeight: FontWeight.bold)),
            SizedBox(height: 8),

            // Outside purchase status
            if (item.outsidePurchaseStatus != null) ...[
              _buildStatusBadge(item),
              if (item.outsidePurchaseStatus == 'rejected' &&
                  item.outsidePurchaseRejectionReason != null)
                _buildRejectionReason(item),
              SizedBox(height: 8),
            ],

            // Show cost details
            if (item.outsidePurchaseStatus == 'approved' ||
                item.outsidePurchaseStatus == 'pending')
              Text('Outside Purchase Cost: \$${item.outsidePurchaseCost}'),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusBadge(OrderDetail item) {
    final config = _getStatusConfig(item.outsidePurchaseStatus!);
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: config['color'].withOpacity(0.1),
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: config['color']),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(config['icon'], size: 14, color: config['color']),
          SizedBox(width: 4),
          Text(config['text'], style: TextStyle(color: config['color'], fontSize: 12)),
        ],
      ),
    );
  }

  Widget _buildRejectionReason(OrderDetail item) {
    return Padding(
      padding: EdgeInsets.only(top: 4),
      child: Text(
        'Reason: ${item.outsidePurchaseRejectionReason}',
        style: TextStyle(color: Colors.red[700], fontSize: 11, fontStyle: FontStyle.italic),
      ),
    );
  }

  Map<String, dynamic> _getStatusConfig(String status) {
    switch (status) {
      case 'pending':
        return {'color': Colors.orange, 'text': 'Pending Approval', 'icon': Icons.hourglass_empty};
      case 'approved':
        return {'color': Colors.green, 'text': 'Approved', 'icon': Icons.check_circle};
      case 'rejected':
        return {'color': Colors.red, 'text': 'Rejected', 'icon': Icons.cancel};
      default:
        return {'color': Colors.grey, 'text': 'Unknown', 'icon': Icons.help};
    }
  }
}
```

---

## 🆘 Support

If you need help implementing these changes:
1. Check the API documentation
2. Test with Postman first
3. Verify response structure matches expectations
4. Contact backend team if API responses differ from this guide
