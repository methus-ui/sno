# Admin Store Prepayment - Quick Start Guide

**Feature:** Mark COD orders as prepaid when store has already paid item amount to platform.

---

## What Is This?

When a store pays you (admin) in advance for items, you can mark their COD orders as "prepaid". This tells delivery men to only collect **delivery fees** from customers, not the full order amount.

---

## When to Use This?

✅ Store has paid you for items in advance (via UPI, bank transfer, etc.)
✅ Order is COD (cash on delivery)
✅ Order is NOT yet delivered

❌ Don't use for digital payment orders
❌ Don't use after order is delivered

---

## How to Use

### Method 1: From Order View Page

1. Open any COD order (not delivered)
2. Scroll down to **payment information section**
3. Look for **"Store Prepayment"** card (orange box)
4. Click **"Mark Item Amount Paid"** button
5. Modal opens showing:
   - **Total Order Amount:** ₹500
   - **Item Amount (Store Receives):** ₹450 (green)
   - **Delivery Fees (DM Collects):** ₹50 (orange)
6. Add notes (optional): "Store paid via UPI on 9-Mar"
7. Click **"Confirm Prepayment"**
8. Page reloads → Green success card appears

### Method 2: From Edit V2 Page

1. Open order in Edit V2 mode
2. Look at **top bar** (dark header)
3. Click **"Mark Prepaid"** button (orange)
4. Same modal as Method 1
5. Confirm → Badge changes to **"Prepaid ✓"** (green)

---

## What Happens After Marking?

### Before Delivery:
- Order shows **green "Prepaid" badge**
- Prepayment details visible to all admins
- DM hasn't collected money yet

### After Delivery:
- DM collects **₹50** from customer (delivery fees only)
- DM's wallet: `collected_cash += 50` (NOT 500)
- Store keeps **₹450** (already paid to you)

---

## Example Scenario

**Order #12345:**
- Items: ₹450
- Delivery Charge: ₹40
- DM Tips: ₹10
- **Total: ₹500**

**Store owner calls you:**
> "I just paid you ₹450 via UPI for my items. Please mark my orders as prepaid."

**You do:**
1. Verify ₹450 received in bank
2. Mark Order #12345 as prepaid
3. DM delivers order
4. DM collects **₹50** from customer (not ₹500)
5. You keep **₹450** (already in your account)

---

## How to Reverse (Super Admin Only)

If you marked wrong order by mistake:

1. Open the prepaid order
2. Click **"Reverse Prepayment"** button (red)
3. Enter reason: "Marked wrong order by mistake"
4. Confirm
5. All prepaid fields cleared
6. Order back to normal COD

**Note:** Can only reverse BEFORE delivery.

---

## Visual Guide

### Order View - Before Prepayment
```
┌────────────────────────────────────────┐
│ Store Prepayment                       │
│ ⚠️ Mark if store has already paid...   │
│                                        │
│ [Mark Item Amount Paid]                │
└────────────────────────────────────────┘
```

### Order View - After Prepayment
```
┌────────────────────────────────────────┐
│ ✓ Store Amount Prepaid                 │
│ Item Amount: ₹450.00                   │
│ Delivery Fees: ₹50.00                  │
│ Notes: Store paid via UPI on 9-Mar     │
│ Marked by: Admin Name | 9 Mar, 2:30 PM│
│                                        │
│ [Reverse Prepayment] (super admin)     │
└────────────────────────────────────────┘
```

### Edit V2 Topbar
```
Before: [Bill QR] <spacer> [Save]
After:  [Bill QR] [Mark Prepaid] <spacer> [Save]
        OR [Prepaid ✓] if already marked
```

---

## FAQ

### Q: Can I mark digital payment orders?
**A:** No, only COD orders. Digital payments already go to you directly.

### Q: Can I mark after delivery?
**A:** No, only BEFORE delivery. This prevents wallet calculation issues.

### Q: What if I mark wrong order?
**A:** Super admin can reverse it (before delivery). Click "Reverse Prepayment" button.

### Q: Will DM see this in their app?
**A:** Not yet (coming in next update). DM will see orange "Prepaid" badge on order card showing exact amount to collect.

### Q: Can I mark multiple orders at once?
**A:** Not yet (feature coming soon). For now, mark one by one.

### Q: Where can I see all prepaid orders?
**A:** Use order filters → Add filter for "prepaid orders" (coming in next update). For now, look for green "Prepaid ✓" badge on order view pages.

### Q: What if order has outside purchase items?
**A:** Feature works fine. DM's cash already deducts outside purchase cost separately.

### Q: Can vendor employees mark prepaid?
**A:** No, only admin employees. Vendors cannot access this feature.

---

## Troubleshooting

### Button not showing?
- ✅ Check order is COD
- ✅ Check order not delivered yet
- ✅ Refresh page (Ctrl+F5)
- ✅ Clear browser cache

### Modal not opening?
- ✅ Check browser console for errors
- ✅ Disable ad blockers
- ✅ Try different browser

### DM still collecting full amount?
- ✅ Check order marked BEFORE delivery
- ✅ Check green "Prepaid" badge visible
- ✅ Contact support if issue persists

---

## Tips

1. **Always verify payment first** before marking prepaid
2. **Add notes** to remember which UPI/bank transaction it was
3. **Mark before delivery** to ensure DM collects correct amount
4. **Super admins only** can reverse if needed
5. **Check twice** before confirming (no undo for regular admins)

---

## Technical Details (For Developers)

- **Database:** 6 new columns in `orders` table
- **Routes:** 3 new AJAX endpoints
- **Logic:** Modified `order.php` line 342 for cash collection
- **Testing:** All 10 automated tests passed
- **Documentation:** Full technical docs in `ADMIN_STORE_PREPAYMENT_IMPLEMENTATION.md`

---

## Support

**Issues?** Contact technical support with:
- Order ID
- Screenshot of error
- Browser console logs (F12 → Console tab)

---

**Feature Version:** 1.0
**Release Date:** 2026-03-09
**Status:** ✅ Production Ready
