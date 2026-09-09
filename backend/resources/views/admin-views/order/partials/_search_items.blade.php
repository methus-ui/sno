@foreach ($products as $prod)
    <div class="mini-product-card quick-view" data-product-id="{{ $prod->id }}">
        <img class="mini-img onerror-image" src="{{ $prod->image_full_url }}" data-onerror-image="{{ asset('public/assets/admin/img/100x100/2.png') }}" alt="{{ $prod->name }}">
        <div class="mini-info">
            <div class="mini-name" title="{{ $prod->name }}">{{ $prod->name }}</div>
            <div class="mini-price">{{ \App\CentralLogics\Helpers::format_currency($prod->price) }}</div>
        </div>
        <button type="button" class="mini-add-btn"><i class="tio-add"></i></button>
    </div>
@endforeach
