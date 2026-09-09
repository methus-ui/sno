# Tiered Additional Charge - Quick Start Guide

## 🚀 Implementation Complete!

Your tiered additional charge system is now live and ready to use. Here's how to enable it:

## ⚡ Quick Enable (2 Minutes)

### Step 1: Access Business Settings
1. Login to **Admin Panel**
2. Go to **Settings** → **Business Settings**
3. Click on **Business** tab
4. Scroll down to **Additional Charge** section

### Step 2: Enable Additional Charge
1. Toggle **ON** the "Additional Charge" switch
2. Set **Charge Name**: `Convenience Fee` (or your preferred name)
3. Set **Flat Charge Amount**: `25` (fallback when tiered is off)

### Step 3: Enable Tiered System
1. Toggle **ON** the "Tiered Additional Charges" switch
2. You'll see a card with tier configuration

### Step 4: Verify Default Tiers
The system comes pre-configured with these tiers (as you requested):

| Order Amount | Charge |
|-------------|--------|
| Under ₹500 | ₹25 |
| ₹500 - ₹999 | ₹30 |
| ₹1000+ | ₹35 |

### Step 5: Save
1. Click **Submit** button at the bottom
2. Done! ✅

## 📱 Testing

### Test Order 1: ₹400 (Should charge ₹25)
- Place order with items totaling ₹400
- Expected additional charge: **₹25**

### Test Order 2: ₹750 (Should charge ₹30)
- Place order with items totaling ₹750
- Expected additional charge: **₹30**

### Test Order 3: ₹1500 (Should charge ₹35)
- Place order with items totaling ₹1500
- Expected additional charge: **₹35**

## 🔧 Customize Tiers (Optional)

Want different amounts? Easy!

1. In the **Tiered Additional Charges** card:
   - **Edit existing tier**: Change Min/Max/Charge values
   - **Add new tier**: Click "Add Tier" button
   - **Remove tier**: Click red "Remove" button (first tier can't be removed)

2. **Example: Add a 4th tier for large orders**
   - Min: `2000`
   - Max: *(leave blank)*
   - Charge: `40`

3. Click **Submit** to save

## 🎯 Current Status

✅ **Database Migration:** Complete
✅ **Backend Logic:** Implemented
✅ **Admin UI:** Ready
✅ **API Integration:** Working
✅ **Testing:** All tests passed

## 🔍 Verify Installation

Run this command to verify everything works:

```bash
php scripts/test-tiered-additional-charge.php
```

Expected output:
```
✅ PASS: Tiered charge settings exist
✅ PASS: Tier data is valid JSON array
✅ PASS: All tiered calculations correct!
```

## 💡 How It Works

1. **Customer places order** → Order total calculated
2. **System checks tiers** → Finds matching range
3. **Applies correct charge** → Adds to order total
4. **Customer pays** → Sees charge in breakdown

**No app updates needed!** Everything is server-side.

## 📊 Order Breakdown Example

**Order Details:**
- Items: ₹500
- Delivery: ₹40
- **Convenience Fee: ₹30** ← (Tiered charge for ₹500 order)
- **Total: ₹570**

## 🛡️ Safety Features

- ✅ Backward compatible (won't break existing orders)
- ✅ Can disable anytime (instant rollback)
- ✅ Falls back to flat charge if issues occur
- ✅ No frontend changes (works with all apps)

## 🔄 To Disable

**Option 1: Keep flat charge (disable tiers only)**
- Turn OFF "Tiered Additional Charges" toggle
- All orders use the flat ₹25 charge

**Option 2: Disable all charges**
- Turn OFF "Additional Charge" toggle
- No additional charges applied

## 📞 Support

If you see any issues:
1. Check Admin Panel → Business Settings
2. Run test script: `php scripts/test-tiered-additional-charge.php`
3. Check logs: `storage/logs/laravel.log`

## 📚 Full Documentation

For detailed information, see: `TIERED_ADDITIONAL_CHARGE_IMPLEMENTATION.md`

---

**That's it!** Your tiered charge system is ready to use. Enable it now and start applying dynamic charges based on order amounts.
