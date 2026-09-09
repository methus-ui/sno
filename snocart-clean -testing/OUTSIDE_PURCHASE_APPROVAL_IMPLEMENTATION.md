# Outside Purchase Approval System - Implementation Guide

## Overview
This implementation adds an admin approval workflow for outside purchase requests initiated by delivery men.

## Changes Made

### 1. Database Migration
**File:** `database/migrations/2026_02_10_100000_add_outside_purchase_approval_to_order_details_table.php`

Adds the following columns to `order_details` table:
- `outside_purchase_status` - Status of the request (null, 'pending', 'approved', 'rejected')
- `outside_purchase_requested_by` - Who initiated the request ('deliveryman' or 'admin')
- `outside_purchase_approved_by` - Admin ID who approved/rejected
- `outside_purchase_approved_at` - Timestamp of approval/rejection
- `outside_purchase_rejection_reason` - Optional reason for rejection

**To apply migration:**
```bash
php artisan migrate
```

### 2. Delivery Man API Changes
**File:** `app/Http/Controllers/Api/V1/DeliverymanController.php`

**Method:** `mark_outside_purchase()`

**Changes:**
- Now creates a **pending request** instead of directly marking as outside purchase
- Sets `is_outside_purchase = false` (will be true after admin approval)
- Sets `outside_purchase_status = 'pending'`
- Sets `outside_purchase_requested_by = 'deliveryman'`
- Returns status 'pending' in response

**API Response:**
```json
{
    "message": "Outside purchase request submitted",
    "status": "pending"
}
```

### 3. Admin Controller Changes
**File:** `app/Http/Controllers/Admin/OrderController.php`

#### Updated Method: `mark_outside_purchase()`
Admin can still directly mark items as outside purchase (auto-approved):
- Sets `outside_purchase_status = 'approved'`
- Sets `outside_purchase_requested_by = 'admin'`
- Records admin ID and timestamp

#### New Method: `approve_outside_purchase()`
**Route:** `POST /admin/order/approve-outside-purchase`
**Parameters:** `order_detail_id`

**Functionality:**
- Validates request is in 'pending' status
- Sets `is_outside_purchase = true`
- Sets `outside_purchase_status = 'approved'`
- Records admin ID and approval timestamp
- Recalculates order's total outside purchase amount
- Shows success message

#### New Method: `reject_outside_purchase()`
**Route:** `POST /admin/order/reject-outside-purchase`
**Parameters:** `order_detail_id`, `rejection_reason` (optional)

**Functionality:**
- Validates request is in 'pending' status
- Sets `is_outside_purchase = false`
- Sets `outside_purchase_status = 'rejected'`
- Records admin ID, timestamp, and rejection reason
- Shows success message

### 4. Routes
**File:** `routes/admin.php`

New routes added:
```php
Route::post('approve-outside-purchase', 'OrderController@approve_outside_purchase')
    ->name('approve-outside-purchase');
Route::post('reject-outside-purchase', 'OrderController@reject_outside_purchase')
    ->name('reject-outside-purchase');
Route::get('get-stores-for-outside-purchase', 'OrderController@get_stores_for_outside_purchase')
    ->name('get-stores-for-outside-purchase');
```

**Delivery Man API Routes:**
```php
Route::get('get-stores-for-outside-purchase', 'DeliverymanController@get_stores_for_outside_purchase');
```

### 5. Store Selection Feature

**New Endpoints:**

#### Admin: `GET /admin/order/get-stores-for-outside-purchase`
**Parameters:** `search` (optional) - Search by store name or ID
**Response:**
```json
[
  {
    "id": 1,
    "text": "Store Name - Zone Name",
    "name": "Store Name",
    "address": "Store Address",
    "zone": "Zone Name"
  }
]
```

#### Delivery Man API: `GET /api/v1/delivery-man/get-stores-for-outside-purchase`
**Parameters:** `search` (optional) - Search by store name or ID
**Response:**
```json
{
  "stores": [
    {
      "id": 1,
      "name": "Store Name",
      "address": "Store Address",
      "zone": "Zone Name",
      "display_name": "Store Name - Zone Name"
    }
  ],
  "total": 1
}
```

**Features:**
- Returns **all active stores** (not filtered by zone)
- Supports search by name or ID
- Limited to 50 results per query
- Optimized with Select2 AJAX on admin side

### 6. Admin View Updates
**File:** `resources/views/admin-views/order/order-view.blade.php`

**Added UI Components:**

1. **Pending Request Badge** - Shows when status is 'pending'
   - Yellow warning badge with cost and store info
   - Approve button (green checkmark)
   - Reject button (red cross)

2. **Rejected Badge** - Shows when status is 'rejected'
   - Red badge showing rejection reason (if provided)

3. **JavaScript Handlers:**
   - `approve-outside-purchase-btn` - Handles approval confirmation and AJAX request
   - `reject-outside-purchase-btn` - Shows rejection reason dialog and handles AJAX request
   - `mark-outside-purchase` - Updated with Select2 AJAX search for store selection

4. **Store Selection (Select2 Integration):**
   - Searchable dropdown with all active stores (no zone filter)
   - Shows store name, zone, and address
   - AJAX-powered search for better performance
   - Optional field - can be left empty if not from a listed store

## Workflow

### Delivery Man Flow
1. Delivery man marks item as outside purchase via API
2. Request is created with status = 'pending'
3. Item is NOT yet marked as outside purchase
4. Delivery man receives confirmation that request is submitted

### Admin Flow
1. Admin views order details
2. Sees pending outside purchase request with:
   - Yellow badge showing cost and store
   - Approve and Reject buttons
3. Admin can:
   - **Approve:** Item becomes outside purchase, amount is calculated
   - **Reject:** Provide optional reason, request is denied

### Admin Direct Entry
1. Admin can still directly mark items as outside purchase
2. These are auto-approved with admin as requester

## Translation Keys Required

Add these keys to your translation files:

```php
'outside_purchase_request' => 'Outside Purchase Request',
'pending_approval' => 'Pending Approval',
'approve_outside_purchase' => 'Approve Outside Purchase',
'reject_outside_purchase' => 'Reject Outside Purchase',
'approve_outside_purchase_confirm' => 'Do you want to approve this outside purchase request?',
'outside_purchase_approved_successfully' => 'Outside purchase approved successfully',
'outside_purchase_rejected_successfully' => 'Outside purchase rejected successfully',
'outside_purchase_not_pending' => 'This outside purchase request is not pending',
'outside_purchase_rejected' => 'Outside Purchase Rejected',
'rejection_reason_optional' => 'Enter rejection reason (optional)',
'enter_rejection_reason' => 'Enter reason for rejection...',
'outside_purchase_request_submitted' => 'Outside purchase request submitted for approval',
'select_store_or_leave_empty' => 'Select store or leave empty',
'search_by_store_name' => 'Type to search by store name',
```

## Database Model Updates (Optional)

If you want to access approval details in your OrderDetail model, add this relationship:

```php
// In app/Models/OrderDetail.php

public function approvedBy()
{
    return $this->belongsTo(Admin::class, 'outside_purchase_approved_by');
}

public function outsidePurchaseStore()
{
    return $this->belongsTo(Store::class, 'outside_purchase_store_id');
}
```

## Testing Checklist

- [ ] Run migration successfully
- [ ] Delivery man can submit outside purchase request via API
- [ ] Request appears with 'pending' status in admin order view
- [ ] Admin can approve pending request
- [ ] Order total is updated after approval
- [ ] Admin can reject pending request with reason
- [ ] Rejection reason is displayed correctly
- [ ] Admin can still directly mark items as outside purchase
- [ ] Translation keys are added and working
- [ ] Buttons disable during AJAX requests
- [ ] Success/error messages display correctly

## Notes

- Only requests with status 'pending' can be approved or rejected
- Approval/rejection updates are tracked with admin ID and timestamp
- Rejection reasons are optional but recommended for record keeping
- The system maintains backward compatibility with direct admin marking
- Order totals are automatically recalculated after approval
