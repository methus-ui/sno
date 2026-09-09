# Customer App - Bargaining Mode Integration Guide

**Date:** 2026-03-26
**Backend Status:** ✅ Fully Implemented (Phase 1-5 Complete)
**Required:** Customer App Integration Only

---

## 📋 Overview

Bargaining Mode allows customers to:
- Submit their cart for competitive pricing
- Get offers from multiple stores automatically
- Compare prices and choose the best deal
- Save money on every order

**Implementation Time:** 2-3 days (UI + API integration)

---

## ✅ Backend Checklist (Verify First)

### Step 1: Verify Backend Deployment

```bash
# 1. Check if bargaining is enabled
curl https://your-domain.com/api/v2/bargaining/history \
  -H "Authorization: Bearer YOUR_TEST_TOKEN"

# Should return 200 (even if empty history)
# If returns 503/404, bargaining needs deployment
```

### Step 2: Run Deployment (if needed)

```bash
cd /var/www/html/new_public/new

# 1. Run migrations
php artisan migrate --path=database/migrations/2026_03_17_000001_create_bargaining_requests_table.php
php artisan migrate --path=database/migrations/2026_03_17_000002_create_bargaining_cart_items_table.php
php artisan migrate --path=database/migrations/2026_03_17_000003_create_bargaining_item_matches_table.php
php artisan migrate --path=database/migrations/2026_03_17_000004_create_bargaining_store_offers_table.php
php artisan migrate --path=database/migrations/2026_03_17_000005_create_bargaining_offer_items_table.php
php artisan migrate --path=database/migrations/2026_03_17_000006_create_store_bargaining_settings_table.php

# 2. Enable in .env
echo "BARGAINING_ENABLED=true" >> .env
echo "BARGAINING_INSTANT_MODE=true" >> .env
echo "BARGAINING_WAIT_MODE=true" >> .env
echo "BARGAINING_WAIT_DURATION=60" >> .env

# 3. Clear caches
php artisan config:cache
php artisan route:cache
php artisan cache:clear

# 4. Test
php scripts/test-bargaining-api.php
```

---

## 📱 Customer App Integration

### Architecture Overview

```
Cart Screen
    ↓
[Bargain Button] → Bargaining Mode Selection Screen
                         ↓
              [Instant Mode] or [Wait Mode]
                         ↓
              Bargaining Status Screen (polling)
                         ↓
              Offers List Screen (ranked)
                         ↓
              Offer Details Screen (expandable)
                         ↓
              Accept Confirmation
                         ↓
              Cart Updated → Checkout
```

---

## 🔌 API Integration

### 1. Add Bargaining Service

Create `lib/services/bargaining_service.dart`:

```dart
import 'package:dio/dio.dart';
import 'package:get/get.dart';

class BargainingService extends GetxService {
  final Dio _dio = Dio();

  String get baseUrl => AppConfig.baseUrl + '/api/v2/bargaining';

  // Get auth token from your existing auth service
  String get authToken => Get.find<AuthService>().token;

  Map<String, String> get headers => {
    'Authorization': 'Bearer $authToken',
    'zoneId': jsonEncode(Get.find<LocationService>().zoneIds),
    'moduleId': Get.find<ModuleService>().moduleId.toString(),
  };

  // 1. Initiate bargaining
  Future<BargainingRequest> initiate({
    required String mode, // 'instant' or 'wait'
    double? customerBudget,
    Map<String, dynamic>? deliveryAddress,
    double? latitude,
    double? longitude,
  }) async {
    try {
      final response = await _dio.post(
        '$baseUrl/initiate',
        options: Options(headers: headers),
        data: {
          'mode': mode,
          if (customerBudget != null) 'customer_budget': customerBudget,
          if (deliveryAddress != null) 'delivery_address': deliveryAddress,
          if (latitude != null) 'latitude': latitude,
          if (longitude != null) 'longitude': longitude,
        },
      );

      return BargainingRequest.fromJson(response.data);
    } catch (e) {
      throw _handleError(e);
    }
  }

  // 2. Get status (poll every 3 seconds)
  Future<BargainingStatus> getStatus(String requestCode) async {
    try {
      final response = await _dio.get(
        '$baseUrl/status/$requestCode',
        options: Options(headers: headers),
      );

      return BargainingStatus.fromJson(response.data);
    } catch (e) {
      throw _handleError(e);
    }
  }

  // 3. Accept offer
  Future<BargainingAcceptance> acceptOffer({
    required String requestCode,
    required int offerId,
  }) async {
    try {
      final response = await _dio.post(
        '$baseUrl/accept-offer',
        options: Options(headers: headers),
        data: {
          'request_code': requestCode,
          'offer_id': offerId,
        },
      );

      return BargainingAcceptance.fromJson(response.data);
    } catch (e) {
      throw _handleError(e);
    }
  }

  // 4. Cancel bargaining
  Future<void> cancel(String requestCode) async {
    try {
      await _dio.post(
        '$baseUrl/cancel/$requestCode',
        options: Options(headers: headers),
      );
    } catch (e) {
      throw _handleError(e);
    }
  }

  // 5. Get history
  Future<List<BargainingHistoryItem>> getHistory({
    int limit = 20,
    int offset = 0,
  }) async {
    try {
      final response = await _dio.get(
        '$baseUrl/history',
        options: Options(headers: headers),
        queryParameters: {
          'limit': limit,
          'offset': offset,
        },
      );

      final data = response.data['history'] as List;
      return data.map((e) => BargainingHistoryItem.fromJson(e)).toList();
    } catch (e) {
      throw _handleError(e);
    }
  }

  // 6. Get offer details
  Future<BargainingOfferDetails> getOfferDetails(int offerId) async {
    try {
      final response = await _dio.get(
        '$baseUrl/offer/$offerId',
        options: Options(headers: headers),
      );

      return BargainingOfferDetails.fromJson(response.data);
    } catch (e) {
      throw _handleError(e);
    }
  }

  String _handleError(dynamic e) {
    if (e is DioError) {
      if (e.response?.data != null && e.response!.data['errors'] != null) {
        return e.response!.data['errors'][0]['message'] ?? 'Unknown error';
      }
      return e.message;
    }
    return e.toString();
  }
}
```

---

### 2. Add Data Models

Create `lib/models/bargaining_models.dart`:

```dart
// Bargaining Request (after initiation)
class BargainingRequest {
  final String requestCode;
  final String status;
  final String mode;
  final int totalCartItems;
  final double originalCartValue;
  final int? waitDuration;
  final DateTime expiresAt;
  final int timeRemaining;

  BargainingRequest({
    required this.requestCode,
    required this.status,
    required this.mode,
    required this.totalCartItems,
    required this.originalCartValue,
    this.waitDuration,
    required this.expiresAt,
    required this.timeRemaining,
  });

  factory BargainingRequest.fromJson(Map<String, dynamic> json) {
    return BargainingRequest(
      requestCode: json['request_code'],
      status: json['status'],
      mode: json['mode'],
      totalCartItems: json['total_cart_items'],
      originalCartValue: (json['original_cart_value'] as num).toDouble(),
      waitDuration: json['wait_duration'],
      expiresAt: DateTime.parse(json['expires_at']),
      timeRemaining: json['time_remaining'],
    );
  }
}

// Bargaining Status (polled)
class BargainingStatus {
  final String requestCode;
  final String status;
  final String mode;
  final int totalCartItems;
  final double originalCartValue;
  final int totalStoresMatched;
  final int totalOffersReceived;
  final bool canAcceptOffers;
  final bool isExpired;
  final int timeRemaining;
  final BargainingOffer? bestOffer;
  final List<BargainingOffer> allOffers;

  BargainingStatus({
    required this.requestCode,
    required this.status,
    required this.mode,
    required this.totalCartItems,
    required this.originalCartValue,
    required this.totalStoresMatched,
    required this.totalOffersReceived,
    required this.canAcceptOffers,
    required this.isExpired,
    required this.timeRemaining,
    this.bestOffer,
    required this.allOffers,
  });

  factory BargainingStatus.fromJson(Map<String, dynamic> json) {
    return BargainingStatus(
      requestCode: json['request_code'],
      status: json['status'],
      mode: json['mode'],
      totalCartItems: json['total_cart_items'],
      originalCartValue: (json['original_cart_value'] as num).toDouble(),
      totalStoresMatched: json['total_stores_matched'],
      totalOffersReceived: json['total_offers_received'],
      canAcceptOffers: json['can_accept_offers'],
      isExpired: json['is_expired'],
      timeRemaining: json['time_remaining'],
      bestOffer: json['best_offer'] != null
          ? BargainingOffer.fromJson(json['best_offer'])
          : null,
      allOffers: (json['all_offers'] as List)
          .map((e) => BargainingOffer.fromJson(e))
          .toList(),
    );
  }
}

// Bargaining Offer
class BargainingOffer {
  final int offerId;
  final int storeId;
  final String storeName;
  final String storeLogo;
  final double storeRating;
  final String offerType;
  final bool isVendorOffer;
  final int itemsAvailable;
  final int itemsMissing;
  final double fulfillmentPercentage;
  final List<String> missingItems;
  final double subtotal;
  final double totalDiscount;
  final double taxAmount;
  final double deliveryCharge;
  final double totalAmount;
  final double savings;
  final int rank;
  final bool isBestOffer;
  final String? vendorNotes;
  final String? estimatedDeliveryTime;

  BargainingOffer({
    required this.offerId,
    required this.storeId,
    required this.storeName,
    required this.storeLogo,
    required this.storeRating,
    required this.offerType,
    required this.isVendorOffer,
    required this.itemsAvailable,
    required this.itemsMissing,
    required this.fulfillmentPercentage,
    required this.missingItems,
    required this.subtotal,
    required this.totalDiscount,
    required this.taxAmount,
    required this.deliveryCharge,
    required this.totalAmount,
    required this.savings,
    required this.rank,
    required this.isBestOffer,
    this.vendorNotes,
    this.estimatedDeliveryTime,
  });

  factory BargainingOffer.fromJson(Map<String, dynamic> json) {
    return BargainingOffer(
      offerId: json['offer_id'],
      storeId: json['store_id'],
      storeName: json['store_name'],
      storeLogo: json['store_logo'],
      storeRating: (json['store_rating'] as num).toDouble(),
      offerType: json['offer_type'],
      isVendorOffer: json['is_vendor_offer'],
      itemsAvailable: json['items_available'],
      itemsMissing: json['items_missing'],
      fulfillmentPercentage: (json['fulfillment_percentage'] as num).toDouble(),
      missingItems: List<String>.from(json['missing_items'] ?? []),
      subtotal: (json['subtotal'] as num).toDouble(),
      totalDiscount: (json['total_discount'] as num).toDouble(),
      taxAmount: (json['tax_amount'] as num).toDouble(),
      deliveryCharge: (json['delivery_charge'] as num).toDouble(),
      totalAmount: (json['total_amount'] as num).toDouble(),
      savings: (json['savings'] as num).toDouble(),
      rank: json['rank'],
      isBestOffer: json['is_best_offer'],
      vendorNotes: json['vendor_notes'],
      estimatedDeliveryTime: json['estimated_delivery_time'],
    );
  }
}

// Add more models as needed (BargainingAcceptance, BargainingHistoryItem, etc.)
```

---

### 3. Add Bargaining Controller

Create `lib/controllers/bargaining_controller.dart`:

```dart
import 'dart:async';
import 'package:get/get.dart';

class BargainingController extends GetxController {
  final BargainingService _service = Get.find<BargainingService>();

  final Rx<BargainingStatus?> status = Rx<BargainingStatus?>(null);
  final RxBool isLoading = false.obs;
  final RxString error = ''.obs;
  Timer? _pollingTimer;

  // Initiate bargaining
  Future<void> initiate(String mode) async {
    try {
      isLoading.value = true;
      error.value = '';

      final request = await _service.initiate(
        mode: mode,
        latitude: Get.find<LocationService>().latitude,
        longitude: Get.find<LocationService>().longitude,
      );

      // Navigate to status screen
      Get.to(() => BargainingStatusScreen(requestCode: request.requestCode));

      // Start polling
      startPolling(request.requestCode);

    } catch (e) {
      error.value = e.toString();
      Get.snackbar('Error', error.value);
    } finally {
      isLoading.value = false;
    }
  }

  // Start status polling (every 3 seconds)
  void startPolling(String requestCode) {
    _pollingTimer?.cancel();

    // Immediate first call
    checkStatus(requestCode);

    // Then poll every 3 seconds
    _pollingTimer = Timer.periodic(Duration(seconds: 3), (_) {
      checkStatus(requestCode);
    });
  }

  // Check status
  Future<void> checkStatus(String requestCode) async {
    try {
      final newStatus = await _service.getStatus(requestCode);
      status.value = newStatus;

      // Stop polling if completed or expired
      if (newStatus.status == 'accepted' ||
          newStatus.status == 'expired' ||
          newStatus.status == 'cancelled') {
        stopPolling();
      }

    } catch (e) {
      error.value = e.toString();
    }
  }

  // Accept offer
  Future<void> acceptOffer(int offerId) async {
    try {
      isLoading.value = true;

      final acceptance = await _service.acceptOffer(
        requestCode: status.value!.requestCode,
        offerId: offerId,
      );

      stopPolling();

      // Show success message
      Get.snackbar(
        'Success',
        'Saved ${acceptance.totalSavings.toStringAsFixed(2)}! Cart updated.',
        backgroundColor: Colors.green,
        colorText: Colors.white,
      );

      // Navigate to checkout
      Get.offAll(() => CheckoutScreen());

    } catch (e) {
      error.value = e.toString();
      Get.snackbar('Error', error.value);
    } finally {
      isLoading.value = false;
    }
  }

  // Cancel bargaining
  Future<void> cancel() async {
    try {
      if (status.value != null) {
        await _service.cancel(status.value!.requestCode);
        stopPolling();
        Get.back();
      }
    } catch (e) {
      Get.snackbar('Error', e.toString());
    }
  }

  void stopPolling() {
    _pollingTimer?.cancel();
    _pollingTimer = null;
  }

  @override
  void onClose() {
    stopPolling();
    super.onClose();
  }
}
```

---

## 🎨 UI Screens

### Screen 1: Mode Selection (on Cart Screen)

Add a "Find Better Prices" button to your cart screen:

```dart
// In your existing cart screen
ElevatedButton.icon(
  icon: Icon(Icons.local_offer),
  label: Text('Find Better Prices'),
  onPressed: () => Get.to(() => BargainingModeSelectionScreen()),
  style: ElevatedButton.styleFrom(
    backgroundColor: Colors.orange,
    padding: EdgeInsets.symmetric(vertical: 16, horizontal: 24),
  ),
)
```

### Screen 2: Mode Selection Screen

Create `lib/screens/bargaining/mode_selection_screen.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:get/get.dart';

class BargainingModeSelectionScreen extends StatelessWidget {
  final BargainingController controller = Get.put(BargainingController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Choose Bargaining Mode'),
      ),
      body: Padding(
        padding: EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Instant Mode Card
            _buildModeCard(
              title: 'Instant Mode',
              subtitle: 'Get best offers immediately (2-3 seconds)',
              icon: Icons.flash_on,
              color: Colors.orange,
              onTap: () => controller.initiate('instant'),
            ),

            SizedBox(height: 24),

            // Wait Mode Card
            _buildModeCard(
              title: 'Wait Mode',
              subtitle: 'Wait 60 seconds for vendors to bid',
              icon: Icons.schedule,
              color: Colors.blue,
              onTap: () => controller.initiate('wait'),
            ),

            SizedBox(height: 16),

            Text(
              'Both modes find the best prices from multiple stores!',
              style: TextStyle(color: Colors.grey),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildModeCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Card(
      elevation: 4,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Row(
            children: [
              CircleAvatar(
                radius: 30,
                backgroundColor: color.withOpacity(0.2),
                child: Icon(icon, color: color, size: 30),
              ),
              SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    SizedBox(height: 4),
                    Text(
                      subtitle,
                      style: TextStyle(color: Colors.grey[600]),
                    ),
                  ],
                ),
              ),
              Icon(Icons.arrow_forward_ios, color: Colors.grey),
            ],
          ),
        ),
      ),
    );
  }
}
```

### Screen 3: Status Screen (Polling)

Create `lib/screens/bargaining/status_screen.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:get/get.dart';

class BargainingStatusScreen extends StatelessWidget {
  final String requestCode;
  final BargainingController controller = Get.find<BargainingController>();

  BargainingStatusScreen({required this.requestCode});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Finding Best Prices'),
        actions: [
          IconButton(
            icon: Icon(Icons.close),
            onPressed: () => _confirmCancel(),
          ),
        ],
      ),
      body: Obx(() {
        final status = controller.status.value;

        if (status == null) {
          return Center(child: CircularProgressIndicator());
        }

        return SingleChildScrollView(
          padding: EdgeInsets.all(16),
          child: Column(
            children: [
              // Timer Card
              _buildTimerCard(status),

              SizedBox(height: 16),

              // Stats Card
              _buildStatsCard(status),

              SizedBox(height: 16),

              // Best Offer Card (if available)
              if (status.bestOffer != null) ...[
                _buildBestOfferCard(status.bestOffer!),
                SizedBox(height: 16),
              ],

              // All Offers List
              if (status.allOffers.isNotEmpty) ...[
                Text(
                  'All Offers (${status.allOffers.length})',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                SizedBox(height: 8),
                ...status.allOffers.map((offer) => _buildOfferCard(offer)),
              ] else if (status.status == 'initiated' || status.status == 'matching') ...[
                _buildSearchingAnimation(),
              ],
            ],
          ),
        );
      }),
    );
  }

  Widget _buildTimerCard(BargainingStatus status) {
    final minutes = (status.timeRemaining ~/ 60).toString().padLeft(2, '0');
    final seconds = (status.timeRemaining % 60).toString().padLeft(2, '0');

    return Card(
      color: status.isExpired ? Colors.red[50] : Colors.blue[50],
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  status.isExpired ? 'Expired' : 'Time Remaining',
                  style: TextStyle(color: Colors.grey[700]),
                ),
                SizedBox(height: 4),
                Text(
                  '$minutes:$seconds',
                  style: TextStyle(
                    fontSize: 32,
                    fontWeight: FontWeight.bold,
                    color: status.isExpired ? Colors.red : Colors.blue,
                  ),
                ),
              ],
            ),
            Icon(
              Icons.timer,
              size: 48,
              color: status.isExpired ? Colors.red : Colors.blue,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatsCard(BargainingStatus status) {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          children: [
            _buildStatRow('Cart Items', status.totalCartItems.toString()),
            Divider(),
            _buildStatRow('Original Value', '₹${status.originalCartValue.toStringAsFixed(2)}'),
            Divider(),
            _buildStatRow('Stores Matched', status.totalStoresMatched.toString()),
            Divider(),
            _buildStatRow('Offers Received', status.totalOffersReceived.toString()),
          ],
        ),
      ),
    );
  }

  Widget _buildStatRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: TextStyle(color: Colors.grey[600])),
        Text(
          value,
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
        ),
      ],
    );
  }

  Widget _buildBestOfferCard(BargainingOffer offer) {
    return Card(
      color: Colors.green[50],
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.star, color: Colors.amber, size: 28),
                SizedBox(width: 8),
                Text(
                  'Best Offer',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Colors.green[800],
                  ),
                ),
              ],
            ),
            SizedBox(height: 12),

            // Store Info
            Row(
              children: [
                CircleAvatar(
                  backgroundImage: NetworkImage(offer.storeLogo),
                  radius: 24,
                ),
                SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        offer.storeName,
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Row(
                        children: [
                          Icon(Icons.star, color: Colors.amber, size: 16),
                          Text(' ${offer.storeRating.toStringAsFixed(1)}'),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),

            SizedBox(height: 12),
            Divider(),

            // Pricing
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Total Amount:', style: TextStyle(fontSize: 16)),
                Text(
                  '₹${offer.totalAmount.toStringAsFixed(2)}',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.green[800],
                  ),
                ),
              ],
            ),

            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('You Save:', style: TextStyle(color: Colors.green[700])),
                Text(
                  '₹${offer.savings.toStringAsFixed(2)}',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.green[700],
                  ),
                ),
              ],
            ),

            SizedBox(height: 12),

            // Fulfillment
            Row(
              children: [
                Expanded(
                  child: LinearProgressIndicator(
                    value: offer.fulfillmentPercentage / 100,
                    backgroundColor: Colors.grey[300],
                    valueColor: AlwaysStoppedAnimation<Color>(Colors.green),
                  ),
                ),
                SizedBox(width: 8),
                Text('${offer.fulfillmentPercentage.toInt()}%'),
              ],
            ),
            Text(
              '${offer.itemsAvailable}/${offer.itemsAvailable + offer.itemsMissing} items available',
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),

            SizedBox(height: 16),

            // Accept Button
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => _confirmAccept(offer),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  padding: EdgeInsets.symmetric(vertical: 16),
                ),
                child: Text(
                  'Accept This Offer',
                  style: TextStyle(fontSize: 18, color: Colors.white),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOfferCard(BargainingOffer offer) {
    return Card(
      margin: EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundImage: NetworkImage(offer.storeLogo),
        ),
        title: Text(offer.storeName),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.star, size: 16, color: Colors.amber),
                Text(' ${offer.storeRating.toStringAsFixed(1)}'),
                SizedBox(width: 8),
                Text('• ${offer.fulfillmentPercentage.toInt()}% available'),
              ],
            ),
            if (offer.isVendorOffer)
              Chip(
                label: Text('Vendor Offer', style: TextStyle(fontSize: 10)),
                backgroundColor: Colors.orange[100],
              ),
          ],
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              '₹${offer.totalAmount.toStringAsFixed(2)}',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
            ),
            Text(
              'Save ₹${offer.savings.toStringAsFixed(2)}',
              style: TextStyle(color: Colors.green, fontSize: 12),
            ),
          ],
        ),
        onTap: () => _viewOfferDetails(offer.offerId),
      ),
    );
  }

  Widget _buildSearchingAnimation() {
    return Column(
      children: [
        SizedBox(height: 48),
        CircularProgressIndicator(),
        SizedBox(height: 24),
        Text(
          'Searching for best offers...',
          style: TextStyle(fontSize: 16, color: Colors.grey[600]),
        ),
      ],
    );
  }

  void _confirmAccept(BargainingOffer offer) {
    Get.dialog(
      AlertDialog(
        title: Text('Accept Offer?'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Store: ${offer.storeName}'),
            Text('Total: ₹${offer.totalAmount.toStringAsFixed(2)}'),
            Text(
              'Savings: ₹${offer.savings.toStringAsFixed(2)}',
              style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold),
            ),
            if (offer.itemsMissing > 0) ...[
              SizedBox(height: 8),
              Text(
                '⚠️ ${offer.itemsMissing} items unavailable',
                style: TextStyle(color: Colors.orange),
              ),
            ],
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Get.back(),
            child: Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Get.back();
              controller.acceptOffer(offer.offerId);
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
            child: Text('Accept'),
          ),
        ],
      ),
    );
  }

  void _confirmCancel() {
    Get.dialog(
      AlertDialog(
        title: Text('Cancel Bargaining?'),
        content: Text('Are you sure you want to cancel this bargaining session?'),
        actions: [
          TextButton(
            onPressed: () => Get.back(),
            child: Text('No'),
          ),
          ElevatedButton(
            onPressed: () {
              Get.back();
              controller.cancel();
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: Text('Yes, Cancel'),
          ),
        ],
      ),
    );
  }

  void _viewOfferDetails(int offerId) {
    // Navigate to offer details screen
    Get.to(() => BargainingOfferDetailsScreen(offerId: offerId));
  }
}
```

---

## 🎯 Testing Checklist

### Backend Test

```bash
# Test initiate (should work if cart not empty)
curl -X POST https://your-domain.com/api/v2/bargaining/initiate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "zoneId: [1]" \
  -H "moduleId: 1" \
  -H "Content-Type: application/json" \
  -d '{"mode":"instant"}'

# Expected: 201 with request_code
```

### App Integration Test

1. **Add items to cart** (3-5 items from different stores)
2. **Click "Find Better Prices"** → Mode selection appears
3. **Select Instant Mode** → Status screen appears
4. **Wait 2-3 seconds** → Offers appear
5. **Click best offer** → Details shown
6. **Accept offer** → Cart updated, navigate to checkout
7. **Place order** → Should use the bargained store

---

## 🔧 Configuration

### Recommended Settings

```bash
# Backend .env
BARGAINING_ENABLED=true
BARGAINING_INSTANT_MODE=true
BARGAINING_WAIT_MODE=true
BARGAINING_WAIT_DURATION=60
BARGAINING_MIN_CART_VALUE=0
BARGAINING_REQUIRE_BARCODE=false  # Enable fuzzy matching
```

---

## 🚀 Deployment Steps

### 1. Backend (if not deployed)
```bash
cd /var/www/html/new_public/new
php artisan migrate --path=database/migrations/2026_03_17_*
php artisan config:cache
php artisan route:cache
php scripts/test-bargaining-api.php
```

### 2. Customer App
```bash
# Add dependencies to pubspec.yaml
dependencies:
  dio: ^5.0.0
  get: ^4.6.0

# Add files
lib/services/bargaining_service.dart
lib/models/bargaining_models.dart
lib/controllers/bargaining_controller.dart
lib/screens/bargaining/mode_selection_screen.dart
lib/screens/bargaining/status_screen.dart
lib/screens/bargaining/offer_details_screen.dart

# Initialize service in main.dart
Get.lazyPut(() => BargainingService());
```

---

## 💡 Tips

1. **Polling Interval:** 3 seconds is optimal (not too frequent, still responsive)
2. **Timeout Handling:** Stop polling if user leaves screen
3. **Error Handling:** Show friendly messages for "Cart empty", "No offers", etc.
4. **Analytics:** Track acceptance rate, average savings
5. **A/B Testing:** Test instant vs wait mode adoption

---

## 🎨 Design Assets Needed

- Bargaining icon (orange lightning bolt)
- Mode selection icons (flash + clock)
- Empty state illustration ("Searching for offers...")
- Success animation (when offer accepted)

---

## 📊 Analytics Events to Track

```dart
// Track these events
Analytics.logEvent('bargaining_initiated', {'mode': 'instant'});
Analytics.logEvent('bargaining_offers_received', {'count': 5});
Analytics.logEvent('bargaining_offer_accepted', {'savings': 65.0});
Analytics.logEvent('bargaining_cancelled');
```

---

## ✅ Launch Checklist

- [ ] Backend migrations run
- [ ] Feature flag enabled
- [ ] API test passes
- [ ] Service class added
- [ ] Models added
- [ ] Controller added
- [ ] UI screens added
- [ ] Cart button added
- [ ] Polling works
- [ ] Accept flow works
- [ ] Cancel flow works
- [ ] Error handling tested
- [ ] Analytics integrated
- [ ] QA testing done

---

## 🆘 Support

**Backend Issues:** Check `storage/logs/laravel.log | grep bargaining`

**App Issues:** Add debug logging in service calls

**Emergency Disable:** `BARGAINING_ENABLED=false` + `php artisan config:cache`

---

**Ready to integrate?** Start with Step 1 (Backend Verification) and follow this guide sequentially! 🚀
