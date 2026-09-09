@extends('layouts.admin.app')

@section('title', translate('messages.order_details'))

@push('css_or_js')
<style>
/* Bargaining Hero Card Styling */
.bargaining-hero-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    margin-bottom: 1.5rem;
}

.bargaining-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
}

.bargaining-badge i {
    font-size: 18px;
}

.metric-box {
    background: rgba(255, 255, 255, 0.15);
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    backdrop-filter: blur(10px);
    transition: transform 0.2s;
}

.metric-box:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.2);
}

.metric-box .label {
    font-size: 12px;
    opacity: 0.9;
    display: block;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-box .value {
    font-size: 28px;
    font-weight: bold;
    display: block;
    margin-bottom: 4px;
    line-height: 1.2;
}

.metric-box .percentage {
    font-size: 14px;
    opacity: 0.9;
    font-weight: 500;
}

.metric-box.savings .value {
    color: #10b981;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Items Comparison Table */
.items-comparison-table {
    background: white;
}

.items-comparison-table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    padding: 12px;
}

.items-comparison-table .price-original {
    color: #6c757d;
    text-decoration: line-through;
}

.items-comparison-table .price-bargained {
    color: #10b981;
    font-weight: 600;
    font-size: 16px;
}

.items-comparison-table .savings-cell {
    color: #10b981;
    font-weight: 600;
    background: #f0fdf4;
}

/* Competing Offers Section */
.competing-offers-section {
    margin-top: 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

.competing-offers-section summary {
    cursor: pointer;
    padding: 16px 20px;
    background: #f8f9fa;
    font-weight: 600;
    list-style: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    user-select: none;
}

.competing-offers-section summary:hover {
    background: #e9ecef;
}

.competing-offers-section summary::-webkit-details-marker {
    display: none;
}

.competing-offers-section summary::after {
    content: '▼';
    transition: transform 0.2s;
    font-size: 12px;
}

.competing-offers-section[open] summary::after {
    transform: rotate(-180deg);
}

.offer-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 12px;
    transition: all 0.2s;
}

.offer-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateX(4px);
}

.offer-card.winning-offer {
    border-color: #10b981;
    background: #f0fdf4;
    border-width: 2px;
}

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    font-weight: bold;
    font-size: 16px;
}

.winning-offer .rank-badge {
    background: #10b981;
}

.store-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    margin-left: 16px;
}

.store-info img {
    border-radius: 8px;
    object-fit: cover;
}

.store-name {
    font-weight: 600;
    font-size: 15px;
}

.offer-metrics {
    display: flex;
    gap: 20px;
    align-items: center;
}

.offer-metrics span {
    font-size: 14px;
    color: #6c757d;
}

/* Header Section */
.page-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.order-badge-group {
    display: flex;
    gap: 8px;
    align-items: center;
}

/* Responsive */
@media (max-width: 768px) {
    .metric-box .value {
        font-size: 20px;
    }

    .offer-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .offer-metrics {
        width: 100%;
        justify-content: space-between;
    }
}
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header-custom">
        <div>
            <h1 class="page-header-title">
                <i class="tio-shopping-cart-outlined"></i> {{ translate('messages.order') }} #{{ $order->id }}
            </h1>
            <div class="order-badge-group mt-2">
                <span class="badge badge-soft-{{ $order->order_status == 'delivered' ? 'success' : 'primary' }}">
                    {{ translate('messages.' . $order->order_status) }}
                </span>
                @if($order->is_bargaining_order)
                <span class="badge badge-soft-purple">
                    <i class="tio-trophy"></i> {{ translate('messages.bargaining_order') }}
                </span>
                @endif
            </div>
        </div>
        <div>
            <a href="{{ route('admin.order.details', $order->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="tio-visible"></i> {{ translate('messages.view_classic_v1') }}
            </a>
            <a href="{{ route('admin.order.edit-v2', $order->id) }}" class="btn btn-primary btn-sm">
                <i class="tio-edit"></i> {{ translate('messages.edit_order') }}
            </a>
        </div>
    </div>

    <!-- Bargaining Hero Card (only for bargaining orders) -->
    @if($order->is_bargaining_order && $bargainingData)
    <div class="card bargaining-hero-card">
        <div class="card-body">
            <div class="bargaining-badge">
                <i class="tio-trophy"></i>
                <span>{{ translate('messages.won_via_bargaining') }}</span>
            </div>

            <div class="row mt-4">
                <div class="col-md-3 mb-3">
                    <div class="metric-box savings">
                        <span class="label">{{ translate('messages.total_savings') }}</span>
                        <span class="value">{{ \App\CentralLogics\Helpers::format_currency($bargainingData['total_savings']) }}</span>
                        <span class="percentage">{{ $bargainingData['savings_percentage'] }}% {{ translate('messages.off') }}</span>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="metric-box">
                        <span class="label">{{ translate('messages.fulfillment') }}</span>
                        <span class="value">{{ $bargainingData['fulfillment_percentage'] }}%</span>
                        <span class="percentage">
                            {{ $bargainingData['items_missing'] == 0 ? translate('messages.all_items_available') : $bargainingData['items_missing'] . ' ' . translate('messages.items_missing') }}
                        </span>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="metric-box">
                        <span class="label">{{ translate('messages.winning_rank') }}</span>
                        <span class="value">#1</span>
                        <span class="percentage">{{ translate('messages.of') }} {{ $bargainingData['total_offers_received'] }} {{ translate('messages.offers') }}</span>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="metric-box">
                        <span class="label">{{ translate('messages.request_code') }}</span>
                        <span class="value" style="font-size: 18px;">{{ $bargainingData['request_code'] }}</span>
                        <span class="percentage">{{ ucfirst($bargainingData['mode']) }} {{ translate('messages.mode') }}</span>
                    </div>
                </div>
            </div>

            @if($bargainingData['vendor_notes'])
            <div class="alert alert-light mt-3 mb-0" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                <i class="tio-info-outined"></i>
                <strong>{{ translate('messages.vendor_notes') }}:</strong> {{ $bargainingData['vendor_notes'] }}
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Missing Items Alert -->
    @if($bargainingData && $bargainingData['items_missing'] > 0)
    <div class="alert alert-warning">
        <i class="tio-info-outined"></i>
        <strong>{{ $bargainingData['items_missing'] }} {{ translate('messages.items_unavailable') }}</strong>
        <ul class="mb-0 mt-2">
            @foreach($bargainingData['missing_items_detail'] as $missing)
            <li>{{ $missing }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-8 mb-3 mb-lg-0">
            <!-- Order Items Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-shop"></i> {{ translate('messages.order_items') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-nowrap items-comparison-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ translate('messages.item') }}</th>
                                    <th class="text-center">{{ translate('messages.qty') }}</th>
                                    @if($order->is_bargaining_order)
                                    <th class="text-right">{{ translate('messages.original_price') }}</th>
                                    <th class="text-right">{{ translate('messages.bargained_price') }}</th>
                                    <th class="text-right">{{ translate('messages.savings') }}</th>
                                    @else
                                    <th class="text-right">{{ translate('messages.price') }}</th>
                                    <th class="text-right">{{ translate('messages.total') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->details as $detail)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $detail->item_image_full_url ?? asset('public/assets/admin/img/100x100/food-default-image.png') }}"
                                                 width="50" class="mr-3 rounded" alt="{{ $detail->item_name }}">
                                            <div>
                                                <h6 class="mb-0">{{ Str::limit($detail->item_name, 50) }}</h6>
                                                @if($detail->variation && count(json_decode($detail->variation, true)) > 0)
                                                <small class="text-muted">
                                                    {{ translate('messages.variation') }}:
                                                    @foreach(json_decode($detail->variation, true) as $variation)
                                                    {{ $variation['type'] ?? '' }}
                                                    @endforeach
                                                </small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-soft-dark">{{ $detail->quantity }}</span>
                                    </td>
                                    @if($order->is_bargaining_order && isset($detail->original_price))
                                    <td class="text-right">
                                        <span class="price-original">{{ \App\CentralLogics\Helpers::format_currency($detail->original_price) }}</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="price-bargained">{{ \App\CentralLogics\Helpers::format_currency($detail->price) }}</span>
                                    </td>
                                    <td class="text-right savings-cell">
                                        {{ \App\CentralLogics\Helpers::format_currency(($detail->original_price - $detail->price) * $detail->quantity) }}
                                    </td>
                                    @else
                                    <td class="text-right">
                                        {{ \App\CentralLogics\Helpers::format_currency($detail->price) }}
                                    </td>
                                    <td class="text-right">
                                        <strong>{{ \App\CentralLogics\Helpers::format_currency($detail->price * $detail->quantity) }}</strong>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Competing Offers (only for bargaining orders) -->
            @if($bargainingData && isset($bargainingData['competing_offers']) && $bargainingData['competing_offers']->isNotEmpty())
            <details class="competing-offers-section">
                <summary>
                    <span>
                        <i class="tio-chart-bar-1"></i> {{ translate('messages.view_competing_offers') }}
                        ({{ $bargainingData['competing_offers']->count() }})
                    </span>
                </summary>
                <div class="card-body">
                    @foreach($bargainingData['competing_offers'] as $offer)
                    <div class="offer-card {{ $offer->id == $order->bargaining_accepted_offer_id ? 'winning-offer' : '' }}">
                        <div class="rank-badge">{{ $offer->rank }}</div>
                        <div class="store-info">
                            <img src="{{ $offer->store->logo_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                 width="50" height="50" alt="{{ $offer->store->name }}">
                            <div>
                                <div class="store-name">{{ $offer->store->name }}</div>
                                @if($offer->id == $order->bargaining_accepted_offer_id)
                                <span class="badge badge-soft-success">{{ translate('messages.winning_offer') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="offer-metrics">
                            <span>
                                <i class="tio-money"></i>
                                <strong>{{ \App\CentralLogics\Helpers::format_currency($offer->total_amount) }}</strong>
                            </span>
                            <span>
                                <i class="tio-checkmark-circle"></i>
                                {{ $offer->fulfillment_percentage }}% {{ translate('messages.fulfillment') }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </details>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Customer Info Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-user"></i> {{ translate('messages.customer_info') }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($order->customer)
                    <div class="d-flex align-items-center mb-3">
                        <img src="{{ $order->customer->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}"
                             class="rounded-circle mr-3" width="60" height="60" alt="{{ $order->customer->f_name }}">
                        <div>
                            <h6 class="mb-1">{{ $order->customer->f_name }} {{ $order->customer->l_name }}</h6>
                            <small class="text-muted">{{ translate('messages.customer_id') }}: #{{ $order->customer->id }}</small>
                        </div>
                    </div>
                    <div>
                        <p class="mb-2">
                            <i class="tio-call-talking-quiet mr-2"></i>
                            <a href="tel:{{ $order->customer->phone }}">{{ $order->customer->phone }}</a>
                        </p>
                        @if($order->customer->email)
                        <p class="mb-2">
                            <i class="tio-email mr-2"></i>
                            <a href="mailto:{{ $order->customer->email }}">{{ $order->customer->email }}</a>
                        </p>
                        @endif
                        <p class="mb-0">
                            <i class="tio-shopping-cart mr-2"></i>
                            {{ translate('messages.total_orders') }}: <strong>{{ $order->customer->orders_count ?? 0 }}</strong>
                        </p>
                    </div>
                    @else
                    <p class="text-muted">{{ translate('messages.guest_user') }}</p>
                    @endif
                </div>
            </div>

            <!-- Store Info Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-shop"></i> {{ translate('messages.store_info') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img src="{{ $order->store->logo_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}"
                             class="rounded mr-3" width="60" height="60" alt="{{ $order->store->name }}">
                        <div>
                            <h6 class="mb-1">{{ $order->store->name }}</h6>
                            <small class="text-muted">{{ translate('messages.store_id') }}: #{{ $order->store->id }}</small>
                        </div>
                    </div>
                    <div>
                        <p class="mb-2">
                            <i class="tio-call-talking-quiet mr-2"></i>
                            <a href="tel:{{ $order->store->phone }}">{{ $order->store->phone }}</a>
                        </p>
                        @if($order->store->email)
                        <p class="mb-2">
                            <i class="tio-email mr-2"></i>
                            {{ $order->store->email }}
                        </p>
                        @endif
                        @if($order->is_bargaining_order && $bargainingData)
                        <div class="alert alert-soft-purple mt-3 mb-0">
                            <i class="tio-trophy"></i>
                            <small>{{ translate('messages.this_store_won_the_bargaining_with_best_offer') }}</small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-money"></i> {{ translate('messages.order_summary') }}
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6">{{ translate('messages.subtotal') }}:</dt>
                        <dd class="col-6 text-right">{{ \App\CentralLogics\Helpers::format_currency($order->order_amount - $order->total_tax_amount - $order->delivery_charge) }}</dd>

                        @if($order->is_bargaining_order && $bargainingData && isset($bargainingData['total_savings']))
                        <dt class="col-6 text-success">{{ translate('messages.bargaining_discount') }}:</dt>
                        <dd class="col-6 text-right text-success">-{{ \App\CentralLogics\Helpers::format_currency($bargainingData['total_savings']) }}</dd>
                        @endif

                        @if($order->store_discount_amount > 0)
                        <dt class="col-6">{{ translate('messages.discount') }}:</dt>
                        <dd class="col-6 text-right">-{{ \App\CentralLogics\Helpers::format_currency($order->store_discount_amount) }}</dd>
                        @endif

                        @if($order->coupon_discount_amount > 0)
                        <dt class="col-6">{{ translate('messages.coupon_discount') }}:</dt>
                        <dd class="col-6 text-right">-{{ \App\CentralLogics\Helpers::format_currency($order->coupon_discount_amount) }}</dd>
                        @endif

                        <dt class="col-6">{{ translate('messages.tax') }}:</dt>
                        <dd class="col-6 text-right">{{ \App\CentralLogics\Helpers::format_currency($order->total_tax_amount) }}</dd>

                        @if($order->order_type != 'take_away')
                        <dt class="col-6">{{ translate('messages.delivery_fee') }}:</dt>
                        <dd class="col-6 text-right">{{ \App\CentralLogics\Helpers::format_currency($order->delivery_charge) }}</dd>
                        @endif

                        @if($order->dm_tips > 0)
                        <dt class="col-6">{{ translate('messages.dm_tips') }}:</dt>
                        <dd class="col-6 text-right">{{ \App\CentralLogics\Helpers::format_currency($order->dm_tips) }}</dd>
                        @endif

                        <dt class="col-6 border-top pt-2"><strong>{{ translate('messages.total') }}:</strong></dt>
                        <dd class="col-6 text-right border-top pt-2">
                            <strong class="h5">{{ \App\CentralLogics\Helpers::format_currency($order->order_amount) }}</strong>
                        </dd>
                    </dl>

                    <div class="mt-3 pt-3 border-top">
                        <p class="mb-1">
                            <span class="text-muted">{{ translate('messages.payment_method') }}:</span>
                            <strong class="float-right">{{ translate('messages.' . $order->payment_method) }}</strong>
                        </p>
                        <p class="mb-0">
                            <span class="text-muted">{{ translate('messages.order_type') }}:</span>
                            <strong class="float-right">{{ translate('messages.' . $order->order_type) }}</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
