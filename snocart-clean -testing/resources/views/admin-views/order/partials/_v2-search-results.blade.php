@forelse ($products as $product)
    @php
        $foodVariations = json_decode($product->food_variations ?? '[]', true);
        $choiceOptions  = json_decode($product->choice_options ?? '[]', true);
        $hasVariations  = !empty($foodVariations) || !empty($choiceOptions);
        $oos            = $product->is_out_of_stock ?? false;
    @endphp
    <div class="v2-product-card {{ $oos ? 'v2-oos' : '' }}"
         data-product-id="{{ $product->id }}"
         data-has-variations="{{ $hasVariations ? 1 : 0 }}"
         data-out-of-stock="{{ $oos ? 1 : 0 }}"
         tabindex="0"
         title="{{ $product->name }}">

        @if($oos)
            <span class="v2-oos-badge">{{ translate('messages.out_of_stock') }}</span>
        @elseif($product->discount > 0)
            <span class="badge badge-soft-danger pos-abs top-right badge-xs">
                -{{ $product->discount }}{{ $product->discount_type == 'percent' ? '%' : '' }}
            </span>
        @endif

        <img src="{{ $product->image_full_url ?? asset('public/assets/admin/img/100x100/1.png') }}"
             alt="{{ $product->name }}"
             class="v2-product-img"
             onerror="this.src='{{ asset('public/assets/admin/img/100x100/1.png') }}'">

        <div class="v2-product-name" title="{{ $product->name }}">
            {{ Str::limit($product->name, 30) }}
        </div>

        @if($product->unit)
            <div class="text-muted" style="font-size:0.65rem; margin-top:2px;">
                <i class="tio-scale" style="font-size:0.7rem;"></i> {{ is_object($product->unit) ? $product->unit->unit : $product->unit }}
            </div>
        @endif

        @if($product->barcode)
            <div class="text-muted" style="font-size:0.6rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                {{ $product->barcode }}
            </div>
        @endif

        <div class="small text-primary font-weight-bold mt-1">
            {{ \App\CentralLogics\Helpers::format_currency($product->price) }}
        </div>

        <button class="btn btn-xs v2-add-btn {{ $oos ? 'btn-warning' : 'btn-primary' }}"
                data-product-id="{{ $product->id }}"
                data-has-variations="{{ $hasVariations ? 1 : 0 }}"
                data-item-type="item"
                data-oos="{{ $oos ? 1 : 0 }}">
            @if($oos)
                {{ translate('messages.out_of_stock') }}
            @else
                {{ $hasVariations ? translate('messages.select') : translate('messages.add') }}
            @endif
        </button>
    </div>
@empty
    <div style="grid-column: 1 / -1; text-align: center; color: #adb5bd; padding: 60px 20px;">
        <i class="tio-search" style="font-size: 3rem; opacity: 0.4;"></i>
        <p class="mt-2 mb-0">{{ translate('messages.no_items_found') }}</p>
        <p class="small">{{ translate('messages.try_different_keyword') }}</p>
    </div>
@endforelse
