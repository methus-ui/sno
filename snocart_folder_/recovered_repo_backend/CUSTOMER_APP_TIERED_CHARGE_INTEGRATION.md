# Customer App Integration Guide: Tiered Additional Charge

**Date:** 2026-03-28
**Status:** ✅ READY FOR IMPLEMENTATION

## Overview

The backend now supports **dynamic tiered additional charges** that vary based on cart total. Customer apps need to be updated to fetch tier configuration and calculate charges dynamically.

---

## What Changed

### Before (Flat Charge)
```
Cart Total: ₹500
Additional Charge: ₹20 (always fixed)
Final Total: ₹520
```

### After (Tiered Charge)
```
Cart Total: ₹500
Additional Charge: ₹30 (tier 2: ₹500-999)
Final Total: ₹530

Cart Total: ₹1200
Additional Charge: ₹35 (tier 3: ₹1000+)
Final Total: ₹1235
```

---

## API Changes

### Config Endpoint: `GET /api/v1/config`

**New Fields Added:**

```json
{
  "additional_charge_status": 1,
  "additional_charge_name": "Convenience Fee",
  "additional_charge": 20,
  "additional_charge_tiered_enabled": 1,
  "additional_charge_tiers": [
    {
      "min_amount": 0,
      "max_amount": 499.99,
      "charge": 25
    },
    {
      "min_amount": 500,
      "max_amount": 999.99,
      "charge": 30
    },
    {
      "min_amount": 1000,
      "max_amount": null,
      "charge": 35
    }
  ]
}
```

**Field Descriptions:**

| Field | Type | Description |
|-------|------|-------------|
| `additional_charge_status` | int | 0=disabled, 1=enabled |
| `additional_charge_tiered_enabled` | int | 0=use flat charge, 1=use tiers |
| `additional_charge` | float | Fallback flat charge (when tiers disabled) |
| `additional_charge_tiers` | array | Tier configuration (when tiers enabled) |

**Tier Object:**

| Field | Type | Description |
|-------|------|-------------|
| `min_amount` | float | Minimum cart total for this tier |
| `max_amount` | float/null | Maximum cart total (null = no limit) |
| `charge` | float | Fee amount for this tier |

---

## Implementation Guide

### Step 1: Update Config Model/State

**Flutter (Provider/Riverpod):**

```dart
class AppConfig {
  final bool additionalChargeStatus;
  final String additionalChargeName;
  final double additionalCharge; // Fallback flat charge
  final bool additionalChargeTieredEnabled;
  final List<ChargeTier>? additionalChargeTiers;

  AppConfig.fromJson(Map<String, dynamic> json)
    : additionalChargeStatus = json['additional_charge_status'] == 1,
      additionalChargeName = json['additional_charge_name'] ?? 'Service Charge',
      additionalCharge = (json['additional_charge'] ?? 0).toDouble(),
      additionalChargeTieredEnabled = json['additional_charge_tiered_enabled'] == 1,
      additionalChargeTiers = json['additional_charge_tiers'] != null
          ? (json['additional_charge_tiers'] as List)
              .map((t) => ChargeTier.fromJson(t))
              .toList()
          : null;
}

class ChargeTier {
  final double minAmount;
  final double? maxAmount;
  final double charge;

  ChargeTier.fromJson(Map<String, dynamic> json)
    : minAmount = (json['min_amount']).toDouble(),
      maxAmount = json['max_amount'] != null ? (json['max_amount']).toDouble() : null,
      charge = (json['charge']).toDouble();
}
```

**React Native:**

```typescript
interface ChargeTier {
  min_amount: number;
  max_amount: number | null;
  charge: number;
}

interface AppConfig {
  additional_charge_status: number;
  additional_charge_name: string;
  additional_charge: number;
  additional_charge_tiered_enabled: number;
  additional_charge_tiers?: ChargeTier[];
}
```

---

### Step 2: Calculate Additional Charge

**Flutter:**

```dart
class CartCalculator {
  static double calculateAdditionalCharge(double cartTotal, AppConfig config) {
    // Check if additional charge is enabled
    if (!config.additionalChargeStatus) {
      return 0.0;
    }

    // Check if tiered system is enabled
    if (!config.additionalChargeTieredEnabled || config.additionalChargeTiers == null) {
      return config.additionalCharge; // Use flat charge
    }

    // Find matching tier
    for (var tier in config.additionalChargeTiers!) {
      if (cartTotal >= tier.minAmount) {
        if (tier.maxAmount == null || cartTotal <= tier.maxAmount!) {
          return tier.charge;
        }
      }
    }

    return 0.0; // No tier matched (shouldn't happen if tiers configured correctly)
  }
}
```

**React Native:**

```typescript
export const calculateAdditionalCharge = (
  cartTotal: number,
  config: AppConfig
): number => {
  // Check if additional charge is enabled
  if (config.additional_charge_status !== 1) {
    return 0;
  }

  // Check if tiered system is enabled
  if (
    config.additional_charge_tiered_enabled !== 1 ||
    !config.additional_charge_tiers
  ) {
    return config.additional_charge; // Use flat charge
  }

  // Find matching tier
  for (const tier of config.additional_charge_tiers) {
    if (cartTotal >= tier.min_amount) {
      if (tier.max_amount === null || cartTotal <= tier.max_amount) {
        return tier.charge;
      }
    }
  }

  return 0; // No tier matched
};
```

---

### Step 3: Update Cart UI

**Before:**
```dart
// Old code (flat charge)
double additionalCharge = config.additionalCharge;
```

**After:**
```dart
// New code (dynamic charge)
double cartTotal = calculateCartSubtotal();
double additionalCharge = CartCalculator.calculateAdditionalCharge(
  cartTotal,
  config
);
```

**Display in Cart:**

```dart
Text('Items: ₹${cartTotal.toStringAsFixed(2)}'),
Text('Delivery: ₹${deliveryCharge.toStringAsFixed(2)}'),
if (additionalCharge > 0)
  Text('${config.additionalChargeName}: ₹${additionalCharge.toStringAsFixed(2)}'),
Divider(),
Text('Total: ₹${(cartTotal + deliveryCharge + additionalCharge).toStringAsFixed(2)}'),
```

---

### Step 4: Update Checkout Flow

**Order Summary:**

```dart
OrderSummary(
  items: cartItems,
  subtotal: cartTotal,
  deliveryCharge: deliveryCharge,
  additionalCharge: CartCalculator.calculateAdditionalCharge(cartTotal, config),
  additionalChargeName: config.additionalChargeName,
  discount: discountAmount,
  tax: taxAmount,
  finalTotal: calculateFinalTotal(),
)
```

---

## Testing Checklist

### ✅ Config Loading
- [ ] App fetches config on launch
- [ ] `additional_charge_tiered_enabled` field parsed correctly
- [ ] `additional_charge_tiers` array parsed correctly
- [ ] Fallback to flat charge when tiers not available

### ✅ Cart Calculation
- [ ] Cart total ₹250 → Shows ₹25 fee
- [ ] Cart total ₹499 → Shows ₹25 fee
- [ ] Cart total ₹500 → Shows ₹30 fee
- [ ] Cart total ₹999 → Shows ₹30 fee
- [ ] Cart total ₹1000 → Shows ₹35 fee
- [ ] Cart total ₹2000 → Shows ₹35 fee

### ✅ Dynamic Updates
- [ ] Fee updates when items added/removed from cart
- [ ] Fee updates when item quantity changes
- [ ] Fee updates when discount applied
- [ ] Fee displays in cart summary
- [ ] Fee displays in checkout summary
- [ ] Fee displays in order confirmation

### ✅ Edge Cases
- [ ] Handles null max_amount (unlimited tier)
- [ ] Handles empty cart (₹0 total)
- [ ] Handles disabled additional charge
- [ ] Handles missing tier configuration

---

## Example Test Cases

### Test Case 1: Low-Value Cart
```
Cart Total: ₹350
Expected Fee: ₹25 (Tier 1: ₹0-499)
Total: ₹350 + ₹40 (delivery) + ₹25 (fee) = ₹415
```

### Test Case 2: Mid-Value Cart
```
Cart Total: ₹750
Expected Fee: ₹30 (Tier 2: ₹500-999)
Total: ₹750 + ₹40 (delivery) + ₹30 (fee) = ₹820
```

### Test Case 3: High-Value Cart
```
Cart Total: ₹1500
Expected Fee: ₹35 (Tier 3: ₹1000+)
Total: ₹1500 + ₹40 (delivery) + ₹35 (fee) = ₹1575
```

### Test Case 4: Boundary Testing
```
Cart Total: ₹499.99 → Fee: ₹25 (Tier 1 max)
Cart Total: ₹500.00 → Fee: ₹30 (Tier 2 min)
Cart Total: ₹999.99 → Fee: ₹30 (Tier 2 max)
Cart Total: ₹1000.00 → Fee: ₹35 (Tier 3 min)
```

---

## Backward Compatibility

✅ **Fully Backward Compatible**

If customer app is not updated:
- Old apps will use `additional_charge` (flat fee)
- Old apps will ignore `additional_charge_tiers` (unknown field)
- Orders will still process correctly with flat charge

When customer app is updated:
- New apps will check `additional_charge_tiered_enabled`
- If enabled, use tiers
- If disabled, use flat charge
- Smooth transition - no breaking changes

---

## API Endpoints Reference

### Get Config
```
GET /api/v1/config
Headers: None required
Response: Config object with tier data
```

### Place Order
```
POST /api/v1/customer/order/place
Headers: Authorization: Bearer <token>
Body: {
  "cart": [...],
  "delivery_charge": 40,
  "payment_method": "cash_on_delivery",
  ...
}

Note: Backend automatically calculates tiered charge
      based on order amount. Customer app just displays it.
```

---

## FAQ

**Q: Do I need to send the additional_charge value when placing orders?**
A: No. The backend calculates it automatically based on order amount and tier configuration.

**Q: What if tiers are changed while user is in checkout?**
A: Backend uses the current tier configuration at order placement time. Customer app should refresh config periodically.

**Q: What if tier calculation fails?**
A: Backend will fallback to flat `additional_charge` value. Always safe.

**Q: Can admin change tiers after orders are placed?**
A: Yes, but it only affects NEW orders. Existing orders keep their original charge.

---

## Support

If you encounter issues:
1. Check API response format matches this guide
2. Verify tier calculation logic in your app
3. Test with the example values provided
4. Contact backend team if API response is incorrect

---

## Summary

**What to implement:**
1. Parse new config fields (`additional_charge_tiered_enabled`, `additional_charge_tiers`)
2. Add tier calculation logic to cart
3. Display calculated fee dynamically
4. Test with all tier boundaries

**Estimated effort:** 2-3 hours
**Risk level:** Low (backward compatible)
**Testing priority:** High (affects order totals)

✅ Backend is ready and tested
🔧 Customer app needs updates
📱 No API breaking changes
