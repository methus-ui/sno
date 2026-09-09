<?php
$keys = array_keys($cart_product_ids);
$index = array_search($product->id, $keys);
$in_cart = in_array($product->id, array_keys($cart_product_ids));
$cart_count = isset($cart_product_ids[$product->id]) ? $cart_product_ids[$product->id] : 0;
?>

<div class="pos-product-card {{ $in_cart ? 'active quick-View-Cart-Item' : 'quick-View' }} product-card card h-100"
    data-product-id="{{ $product->id }}"
    data-item-key="{{ $index ?? null }}"
    data-id="{{ $product->id }}"
    data-item-count="{{ $cart_count }}">
    
    {{-- Product Image Container --}}
    <div class="product-image-wrapper">
        <img src="{{ $product['image_full_url'] ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"
            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
            class="product-image onerror-image"
            alt="{{ $product['name'] }}">
        
        {{-- Stock Status Badge --}}
        @php
            $stock_status = 'in-stock';
            $stock_text = translate('In Stock');
            $stock_qty = $product->stock ?? 0;
            
            if (isset($product->stock) && $product->stock == 0) {
                $stock_status = 'out-of-stock';
                $stock_text = translate('Out of Stock');
            } elseif (isset($product->stock) && $product->stock > 0 && $product->stock <= 10) {
                $stock_status = 'low-stock';
                $stock_text = translate('Low Stock');
            }
        @endphp
        
        @if($stock_status !== 'in-stock')
        <span class="stock-badge badge-{{ $stock_status }}">{{ $stock_text }}</span>
        @endif
        
        {{-- In Cart Indicator --}}
        @if($in_cart)
        <div class="cart-indicator">
            <i class="tio-checkmark-circle"></i>
            <span class="cart-count">{{ $cart_count }}</span>
        </div>
        @endif
        
        {{-- Discount Badge --}}
        @if($product->discount > 0 || \App\CentralLogics\Helpers::get_store_discount($store_data))
        @php
            $discount_amount = \App\CentralLogics\Helpers::product_discount_calculate($product, $product['price'], $store_data)['discount_amount'];
            $discount_percent = ($discount_amount / $product['price']) * 100;
        @endphp
        <span class="discount-badge">
            {{ round($discount_percent) }}% OFF
        </span>
        @endif
        
        {{-- Veg/Non-Veg Badge --}}
        @if (config('toggle_veg_non_veg') && isset($product->veg))
        <span class="veg-badge {{ $product->veg ? 'veg' : 'non-veg' }}">
            <span class="veg-indicator"></span>
        </span>
        @endif
    </div>

    {{-- Product Info --}}
    <div class="product-info">
        <h3 class="product-title" title="{{ $product['name'] }}">
            {{ $product['name'] }}
        </h3>
        
        <div class="product-pricing">
            @php
                $final_price = $product['price'] - \App\CentralLogics\Helpers::product_discount_calculate($product, $product['price'], $store_data)['discount_amount'];
            @endphp
            
            <span class="current-price">
                {{ \App\CentralLogics\Helpers::format_currency($final_price) }}
            </span>
            
            @if($product->discount > 0 || \App\CentralLogics\Helpers::get_store_discount($store_data))
            <span class="original-price">
                {{ \App\CentralLogics\Helpers::format_currency($product['price']) }}
            </span>
            @endif
        </div>
        
        {{-- Quick Action Buttons --}}
        <div class="product-actions">
            @if($stock_status === 'in-stock')
                @if($in_cart)
                <button type="button" class="btn-action btn-view">
                    <i class="tio-visible"></i>
                    <span>{{ translate('View') }}</span>
                </button>
                @else
                <button type="button" class="btn-action btn-add-cart quick-add-to-cart" data-product-id="{{ $product->id }}">
                    <i class="tio-add-to-queue"></i>
                    <span>{{ translate('Add') }}</span>
                </button>
                @endif
            @else
            <button type="button" class="btn-action btn-disabled" disabled>
                <i class="tio-clear-circle"></i>
                <span>{{ translate('Unavailable') }}</span>
            </button>
            @endif
        </div>
    </div>
</div>

<style>
.pos-product-card {
    position: relative;
    background: #FFFFFF;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid transparent;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.pos-product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    border-color: #E5E7EB;
}

.pos-product-card.active {
    border-color: #10B981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}

.pos-product-card.active:hover {
    border-color: #059669;
    box-shadow: 0 12px 24px rgba(16, 185, 129, 0.3);
}

/* Product Image */
.product-image-wrapper {
    position: relative;
    width: 100%;
    padding-top: 100%; /* 1:1 Aspect Ratio */
    background: #F9FAFB;
    overflow: hidden;
}

.product-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.pos-product-card:hover .product-image {
    transform: scale(1.08);
}

/* Stock Badge */
.stock-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 2;
    backdrop-filter: blur(8px);
}

.badge-in-stock {
    background: rgba(16, 185, 129, 0.95);
    color: white;
}

.badge-low-stock {
    background: rgba(245, 158, 11, 0.95);
    color: white;
}

.badge-out-of-stock {
    background: rgba(239, 68, 68, 0.95);
    color: white;
}

/* Cart Indicator */
.cart-indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    z-index: 2;
    animation: bounceIn 0.5s ease;
}

@keyframes bounceIn {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); opacity: 1; }
}

.cart-indicator i {
    color: white;
    font-size: 20px;
}

.cart-count {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: #EF4444;
    color: white;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
}

/* Discount Badge */
.discount-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
    color: white;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    z-index: 2;
}

/* Veg/Non-Veg Badge */
.veg-badge {
    position: absolute;
    bottom: 8px;
    left: 8px;
    width: 20px;
    height: 20px;
    border: 2px solid;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    z-index: 2;
}

.veg-badge.veg {
    border-color: #10B981;
}

.veg-badge.non-veg {
    border-color: #EF4444;
}

.veg-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
}

.veg-badge.veg .veg-indicator {
    color: #10B981;
}

.veg-badge.non-veg .veg-indicator {
    color: #EF4444;
}

/* Product Info */
.product-info {
    padding: 14px;
}

.product-title {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 10px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    min-height: 38px;
}

/* Product Pricing */
.product-pricing {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.current-price {
    font-size: 18px;
    font-weight: 700;
    color: #2563EB;
    font-family: 'Roboto Mono', monospace;
}

.original-price {
    font-size: 14px;
    color: #9CA3AF;
    text-decoration: line-through;
    font-family: 'Roboto Mono', monospace;
}

/* Product Actions */
.product-actions {
    display: flex;
    gap: 8px;
}

.btn-action {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn-add-cart {
    background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-add-cart:hover {
    background: linear-gradient(135deg, #1D4ED8 0%, #1E40AF 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
}

.btn-add-cart:active {
    transform: translateY(0);
}

.btn-view {
    background: white;
    color: #2563EB;
    border: 2px solid #2563EB;
}

.btn-view:hover {
    background: #EFF6FF;
}

.btn-disabled {
    background: #F3F4F6;
    color: #9CA3AF;
    cursor: not-allowed;
    opacity: 0.7;
}

.btn-action i {
    font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .product-title {
        font-size: 13px;
        min-height: 34px;
    }
    
    .current-price {
        font-size: 16px;
    }
    
    .btn-action {
        padding: 8px 12px;
        font-size: 12px;
    }
    
    .btn-action span {
        display: none;
    }
    
    .btn-action i {
        font-size: 18px;
    }
}

/* Loading Animation */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.pos-product-card.loading {
    animation: pulse 1.5s ease-in-out infinite;
}
</style>

<script src="{{ asset('public/assets/admin') }}/js/view-pages/common.js"></script>
