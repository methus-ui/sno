{{-- Single Items Product Grid --}}
<?php
if (session()->get('cart_product_ids') && count(session()->get('cart_product_ids')) > 0) {
    $cart_product_ids = session()->get('cart_product_ids');
} else {
    $cart_product_ids = [];
}
?>

@forelse($products as $product)
    <div class="order--item-box item-box" data-product-id="{{ $product->id }}">
        @include('admin-views.pos._single_product', [
            'product' => $product,
            'store_data' => $store,
            'cart_product_ids' => $cart_product_ids
        ])
    </div>
@empty
    {{-- Empty State --}}
    <div class="empty-state-container col-12">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="tio-shopping-cart-outlined"></i>
            </div>
            <h3 class="empty-state-title">{{ translate('messages.no_products_found') }}</h3>
            <p class="empty-state-text">
                {{ translate('messages.try_adjusting_your_filters_or_search_terms') }}
            </p>
            <button type="button" class="btn btn-primary btn-reset-filters" onclick="window.location.href='{{ url()->current() }}'">
                <i class="tio-refresh"></i>
                {{ translate('messages.reset_filters') }}
            </button>
        </div>
    </div>
@endforelse

<style>
/* Product Grid Container */
.order--item-box {
    opacity: 0;
    animation: fadeInUp 0.4s ease forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Stagger animation for multiple items */
.order--item-box:nth-child(1) { animation-delay: 0.05s; }
.order--item-box:nth-child(2) { animation-delay: 0.1s; }
.order--item-box:nth-child(3) { animation-delay: 0.15s; }
.order--item-box:nth-child(4) { animation-delay: 0.2s; }
.order--item-box:nth-child(5) { animation-delay: 0.25s; }
.order--item-box:nth-child(6) { animation-delay: 0.3s; }
.order--item-box:nth-child(7) { animation-delay: 0.35s; }
.order--item-box:nth-child(8) { animation-delay: 0.4s; }

/* Empty State */
.empty-state-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    padding: 40px 20px;
}

.empty-state {
    text-align: center;
    max-width: 400px;
}

.empty-state-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 24px;
    border-radius: 50%;
    background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.empty-state-icon i {
    font-size: 56px;
    color: #2563EB;
}

.empty-state-title {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 12px;
}

.empty-state-text {
    font-size: 15px;
    color: #6B7280;
    margin: 0 0 24px;
    line-height: 1.6;
}

.btn-reset-filters {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.btn-reset-filters:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
}

.btn-reset-filters i {
    font-size: 18px;
}

/* Loading Skeleton */
.skeleton-loader {
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

@keyframes skeleton-loading {
    0% { background-color: #F3F4F6; }
    50% { background-color: #E5E7EB; }
    100% { background-color: #F3F4F6; }
}

.skeleton-product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.skeleton-image {
    width: 100%;
    padding-top: 100%;
    background: #F3F4F6;
}

.skeleton-content {
    padding: 14px;
}

.skeleton-title {
    height: 16px;
    width: 80%;
    margin-bottom: 8px;
    border-radius: 4px;
}

.skeleton-price {
    height: 20px;
    width: 60%;
    margin-bottom: 12px;
    border-radius: 4px;
}

.skeleton-button {
    height: 38px;
    width: 100%;
    border-radius: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .empty-state-container {
        min-height: 300px;
        padding: 30px 15px;
    }
    
    .empty-state-icon {
        width: 100px;
        height: 100px;
        margin-bottom: 20px;
    }
    
    .empty-state-icon i {
        font-size: 48px;
    }
    
    .empty-state-title {
        font-size: 20px;
    }
    
    .empty-state-text {
        font-size: 14px;
    }
}
</style>

{{-- Add Quick Add to Cart Script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick add to cart functionality (for products without variants)
    document.querySelectorAll('.quick-add-to-cart').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const productId = this.dataset.productId;
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<i class="tio-refresh rotating"></i> <span>Adding...</span>';
            
            // Here you would make an AJAX call to add the product
            // For now, we'll just trigger the modal
            const productCard = this.closest('.pos-product-card');
            if (productCard) {
                productCard.click();
            }
        });
    });
});

// Rotating animation for loading icon
const style = document.createElement('style');
style.textContent = `
    @keyframes rotating {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .rotating {
        animation: rotating 1s linear infinite;
    }
`;
document.head.appendChild(style);
</script>
