@forelse ($products as $product)
    <div class="modern-item-card ajax-result-item">
        <div class="item-image-wrapper">
            <img class="w-100 onerror-image"
                 data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                 src="{{ $product->image_full_url }}"
                 alt="{{ $product->name }}">
            <span class="quick-edit-badge">
                <i class="tio-add"></i> Add
            </span>
        </div>

        <div class="item-info-section">
            @if($product->category)
                <span class="category-badge">
                    {{ Str::limit($product->category->name, 12) }}
                </span>
            @endif

            @if($product->barcode)
                <div class="barcode-badge mb-1">
                    <i class="tio-barcode"></i> {{ $product->barcode }}
                </div>
            @endif

            <h6 class="item-name" title="{{ $product->name }}">
                {{ $product->name }}
            </h6>

            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 4px;">
                <div class="price-tag">
                    {{ \App\CentralLogics\Helpers::format_currency($product->price) }}
                </div>

                @if($product->stock)
                    <span class="stock-badge {{ $product->stock > 10 ? 'badge-soft-success' : 'badge-soft-warning' }}">
                        {{ $product->stock }} stock
                    </span>
                @endif
            </div>

            @if($product->discount > 0)
                <div class="mb-2">
                    <span style="background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 3px; font-size: 0.6rem; font-weight: 500;">
                        {{ $product->discount }}{{ $product->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }} OFF
                    </span>
                </div>
            @endif

            <button type="button"
                    class="add-item-btn quick-view"
                    data-product-id="{{ $product->id }}">
                <i class="tio-add-circle-outlined"></i>
                {{ translate('messages.add') }}
            </button>
        </div>
    </div>
@empty
    <div class="no-results-container" style="grid-column: 1 / -1;">
        <div class="text-center py-4">
            <div style="width: 60px; height: 60px; margin: 0 auto 12px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="tio-search" style="font-size: 1.5rem; color: #94a3b8;"></i>
            </div>
            <h6 style="color: #475569; font-weight: 600; margin-bottom: 4px; font-size: 0.85rem;">{{ translate('messages.no_items_found') }}</h6>
            <p style="color: #94a3b8; font-size: 0.75rem; margin: 0;">{{ translate('messages.try_different_search_or_category') }}</p>
        </div>
    </div>
@endforelse
