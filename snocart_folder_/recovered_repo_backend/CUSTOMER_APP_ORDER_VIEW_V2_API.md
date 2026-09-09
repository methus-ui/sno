# Customer App - Order View V2 with Bargaining Integration API

## 📋 Table of Contents
1. [Order Details API (with Bargaining Data)](#1-order-details-api)
2. [Bargaining APIs](#2-bargaining-apis)
3. [Response Structures](#3-response-structures)
4. [Integration Guide](#4-integration-guide)
5. [UI Components](#5-ui-components)

---

## 1. Order Details API (with Bargaining Data)

### **Endpoint: Get Order Details V2 (with Bargaining)**
```
GET /api/v1/customer/order/details-v2/{order_id}
```

**Headers:**
```json
{
  "Authorization": "Bearer {token}",
  "Content-Type": "application/json"
}
```

**Response:**
```json
{
  "id": 109955,
  "order_status": "pending",
  "order_amount": 784.00,
  "payment_method": "cash_on_delivery",
  "order_type": "delivery",
  "created_at": "2024-03-26 10:30:00",

  // Bargaining-specific fields
  "is_bargaining_order": true,
  "bargaining_data": {
    "request_code": "BR-ABC123",
    "mode": "instant",
    "original_cart_value": 900.00,
    "final_price": 784.00,
    "total_savings": 116.00,
    "savings_percentage": 12.9,
    "total_offers_received": 8,
    "winning_store": {
      "id": 203,
      "name": "Fresh Mart",
      "logo": "https://new.snocart.com/storage/store/...",
      "rating": 4.5
    },
    "fulfillment_percentage": 100,
    "items_missing": 0,
    "missing_items_detail": [],
    "vendor_notes": "All items available. Ready for delivery.",
    "time_to_accept": 120,
    "competing_offers": [
      {
        "rank": 1,
        "store_name": "Fresh Mart",
        "store_logo": "https://...",
        "total_amount": 784.00,
        "fulfillment_percentage": 100,
        "is_winner": true
      },
      {
        "rank": 2,
        "store_name": "Quick Shop",
        "store_logo": "https://...",
        "total_amount": 820.00,
        "fulfillment_percentage": 95,
        "is_winner": false
      }
      // ... top 5 offers
    ]
  },

  // Standard order fields
  "customer": {
    "id": 1,
    "name": "John Doe",
    "phone": "+91 9876543210",
    "email": "john@example.com"
  },

  "store": {
    "id": 203,
    "name": "Fresh Mart",
    "logo": "https://...",
    "phone": "+91 9876543210",
    "address": "123 Main St"
  },

  "delivery_address": {
    "address": "456 Elm Street",
    "latitude": 28.6139,
    "longitude": 77.2090
  },

  "items": [
    {
      "id": 1,
      "name": "Fresh Milk",
      "quantity": 2,
      "price": 50.00,
      "original_price": 60.00,  // Only for bargaining orders
      "savings_per_item": 10.00, // Only for bargaining orders
      "image": "https://...",
      "variations": []
    }
  ],

  "order_summary": {
    "subtotal": 684.00,
    "bargaining_discount": -116.00,  // Only for bargaining orders
    "store_discount": 0.00,
    "tax": 50.00,
    "delivery_fee": 50.00,
    "total": 784.00
  }
}
```

---

## 2. Bargaining APIs

### **2.1 Initiate Bargaining**
```
POST /api/v2/customer/bargaining/initiate
```

**Request:**
```json
{
  "mode": "instant",  // or "wait"
  "cart_items": [
    {
      "item_id": 123,
      "quantity": 2,
      "variations": []
    }
  ],
  "delivery_address_id": 456,
  "customer_budget": 800.00  // optional
}
```

**Response:**
```json
{
  "request_code": "BR-ABC123",
  "status": "matching",
  "estimated_response_time": "2-5 minutes",
  "total_stores_matched": 15,
  "message": "Finding best offers from stores near you..."
}
```

---

### **2.2 Get Bargaining Status**
```
GET /api/v2/customer/bargaining/{request_code}/status
```

**Response:**
```json
{
  "request_code": "BR-ABC123",
  "status": "offers_received",
  "original_cart_value": 900.00,
  "total_offers_received": 8,
  "best_offer": {
    "store_id": 203,
    "store_name": "Fresh Mart",
    "store_logo": "https://...",
    "total_amount": 784.00,
    "savings": 116.00,
    "savings_percentage": 12.9,
    "fulfillment_percentage": 100,
    "delivery_time": "30-40 mins",
    "rank": 1
  },
  "all_offers": [
    {
      "store_id": 203,
      "store_name": "Fresh Mart",
      "store_logo": "https://...",
      "total_amount": 784.00,
      "savings": 116.00,
      "savings_percentage": 12.9,
      "fulfillment_percentage": 100,
      "items_available": 15,
      "items_missing": 0,
      "special_discount": 20.00,
      "vendor_notes": "All items in stock",
      "rank": 1,
      "rating": 4.5,
      "delivery_time": "30-40 mins"
    },
    {
      "store_id": 204,
      "store_name": "Quick Shop",
      "store_logo": "https://...",
      "total_amount": 820.00,
      "savings": 80.00,
      "savings_percentage": 8.9,
      "fulfillment_percentage": 95,
      "items_available": 14,
      "items_missing": 1,
      "missing_items": ["Organic Milk"],
      "vendor_notes": "One item unavailable",
      "rank": 2,
      "rating": 4.3,
      "delivery_time": "25-35 mins"
    }
    // ... more offers
  ]
}
```

---

### **2.3 Accept Offer**
```
POST /api/v2/customer/bargaining/{request_code}/accept-offer
```

**Request:**
```json
{
  "offer_id": 789
}
```

**Response:**
```json
{
  "request_code": "BR-ABC123",
  "status": "accepted",
  "accepted_offer": {
    "store_id": 203,
    "store_name": "Fresh Mart",
    "total_amount": 784.00,
    "savings": 116.00
  },
  "message": "Offer accepted! Proceed to checkout.",
  "cart_updated": true
}
```

---

### **2.4 Place Order (After Accepting Offer)**
```
POST /api/v1/customer/order/place
```

**Request:**
```json
{
  "order_amount": 784.00,
  "payment_method": "cash_on_delivery",
  "order_type": "delivery",
  "store_id": 203,
  "address_id": 456,
  // ... other standard order fields
}
```

**Response:**
```json
{
  "order_id": 109955,
  "message": "Order placed successfully",
  "is_bargaining_order": true,
  "savings": 116.00
}
```

---

## 3. Response Structures

### **3.1 Bargaining Data Object**
```typescript
interface BargainingData {
  request_code: string;           // "BR-ABC123"
  mode: "instant" | "wait";
  original_cart_value: number;    // 900.00
  final_price: number;            // 784.00
  total_savings: number;          // 116.00
  savings_percentage: number;     // 12.9
  total_offers_received: number;  // 8
  winning_store: {
    id: number;
    name: string;
    logo: string;
    rating: number;
  };
  fulfillment_percentage: number; // 100
  items_missing: number;          // 0
  missing_items_detail: string[]; // ["Item name"]
  vendor_notes: string;
  time_to_accept: number;         // seconds
  competing_offers: CompetingOffer[];
}

interface CompetingOffer {
  rank: number;
  store_name: string;
  store_logo: string;
  total_amount: number;
  fulfillment_percentage: number;
  is_winner: boolean;
}
```

---

## 4. Integration Guide

### **Step 1: Check if Order has Bargaining Data**

```dart
// Flutter example
Future<OrderDetails> fetchOrderDetails(int orderId) async {
  final response = await http.get(
    Uri.parse('$baseUrl/api/v1/customer/orders/details/$orderId'),
    headers: {'Authorization': 'Bearer $token'},
  );

  final data = json.decode(response.body);
  return OrderDetails.fromJson(data);
}

// Check if bargaining order
if (order.isBargainingOrder && order.bargainingData != null) {
  // Show bargaining UI components
  showBargainingHeroCard(order.bargainingData);
  showPriceComparison(order.items, order.bargainingData);
  showCompetingOffers(order.bargainingData.competingOffers);
} else {
  // Show standard order UI
  showStandardOrderView(order);
}
```

---

### **Step 2: Display Bargaining Hero Card**

```dart
Widget buildBargainingHeroCard(BargainingData data) {
  return Container(
    decoration: BoxDecoration(
      gradient: LinearGradient(
        colors: [Color(0xFF667EEA), Color(0xFF764BA2)],
      ),
    ),
    child: Column(
      children: [
        // Trophy icon + "Won via Bargaining"
        Row(
          children: [
            Icon(Icons.emoji_events, color: Colors.white),
            Text('Won via Bargaining', style: TextStyle(color: Colors.white)),
          ],
        ),

        // Metrics grid
        Row(
          children: [
            MetricBox(
              label: 'Total Savings',
              value: '₹${data.totalSavings}',
              subtitle: '${data.savingsPercentage}% off',
            ),
            MetricBox(
              label: 'Fulfillment',
              value: '${data.fulfillmentPercentage}%',
            ),
            MetricBox(
              label: 'Rank',
              value: '#1 of ${data.totalOffersReceived}',
            ),
          ],
        ),

        // Vendor notes
        if (data.vendorNotes.isNotEmpty)
          Text(data.vendorNotes),
      ],
    ),
  );
}
```

---

### **Step 3: Display Price Comparison**

```dart
Widget buildItemRow(OrderItem item, bool isBargaining) {
  return Row(
    children: [
      // Item image
      Image.network(item.image, width: 50, height: 50),

      // Item name
      Text(item.name),

      // Quantity
      Text('x${item.quantity}'),

      // Price comparison (only for bargaining)
      if (isBargaining) ...[
        // Original price (strikethrough)
        Text(
          '₹${item.originalPrice}',
          style: TextStyle(
            decoration: TextDecoration.lineThrough,
            color: Colors.grey,
          ),
        ),

        // Bargained price (green)
        Text(
          '₹${item.price}',
          style: TextStyle(
            color: Colors.green,
            fontWeight: FontWeight.bold,
          ),
        ),

        // Savings badge
        Container(
          padding: EdgeInsets.all(4),
          decoration: BoxDecoration(
            color: Colors.green[100],
            borderRadius: BorderRadius.circular(4),
          ),
          child: Text(
            'Saved ₹${item.savingsPerItem}',
            style: TextStyle(color: Colors.green[800]),
          ),
        ),
      ] else ...[
        Text('₹${item.price}'),
      ],
    ],
  );
}
```

---

### **Step 4: Display Competing Offers**

```dart
Widget buildCompetingOffersSection(List<CompetingOffer> offers) {
  return ExpansionTile(
    title: Text('View Competing Offers (${offers.length})'),
    children: offers.map((offer) {
      return Card(
        color: offer.isWinner ? Colors.green[50] : Colors.white,
        child: ListTile(
          leading: CircleAvatar(
            child: Text('#${offer.rank}'),
            backgroundColor: offer.isWinner ? Colors.green : Colors.grey,
          ),
          title: Text(offer.storeName),
          subtitle: Text('${offer.fulfillmentPercentage}% fulfillment'),
          trailing: Column(
            children: [
              Text('₹${offer.totalAmount}',
                style: TextStyle(fontWeight: FontWeight.bold)),
              if (offer.isWinner)
                Icon(Icons.emoji_events, color: Colors.green),
            ],
          ),
        ),
      );
    }).toList(),
  );
}
```

---

## 5. UI Components

### **5.1 Order Detail Screen Layout**

```
┌─────────────────────────────────────────────────────────────┐
│ ← Back          Order #109955                     ⋮ Menu    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  [IF BARGAINING ORDER]                                      │
│  ╔═══════════════════════════════════════════════════════╗ │
│  ║  🏆 You Won via Bargaining!                           ║ │
│  ║                                                       ║ │
│  ║  💰 Saved ₹116    🎯 100%      🥇 #1 of 8           ║ │
│  ║     (12.9% off)      Fulfilled                       ║ │
│  ╚═══════════════════════════════════════════════════════╝ │
│                                                             │
│  📊 Order Status: PENDING                                   │
│  ──────────────────────────────────────────────────────── │
│  ● Pending        ○ Confirmed       ○ Out for Delivery    │
│                                                             │
│  🏪 Store: Fresh Mart                                       │
│  📍 123 Main Street                                         │
│  ⭐ 4.5 (230 reviews)                                       │
│                                                             │
│  📦 Order Items                                             │
│  ──────────────────────────────────────────────────────── │
│  [Image] Fresh Milk x2                                     │
│          Was ₹60  Now ₹50  [Saved ₹10 badge]              │
│                                                             │
│  [Image] Bread x1                                          │
│          Was ₹40  Now ₹35  [Saved ₹5 badge]               │
│                                                             │
│  💰 Payment Summary                                         │
│  ──────────────────────────────────────────────────────── │
│  Subtotal                               ₹684.00            │
│  Bargaining Discount                   -₹116.00 🎉         │
│  Tax                                     ₹50.00            │
│  Delivery Fee                            ₹50.00            │
│  ──────────────────────────────────────────────────────── │
│  Total                                  ₹784.00            │
│                                                             │
│  📋 View Competing Offers (8) ▼                             │
│                                                             │
│  🚚 Delivery Address                                        │
│  456 Elm Street, Apt 4B                                    │
│                                                             │
│  [Track Order Button]                                      │
│  [Contact Store Button]                                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### **5.2 Competing Offers Expansion**

```
┌─────────────────────────────────────────────────────────────┐
│  📋 View Competing Offers (8) ▲                             │
│  ──────────────────────────────────────────────────────── │
│                                                             │
│  🏆 #1  Fresh Mart                    ₹784 ✓ Winner       │
│       100% fulfilled · All items available                 │
│                                                             │
│     #2  Quick Shop                    ₹820                 │
│       95% fulfilled · 1 item missing                       │
│                                                             │
│     #3  Super Store                   ₹850                 │
│       100% fulfilled                                       │
│                                                             │
│     #4  Daily Needs                   ₹880                 │
│       90% fulfilled · 2 items missing                      │
│                                                             │
│     #5  Corner Shop                   ₹920                 │
│       85% fulfilled                                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 6. API Implementation (Backend)

**Create the customer order details endpoint:**

File: `app/Http/Controllers/Api/V1/CustomerController.php`

```php
/**
 * Get order details with bargaining data
 *
 * @param Request $request
 * @param int $orderId
 * @return JsonResponse
 */
public function getOrderDetails(Request $request, $orderId)
{
    $order = Order::with([
        'details.item',
        'store',
        'customer',
        'delivery_man',
        'bargainingRequest',
        'bargainingAcceptedOffer.store',
        'bargainingRequest.storeOffers' => function($q) {
            $q->where('status', 'submitted')
              ->orderBy('rank', 'asc')
              ->limit(5);
        }
    ])->find($orderId);

    if (!$order) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    // Verify user owns this order
    if ($order->user_id !== $request->user()->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Build response
    $response = [
        'id' => $order->id,
        'order_status' => $order->order_status,
        'order_amount' => $order->order_amount,
        'payment_method' => $order->payment_method,
        'order_type' => $order->order_type,
        'created_at' => $order->created_at,
        'is_bargaining_order' => $order->is_bargaining_order,

        // Bargaining data (only if applicable)
        'bargaining_data' => null,

        // Standard fields
        'customer' => [
            'id' => $order->customer->id,
            'name' => $order->customer->f_name . ' ' . $order->customer->l_name,
            'phone' => $order->customer->phone,
            'email' => $order->customer->email,
        ],

        'store' => [
            'id' => $order->store->id,
            'name' => $order->store->name,
            'logo' => $order->store->logo_full_url,
            'phone' => $order->store->phone,
            'rating' => $order->store->rating,
        ],

        'items' => $order->details->map(function($detail) use ($order) {
            return [
                'id' => $detail->id,
                'name' => $detail->item_name,
                'quantity' => $detail->quantity,
                'price' => $detail->price,
                'original_price' => $order->is_bargaining_order ? ($detail->original_price ?? $detail->price) : null,
                'savings_per_item' => $order->is_bargaining_order ? (($detail->original_price ?? $detail->price) - $detail->price) : null,
                'image' => $detail->item_image_full_url,
            ];
        }),

        'order_summary' => [
            'subtotal' => $order->order_amount - $order->total_tax_amount - $order->delivery_charge,
            'bargaining_discount' => 0,
            'tax' => $order->total_tax_amount,
            'delivery_fee' => $order->delivery_charge,
            'total' => $order->order_amount,
        ],
    ];

    // Add bargaining data if applicable
    if ($order->is_bargaining_order && $order->bargainingRequest) {
        $response['bargaining_data'] = [
            'request_code' => $order->bargainingRequest->request_code,
            'mode' => $order->bargainingRequest->mode,
            'original_cart_value' => $order->bargainingRequest->original_cart_value,
            'final_price' => $order->bargainingRequest->final_price,
            'total_savings' => $order->bargainingRequest->total_savings,
            'savings_percentage' => round(($order->bargainingRequest->total_savings / $order->bargainingRequest->original_cart_value) * 100, 1),
            'total_offers_received' => $order->bargainingRequest->total_offers_received,
            'winning_store' => [
                'id' => $order->store->id,
                'name' => $order->store->name,
                'logo' => $order->store->logo_full_url,
                'rating' => $order->store->rating,
            ],
            'fulfillment_percentage' => $order->bargainingAcceptedOffer?->fulfillment_percentage ?? 100,
            'items_missing' => $order->bargainingAcceptedOffer?->items_missing ?? 0,
            'missing_items_detail' => $order->bargainingAcceptedOffer?->missing_items_detail ?? [],
            'vendor_notes' => $order->bargainingAcceptedOffer?->vendor_notes ?? '',
            'competing_offers' => $order->bargainingRequest->storeOffers->map(function($offer) use ($order) {
                return [
                    'rank' => $offer->rank,
                    'store_name' => $offer->store->name,
                    'store_logo' => $offer->store->logo_full_url,
                    'total_amount' => $offer->total_amount,
                    'fulfillment_percentage' => $offer->fulfillment_percentage,
                    'is_winner' => $offer->id === $order->bargaining_accepted_offer_id,
                ];
            }),
        ];

        $response['order_summary']['bargaining_discount'] = -$order->bargainingRequest->total_savings;
    }

    return response()->json($response);
}
```

---

## 7. Testing

### **7.1 Test with Postman**

**Request:**
```
GET https://new.snocart.com/api/v1/customer/orders/details/109955
Authorization: Bearer {your_token}
```

**Expected Response:**
- 200 OK for successful request
- Order details with all fields
- `is_bargaining_order` = true/false
- `bargaining_data` = null (for regular orders) or object (for bargaining orders)

---

## 8. Summary

### **APIs Needed:**

| Endpoint | Purpose |
|----------|---------|
| `GET /api/v1/customer/orders/details/{id}` | Get order with bargaining data |
| `POST /api/v2/customer/bargaining/initiate` | Start bargaining |
| `GET /api/v2/customer/bargaining/{code}/status` | Get offers |
| `POST /api/v2/customer/bargaining/{code}/accept-offer` | Accept offer |
| `POST /api/v1/customer/order/place` | Place order |

### **Key Features:**

✅ Bargaining hero card with savings
✅ Price comparison (original vs bargained)
✅ Competing offers list (top 5)
✅ Fulfillment percentage
✅ Missing items alert
✅ Vendor notes
✅ Savings badges on items

---

**Next Steps:**
1. Implement the customer order details API endpoint
2. Test with Postman
3. Integrate into customer app UI
4. Add bargaining flow screens

Would you like me to:
1. Create the API controller code?
2. Provide more UI examples?
3. Create a complete Flutter/React Native integration example?
