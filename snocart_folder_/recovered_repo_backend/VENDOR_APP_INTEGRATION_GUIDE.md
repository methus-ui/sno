# Vendor App Integration Guide - ESP32 Sound Box

## Complete Flutter Integration for Sound Box Device Management

**Version:** 1.0.0
**Date:** 2026-02-26
**Platform:** Flutter (Vendor Mobile App)

---

## 📱 Overview

This guide provides everything needed to integrate Sound Box device management into your Flutter vendor app.

**Features to Implement:**
1. ✅ Generate QR code for device pairing
2. ✅ List all paired devices
3. ✅ View device status (online/offline)
4. ✅ Send test notifications
5. ✅ Update device settings (volume, language, auto-accept)
6. ✅ Deactivate/reactivate devices
7. ✅ View device statistics

---

## 🔗 API Endpoints Reference

### Base URL
```
https://new.snocart.com/api/v1/vendor/device-management
```

### Authentication Headers
```http
Authorization: Bearer {vendor_token}
vendorType: owner
Content-Type: application/json
```

---

## 📡 API Endpoints (Complete List)

### 1. Generate Pairing QR Code

**Endpoint:** `POST /generate-pairing-qr`

**Purpose:** Generate a QR code for pairing a new ESP32 device.

**Request:**
```json
{
  "store_id": 5
}
```

**Response (200 OK):**
```json
{
  "pairing_token": "PT-ABC123XYZ456789012",
  "qr_data": "SNOCART:PAIR:PT-ABC123XYZ456789012:https://new.snocart.com",
  "expires_at": "2026-02-26T10:35:00Z",
  "expires_in": 300,
  "message": "QR code generated successfully"
}
```

**Error Responses:**
```json
// 400 - Store limit reached
{
  "errors": [
    {
      "code": "pairing",
      "message": "Store has reached maximum device limit (5)"
    }
  ]
}

// 400 - Invalid store
{
  "errors": [
    {
      "code": "pairing",
      "message": "Store not found or does not belong to vendor"
    }
  ]
}
```

**Flutter Implementation:**
```dart
Future<PairingQRResponse> generatePairingQR(int storeId) async {
  final response = await post(
    Uri.parse('$baseUrl/device-management/generate-pairing-qr'),
    headers: {
      'Authorization': 'Bearer $vendorToken',
      'vendorType': 'owner',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({'store_id': storeId}),
  );

  if (response.statusCode == 200) {
    return PairingQRResponse.fromJson(jsonDecode(response.body));
  } else {
    throw Exception('Failed to generate QR code');
  }
}
```

---

### 2. List All Devices

**Endpoint:** `GET /list?store_id={store_id}`

**Purpose:** Get all paired devices for a store.

**Request:**
```http
GET /list?store_id=5
```

**Response (200 OK):**
```json
{
  "devices": [
    {
      "id": 1,
      "device_id": "AA:BB:CC:DD:EE:FF",
      "device_name": "Kitchen Sound Box",
      "is_active": true,
      "is_online": true,
      "last_ping_at": "2026-02-26T10:30:00Z",
      "wifi_ssid": "Store_WiFi",
      "local_ip": "192.168.1.100",
      "firmware_version": "1.0.0",
      "paired_at": "2026-02-26T09:00:00Z",
      "paired_by": "John Doe",
      "uptime_seconds": 3600
    },
    {
      "id": 2,
      "device_id": "11:22:33:44:55:66",
      "device_name": "Counter Sound Box",
      "is_active": true,
      "is_online": false,
      "last_ping_at": "2026-02-26T09:00:00Z",
      "wifi_ssid": "Store_WiFi",
      "local_ip": "192.168.1.101",
      "firmware_version": "1.0.0",
      "paired_at": "2026-02-25T14:00:00Z",
      "paired_by": "Jane Smith",
      "uptime_seconds": null
    }
  ],
  "total": 2
}
```

**Flutter Implementation:**
```dart
Future<DeviceListResponse> getDeviceList(int storeId) async {
  final response = await get(
    Uri.parse('$baseUrl/device-management/list?store_id=$storeId'),
    headers: {
      'Authorization': 'Bearer $vendorToken',
      'vendorType': 'owner',
    },
  );

  if (response.statusCode == 200) {
    return DeviceListResponse.fromJson(jsonDecode(response.body));
  } else {
    throw Exception('Failed to load devices');
  }
}
```

---

### 3. Get Device Status

**Endpoint:** `GET /status?device_id={device_id}`

**Purpose:** Get detailed status of a specific device.

**Request:**
```http
GET /status?device_id=1
```

**Response (200 OK):**
```json
{
  "device_id": 1,
  "is_online": true,
  "last_ping_at": "2026-02-26T10:30:00Z",
  "uptime_seconds": 3600,
  "is_active": true,
  "webhook_url": "http://192.168.1.100:8080/webhook",
  "wifi_ssid": "Store_WiFi",
  "local_ip": "192.168.1.100"
}
```

**Flutter Implementation:**
```dart
Future<DeviceStatus> getDeviceStatus(int deviceId) async {
  final response = await get(
    Uri.parse('$baseUrl/device-management/status?device_id=$deviceId'),
    headers: {
      'Authorization': 'Bearer $vendorToken',
      'vendorType': 'owner',
    },
  );

  if (response.statusCode == 200) {
    return DeviceStatus.fromJson(jsonDecode(response.body));
  } else {
    throw Exception('Failed to get device status');
  }
}
```

---

### 4. Send Test Notification

**Endpoint:** `POST /test-notification`

**Purpose:** Send a test order notification to verify device is working.

**Request:**
```json
{
  "device_id": 1
}
```

**Response (200 OK):**
```json
{
  "notification_sent": true,
  "delivery_status": "success",
  "message": "Test notification sent successfully"
}
```

**Error Responses:**
```json
// 400 - Device offline
{
  "errors": [
    {
      "code": "device",
      "message": "Device is offline"
    }
  ]
}
```

**Flutter Implementation:**
```dart
Future<TestNotificationResponse> sendTestNotification(int deviceId) async {
  final response = await post(
    Uri.parse('$baseUrl/device-management/test-notification'),
    headers: {
      'Authorization': 'Bearer $vendorToken',
      'vendorType': 'owner',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({'device_id': deviceId}),
  );

  if (response.statusCode == 200) {
    return TestNotificationResponse.fromJson(jsonDecode(response.body));
  } else {
    throw Exception('Failed to send test notification');
  }
}
```

---

### 5. Update Device Settings

**Endpoint:** `PUT /update-settings`

**Purpose:** Update device configuration (volume, language, auto-accept, etc.).

**Request:**
```json
{
  "device_id": 1,
  "settings": {
    "volume": 80,
    "language": "en",
    "auto_accept": false,
    "auto_accept_timeout": 300,
    "announcement_voice": "female",
    "play_sound": true
  }
}
```

**Response (200 OK):**
```json
{
  "message": "Settings updated successfully",
  "settings": {
    "volume": 80,
    "language": "en",
    "auto_accept": false,
    "auto_accept_timeout": 300,
    "announcement_voice": "female",
    "play_sound": true,
    "vibrate": true,
    "led_color": "blue"
  }
}
```

**Available Settings:**
- `volume` (0-100)
- `language` (en, hi, te, ta, kn, ml)
- `auto_accept` (true/false)
- `auto_accept_timeout` (60-600 seconds)
- `announcement_voice` (male/female)
- `play_sound` (true/false)

**Flutter Implementation:**
```dart
Future<SettingsUpdateResponse> updateDeviceSettings(
  int deviceId,
  Map<String, dynamic> settings,
) async {
  final response = await put(
    Uri.parse('$baseUrl/device-management/update-settings'),
    headers: {
      'Authorization': 'Bearer $vendorToken',
      'vendorType': 'owner',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'device_id': deviceId,
      'settings': settings,
    }),
  );

  if (response.statusCode == 200) {
    return SettingsUpdateResponse.fromJson(jsonDecode(response.body));
  } else {
    throw Exception('Failed to update settings');
  }
}
```

---

### 6. Deactivate Device

**Endpoint:** `POST /deactivate`

**Purpose:** Deactivate a device (stops receiving webhooks).

**Request:**
```json
{
  "device_id": 1
}
```

**Response (200 OK):**
```json
{
  "message": "Device deactivated successfully",
  "status": "deactivated"
}
```

**Flutter Implementation:**
```dart
Future<DeactivateResponse> deactivateDevice(int deviceId) async {
  final response = await post(
    Uri.parse('$baseUrl/device-management/deactivate'),
    headers: {
      'Authorization': 'Bearer $vendorToken',
      'vendorType': 'owner',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({'device_id': deviceId}),
  );

  if (response.statusCode == 200) {
    return DeactivateResponse.fromJson(jsonDecode(response.body));
  } else {
    throw Exception('Failed to deactivate device');
  }
}
```

---

### 7. Get Device Statistics

**Endpoint:** `GET /stats?device_id={device_id}&days={days}`

**Purpose:** Get device performance statistics.

**Request:**
```http
GET /stats?device_id=1&days=7
```

**Response (200 OK):**
```json
{
  "device_id": 1,
  "device_name": "Kitchen Sound Box",
  "uptime_seconds": 86400,
  "is_online": true,
  "webhooks_total": 150,
  "webhooks_success": 148,
  "webhooks_failed": 2,
  "webhooks_pending": 0,
  "success_rate": 98.67
}
```

**Flutter Implementation:**
```dart
Future<DeviceStats> getDeviceStats(int deviceId, {int days = 7}) async {
  final response = await get(
    Uri.parse('$baseUrl/device-management/stats?device_id=$deviceId&days=$days'),
    headers: {
      'Authorization': 'Bearer $vendorToken',
      'vendorType': 'owner',
    },
  );

  if (response.statusCode == 200) {
    return DeviceStats.fromJson(jsonDecode(response.body));
  } else {
    throw Exception('Failed to get device stats');
  }
}
```

---

## 📋 Complete JSON Response Structures

This section provides **exact** JSON response structures from the production API for all endpoints. All field names, data types, and formats are taken directly from the backend implementation.

---

### 1. POST /generate-pairing-qr - Complete Response

**Success (200 OK):**
```json
{
  "pairing_token": "PT-ABC123XYZ456789012",
  "qr_data": "SNOCART:PAIR:PT-ABC123XYZ456789012:https://new.snocart.com",
  "expires_at": "2026-02-26T10:35:00.000000Z",
  "expires_in": 300
}
```

**Field Details:**
- `pairing_token` (string) - Unique token, format: `PT-{20 alphanumeric chars}`
- `qr_data` (string) - Complete QR payload: `SNOCART:PAIR:{token}:{server_url}`
- `expires_at` (timestamp) - ISO 8601 UTC timestamp with microseconds
- `expires_in` (integer) - Seconds until expiry (always 300 = 5 minutes)

---

### 2. GET /list - Complete Response

**Success (200 OK):**
```json
{
  "devices": [
    {
      "id": 1,
      "device_id": "A4:CF:12:34:56:78",
      "device_name": "Kitchen Sound Box",
      "is_active": true,
      "is_online": true,
      "last_ping_at": "2026-02-26T10:29:30.000000Z",
      "wifi_ssid": "Store_WiFi_5G",
      "local_ip": "192.168.1.105",
      "firmware_version": "v1.0.2",
      "paired_at": "2026-02-25T08:15:00.000000Z",
      "paired_by": "John Doe",
      "uptime_seconds": 86400
    },
    {
      "id": 2,
      "device_id": "B8:27:EB:AA:BB:CC",
      "device_name": "Counter Sound Box",
      "is_active": true,
      "is_online": false,
      "last_ping_at": "2026-02-26T09:50:00.000000Z",
      "wifi_ssid": "Store_WiFi_5G",
      "local_ip": "192.168.1.106",
      "firmware_version": "v1.0.1",
      "paired_at": "2026-02-24T14:20:00.000000Z",
      "paired_by": "Jane Smith",
      "uptime_seconds": 172800
    }
  ],
  "total": 2
}
```

**Field Details per Device:**
- `id` (integer) - Database primary key
- `device_id` (string) - MAC address or unique device identifier
- `device_name` (string) - User-friendly name set during pairing
- `is_active` (boolean) - Device activation status (can be deactivated)
- `is_online` (boolean) - `true` if pinged within last 5 minutes (300 seconds)
- `last_ping_at` (timestamp|null) - Last heartbeat received (ISO 8601 UTC)
- `wifi_ssid` (string|null) - WiFi network name device is connected to
- `local_ip` (string|null) - Device's local IP address (e.g., 192.168.1.x)
- `firmware_version` (string|null) - ESP32 firmware version (e.g., "v1.0.2")
- `paired_at` (timestamp) - When device was first paired (ISO 8601 UTC)
- `paired_by` (string|null) - Full name of employee who paired device
- `uptime_seconds` (integer) - Seconds since `last_ping_at` (0 if never pinged)

**Root Fields:**
- `devices` (array) - Array of device objects
- `total` (integer) - Total count of devices

---

### 3. GET /status - Complete Response

**Success (200 OK):**
```json
{
  "device_id": 1,
  "is_online": true,
  "last_ping_at": "2026-02-26T10:29:45.000000Z",
  "uptime_seconds": 86415,
  "is_active": true,
  "webhook_url": "http://192.168.1.105:8080/webhook",
  "wifi_ssid": "Store_WiFi_5G",
  "local_ip": "192.168.1.105"
}
```

**Field Details:**
- `device_id` (integer) - Database ID (same as `id` from /list)
- `is_online` (boolean) - Online if `last_ping_at` < 5 minutes ago
- `last_ping_at` (timestamp|null) - Last heartbeat timestamp (ISO 8601 UTC)
- `uptime_seconds` (integer) - Seconds since last ping (0 if never pinged)
- `is_active` (boolean) - Device activation status
- `webhook_url` (string) - Full webhook endpoint URL (local network)
- `wifi_ssid` (string|null) - Connected WiFi network SSID
- `local_ip` (string|null) - Device's local IP address

**Online Detection Logic:**
```
is_online = (now() - last_ping_at) <= 300 seconds
```

---

### 4. POST /deactivate - Complete Response

**Success (200 OK):**
```json
{
  "message": "Device deactivated successfully",
  "status": "deactivated"
}
```

**Field Details:**
- `message` (string) - Human-readable success message
- `status` (string) - Always `"deactivated"` on success

**Error - Unauthorized (403 Forbidden):**
```json
{
  "errors": [
    {
      "code": "auth",
      "message": "Unauthorized"
    }
  ]
}
```

**Error - Not Found (400 Bad Request):**
```json
{
  "errors": [
    {
      "code": "device",
      "message": "Device not found"
    }
  ]
}
```

---

### 5. PUT /update-settings - Complete Response

**Request:**
```json
{
  "device_id": 1,
  "settings": {
    "volume": 85,
    "language": "hi",
    "auto_accept": false,
    "auto_accept_timeout": 300,
    "sound_enabled": true
  }
}
```

**Success (200 OK):**
```json
{
  "message": "Settings updated successfully",
  "settings": {
    "volume": 85,
    "language": "hi",
    "auto_accept": false,
    "auto_accept_timeout": 300,
    "sound_enabled": true
  }
}
```

**Field Details:**
- `message` (string) - Success confirmation message
- `settings` (object) - Complete settings object (includes defaults for unset keys)

**Available Settings Keys:**
- `volume` (integer) - Speaker volume 0-100 (default: 80)
- `language` (string) - Audio language code: `en`, `hi`, `ta`, etc. (default: `"en"`)
- `auto_accept` (boolean) - Auto-accept orders without button press (default: `false`)
- `auto_accept_timeout` (integer) - Seconds before auto-accept triggers (default: 300)
- `sound_enabled` (boolean) - Enable notification sounds (default: `true`)

**Settings Merge Behavior:**
- New settings are **merged** with existing settings
- Unspecified keys retain their current values
- Missing keys use defaults from `config/soundbox.php`

---

### 6. POST /test-notification - Complete Response

**Success (200 OK):**
```json
{
  "notification_sent": true,
  "delivery_status": "success",
  "message": "Test notification sent successfully"
}
```

**Field Details:**
- `notification_sent` (boolean) - `true` if HTTP POST to device succeeded
- `delivery_status` (string) - `"success"` or `"failed"`
- `message` (string) - Human-readable status message

**Error - Device Offline (400 Bad Request):**
```json
{
  "errors": [
    {
      "code": "device",
      "message": "Device is offline"
    }
  ]
}
```

**Error - Webhook Failed (400 Bad Request):**
```json
{
  "notification_sent": false,
  "delivery_status": "failed",
  "message": "Failed to send notification"
}
```

**Important Notes:**
- Test notification only works if device is online (recent heartbeat)
- Sends a test webhook to device's `webhook_url`
- Device should play test sound and light up buttons
- Timeout is 5 seconds (configurable in `config/soundbox.php`)

---

### 7. GET /stats - Complete Response

**Request:**
```
GET /stats?device_id=1&days=7
```

**Success (200 OK):**
```json
{
  "device_id": 1,
  "device_name": "Kitchen Sound Box",
  "uptime_seconds": 86400,
  "is_online": true,
  "webhooks_total": 45,
  "webhooks_success": 43,
  "webhooks_failed": 2,
  "webhooks_pending": 0,
  "success_rate": 95.56
}
```

**Field Details:**
- `device_id` (integer) - Database ID
- `device_name` (string) - Device name
- `uptime_seconds` (integer) - Seconds since last ping (0 if never pinged)
- `is_online` (boolean) - Online if pinged within last 5 minutes
- `webhooks_total` (integer) - Total webhooks sent in period
- `webhooks_success` (integer) - Successfully delivered webhooks
- `webhooks_failed` (integer) - Failed deliveries (after all retry attempts)
- `webhooks_pending` (integer) - Currently being retried
- `success_rate` (float) - Percentage with 2 decimal places (0.00-100.00)

**Query Parameters:**
- `device_id` (required, integer) - Device database ID
- `days` (optional, integer) - Days to analyze (1-30, default: 7)

**Success Rate Calculation:**
```
success_rate = (webhooks_success / webhooks_total) × 100
```

If `webhooks_total = 0`, then `success_rate = 0.00`.

**Statistics Period:**
- Analyzes webhook queue entries created in last `days` days
- Default: 7 days
- Maximum: 30 days
- Minimum: 1 day

---

## 🚨 Error Response Format (All Endpoints)

### Validation Error (400 Bad Request)
```json
{
  "errors": [
    {
      "code": "store_id",
      "message": "The store id field is required."
    },
    {
      "code": "device_id",
      "message": "The device id must be an integer."
    }
  ]
}
```

### Authorization Error (403 Forbidden)
```json
{
  "errors": [
    {
      "code": "auth",
      "message": "Unauthorized"
    }
  ]
}
```

**Occurs when:**
- Device's `vendor_id` doesn't match token's `vendor_id`
- Employee doesn't have permission to manage devices

### Not Found Error (404 Not Found)
```json
{
  "errors": [
    {
      "code": "device_id",
      "message": "The selected device id is invalid."
    }
  ]
}
```

### Server Error (500 Internal Server Error)
```json
{
  "errors": [
    {
      "code": "server",
      "message": "An unexpected error occurred"
    }
  ]
}
```

---

## 🔑 Authentication Details

**All endpoints require:**

```http
Authorization: Bearer {vendor_auth_token}
vendorType: owner
Content-Type: application/json
```

**Token Sources:**
- `vendors.auth_token` - For owner/admin
- `vendor_employees.auth_token` - For employees (with device management permission)

**Token Validation:**
- Middleware: `VendorTokenIsValid`
- Checks token exists and is active
- Verifies vendor/employee is active
- Attaches vendor object to request

**Authorization Check:**
- All endpoints verify `vendor_id` in token matches device's `vendor_id`
- Employees must have `manage_devices` permission (future enhancement)

---

## 📊 Important Notes

### Timestamps
- All timestamps are in **UTC ISO 8601** format
- Format: `2026-02-26T10:29:30.000000Z`
- Always includes microseconds (6 decimal places)
- Parse with `DateTime.parse()` in Dart

### Online Detection
- Device considered **online** if `last_ping_at` is within last **5 minutes** (300 seconds)
- Configured in `config/soundbox.php` → `device.offline_threshold`
- Heartbeat interval is 60 seconds (ESP32 pings every minute)

### Uptime Calculation
```dart
uptime_seconds = now() - last_ping_at (in seconds)
```
- Returns `0` if device never pinged
- Returns `null` if `last_ping_at` is `null`

### Settings Merge
- When updating settings, **new values are merged** with existing settings
- Unspecified keys keep their current values
- Missing keys use defaults from `config/soundbox.php`
- Example: Updating only `volume` doesn't affect `language` or `auto_accept`

### Rate Limiting
- **120 requests/minute** for heartbeat endpoints
- **60 requests/minute** for order action endpoints
- **No rate limit** for device management endpoints (managed by vendor middleware)

### Webhook Retry Logic
- Failed webhooks retry **3 times**
- Retry delay: **30 seconds** between attempts
- Status: `pending` → `success` or `failed`
- View retry queue in device stats

### Test Notification Requirements
- Device must be **online** (`is_online = true`)
- Webhook URL must be reachable
- Device must respond within **5 seconds**
- Sends event: `"test_notification"` in payload

---

## 📦 Flutter Data Models

### PairingQRResponse
```dart
class PairingQRResponse {
  final String pairingToken;
  final String qrData;
  final DateTime expiresAt;
  final int expiresIn;
  final String message;

  PairingQRResponse({
    required this.pairingToken,
    required this.qrData,
    required this.expiresAt,
    required this.expiresIn,
    required this.message,
  });

  factory PairingQRResponse.fromJson(Map<String, dynamic> json) {
    return PairingQRResponse(
      pairingToken: json['pairing_token'],
      qrData: json['qr_data'],
      expiresAt: DateTime.parse(json['expires_at']),
      expiresIn: json['expires_in'],
      message: json['message'],
    );
  }
}
```

### Device
```dart
class Device {
  final int id;
  final String deviceId;
  final String deviceName;
  final bool isActive;
  final bool isOnline;
  final DateTime? lastPingAt;
  final String? wifiSsid;
  final String? localIp;
  final String? firmwareVersion;
  final DateTime? pairedAt;
  final String? pairedBy;
  final int? uptimeSeconds;

  Device({
    required this.id,
    required this.deviceId,
    required this.deviceName,
    required this.isActive,
    required this.isOnline,
    this.lastPingAt,
    this.wifiSsid,
    this.localIp,
    this.firmwareVersion,
    this.pairedAt,
    this.pairedBy,
    this.uptimeSeconds,
  });

  factory Device.fromJson(Map<String, dynamic> json) {
    return Device(
      id: json['id'],
      deviceId: json['device_id'],
      deviceName: json['device_name'],
      isActive: json['is_active'],
      isOnline: json['is_online'],
      lastPingAt: json['last_ping_at'] != null
          ? DateTime.parse(json['last_ping_at'])
          : null,
      wifiSsid: json['wifi_ssid'],
      localIp: json['local_ip'],
      firmwareVersion: json['firmware_version'],
      pairedAt: json['paired_at'] != null
          ? DateTime.parse(json['paired_at'])
          : null,
      pairedBy: json['paired_by'],
      uptimeSeconds: json['uptime_seconds'],
    );
  }

  String getStatusText() {
    if (!isActive) return 'Deactivated';
    if (isOnline) return 'Online';
    return 'Offline';
  }

  Color getStatusColor() {
    if (!isActive) return Colors.grey;
    if (isOnline) return Colors.green;
    return Colors.red;
  }
}
```

### DeviceListResponse
```dart
class DeviceListResponse {
  final List<Device> devices;
  final int total;

  DeviceListResponse({
    required this.devices,
    required this.total,
  });

  factory DeviceListResponse.fromJson(Map<String, dynamic> json) {
    return DeviceListResponse(
      devices: (json['devices'] as List)
          .map((device) => Device.fromJson(device))
          .toList(),
      total: json['total'],
    );
  }
}
```

---

## 🎨 Flutter UI Screens

### 1. Sound Box Management Screen (List)

**Path:** `lib/view/screens/store/sound_box_management_screen.dart`

```dart
import 'package:flutter/material.dart';

class SoundBoxManagementScreen extends StatefulWidget {
  final int storeId;

  const SoundBoxManagementScreen({Key? key, required this.storeId})
      : super(key: key);

  @override
  _SoundBoxManagementScreenState createState() =>
      _SoundBoxManagementScreenState();
}

class _SoundBoxManagementScreenState extends State<SoundBoxManagementScreen> {
  List<Device> devices = [];
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    loadDevices();
  }

  Future<void> loadDevices() async {
    setState(() => isLoading = true);
    try {
      final response = await getDeviceList(widget.storeId);
      setState(() {
        devices = response.devices;
        isLoading = false;
      });
    } catch (e) {
      setState(() => isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to load devices: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Sound Box Devices'),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh),
            onPressed: loadDevices,
          ),
        ],
      ),
      body: isLoading
          ? Center(child: CircularProgressIndicator())
          : devices.isEmpty
              ? _buildEmptyState()
              : ListView.builder(
                  itemCount: devices.length,
                  itemBuilder: (context, index) {
                    return _buildDeviceCard(devices[index]);
                  },
                ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showAddDeviceDialog(),
        icon: Icon(Icons.add),
        label: Text('Add Sound Box'),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.speaker, size: 80, color: Colors.grey),
          SizedBox(height: 16),
          Text(
            'No devices paired yet',
            style: TextStyle(fontSize: 18, color: Colors.grey[600]),
          ),
          SizedBox(height: 8),
          ElevatedButton(
            onPressed: () => _showAddDeviceDialog(),
            child: Text('Add Your First Sound Box'),
          ),
        ],
      ),
    );
  }

  Widget _buildDeviceCard(Device device) {
    return Card(
      margin: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: device.getStatusColor(),
          child: Icon(
            device.isOnline ? Icons.speaker : Icons.speaker_phone,
            color: Colors.white,
          ),
        ),
        title: Text(device.deviceName),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('MAC: ${device.deviceId}'),
            Text('Status: ${device.getStatusText()}'),
            if (device.lastPingAt != null)
              Text(
                'Last seen: ${_formatDateTime(device.lastPingAt!)}',
                style: TextStyle(fontSize: 12, color: Colors.grey[600]),
              ),
          ],
        ),
        trailing: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            IconButton(
              icon: Icon(Icons.settings),
              onPressed: () => _openDeviceSettings(device),
            ),
            PopupMenuButton(
              itemBuilder: (context) => [
                PopupMenuItem(
                  value: 'test',
                  child: Row(
                    children: [
                      Icon(Icons.notifications_active),
                      SizedBox(width: 8),
                      Text('Send Test'),
                    ],
                  ),
                ),
                PopupMenuItem(
                  value: 'stats',
                  child: Row(
                    children: [
                      Icon(Icons.analytics),
                      SizedBox(width: 8),
                      Text('View Stats'),
                    ],
                  ),
                ),
                PopupMenuItem(
                  value: 'deactivate',
                  child: Row(
                    children: [
                      Icon(Icons.block, color: Colors.red),
                      SizedBox(width: 8),
                      Text('Deactivate', style: TextStyle(color: Colors.red)),
                    ],
                  ),
                ),
              ],
              onSelected: (value) {
                switch (value) {
                  case 'test':
                    _sendTestNotification(device);
                    break;
                  case 'stats':
                    _viewDeviceStats(device);
                    break;
                  case 'deactivate':
                    _confirmDeactivate(device);
                    break;
                }
              },
            ),
          ],
        ),
        isThreeLine: true,
        onTap: () => _openDeviceDetails(device),
      ),
    );
  }

  void _showAddDeviceDialog() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => DevicePairingScreen(storeId: widget.storeId),
      ),
    ).then((_) => loadDevices());
  }

  void _sendTestNotification(Device device) async {
    try {
      final response = await sendTestNotification(device.id);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(response.message),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to send test: $e')),
      );
    }
  }

  void _confirmDeactivate(Device device) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Deactivate Device?'),
        content: Text(
          'This will stop ${device.deviceName} from receiving order notifications.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(context);
              await deactivateDevice(device.id);
              loadDevices();
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: Text('Deactivate'),
          ),
        ],
      ),
    );
  }

  String _formatDateTime(DateTime dateTime) {
    final diff = DateTime.now().difference(dateTime);
    if (diff.inMinutes < 60) {
      return '${diff.inMinutes} min ago';
    } else if (diff.inHours < 24) {
      return '${diff.inHours} hours ago';
    } else {
      return '${diff.inDays} days ago';
    }
  }

  void _openDeviceSettings(Device device) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => DeviceSettingsScreen(device: device),
      ),
    ).then((_) => loadDevices());
  }

  void _openDeviceDetails(Device device) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => DeviceDetailsScreen(device: device),
      ),
    ).then((_) => loadDevices());
  }

  void _viewDeviceStats(Device device) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => DeviceStatsScreen(device: device),
      ),
    );
  }
}
```

---

### 2. Device Pairing Screen (QR Code)

**Path:** `lib/view/screens/store/device_pairing_screen.dart`

```dart
import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

class DevicePairingScreen extends StatefulWidget {
  final int storeId;

  const DevicePairingScreen({Key? key, required this.storeId})
      : super(key: key);

  @override
  _DevicePairingScreenState createState() => _DevicePairingScreenState();
}

class _DevicePairingScreenState extends State<DevicePairingScreen> {
  PairingQRResponse? qrResponse;
  bool isLoading = true;
  int remainingSeconds = 0;
  Timer? countdownTimer;

  @override
  void initState() {
    super.initState();
    generateQR();
  }

  @override
  void dispose() {
    countdownTimer?.cancel();
    super.dispose();
  }

  Future<void> generateQR() async {
    setState(() => isLoading = true);
    try {
      final response = await generatePairingQR(widget.storeId);
      setState(() {
        qrResponse = response;
        remainingSeconds = response.expiresIn;
        isLoading = false;
      });
      startCountdown();
    } catch (e) {
      setState(() => isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to generate QR: $e')),
      );
    }
  }

  void startCountdown() {
    countdownTimer = Timer.periodic(Duration(seconds: 1), (timer) {
      setState(() {
        if (remainingSeconds > 0) {
          remainingSeconds--;
        } else {
          timer.cancel();
        }
      });
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Pair Sound Box'),
      ),
      body: isLoading
          ? Center(child: CircularProgressIndicator())
          : qrResponse == null
              ? _buildErrorState()
              : _buildQRView(),
    );
  }

  Widget _buildQRView() {
    final bool isExpired = remainingSeconds <= 0;

    return Center(
      child: SingleChildScrollView(
        padding: EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              'Scan QR Code on ESP32',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 16),
            Text(
              'Open the Sound Box device and scan this QR code to pair',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey[600]),
            ),
            SizedBox(height: 32),
            Container(
              padding: EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black12,
                    blurRadius: 10,
                    offset: Offset(0, 4),
                  ),
                ],
              ),
              child: isExpired
                  ? Column(
                      children: [
                        Icon(Icons.timer_off, size: 80, color: Colors.red),
                        SizedBox(height: 16),
                        Text(
                          'QR Code Expired',
                          style: TextStyle(fontSize: 18, color: Colors.red),
                        ),
                      ],
                    )
                  : QrImageView(
                      data: qrResponse!.qrData,
                      version: QrVersions.auto,
                      size: 250.0,
                      backgroundColor: Colors.white,
                    ),
            ),
            SizedBox(height: 24),
            if (!isExpired)
              Container(
                padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: remainingSeconds < 60 ? Colors.red[50] : Colors.blue[50],
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.timer,
                      color: remainingSeconds < 60 ? Colors.red : Colors.blue,
                    ),
                    SizedBox(width: 8),
                    Text(
                      'Expires in ${_formatSeconds(remainingSeconds)}',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w500,
                        color: remainingSeconds < 60 ? Colors.red : Colors.blue,
                      ),
                    ),
                  ],
                ),
              ),
            if (isExpired)
              ElevatedButton.icon(
                onPressed: generateQR,
                icon: Icon(Icons.refresh),
                label: Text('Generate New QR Code'),
                style: ElevatedButton.styleFrom(
                  padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                ),
              ),
            SizedBox(height: 32),
            _buildInstructions(),
          ],
        ),
      ),
    );
  }

  Widget _buildInstructions() {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Pairing Instructions:',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 12),
            _buildInstructionStep('1', 'Power on your Sound Box device'),
            _buildInstructionStep('2', 'Wait for setup mode (yellow LED)'),
            _buildInstructionStep('3', 'Scan this QR code with the device'),
            _buildInstructionStep('4', 'Device will connect to WiFi'),
            _buildInstructionStep('5', 'Green LED indicates successful pairing'),
          ],
        ),
      ),
    );
  }

  Widget _buildInstructionStep(String number, String text) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          CircleAvatar(
            radius: 12,
            child: Text(number, style: TextStyle(fontSize: 12)),
          ),
          SizedBox(width: 12),
          Expanded(child: Text(text)),
        ],
      ),
    );
  }

  Widget _buildErrorState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.error_outline, size: 80, color: Colors.red),
          SizedBox(height: 16),
          Text('Failed to generate QR code'),
          SizedBox(height: 16),
          ElevatedButton(
            onPressed: generateQR,
            child: Text('Try Again'),
          ),
        ],
      ),
    );
  }

  String _formatSeconds(int seconds) {
    final minutes = seconds ~/ 60;
    final secs = seconds % 60;
    return '$minutes:${secs.toString().padLeft(2, '0')}';
  }
}
```

---

### 3. Device Settings Screen

**Path:** `lib/view/screens/store/device_settings_screen.dart`

```dart
import 'package:flutter/material.dart';

class DeviceSettingsScreen extends StatefulWidget {
  final Device device;

  const DeviceSettingsScreen({Key? key, required this.device})
      : super(key: key);

  @override
  _DeviceSettingsScreenState createState() => _DeviceSettingsScreenState();
}

class _DeviceSettingsScreenState extends State<DeviceSettingsScreen> {
  late Map<String, dynamic> settings;
  bool isSaving = false;

  @override
  void initState() {
    super.initState();
    settings = {
      'volume': 80,
      'language': 'en',
      'auto_accept': false,
      'auto_accept_timeout': 300,
      'announcement_voice': 'female',
      'play_sound': true,
    };
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Device Settings'),
        actions: [
          if (isSaving)
            Center(
              child: Padding(
                padding: EdgeInsets.symmetric(horizontal: 16),
                child: SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                  ),
                ),
              ),
            )
          else
            IconButton(
              icon: Icon(Icons.save),
              onPressed: _saveSettings,
            ),
        ],
      ),
      body: ListView(
        padding: EdgeInsets.all(16),
        children: [
          _buildVolumeSlider(),
          SizedBox(height: 24),
          _buildLanguageDropdown(),
          SizedBox(height: 24),
          _buildVoiceSelector(),
          SizedBox(height: 24),
          _buildAutoAcceptSwitch(),
          if (settings['auto_accept'])
            _buildAutoAcceptTimeout(),
          SizedBox(height: 24),
          _buildPlaySoundSwitch(),
        ],
      ),
    );
  }

  Widget _buildVolumeSlider() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Volume', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w500)),
        SizedBox(height: 8),
        Row(
          children: [
            Icon(Icons.volume_down),
            Expanded(
              child: Slider(
                value: settings['volume'].toDouble(),
                min: 0,
                max: 100,
                divisions: 20,
                label: '${settings['volume']}%',
                onChanged: (value) {
                  setState(() => settings['volume'] = value.toInt());
                },
              ),
            ),
            Icon(Icons.volume_up),
            SizedBox(width: 8),
            Text('${settings['volume']}%'),
          ],
        ),
      ],
    );
  }

  Widget _buildLanguageDropdown() {
    final languages = {
      'en': 'English',
      'hi': 'Hindi',
      'te': 'Telugu',
      'ta': 'Tamil',
      'kn': 'Kannada',
      'ml': 'Malayalam',
    };

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Language', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w500)),
        SizedBox(height: 8),
        DropdownButtonFormField<String>(
          value: settings['language'],
          decoration: InputDecoration(
            border: OutlineInputBorder(),
            prefixIcon: Icon(Icons.language),
          ),
          items: languages.entries.map((entry) {
            return DropdownMenuItem(
              value: entry.key,
              child: Text(entry.value),
            );
          }).toList(),
          onChanged: (value) {
            setState(() => settings['language'] = value);
          },
        ),
      ],
    );
  }

  Widget _buildVoiceSelector() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Announcement Voice', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w500)),
        SizedBox(height: 8),
        Row(
          children: [
            Expanded(
              child: RadioListTile<String>(
                title: Text('Male'),
                value: 'male',
                groupValue: settings['announcement_voice'],
                onChanged: (value) {
                  setState(() => settings['announcement_voice'] = value);
                },
              ),
            ),
            Expanded(
              child: RadioListTile<String>(
                title: Text('Female'),
                value: 'female',
                groupValue: settings['announcement_voice'],
                onChanged: (value) {
                  setState(() => settings['announcement_voice'] = value);
                },
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildAutoAcceptSwitch() {
    return SwitchListTile(
      title: Text('Auto-Accept Orders'),
      subtitle: Text('Automatically accept orders after timeout'),
      value: settings['auto_accept'],
      onChanged: (value) {
        setState(() => settings['auto_accept'] = value);
      },
      secondary: Icon(Icons.check_circle_outline),
    );
  }

  Widget _buildAutoAcceptTimeout() {
    return Padding(
      padding: EdgeInsets.only(left: 16, top: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Auto-Accept Timeout'),
          Slider(
            value: settings['auto_accept_timeout'].toDouble(),
            min: 60,
            max: 600,
            divisions: 9,
            label: '${settings['auto_accept_timeout']} sec',
            onChanged: (value) {
              setState(() => settings['auto_accept_timeout'] = value.toInt());
            },
          ),
          Text('${settings['auto_accept_timeout']} seconds'),
        ],
      ),
    );
  }

  Widget _buildPlaySoundSwitch() {
    return SwitchListTile(
      title: Text('Play Notification Sound'),
      subtitle: Text('Play sound when new order arrives'),
      value: settings['play_sound'],
      onChanged: (value) {
        setState(() => settings['play_sound'] = value);
      },
      secondary: Icon(Icons.music_note),
    );
  }

  Future<void> _saveSettings() async {
    setState(() => isSaving = true);
    try {
      await updateDeviceSettings(widget.device.id, settings);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Settings saved successfully'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context);
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to save settings: $e')),
      );
    } finally {
      setState(() => isSaving = false);
    }
  }
}
```

---

## 🔧 Required Dependencies

Add to `pubspec.yaml`:

```yaml
dependencies:
  qr_flutter: ^4.1.0  # For QR code generation
  http: ^1.1.0        # For API calls
```

---

## 🎯 Navigation Integration

Add to your store dashboard screen:

```dart
// In store dashboard menu
ListTile(
  leading: Icon(Icons.speaker),
  title: Text('Sound Box Devices'),
  subtitle: Text('Manage ESP32 devices'),
  onTap: () {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => SoundBoxManagementScreen(
          storeId: currentStore.id,
        ),
      ),
    );
  },
),
```

---

## ✅ Testing Checklist

- [ ] Generate QR code successfully
- [ ] QR code countdown timer works
- [ ] QR code expires after 5 minutes
- [ ] Device list shows all paired devices
- [ ] Online/offline status displays correctly
- [ ] Test notification sends successfully
- [ ] Device settings update correctly
- [ ] Deactivate device works
- [ ] Device stats display correctly
- [ ] Error handling for failed API calls

---

## 🚀 Next Steps

1. ✅ Implement the 3 main screens (Management, Pairing, Settings)
2. ✅ Test QR code generation and pairing flow
3. ✅ Test device settings updates
4. ✅ Test notification sending
5. ✅ Add analytics tracking for device usage
6. ✅ Implement push notifications when device goes offline

---

## 📞 API Support

**Base URL:** `https://new.snocart.com/api/v1/vendor/device-management`

**Authentication:** Bearer token + vendorType header

**Rate Limiting:** 60 requests/minute per vendor

**Error Codes:**
- 400 - Bad request (validation errors)
- 401 - Unauthorized (invalid token)
- 403 - Forbidden (device doesn't belong to vendor)
- 404 - Not found (device/store not found)
- 500 - Server error

---

**Happy Integrating! 🎉**
