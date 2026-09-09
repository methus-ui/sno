# Delivery QR Payment API Documentation
## Razorpay Dynamic QR Code Integration for Flutter App

---

## 📋 Table of Contents
1. [Overview](#overview)
2. [Authentication](#authentication)
3. [API Endpoints](#api-endpoints)
4. [Flutter Integration](#flutter-integration)
5. [Webhook Configuration](#webhook-configuration)
6. [Testing](#testing)
7. [Error Handling](#error-handling)

---

## 🎯 Overview

This API allows delivery boys to generate dynamic Razorpay QR codes when confirming delivery. Customers can scan the QR code and pay using any UPI app (Google Pay, PhonePe, Paytm, etc.).

**Flow:**
1. Delivery boy confirms delivery in Flutter app
2. App calls `/generate` API
3. Backend creates Razorpay QR code
4. QR code is displayed to customer
5. Customer scans and pays
6. Webhook notifies backend of payment
7. App polls `/status` API to confirm payment

---

## 🔐 Authentication

All API endpoints (except webhook) require authentication using Bearer token.

**Headers:**
```http
Authorization: Bearer {your_access_token}
Content-Type: application/json
Accept: application/json
```

---

## 📡 API Endpoints

### 1. Generate QR Code

**Endpoint:** `POST /api/v1/delivery/qr-payment/generate`

**Description:** Generates a new Razorpay QR code for delivery payment.

**Request:**
```json
{
  "order_id": 108315,
  "amount": 330.00,
  "delivery_man_id": 244
}
```

**Parameters:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| order_id | integer | Yes | Order ID |
| amount | float | Yes | Amount to collect (in INR) |
| delivery_man_id | integer | No | Delivery man ID (auto-detected if logged in) |

**Success Response (200):**
```json
{
  "success": true,
  "message": "QR code generated successfully",
  "data": {
    "id": 1,
    "order_id": 108315,
    "delivery_man_id": 244,
    "razorpay_qr_id": "qr_1234567890",
    "razorpay_order_id": "order_ABCD1234",
    "amount": 330.00,
    "currency": "INR",
    "status": "active",
    "qr_code_url": "https://api.razorpay.com/v1/qr_codes/qr_1234567890/qr.png",
    "qr_code_data": "upi://pay?pa=test@razorpay&pn=Merchant&am=330.00&...",
    "payment_link": "upi://pay?pa=test@razorpay&pn=Merchant&am=330.00&...",
    "is_expired": false,
    "is_paid": false,
    "expires_at": "2026-02-08T14:10:00.000000Z",
    "paid_at": null,
    "created_at": "2026-02-08T13:40:00.000000Z",
    "order": {
      "id": 108315,
      "order_amount": 330.00,
      "customer": {
        "id": 1099,
        "name": "John Doe",
        "phone": "+919876543210"
      }
    }
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "order_id": ["The order id field is required."],
    "amount": ["The amount must be at least 1."]
  }
}
```

---

### 2. Check QR Payment Status

**Endpoint:** `GET /api/v1/delivery/qr-payment/status/{qr_payment_id}`

**Description:** Check if payment has been received for a QR code.

**Request:**
```http
GET /api/v1/delivery/qr-payment/status/1
```

**Success Response - Paid (200):**
```json
{
  "success": true,
  "message": "Payment completed",
  "data": {
    "status": "paid",
    "is_paid": true,
    "paid_at": "2026-02-08T13:45:00.000000Z",
    "payment_id": "pay_ABCD1234",
    "qr_payment": {
      "id": 1,
      "order_id": 108315,
      "amount": 330.00,
      "status": "paid",
      "razorpay_payment_id": "pay_ABCD1234"
    }
  }
}
```

**Success Response - Pending (200):**
```json
{
  "success": true,
  "message": "Payment pending",
  "data": {
    "status": "pending",
    "is_paid": false,
    "qr_payment": {
      "id": 1,
      "order_id": 108315,
      "amount": 330.00,
      "status": "active",
      "is_expired": false
    }
  }
}
```

**Success Response - Expired (200):**
```json
{
  "success": true,
  "message": "QR code has expired",
  "data": {
    "status": "expired",
    "is_paid": false,
    "expires_at": "2026-02-08T14:10:00.000000Z",
    "qr_payment": {
      "id": 1,
      "status": "active",
      "is_expired": true
    }
  }
}
```

---

### 3. Get QR Payment Details

**Endpoint:** `GET /api/v1/delivery/qr-payment/{qr_payment_id}`

**Description:** Get full details of a QR payment.

**Request:**
```http
GET /api/v1/delivery/qr-payment/1
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_id": 108315,
    "delivery_man_id": 244,
    "razorpay_qr_id": "qr_1234567890",
    "razorpay_order_id": "order_ABCD1234",
    "amount": 330.00,
    "currency": "INR",
    "status": "paid",
    "qr_code_url": "https://api.razorpay.com/v1/qr_codes/qr_1234567890/qr.png",
    "qr_code_data": "upi://pay?pa=test@razorpay&...",
    "payment_link": "upi://pay?pa=test@razorpay&...",
    "is_expired": false,
    "is_paid": true,
    "expires_at": "2026-02-08T14:10:00.000000Z",
    "paid_at": "2026-02-08T13:45:00.000000Z",
    "order": {
      "id": 108315,
      "order_amount": 330.00
    },
    "delivery_man": {
      "id": 244,
      "f_name": "Rajesh",
      "l_name": "Kumar"
    }
  }
}
```

---

### 4. Cancel QR Payment

**Endpoint:** `POST /api/v1/delivery/qr-payment/cancel/{qr_payment_id}`

**Description:** Cancel an active QR code (before payment).

**Request:**
```http
POST /api/v1/delivery/qr-payment/cancel/1
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "QR payment cancelled successfully"
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Cannot cancel a paid QR code"
}
```

---

### 5. Get My QR Payments

**Endpoint:** `GET /api/v1/delivery/qr-payment/my-qr-payments`

**Description:** Get all QR payments for the authenticated delivery man.

**Request:**
```http
GET /api/v1/delivery/qr-payment/my-qr-payments?page=1
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_id": 108315,
      "amount": 330.00,
      "status": "paid",
      "is_paid": true,
      "paid_at": "2026-02-08T13:45:00.000000Z",
      "created_at": "2026-02-08T13:40:00.000000Z",
      "order": {
        "id": 108315,
        "order_amount": 330.00,
        "customer": {
          "id": 1099,
          "name": "John Doe",
          "phone": "+919876543210"
        }
      }
    }
  ],
  "pagination": {
    "total": 25,
    "per_page": 20,
    "current_page": 1,
    "last_page": 2
  }
}
```

---

## 📱 Flutter Integration

### Installation

Add dependencies to `pubspec.yaml`:

```yaml
dependencies:
  http: ^1.2.0
  qr_flutter: ^4.1.0
```

### Example Implementation

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:qr_flutter/qr_flutter.dart';

class DeliveryQRPaymentService {
  final String baseUrl = 'https://new.snocart.com/api/v1/delivery/qr-payment';
  final String accessToken;

  DeliveryQRPaymentService(this.accessToken);

  // 1. Generate QR Code
  Future<Map<String, dynamic>> generateQR({
    required int orderId,
    required double amount,
    int? deliveryManId,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/generate'),
      headers: {
        'Authorization': 'Bearer $accessToken',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({
        'order_id': orderId,
        'amount': amount,
        if (deliveryManId != null) 'delivery_man_id': deliveryManId,
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to generate QR: ${response.body}');
    }
  }

  // 2. Check Payment Status
  Future<Map<String, dynamic>> checkStatus(int qrPaymentId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/status/$qrPaymentId'),
      headers: {
        'Authorization': 'Bearer $accessToken',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to check status: ${response.body}');
    }
  }

  // 3. Cancel QR Payment
  Future<bool> cancelQR(int qrPaymentId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/cancel/$qrPaymentId'),
      headers: {
        'Authorization': 'Bearer $accessToken',
        'Accept': 'application/json',
      },
    );

    return response.statusCode == 200;
  }

  // 4. Get My QR Payments
  Future<Map<String, dynamic>> getMyQRPayments({int page = 1}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/my-qr-payments?page=$page'),
      headers: {
        'Authorization': 'Bearer $accessToken',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to get QR payments: ${response.body}');
    }
  }
}
```

### Flutter Widget Example

```dart
import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'dart:async';

class DeliveryQRPaymentScreen extends StatefulWidget {
  final int orderId;
  final double amount;

  const DeliveryQRPaymentScreen({
    required this.orderId,
    required this.amount,
  });

  @override
  _DeliveryQRPaymentScreenState createState() => _DeliveryQRPaymentScreenState();
}

class _DeliveryQRPaymentScreenState extends State<DeliveryQRPaymentScreen> {
  final DeliveryQRPaymentService _qrService = DeliveryQRPaymentService('YOUR_ACCESS_TOKEN');

  Map<String, dynamic>? qrData;
  bool isLoading = true;
  bool isPaid = false;
  Timer? _statusCheckTimer;

  @override
  void initState() {
    super.initState();
    _generateQR();
  }

  @override
  void dispose() {
    _statusCheckTimer?.cancel();
    super.dispose();
  }

  Future<void> _generateQR() async {
    try {
      final result = await _qrService.generateQR(
        orderId: widget.orderId,
        amount: widget.amount,
      );

      setState(() {
        qrData = result['data'];
        isLoading = false;
      });

      // Start polling for payment status
      _startStatusPolling();
    } catch (e) {
      setState(() {
        isLoading = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to generate QR: $e')),
      );
    }
  }

  void _startStatusPolling() {
    // Check payment status every 3 seconds
    _statusCheckTimer = Timer.periodic(Duration(seconds: 3), (timer) async {
      if (qrData == null) return;

      try {
        final status = await _qrService.checkStatus(qrData!['id']);

        if (status['data']['is_paid'] == true) {
          setState(() {
            isPaid = true;
          });
          timer.cancel();
          _showPaymentSuccess();
        }
      } catch (e) {
        print('Status check error: $e');
      }
    });
  }

  void _showPaymentSuccess() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Text('Payment Received!'),
        content: Text('The customer has successfully paid ₹${widget.amount}'),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop(); // Close dialog
              Navigator.of(context).pop(true); // Return to previous screen
            },
            child: Text('OK'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return Scaffold(
        appBar: AppBar(title: Text('Generating QR Code...')),
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (qrData == null) {
      return Scaffold(
        appBar: AppBar(title: Text('Error')),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text('Failed to generate QR code'),
              SizedBox(height: 20),
              ElevatedButton(
                onPressed: _generateQR,
                child: Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text('Payment QR Code'),
        actions: [
          IconButton(
            icon: Icon(Icons.close),
            onPressed: () async {
              await _qrService.cancelQR(qrData!['id']);
              Navigator.of(context).pop();
            },
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              'Ask customer to scan QR code',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 20),
            Text(
              '₹${widget.amount}',
              style: TextStyle(fontSize: 32, fontWeight: FontWeight.bold, color: Colors.green),
            ),
            SizedBox(height: 30),

            // Display QR Code
            Container(
              padding: EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
                boxShadow: [
                  BoxShadow(
                    color: Colors.grey.withOpacity(0.3),
                    spreadRadius: 5,
                    blurRadius: 7,
                  ),
                ],
              ),
              child: QrImageView(
                data: qrData!['qr_code_data'] ?? '',
                version: QrVersions.auto,
                size: 250.0,
              ),
            ),

            SizedBox(height: 30),

            // Status Indicator
            if (!isPaid)
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                  SizedBox(width: 10),
                  Text('Waiting for payment...'),
                ],
              ),

            SizedBox(height: 20),

            Text(
              'Expires in: ${_getExpiryTime()}',
              style: TextStyle(color: Colors.grey),
            ),

            SizedBox(height: 30),

            // Alternative: Deep link button
            OutlinedButton.icon(
              onPressed: () {
                // Open UPI app directly
                // launch(qrData!['payment_link']);
              },
              icon: Icon(Icons.payment),
              label: Text('Open in UPI App'),
            ),
          ],
        ),
      ),
    );
  }

  String _getExpiryTime() {
    if (qrData?['expires_at'] == null) return 'Never';

    final expiresAt = DateTime.parse(qrData!['expires_at']);
    final remaining = expiresAt.difference(DateTime.now());

    if (remaining.isNegative) return 'Expired';

    return '${remaining.inMinutes} min ${remaining.inSeconds % 60} sec';
  }
}
```

### Usage in Delivery Confirmation Flow

```dart
// When delivery boy confirms delivery
void onDeliveryConfirmed(Order order) async {
  // Navigate to QR payment screen
  final paid = await Navigator.push(
    context,
    MaterialPageRoute(
      builder: (context) => DeliveryQRPaymentScreen(
        orderId: order.id,
        amount: order.orderAmount,
      ),
    ),
  );

  if (paid == true) {
    // Payment successful - complete delivery
    print('Payment received, delivery completed!');
    // Update order status, etc.
  } else {
    // Payment cancelled or failed
    print('Payment not received');
  }
}
```

---

## 🔔 Webhook Configuration

### Razorpay Dashboard Setup

1. Go to Razorpay Dashboard → Settings → Webhooks
2. Add new webhook URL: `https://new.snocart.com/webhooks/razorpay-qr`
3. Select events:
   - `qr_code.credited` (when payment is made to QR)
   - `qr_code.closed` (when QR is closed/expired)
   - `payment.captured` (backup event)
4. Copy webhook secret and add to `.env`:

```env
RAZORPAY_WEBHOOK_SECRET=your_webhook_secret_here
```

### Webhook Events Handled

| Event | Description | Action |
|-------|-------------|--------|
| `qr_code.credited` | Payment made to QR code | Mark payment as paid |
| `qr_code.closed` | QR code closed/expired | Mark QR as cancelled |
| `payment.captured` | Payment captured (backup) | Mark payment as paid |

---

## 🧪 Testing

### Test Mode

Use Razorpay test keys for development:

```env
RAZORPAY_TEST_KEY=rzp_test_xxxxx
RAZORPAY_TEST_SECRET=your_test_secret
```

### Test UPI IDs

For testing, use these UPI IDs in Razorpay test mode:
- `success@razorpay` - Payment succeeds
- `failure@razorpay` - Payment fails

### Manual Testing Steps

1. **Generate QR Code:**
   ```bash
   curl -X POST https://new.snocart.com/api/v1/delivery/qr-payment/generate \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"order_id": 108315, "amount": 330}'
   ```

2. **Check Status:**
   ```bash
   curl -X GET https://new.snocart.com/api/v1/delivery/qr-payment/status/1 \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

3. **Simulate Payment (Test Mode):**
   - Use Razorpay's test payment dashboard
   - Or use test UPI app

---

## ❌ Error Handling

### Common Error Codes

| Code | Message | Solution |
|------|---------|----------|
| 401 | Unauthorized | Check Bearer token |
| 404 | QR payment not found | Verify QR payment ID |
| 422 | Validation failed | Check request parameters |
| 500 | Server error | Contact backend team |

### Flutter Error Handling Example

```dart
try {
  final result = await qrService.generateQR(
    orderId: orderId,
    amount: amount,
  );
  // Success
} on http.ClientException {
  // Network error
  showError('No internet connection');
} catch (e) {
  // Other errors
  if (e.toString().contains('401')) {
    showError('Session expired. Please login again.');
  } else if (e.toString().contains('404')) {
    showError('Order not found');
  } else {
    showError('Something went wrong. Please try again.');
  }
}
```

---

## 📊 Razorpay Dashboard Tracking

All QR payments are tagged with:
- **Order ID:** Visible in notes
- **Delivery Man ID:** Visible in notes
- **Delivery Man Name:** Visible in notes
- **Customer ID:** Visible in notes
- **Store ID:** Visible in notes

Filter payments in Razorpay Dashboard:
1. Go to Payments → All Payments
2. Filter by notes: `created_by: delivery_confirmation`
3. View delivery boy and order details in payment notes

---

## 🚀 Deployment Checklist

- [ ] Run database migration
- [ ] Configure Razorpay live keys in production
- [ ] Add webhook URL in Razorpay dashboard
- [ ] Set webhook secret in `.env`
- [ ] Test QR generation in production
- [ ] Test payment flow end-to-end
- [ ] Monitor webhook logs
- [ ] Test status polling
- [ ] Test QR expiry (30 minutes)
- [ ] Test payment notifications

---

## 📞 Support

For issues or questions:
- Backend Team: backend@snocart.com
- Razorpay Docs: https://razorpay.com/docs/payments/qr-codes/

---

**Last Updated:** 2026-02-08
**API Version:** 1.0
**Razorpay API Version:** v1
