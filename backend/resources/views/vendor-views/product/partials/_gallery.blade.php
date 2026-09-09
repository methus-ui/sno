
@foreach($items as $key=>$item)
<div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3">
    <div class="card h-100 product-gallery-card">
        <!-- Product Image -->
        <div class="card-img-top position-relative" style="padding-top: 75%; overflow: hidden;">
            <img class="position-absolute w-100 h-100 onerror-image"
                style="top: 0; left: 0; object-fit: cover;"
                src="{{ $item['image_full_url'] }}"
                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                alt="{{ $item?->getRawOriginal('name') }}">
            <!-- Status Badge -->
            @if($item->store_id === \App\CentralLogics\Helpers::get_store_id())
                <span class="badge badge-soft-success position-absolute" style="top: 10px; right: 10px; z-index: 1;">
                    <i class="tio-checkmark-circle"></i>
                </span>
            @endif
        </div>

        <div class="card-body d-flex flex-column">
            <!-- Product Name -->
            <h5 class="card-title text-truncate mb-2" title="{{ $item?->getRawOriginal('name') }}">
                {{ Str::limit($item?->getRawOriginal('name'), 35, '...') }}
            </h5>

            <!-- Category Info -->
            <div class="mb-2 small">
                <span class="d-block text-muted mb-1">
                    <strong>{{ translate('messages.Category') }}:</strong><br>
                    {{ Str::limit(($item?->category?->parent ? $item?->category?->parent?->name : $item?->category?->name) ?? translate('messages.uncategorize'), 25, '...') }}
                </span>
                @if ($item->module->module_type == 'food')
                    <span class="badge badge-soft-{{ $item->veg == 1 ? 'success' : 'danger' }} mb-1">
                        {{ $item->veg == 1 ? translate('messages.veg') : translate('messages.non_veg') }}
                    </span>
                @endif
                @if ($item->module->module_type == 'grocery' && $item->organic == 1)
                    <span class="badge badge-soft-success mb-1">
                        {{ translate('messages.organic') }}
                    </span>
                @endif
            </div>

            <!-- Tags -->
            @if($item->tags->count() > 0)
                <div class="mb-2">
                    @foreach($item->tags->take(3) as $c)
                        <span class="badge badge-soft-primary mr-1 mb-1 small">{{ $c->tag }}</span>
                    @endforeach
                    @if($item->tags->count() > 3)
                        <span class="badge badge-soft-secondary mb-1 small">+{{ $item->tags->count() - 3 }}</span>
                    @endif
                </div>
            @endif

            <!-- Description -->
            <p class="small text-muted mb-3 flex-grow-1">
                {{ Str::limit($item?->getRawOriginal('description'), 80, '...') }}
            </p>

            <!-- Action Button -->
            <div class="mt-auto">
                @if($item->store_id !== \App\CentralLogics\Helpers::get_store_id())
                    <a target="_blank" href="{{ route('vendor.item.edit',['id' => $item->id, 'product_gellary' => true]) }}"
                        class="btn btn-outline-primary btn-block btn-sm">
                        <i class="tio-add-circle mr-1"></i>{{ translate('messages.use_this_product_info') }}
                    </a>
                @else
                    <button class="btn btn-soft-success btn-block btn-sm" disabled>
                        <i class="tio-checkmark-circle mr-1"></i>{{ translate('messages.already_in_your_store') }}
                    </button>
                @endif
            </div>
        </div>

        <!-- Expandable Details (Hidden by default, shown on click) -->
        <div class="card-footer bg-light border-top collapse" id="details-{{ $item->id }}">
            <div class="small">
                <!-- Variations -->
                @if ($item->module->module_type == 'food')
                    @if ($item->food_variations && is_array(json_decode($item['food_variations'], true)))
                        <strong class="d-block mb-1">{{ translate('Available_Variations') }}:</strong>
                        @foreach (json_decode($item->food_variations, true) as $variation)
                            @if (isset($variation['price']))
                                <span class="text-muted">{{ translate('please_update_the_food_variations.') }}</span>
                                @break
                            @else
                                <span class="d-block"><strong>{{ $variation['name'] }}</strong></span>
                                @if (isset($variation['values']))
                                    @foreach ($variation['values'] as $value)
                                        <span class="d-block pl-2">- {{ $value['label'] }}</span>
                                    @endforeach
                                @endif
                            @endif
                        @endforeach
                    @endif
                @else
                    @if ($item->variations && is_array(json_decode($item['variations'], true)))
                        <strong class="d-block mb-1">{{ translate('Available_Variations') }}:</strong>
                        @foreach (json_decode($item['variations'], true) as $variation)
                            <span class="d-block">- {{ $variation['type'] }}</span>
                        @endforeach
                    @endif
                @endif

                @if ($item?->unit)
                    <div class="mt-2">
                        <strong>{{ translate('messages.Unit') }}:</strong> {{ $item?->unit?->unit }}
                    </div>
                @endif
            </div>
        </div>

        <!-- View Details Toggle -->
        <div class="card-footer bg-white border-top py-2 text-center">
            <a class="small text-primary" data-toggle="collapse" href="#details-{{ $item->id }}" role="button" aria-expanded="false">
                <span class="when-closed"><i class="tio-chevron-down"></i> {{ translate('messages.view_details') }}</span>
                <span class="when-opened d-none"><i class="tio-chevron-up"></i> {{ translate('messages.hide_details') }}</span>
            </a>
        </div>
    </div>
</div>
@endforeach

<!-- Additional CSS for collapse toggle -->
<style>
    .product-gallery-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .product-gallery-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .collapse.show ~ .card-footer .when-closed {
        display: none;
    }
    .collapse.show ~ .card-footer .when-opened {
        display: inline !important;
    }
</style>

<!-- Toggle script for collapse -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const collapseElements = document.querySelectorAll('[data-toggle="collapse"]');
        collapseElements.forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    if (targetElement.classList.contains('show')) {
                        targetElement.classList.remove('show');
                    } else {
                        targetElement.classList.add('show');
                    }
                }
            });
        });
    });
</script>
<script src="{{ asset('public/assets/admin') }}/js/view-pages/common.js"></script>
