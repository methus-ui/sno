# Barcode Crowdsourcing - Flutter Integration Guide

**Quick Start Guide for Flutter Developers**

---

## 🎯 What Is This?

Delivery men can scan barcodes for items that don't have them yet and **earn ₹5 per scan**!

**Current Opportunity:**
- 33,628 items need barcodes
- ₹168,140 potential earnings available
- Each successful scan = instant ₹5 credit

---

## 📱 UI Flow

```
Order Details Screen
  └─ Item Card
      ├─ Has Barcode → Show barcode ✅
      └─ No Barcode → "Scan & Earn ₹5" button 💰
                       └─ Click → Camera opens
                                 └─ Scan → API call
                                           └─ Success → +₹5 earned! ✨
```

---

## 🔌 API Endpoint

**Base URL:** `https://new.snocart.com/api/v1`

### POST /delivery-man/submit-item-barcode

```dart
final response = await http.post(
  Uri.parse('$baseUrl/delivery-man/submit-item-barcode'),
  headers: {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
  },
  body: jsonEncode({
    'item_id': 1330,
    'barcode': '8901234567890',
    'token': token,  // Also in header, but backend checks both
  }),
);
```

---

## 📦 Data Models

### Request
```dart
class BarcodeSubmission {
  final int itemId;
  final String barcode;
  final String token;

  Map<String, dynamic> toJson() => {
    'item_id': itemId,
    'barcode': barcode,
    'token': token,
  };
}
```

### Success Response (200)
```dart
class BarcodeSubmissionResult {
  final String message;
  final String barcode;
  final double earning;
  final double totalEarning;
  final int scanId;

  BarcodeSubmissionResult.fromJson(Map<String, dynamic> json)
      : message = json['message'],
        barcode = json['barcode'],
        earning = (json['earning'] as num).toDouble(),
        totalEarning = (json['total_earning'] as num).toDouble(),
        scanId = json['scan_id'];
}
```

### Error Response (409)
```dart
class BarcodeError {
  final String message;
  final String? existingBarcode;
  final String? previousScan;

  BarcodeError.fromJson(Map<String, dynamic> json)
      : message = json['message'],
        existingBarcode = json['barcode'],
        previousScan = json['previous_scan'];
}
```

---

## 🎨 UI Components

### 1. Barcode Button Widget

```dart
class BarcodeScanButton extends StatelessWidget {
  final OrderDetailItem item;
  final VoidCallback onSuccess;

  const BarcodeScanButton({
    required this.item,
    required this.onSuccess,
  });

  @override
  Widget build(BuildContext context) {
    // Item already has barcode - show it
    if (item.barcode != null) {
      return Chip(
        avatar: Icon(Icons.check_circle, color: Colors.green),
        label: Text(item.barcode!),
        backgroundColor: Colors.green[50],
      );
    }

    // Item needs barcode - show scan button
    return ElevatedButton.icon(
      icon: Icon(Icons.qr_code_scanner),
      label: Text('Scan & Earn ₹5'),
      style: ElevatedButton.styleFrom(
        backgroundColor: Colors.orange,
        foregroundColor: Colors.white,
      ),
      onPressed: () => _scanAndSubmit(context),
    );
  }

  Future<void> _scanAndSubmit(BuildContext context) async {
    try {
      // Scan barcode
      final result = await BarcodeScanner.scan();

      if (result.rawContent.isEmpty) {
        _showError(context, 'No barcode detected');
        return;
      }

      // Show loading
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => Center(child: CircularProgressIndicator()),
      );

      // Submit to API
      final response = await BarcodeService.submit(
        itemId: item.itemId,
        barcode: result.rawContent,
      );

      Navigator.pop(context); // Close loading

      // Show success
      _showSuccess(context, response);
      onSuccess();

    } catch (e) {
      Navigator.pop(context); // Close loading
      _showError(context, e.toString());
    }
  }

  void _showSuccess(BuildContext context, BarcodeSubmissionResult result) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Row(
          children: [
            Icon(Icons.celebration, color: Colors.orange),
            SizedBox(width: 8),
            Text('Earned!'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              '₹${result.earning}',
              style: TextStyle(
                fontSize: 48,
                fontWeight: FontWeight.bold,
                color: Colors.orange,
              ),
            ),
            SizedBox(height: 16),
            Text(result.message),
            SizedBox(height: 8),
            Text(
              'Total Earning: ₹${result.totalEarning}',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('Great!'),
          ),
        ],
      ),
    );
  }

  void _showError(BuildContext context, String error) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(error),
        backgroundColor: Colors.red,
      ),
    );
  }
}
```

### 2. Service Class

```dart
class BarcodeService {
  static const String baseUrl = 'https://new.snocart.com/api/v1';

  static Future<BarcodeSubmissionResult> submit({
    required int itemId,
    required String barcode,
  }) async {
    final token = await AuthService.getToken();

    final response = await http.post(
      Uri.parse('$baseUrl/delivery-man/submit-item-barcode'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'item_id': itemId,
        'barcode': barcode,
        'token': token,
      }),
    );

    if (response.statusCode == 200) {
      return BarcodeSubmissionResult.fromJson(jsonDecode(response.body));
    } else if (response.statusCode == 409) {
      final error = BarcodeError.fromJson(jsonDecode(response.body));
      throw BarcodeAlreadyExistsException(error.message);
    } else if (response.statusCode == 401) {
      throw UnauthorizedException();
    } else {
      throw Exception('Failed to submit barcode');
    }
  }
}

class BarcodeAlreadyExistsException implements Exception {
  final String message;
  BarcodeAlreadyExistsException(this.message);
}

class UnauthorizedException implements Exception {}
```

### 3. Order Details Integration

```dart
class OrderDetailItemCard extends StatefulWidget {
  final OrderDetailItem item;

  @override
  _OrderDetailItemCardState createState() => _OrderDetailItemCardState();
}

class _OrderDetailItemCardState extends State<OrderDetailItemCard> {
  late String? barcode;

  @override
  void initState() {
    super.initState();
    barcode = widget.item.barcode;
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Item name, image, etc.
            Text(widget.item.itemDetails.name),
            SizedBox(height: 8),

            // Barcode section
            BarcodeScanButton(
              item: widget.item,
              onSuccess: () {
                // Refresh order details or update local state
                setState(() {
                  // Barcode will be updated via API response
                });
              },
            ),
          ],
        ),
      ),
    );
  }
}
```

---

## 💰 Earnings Display

### Barcode Earnings Widget

```dart
class BarcodeEarningsWidget extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return FutureBuilder<BarcodeStats>(
      future: BarcodeService.getStats(),
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return CircularProgressIndicator();
        }

        final stats = snapshot.data!;

        return Card(
          child: Padding(
            padding: EdgeInsets.all(16),
            child: Column(
              children: [
                Text(
                  'Barcode Earnings',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                SizedBox(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    _StatItem(
                      label: 'Total Scans',
                      value: stats.totalScans.toString(),
                      icon: Icons.qr_code_scanner,
                    ),
                    _StatItem(
                      label: 'Earned',
                      value: '₹${stats.totalEarned}',
                      icon: Icons.currency_rupee,
                      color: Colors.green,
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class _StatItem extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color? color;

  const _StatItem({
    required this.label,
    required this.value,
    required this.icon,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, color: color ?? Colors.blue, size: 32),
        SizedBox(height: 8),
        Text(
          value,
          style: TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(label),
      ],
    );
  }
}
```

---

## 🎮 User Experience Tips

### 1. Visual Feedback

**Before Scan:**
```dart
// Show earning potential
Container(
  decoration: BoxDecoration(
    color: Colors.orange[50],
    border: Border.all(color: Colors.orange),
    borderRadius: BorderRadius.circular(8),
  ),
  padding: EdgeInsets.all(8),
  child: Row(
    children: [
      Icon(Icons.info, color: Colors.orange),
      SizedBox(width: 8),
      Text('Scan this item to earn ₹5!'),
    ],
  ),
)
```

**After Scan:**
```dart
// Show success animation
Lottie.asset('assets/success_confetti.json');
```

### 2. Progress Tracking

```dart
// Show how many items scanned today
Text('You\'ve scanned ${todayScans} items today! 🎉')
```

### 3. Gamification

```dart
// Daily goal
if (todayScans >= 10) {
  Text('🏆 Daily goal achieved! +₹50 bonus');
}
```

---

## 🐛 Error Handling

```dart
Future<void> scanBarcode(int itemId) async {
  try {
    final result = await BarcodeService.submit(
      itemId: itemId,
      barcode: scannedCode,
    );

    showSuccess(result.message);

  } on BarcodeAlreadyExistsException catch (e) {
    showWarning('Item already has barcode: ${e.message}');

  } on UnauthorizedException {
    showError('Please login again');
    navigateToLogin();

  } on SocketException {
    showError('No internet connection');

  } catch (e) {
    showError('Failed to submit barcode');
    print('Barcode error: $e');
  }
}
```

---

## 📊 Analytics Events

Track barcode scanning for analytics:

```dart
// When scan is initiated
analytics.logEvent(
  name: 'barcode_scan_started',
  parameters: {
    'item_id': itemId,
    'has_existing_barcode': item.barcode != null,
  },
);

// When scan succeeds
analytics.logEvent(
  name: 'barcode_scan_success',
  parameters: {
    'item_id': itemId,
    'earning': 5,
    'total_earning': totalEarning,
  },
);

// When scan fails
analytics.logEvent(
  name: 'barcode_scan_failed',
  parameters: {
    'item_id': itemId,
    'error': errorMessage,
  },
);
```

---

## ✅ Testing Checklist

- [ ] Scan button shows only for items without barcodes
- [ ] Camera opens when button tapped
- [ ] Barcode submission shows loading indicator
- [ ] Success shows earning amount
- [ ] Error handling works for all error types
- [ ] Total earning updates immediately
- [ ] Scanned item shows barcode (not button) after success
- [ ] Offline handling works
- [ ] Analytics events fire correctly

---

## 🚀 Launch Tips

1. **Soft Launch:**
   - Enable for top 10% active DMs first
   - Monitor for issues
   - Gradually roll out to all

2. **Communication:**
   - In-app tutorial on first use
   - Push notification: "New way to earn! Scan barcodes"
   - Video tutorial in help section

3. **Incentives:**
   - Double earnings for first week (₹10/scan)
   - Bonus for first 100 scans
   - Leaderboard for top scanners

---

## 📞 Need Help?

**Backend API Issues:**
- Check documentation: `BARCODE_CROWDSOURCING_DOCUMENTATION.md`
- Test endpoint manually with Postman

**Flutter Issues:**
- Verify token is being sent correctly
- Check response status codes
- Enable debug logging

**Questions:**
Contact backend team with:
- Item ID
- Delivery man ID
- Error message
- Request/response logs

---

**Last Updated:** 2026-03-08
**Status:** ✅ Ready for Integration
