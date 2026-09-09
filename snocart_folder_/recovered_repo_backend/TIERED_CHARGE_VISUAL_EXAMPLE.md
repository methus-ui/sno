# Visual Example: Tiered Additional Charge

## Current System (What You Asked For)

```
┌─────────────────────────────────────────────────────┐
│                 ADMIN PANEL                          │
│  Business Settings → Additional Charge              │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ☑ Additional Charge (Enabled)                     │
│  ☑ Tiered Additional Charges (Enabled)             │
│                                                     │
│  Tier 1:  ₹0 - ₹499    →  Charge: ₹25             │
│  Tier 2:  ₹500 - ₹999  →  Charge: ₹30             │
│  Tier 3:  ₹1000+       →  Charge: ₹35             │
│                                                     │
│           [ Submit ]                                │
└─────────────────────────────────────────────────────┘
```

## How It Works (Backend)

```
Customer places order for ₹750

┌──────────────────────────┐
│   Order Calculation      │
├──────────────────────────┤
│ Items:        ₹750       │
│ Delivery:     ₹40        │
│ Tax:          ₹15        │
│ Fee:          ₹30  ← Tier 2 (₹500-999)
├──────────────────────────┤
│ Total:        ₹835       │
└──────────────────────────┘

✅ Backend: Calculates ₹30 (correct)
```

## Problem: Customer App Not Updated

```
Customer's phone sees:

┌──────────────────────────┐
│   Shopping Cart          │
├──────────────────────────┤
│ Items:        ₹750       │
│ Delivery:     ₹40        │
│ Tax:          ₹15        │
│ Fee:          ₹20  ← OLD FLAT CHARGE ❌
├──────────────────────────┤
│ Total:        ₹825       │
└──────────────────────────┘

Customer clicks "Place Order"

Backend processes:
┌──────────────────────────┐
│   Order Processed        │
├──────────────────────────┤
│ Items:        ₹750       │
│ Delivery:     ₹40        │
│ Tax:          ₹15        │
│ Fee:          ₹30  ← TIER 2 CHARGE ✓
├──────────────────────────┤
│ Total:        ₹835       │
└──────────────────────────┘

Customer expected: ₹825
Customer charged:  ₹835
Difference:        ₹10 MORE ❌

😠 Angry customer calls: "You charged me more than shown!"
```

## Solution: Update Customer App

```
Updated app calculates dynamically:

┌──────────────────────────┐
│   Shopping Cart          │
├──────────────────────────┤
│ Items:        ₹750       │
│ Delivery:     ₹40        │
│ Tax:          ₹15        │
│ Fee:          ₹30  ← TIER 2 CHARGE ✓
├──────────────────────────┤
│ Total:        ₹835       │
└──────────────────────────┘

Customer clicks "Place Order"

Backend processes:
┌──────────────────────────┐
│   Order Processed        │
├──────────────────────────┤
│ Items:        ₹750       │
│ Delivery:     ₹40        │
│ Tax:          ₹15        │
│ Fee:          ₹30  ← TIER 2 CHARGE ✓
├──────────────────────────┤
│ Total:        ₹835       │
└──────────────────────────┘

Customer expected: ₹835
Customer charged:  ₹835
Difference:        ₹0 ✓

😊 Happy customer!
```

## API Response Example

### Old Config Response (Customer App Uses This):
```json
{
  "additional_charge_status": 1,
  "additional_charge": 20
}
```
App shows: ₹20 (wrong)

### New Config Response (Customer App Needs to Use This):
```json
{
  "additional_charge_status": 1,
  "additional_charge": 20,
  "additional_charge_tiered_enabled": 1,
  "additional_charge_tiers": [
    {"min_amount": 0, "max_amount": 499.99, "charge": 25},
    {"min_amount": 500, "max_amount": 999.99, "charge": 30},
    {"min_amount": 1000, "max_amount": null, "charge": 35}
  ]
}
```
App calculates: ₹30 for ₹750 cart (correct)

## Code Example (What Customer App Needs)

```dart
// OLD CODE (Shows wrong fee):
double fee = config.additionalCharge; // Always ₹20

// NEW CODE (Shows correct fee):
double fee = calculateTieredFee(cartTotal, config.tiers);
```

```dart
// Implementation:
double calculateTieredFee(double total, List<Tier> tiers) {
  for (var tier in tiers) {
    if (total >= tier.minAmount && 
        (tier.maxAmount == null || total <= tier.maxAmount)) {
      return tier.charge;
    }
  }
  return 0;
}
```

## Real-World Examples

### Example 1: Small Order
```
Cart: 2 items, ₹350
Fee Shown: ₹20 (old) vs ₹25 (correct)
Difference: Customer charged ₹5 MORE than expected
```

### Example 2: Medium Order
```
Cart: 5 items, ₹700
Fee Shown: ₹20 (old) vs ₹30 (correct)
Difference: Customer charged ₹10 MORE than expected
```

### Example 3: Large Order
```
Cart: 10 items, ₹1500
Fee Shown: ₹20 (old) vs ₹35 (correct)
Difference: Customer charged ₹15 MORE than expected
```

## Quick Fix Options

### Option A: Disable Tiers (5 minutes)
```
Admin Panel → Business Settings
☐ Tiered Additional Charges (Disable)
[Submit]

Result: All orders use flat ₹20 fee
Impact: Lose dynamic pricing
```

### Option B: Update Customer App (2-3 hours)
```
1. Parse tier data from config API
2. Add tier calculation logic
3. Update cart UI to show calculated fee
4. Test and deploy

Result: Customers see correct fees
Impact: Enable dynamic pricing
```

---

**RECOMMENDATION:** Choose Option A for immediate fix, then implement Option B this week.
