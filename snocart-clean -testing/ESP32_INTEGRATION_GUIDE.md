# ESP32 Sound Box - Hardware Integration Guide

## Arduino Code Examples for ESP32

This guide provides complete ESP32/Arduino code to integrate with the Sound Box backend system.

---

## Hardware Requirements

- **ESP32 DevKit** (any variant with WiFi)
- **DFPlayer Mini** (MP3 player module with speaker)
- **Speaker** (3-5W, 8Ω)
- **Push Buttons** (4x momentary buttons)
- **RGB LED** (for visual feedback)
- **microSD Card** (for audio files)
- **Power Supply** (5V, 2A minimum)

**Optional:**
- LCD Display (16x2 or OLED)
- QR Code Scanner Module
- Rotary Encoder (for volume control)

---

## Wiring Diagram

```
ESP32          DFPlayer Mini
-----          -------------
GPIO 16 (TX) → RX
GPIO 17 (RX) → TX
GND          → GND
5V           → VCC

ESP32          Buttons
-----          -------
GPIO 25      → Accept Button → GND
GPIO 26      → Reject Button → GND
GPIO 27      → Unavailable Button → GND
GPIO 14      → Settings Button → GND

ESP32          RGB LED
-----          -------
GPIO 32      → Red LED → 220Ω → GND
GPIO 33      → Green LED → 220Ω → GND
GPIO 12      → Blue LED → 220Ω → GND
```

---

## Required Libraries

Install these via Arduino Library Manager:

```
- WiFi (built-in)
- HTTPClient (built-in)
- ArduinoJson (v6.21.0+)
- DFRobotDFPlayerMini (v1.0.5+)
- Preferences (built-in for EEPROM)
```

---

## Complete Arduino Code

### soundbox_main.ino

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <DFRobotDFPlayerMini.h>
#include <Preferences.h>
#include <WebServer.h>

// Pin Definitions
#define PIN_ACCEPT_BTN 25
#define PIN_REJECT_BTN 26
#define PIN_UNAVAILABLE_BTN 27
#define PIN_SETTINGS_BTN 14
#define PIN_LED_RED 32
#define PIN_LED_GREEN 33
#define PIN_LED_BLUE 12
#define PIN_DFPLAYER_TX 16
#define PIN_DFPLAYER_RX 17

// Configuration
const char* AP_SSID = "SnocartSoundBox";
const char* AP_PASSWORD = "12345678";
const int WEBHOOK_PORT = 8080;
const int HEARTBEAT_INTERVAL = 60000; // 60 seconds
const int BUTTON_DEBOUNCE = 200; // ms

// Global Variables
Preferences preferences;
DFRobotDFPlayerMini mp3Player;
WebServer webhookServer(WEBHOOK_PORT);
HardwareSerial mp3Serial(1);

String apiKey = "";
String serverUrl = "https://new.snocart.com";
String deviceId = "";
int currentOrderId = 0;
unsigned long lastHeartbeat = 0;
unsigned long lastButtonPress = 0;

// Order data structure
struct OrderData {
  int orderId;
  String orderCode;
  String customerName;
  float totalAmount;
  int itemCount;
  JsonArray items;
};

OrderData currentOrder;

void setup() {
  Serial.begin(115200);
  Serial.println("\n\nSnocart Sound Box v1.0.0");
  Serial.println("==========================\n");

  // Initialize pins
  pinMode(PIN_ACCEPT_BTN, INPUT_PULLUP);
  pinMode(PIN_REJECT_BTN, INPUT_PULLUP);
  pinMode(PIN_UNAVAILABLE_BTN, INPUT_PULLUP);
  pinMode(PIN_SETTINGS_BTN, INPUT_PULLUP);
  pinMode(PIN_LED_RED, OUTPUT);
  pinMode(PIN_LED_GREEN, OUTPUT);
  pinMode(PIN_LED_BLUE, OUTPUT);

  // Initialize preferences (EEPROM)
  preferences.begin("soundbox", false);

  // Get device ID (MAC address)
  deviceId = WiFi.macAddress();
  Serial.print("Device ID: ");
  Serial.println(deviceId);

  // Check if already paired
  apiKey = preferences.getString("api_key", "");

  if (apiKey.length() > 0) {
    Serial.println("Device already paired");
    // Connect to WiFi
    String ssid = preferences.getString("wifi_ssid", "");
    String password = preferences.getString("wifi_pass", "");
    connectToWiFi(ssid.c_str(), password.c_str());
  } else {
    Serial.println("Device not paired - starting setup mode");
    startSetupMode();
  }

  // Initialize DFPlayer
  mp3Serial.begin(9600, SERIAL_8N1, PIN_DFPLAYER_RX, PIN_DFPLAYER_TX);
  if (mp3Player.begin(mp3Serial)) {
    Serial.println("DFPlayer initialized");
    mp3Player.volume(20); // 0-30
  } else {
    Serial.println("DFPlayer initialization failed");
  }

  // Start webhook server
  webhookServer.on("/webhook", HTTP_POST, handleWebhook);
  webhookServer.on("/health", HTTP_GET, handleHealth);
  webhookServer.begin();
  Serial.println("Webhook server started on port " + String(WEBHOOK_PORT));

  // LED indication
  setLED(0, 0, 255); // Blue = ready
}

void loop() {
  webhookServer.handleClient();

  // Send heartbeat
  if (millis() - lastHeartbeat > HEARTBEAT_INTERVAL) {
    sendHeartbeat();
    lastHeartbeat = millis();
  }

  // Check buttons (with debounce)
  if (millis() - lastButtonPress > BUTTON_DEBOUNCE) {
    if (digitalRead(PIN_ACCEPT_BTN) == LOW && currentOrderId > 0) {
      handleAcceptButton();
      lastButtonPress = millis();
    }
    else if (digitalRead(PIN_REJECT_BTN) == LOW && currentOrderId > 0) {
      handleRejectButton();
      lastButtonPress = millis();
    }
    else if (digitalRead(PIN_UNAVAILABLE_BTN) == LOW && currentOrderId > 0) {
      handleUnavailableButton();
      lastButtonPress = millis();
    }
    else if (digitalRead(PIN_SETTINGS_BTN) == LOW) {
      handleSettingsButton();
      lastButtonPress = millis();
    }
  }

  delay(10);
}

// ==================== WiFi Functions ====================

void connectToWiFi(const char* ssid, const char* password) {
  Serial.print("Connecting to WiFi: ");
  Serial.println(ssid);

  WiFi.begin(ssid, password);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi connected!");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
    setLED(0, 255, 0); // Green = connected
  } else {
    Serial.println("\nWiFi connection failed");
    setLED(255, 0, 0); // Red = failed
  }
}

void startSetupMode() {
  Serial.println("Starting WiFi AP for setup...");
  WiFi.softAP(AP_SSID, AP_PASSWORD);
  Serial.print("AP IP: ");
  Serial.println(WiFi.softAPIP());

  // TODO: Start web server for WiFi credentials input
  // For simplicity, hardcode WiFi for now or use Serial input

  setLED(255, 255, 0); // Yellow = setup mode
}

// ==================== Pairing Functions ====================

bool pairDevice(String pairingToken, String wifiSsid, String wifiPass) {
  HTTPClient http;
  String url = serverUrl + "/api/v1/vendor/device/pair";

  http.begin(url);
  http.addHeader("Content-Type", "application/json");

  // Build JSON payload
  DynamicJsonDocument doc(512);
  doc["pairing_token"] = pairingToken;
  doc["device_id"] = deviceId;
  doc["device_name"] = "Sound Box";
  doc["wifi_ssid"] = wifiSsid;
  doc["local_ip"] = WiFi.localIP().toString();
  doc["firmware_version"] = "1.0.0";
  doc["webhook_url"] = "http://" + WiFi.localIP().toString() + ":" + String(WEBHOOK_PORT) + "/webhook";

  String payload;
  serializeJson(doc, payload);

  int httpCode = http.POST(payload);

  if (httpCode == 200) {
    String response = http.getString();
    DynamicJsonDocument responseDoc(1024);
    deserializeJson(responseDoc, response);

    apiKey = responseDoc["device"]["api_key"].as<String>();

    // Save to EEPROM
    preferences.putString("api_key", apiKey);
    preferences.putString("wifi_ssid", wifiSsid);
    preferences.putString("wifi_pass", wifiPass);
    preferences.putInt("store_id", responseDoc["device"]["store_id"]);

    Serial.println("Pairing successful!");
    Serial.print("API Key: ");
    Serial.println(apiKey);

    playSound(1); // Success sound
    setLED(0, 255, 0); // Green
    return true;
  } else {
    Serial.print("Pairing failed. HTTP code: ");
    Serial.println(httpCode);
    playSound(2); // Error sound
    setLED(255, 0, 0); // Red
    return false;
  }

  http.end();
}

// ==================== Heartbeat ====================

void sendHeartbeat() {
  if (apiKey.length() == 0) return;

  HTTPClient http;
  String url = serverUrl + "/api/v1/vendor/device/heartbeat";

  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Key", apiKey);

  DynamicJsonDocument doc(256);
  doc["local_ip"] = WiFi.localIP().toString();
  doc["uptime_seconds"] = millis() / 1000;
  doc["wifi_rssi"] = WiFi.RSSI();

  String payload;
  serializeJson(doc, payload);

  int httpCode = http.POST(payload);

  if (httpCode == 200) {
    Serial.println("Heartbeat sent");
  } else {
    Serial.print("Heartbeat failed: ");
    Serial.println(httpCode);
  }

  http.end();
}

// ==================== Webhook Handler ====================

void handleWebhook() {
  if (!webhookServer.hasArg("plain")) {
    webhookServer.send(400, "application/json", "{\"error\":\"No payload\"}");
    return;
  }

  String body = webhookServer.arg("plain");
  Serial.println("Webhook received:");
  Serial.println(body);

  DynamicJsonDocument doc(4096);
  DeserializationError error = deserializeJson(doc, body);

  if (error) {
    Serial.print("JSON parse error: ");
    Serial.println(error.c_str());
    webhookServer.send(400, "application/json", "{\"error\":\"Invalid JSON\"}");
    return;
  }

  String event = doc["event"];

  if (event == "new_order") {
    handleNewOrderWebhook(doc);
  } else if (event == "test_notification") {
    handleTestNotification(doc);
  }

  webhookServer.send(200, "application/json", "{\"received\":true}");
}

void handleHealth() {
  webhookServer.send(200, "application/json", "{\"status\":\"ok\"}");
}

void handleNewOrderWebhook(JsonDocument& doc) {
  currentOrderId = doc["order"]["order_id"];
  currentOrder.orderId = currentOrderId;
  currentOrder.orderCode = doc["order"]["order_code"].as<String>();
  currentOrder.customerName = doc["order"]["customer_name"].as<String>();
  currentOrder.totalAmount = doc["order"]["total_amount"];
  currentOrder.itemCount = doc["order"]["item_count"];

  Serial.println("New Order: " + currentOrder.orderCode);
  Serial.println("Customer: " + currentOrder.customerName);
  Serial.println("Amount: " + String(currentOrder.totalAmount));

  // Play new order sound
  playSound(3); // Order notification sound

  // Light up buttons
  setLED(0, 0, 255); // Blue = new order

  // Announce order (optional text-to-speech)
  announceOrder();
}

void handleTestNotification(JsonDocument& doc) {
  Serial.println("Test notification received");
  playSound(4); // Test sound
  setLED(255, 255, 255); // White
  delay(1000);
  setLED(0, 0, 255); // Back to blue
}

// ==================== Button Handlers ====================

void handleAcceptButton() {
  Serial.println("Accept button pressed");
  setLED(0, 255, 0); // Green

  HTTPClient http;
  String url = serverUrl + "/api/v1/vendor/order/quick-accept";

  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Key", apiKey);

  DynamicJsonDocument doc(128);
  doc["order_id"] = currentOrderId;

  String payload;
  serializeJson(doc, payload);

  int httpCode = http.POST(payload);

  if (httpCode == 200) {
    Serial.println("Order accepted successfully");
    playSound(5); // Success sound
    currentOrderId = 0; // Clear order
    delay(2000);
    setLED(0, 0, 255); // Back to ready
  } else {
    Serial.print("Accept failed: ");
    Serial.println(httpCode);
    playSound(2); // Error sound
    setLED(255, 0, 0); // Red
  }

  http.end();
}

void handleRejectButton() {
  Serial.println("Reject button pressed");
  setLED(255, 0, 0); // Red

  HTTPClient http;
  String url = serverUrl + "/api/v1/vendor/order/quick-reject";

  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Key", apiKey);

  DynamicJsonDocument doc(256);
  doc["order_id"] = currentOrderId;
  doc["reason"] = "Rejected via sound box";

  String payload;
  serializeJson(doc, payload);

  int httpCode = http.POST(payload);

  if (httpCode == 200) {
    Serial.println("Order rejected successfully");
    playSound(6); // Reject sound
    currentOrderId = 0; // Clear order
    delay(2000);
    setLED(0, 0, 255); // Back to ready
  } else {
    Serial.print("Reject failed: ");
    Serial.println(httpCode);
    playSound(2); // Error sound
  }

  http.end();
}

void handleUnavailableButton() {
  Serial.println("Unavailable button pressed");
  // TODO: Implement item selection UI for marking specific items unavailable
  // For now, this could open a simple menu on LCD display

  setLED(255, 165, 0); // Orange
  playSound(7); // Unavailable sound
}

void handleSettingsButton() {
  Serial.println("Settings button pressed");
  // TODO: Implement settings menu (volume, brightness, etc.)

  setLED(255, 255, 255); // White
  playSound(8); // Menu sound
}

// ==================== Audio Functions ====================

void playSound(int trackNumber) {
  if (mp3Player.available()) {
    mp3Player.play(trackNumber);
  }
}

void announceOrder() {
  // Play audio files in sequence:
  // 1. "New order from"
  // 2. Customer name (pre-recorded or TTS)
  // 3. Item count
  // 4. Total amount

  // Example: Play track 10 (new order announcement)
  playSound(10);
}

// ==================== LED Functions ====================

void setLED(int red, int green, int blue) {
  analogWrite(PIN_LED_RED, red);
  analogWrite(PIN_LED_GREEN, green);
  analogWrite(PIN_LED_BLUE, blue);
}

// ==================== Utility Functions ====================

void printMemoryUsage() {
  Serial.print("Free heap: ");
  Serial.println(ESP.getFreeHeap());
}
```

---

## Audio Files for microSD Card

Create these MP3 files and place in microSD card root:

```
0001.mp3 - Success sound (ding)
0002.mp3 - Error sound (buzzer)
0003.mp3 - New order notification (chime)
0004.mp3 - Test notification
0005.mp3 - Order accepted (positive tone)
0006.mp3 - Order rejected (negative tone)
0007.mp3 - Item unavailable (alert)
0008.mp3 - Menu open sound
0009.mp3 - Button press sound
0010.mp3 - "New order from..." (voice)
```

---

## Pairing Flow (Complete)

### Step 1: Vendor App Generates QR Code

```dart
// Flutter code
final response = await post(
  'https://new.snocart.com/api/v1/vendor/device-management/generate-pairing-qr',
  headers: {'Authorization': 'Bearer $token'},
  body: {'store_id': storeId},
);

final qrData = response.data['qr_data'];
// Display QR code to scan
```

### Step 2: ESP32 Scans QR Code

```cpp
// Parse QR data: "SNOCART:PAIR:PT-ABC123:https://new.snocart.com"
String qrData = scanQRCode(); // From QR scanner module
String[] parts = split(qrData, ':');
String pairingToken = parts[2];
String serverUrl = parts[3];

// Pair device
bool success = pairDevice(pairingToken, wifiSsid, wifiPassword);
```

### Step 3: ESP32 Saves Credentials

```cpp
preferences.putString("api_key", apiKey);
preferences.putString("wifi_ssid", wifiSsid);
preferences.putString("wifi_pass", wifiPassword);
```

---

## Testing Commands

### 1. Test Pairing (via Serial Monitor)

```cpp
void loop() {
  if (Serial.available()) {
    String command = Serial.readStringUntil('\n');

    if (command.startsWith("PAIR:")) {
      // Format: PAIR:token:ssid:password
      // Example: PAIR:PT-ABC123:MyWiFi:password123
      // Parse and call pairDevice()
    }
  }
}
```

### 2. Test Webhook Locally

```bash
curl -X POST http://192.168.1.100:8080/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "event": "new_order",
    "order": {
      "order_id": 100123,
      "order_code": "#ORD-100123",
      "customer_name": "Test Customer",
      "total_amount": 450.00,
      "item_count": 3
    }
  }'
```

### 3. Monitor Serial Output

```
Arduino IDE → Tools → Serial Monitor (115200 baud)

Output:
  Snocart Sound Box v1.0.0
  Device ID: AA:BB:CC:DD:EE:FF
  WiFi connected!
  IP address: 192.168.1.100
  Webhook server started on port 8080
  Heartbeat sent
  Webhook received: {...}
  New Order: #ORD-100123
  Accept button pressed
  Order accepted successfully
```

---

## Advanced Features

### 1. LCD Display Integration

```cpp
#include <LiquidCrystal_I2C.h>
LiquidCrystal_I2C lcd(0x27, 16, 2);

void displayOrderInfo() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(currentOrder.orderCode);
  lcd.setCursor(0, 1);
  lcd.print("Rs." + String(currentOrder.totalAmount));
}
```

### 2. Item Selection for Unavailable

```cpp
int selectedItemIndex = 0;

void handleUnavailableButton() {
  // Show items list on LCD
  // Use rotary encoder or up/down buttons to navigate
  // Mark selected items as unavailable

  JsonArray unavailableItems;
  DynamicJsonDocument doc(512);
  JsonArray items = doc.createNestedArray("unavailable_items");

  JsonObject item1 = items.createNestedObject();
  item1["order_detail_id"] = currentOrder.items[selectedItemIndex]["order_detail_id"];
  item1["note"] = "Out of stock";

  // Send partial-accept request
}
```

### 3. Volume Control with Rotary Encoder

```cpp
#define PIN_ENCODER_CLK 18
#define PIN_ENCODER_DT 19

int volumeLevel = 20;

void readEncoder() {
  // Read encoder rotation
  // Adjust volume
  mp3Player.volume(volumeLevel);
}
```

---

## Troubleshooting

### ESP32 Not Connecting to WiFi

- Check SSID and password
- Verify WiFi is 2.4GHz (ESP32 doesn't support 5GHz)
- Check signal strength (must be > -70 dBm)

### Webhooks Not Received

- Verify webhook URL is accessible: `curl http://ESP_IP:8080/health`
- Check firewall rules
- Ensure ESP32 and server on same network (or port forwarding configured)

### Audio Not Playing

- Check DFPlayer wiring
- Verify microSD card is FAT32 formatted
- Audio files must be named 0001.mp3, 0002.mp3, etc.

### Buttons Not Responding

- Check pull-up resistors
- Add debounce delay
- Verify GPIO pins not used by other functions

---

## Power Consumption

- **Idle**: ~80mA @ 5V (0.4W)
- **WiFi Active**: ~150mA @ 5V (0.75W)
- **Audio Playing**: ~200mA @ 5V (1.0W)
- **Recommended PSU**: 5V, 2A (10W)

For battery operation:
- Use 18650 Li-ion batteries (2x in series = 7.4V)
- Add voltage regulator (LM2596 DC-DC buck converter to 5V)
- Battery life: ~8-10 hours with 3000mAh batteries

---

## Bill of Materials (BOM)

| Component | Quantity | Price (₹) |
|-----------|----------|-----------|
| ESP32 DevKit | 1 | ₹400 |
| DFPlayer Mini | 1 | ₹150 |
| Speaker 3W 8Ω | 1 | ₹50 |
| Push Buttons | 4 | ₹20 |
| RGB LED | 1 | ₹10 |
| Resistors 220Ω | 3 | ₹5 |
| microSD Card 4GB | 1 | ₹200 |
| Jumper Wires | 20 | ₹50 |
| Breadboard | 1 | ₹100 |
| Power Adapter 5V 2A | 1 | ₹150 |
| **Total** | | **₹1,135** |

**Optional:**
- LCD Display 16x2 I2C: ₹250
- QR Scanner Module: ₹800
- Rotary Encoder: ₹50
- Enclosure Box: ₹200

---

## Next Steps

1. ✅ Flash Arduino code to ESP32
2. ✅ Connect hardware components
3. ✅ Test pairing via Serial Monitor
4. ✅ Test webhook reception
5. ✅ Test order actions (accept/reject)
6. ✅ Add LCD display for order details
7. ✅ Integrate QR scanner for easy pairing
8. ✅ Design and 3D print enclosure

---

## Support

For issues or questions:
- Check Serial Monitor output for errors
- Review logs: `storage/logs/laravel-*.log | grep soundbox`
- Test API endpoints with Postman
- Verify device is active: `SELECT * FROM vendor_sound_devices WHERE device_id='...'`

**Happy Building! 🔊📦**
