# Simple Fix Plan - No Transaction Sync

## ONLY Fix These 3 Things:

### 1. Remove lines that modify master prices
- Line 640: `$item->price = $request->mrp;`
- Line 641: `$item->save();`
- Line 696: `$campaign->price = $request->mrp;`
- Line 697: `$campaign->save();`

### 2. Fix remove_from_cart to be simpler
- Just accept order_id and key
- Delete from database
- Recalculate total manually

### 3. Comment out old edit() method
- Lines 2358-2418

### 4. Add simple helper at end
```php
private function recalculateOrderTotals(Order $order)
{
    $total = OrderDetail::where('order_id', $order->id)
        ->sum(\DB::raw('price * quantity'));
    $order->order_amount = $total;
    $order->save();
    return $order;
}
```

## NO TRANSACTION SYNC
## NO COMPLEX VALIDATION
## KEEP IT SIMPLE
