{{-- Inline Edit Toolbar - Sticky top toolbar for quick actions --}}
<div class="inline-edit-toolbar" style="display: none; position: sticky; top: 0; z-index: 1000; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 0 0 12px 12px; margin: -20px -20px 20px -20px; padding: 15px 30px;">
    <div class="container-fluid">
        <div class="row align-items-center">
            {{-- Left: Status --}}
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <i class="tio-edit text-white" style="font-size: 24px; margin-right: 12px;"></i>
                    <div>
                        <h6 class="text-white mb-0" style="font-weight: 600;">
                            Editing Order #{{ $order->id }}
                        </h6>
                        <small class="text-white-50 last-saved-time">
                            <i class="tio-checkmark-circle"></i> Ready to edit
                        </small>
                    </div>
                </div>
            </div>

            {{-- Center: Live Total --}}
            <div class="col-md-4 text-center">
                <div class="live-total-display" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 8px; padding: 10px 20px;">
                    <small class="text-white-50 d-block" style="font-size: 11px; margin-bottom: 2px;">New Total</small>
                    <div class="d-flex align-items-center justify-content-center">
                        <span class="text-white toolbar-total-amount" style="font-size: 24px; font-weight: 700; font-family: 'Courier New', monospace;">
                            {{ \App\CentralLogics\Helpers::format_currency($order->order_amount) }}
                        </span>
                    </div>
                    <small class="toolbar-adjustment text-warning" style="display: none; font-size: 11px; font-weight: 600;">
                        Adjustment: +₹0.00
                    </small>
                </div>
            </div>

            {{-- Right: Action Buttons --}}
            <div class="col-md-4 text-right">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-light btn-sm inline-cancel-edit" style="padding: 8px 20px; font-weight: 600;">
                        <i class="tio-clear"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success btn-sm inline-save-order" style="padding: 8px 20px; font-weight: 600;">
                        <i class="tio-checkmark-circle"></i> Save Changes
                    </button>
                </div>
                <div class="changes-indicator ml-2" style="display: none; display: inline-block; width: 10px; height: 10px; background: #ffc107; border-radius: 50%; animation: pulse 1.5s infinite;"></div>
            </div>
        </div>
    </div>
</div>

{{-- Inline Edit Styles --}}
<style>
/* Total change animation */
.total-changed {
    animation: totalPulse 0.5s ease;
}

@keyframes totalPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Changes indicator pulse */
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.9); }
}

/* Editable item hover */
.inline-editing-active .editable-item {
    transition: all 0.2s ease;
}

.inline-editing-active .editable-item:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Inline edit input */
.inline-edit-input {
    width: 100px;
    display: inline-block;
    padding: 4px 8px;
    font-size: 0.875rem;
}

/* Quantity spinner */
.qty-spinner {
    display: none;
}

.inline-editing-active .qty-spinner {
    display: inline-flex;
}

.qty-btn {
    width: 28px;
    height: 28px;
    padding: 0;
    border: 1px solid #dee2e6;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s;
}

.qty-btn:hover {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}

.inline-qty-input {
    width: 50px;
    text-align: center;
    border-left: 0;
    border-right: 0;
    padding: 4px;
}

/* Edit hint cursor */
.inline-editing-active .editable-price,
.inline-editing-active .editable-discount {
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    transition: all 0.2s;
}

.inline-editing-active .editable-price:hover,
.inline-editing-active .editable-discount:hover {
    background: #fff3cd;
    box-shadow: 0 0 0 2px #ffc107;
}

/* Delete item button */
.inline-delete-item {
    opacity: 0;
    transition: opacity 0.2s;
}

.inline-editing-active .editable-item:hover .inline-delete-item {
    opacity: 1;
}

/* Adjustment row highlight */
#adjustment-row {
    background: #fff3cd;
    font-weight: 600;
}

#adjustment-row.positive {
    background: #ffe5e8;
}

#adjustment-row.negative {
    background: #d4edda;
}
</style>

{{-- Hidden fields for calculator --}}
<input type="hidden" id="order-id" value="{{ $order->id }}">
<input type="hidden" id="store-tax-rate" value="{{ $order->store->tax ?? 0 }}">
<input type="hidden" id="tax-status" value="{{ $order->tax_status ?? 'excluded' }}">
<input type="hidden" id="delivery-charge-value" value="{{ $order->delivery_charge ?? 0 }}">
<input type="hidden" id="additional-charge-value" value="{{ $order->additional_charge ?? 0 }}">
<input type="hidden" id="dm-tips-value" value="{{ $order->dm_tips ?? 0 }}">
<input type="hidden" id="store-discount-value" value="{{ $order->store_discount_amount ?? 0 }}">
<input type="hidden" id="original-order-amount" value="{{ $order->original_order_amount ?? $order->order_amount }}">
