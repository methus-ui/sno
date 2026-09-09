# Barcode Submission API - Quick Reference for Flutter Developers

**API Base URL:** `https://new.snocart.com/api/v1`

---

## 1. Get Order Details (with Barcodes)

**Endpoint:** `GET /delivery-man/order-details`

**Headers:**
```
Authorization: Bearer {delivery_man_token}
```

**Query Parameters:**
```
order_id: 109433 (required)
```

**Response Fields (per item):**
```json
{
    "id": 42018,
    "barcode": "8901262260121",  // ← Use this for verification
    "item_details": {
        "name": "Amul Taaza Milk",
        "image": "...",
        ...
    },
    "quantity": 3,
    "price": 26.0
}
```

**Dart Model:**
```dart
class OrderDetailItem {
  final int id;
  final String? barcode;  // Nullable - some items don't have barcodes
  final ItemDetails itemDetails;
  final int quantity;
  final double price;

  OrderDetailItem.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        barcode = json['barcode'],
        itemDetails = ItemDetails.fromJson(json['item_details']),
        quantity = json['quantity'],
        price = (json['price'] as num).toDouble();
}
```

---

## 2. Submit Barcode

**Endpoint:** `POST /delivery-man/submit-item-barcode`

**Headers:**
```
Authorization: Bearer {delivery_man_token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "order_detail_id": 42018,
    "barcode": "8901262260121",
    "submission_type": "scanned",  // or "manual"
    "metadata": {
        "latitude": 28.6139,
        "longitude": 77.2090,
        "timestamp": "2026-03-08T11:50:04Z"
    }
}
```

**Success Response:**
```json
{
    "success": true,
    "submission_id": 123,
    "is_match": true,  // ← Check this!
    "item_name": "Amul Taaza Milk",
    "submitted_barcode": "8901262260121",
    "expected_barcode": "8901262260121",
    "message": "Barcode verified successfully! Item matches."
}
```

**Dart Model:**
```dart
class BarcodeSubmissionResponse {
  final bool success;
  final int submissionId;
  final bool isMatch;
  final String itemName;
  final String submittedBarcode;
  final String? expectedBarcode;
  final String message;

  BarcodeSubmissionResponse.fromJson(Map<String, dynamic> json)
      : success = json['success'],
        submissionId = json['submission_id'],
        isMatch = json['is_match'],
        itemName = json['item_name'],
        submittedBarcode = json['submitted_barcode'],
        expectedBarcode = json['expected_barcode'],
        message = json['message'];
}
```

---

## 3. Example Flutter Service

```dart
class BarcodeService {
  final String baseUrl = 'https://new.snocart.com/api/v1';
  final String token;

  BarcodeService({required this.token});

  Future<BarcodeSubmissionResponse> submitBarcode({
    required int orderDetailId,
    required String barcode,
    String submissionType = 'scanned',
    Map<String, dynamic>? metadata,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/delivery-man/submit-item-barcode'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'order_detail_id': orderDetailId,
        'barcode': barcode,
        'submission_type': submissionType,
        'metadata': metadata ?? {
          'latitude': position?.latitude,
          'longitude': position?.longitude,
          'timestamp': DateTime.now().toIso8601String(),
        },
      }),
    );

    if (response.statusCode == 200) {
      return BarcodeSubmissionResponse.fromJson(jsonDecode(response.body));
    } else {
      throw Exception('Failed to submit barcode');
    }
  }
}
```

---

## 4. UI Flow

```dart
class BarcodeVerificationScreen extends StatelessWidget {
  final OrderDetailItem item;

  Future<void> scanAndVerify() async {
    try {
      // 1. Scan barcode using camera
      final scannedBarcode = await BarcodeScanner.scan();

      // 2. Submit to API
      final result = await BarcodeService(token: userToken).submitBarcode(
        orderDetailId: item.id,
        barcode: scannedBarcode.rawContent,
        submissionType: 'scanned',
      );

      // 3. Show result
      if (result.isMatch) {
        showSuccessDialog(
          title: '✅ Verified!',
          message: result.message,
          itemName: result.itemName,
        );
      } else {
        showWarningDialog(
          title: '⚠️ Barcode Mismatch',
          message: result.message,
          expected: result.expectedBarcode,
          scanned: result.submittedBarcode,
        );
      }

    } catch (e) {
      showErrorDialog('Failed to verify barcode: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        children: [
          // Show expected barcode if available
          if (item.barcode != null)
            Text('Expected: ${item.barcode}'),

          ElevatedButton(
            onPressed: scanAndVerify,
            child: Text('Scan Barcode'),
          ),
        ],
      ),
    );
  }
}
```

---

## 5. Handle Edge Cases

```dart
class BarcodeHelper {
  static Widget buildBarcodeWidget(OrderDetailItem item) {
    if (item.barcode == null || item.barcode!.isEmpty) {
      // No barcode available
      return Card(
        child: Column(
          children: [
            Icon(Icons.info, color: Colors.orange),
            Text('No barcode available'),
            Text('Please verify item manually'),
            ElevatedButton(
              onPressed: () => confirmManually(item),
              child: Text('Confirm Item'),
            ),
          ],
        ),
      );
    } else {
      // Barcode available - show scan button
      return ElevatedButton.icon(
        icon: Icon(Icons.qr_code_scanner),
        label: Text('Scan Barcode'),
        onPressed: () => scanBarcode(item),
      );
    }
  }
}
```

---

## 6. Error Handling

```dart
Future<void> submitBarcodeWithErrorHandling(int orderDetailId, String barcode) async {
  try {
    final result = await service.submitBarcode(
      orderDetailId: orderDetailId,
      barcode: barcode,
    );

    handleSuccess(result);

  } on SocketException {
    showError('No internet connection');
  } on TimeoutException {
    showError('Request timed out');
  } catch (e) {
    if (e.toString().contains('401')) {
      showError('Unauthorized - Please login again');
      navigateToLogin();
    } else if (e.toString().contains('403')) {
      showError('This order is not assigned to you');
    } else if (e.toString().contains('422')) {
      showError('Invalid barcode format');
    } else {
      showError('Failed to verify barcode');
    }
  }
}
```

---

## 7. Testing

**Test Data:**
- Order Detail ID: 42018
- Expected Barcode: 8901262260121
- Item: Amul Taaza Milk

**Test Cases:**
1. ✅ Scan correct barcode → Should show success
2. ❌ Scan wrong barcode → Should show warning
3. ⚠️ Item without barcode → Should show manual verification
4. 🔄 Offline → Should queue and retry

---

## 8. Performance Tips

1. **Cache order details:** Don't fetch on every scan
2. **Debounce scans:** Prevent duplicate submissions
3. **Show loading states:** API typically responds in < 100ms
4. **Offline queue:** Store failed scans and retry later

---

## 9. Common Questions

**Q: What if item has no barcode?**
A: `barcode` field will be `null` - show manual verification UI

**Q: Can I submit multiple times?**
A: Yes - each scan creates a new record

**Q: What about offline scans?**
A: Queue them and submit when online

**Q: What metadata should I include?**
A: GPS location, timestamp, device model (optional)

---

## 10. API Response Codes

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | Show result to user |
| 401 | Unauthorized | Refresh token or logout |
| 403 | Forbidden | Not your order |
| 422 | Validation Error | Check request format |
| 500 | Server Error | Retry or contact support |

---

## Support

For API issues, check:
1. Token is valid
2. Order is assigned to logged-in DM
3. Request body format is correct
4. Network connection is stable

**Documentation:** See `BARCODE_SUBMISSION_API_DOCUMENTATION.md` for full details

**Last Updated:** 2026-03-08
