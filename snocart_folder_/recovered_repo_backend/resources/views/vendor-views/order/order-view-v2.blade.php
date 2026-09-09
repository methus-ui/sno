@extends('layouts.vendor.app')

@section('title', translate('messages.order_details'))

@push('css_or_js')
<style>
/* Vendor Bargaining Card Styling */
.vendor-bargaining-card {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    margin-bottom: 1.5rem;
}

.vendor-metric-box {
    background: rgba(255, 255, 255, 0.15);
    padding: 16px;
    border-radius: 10px;
    backdrop-filter: blur(10px);
}

.vendor-metric-box .label {
    font-size: 11px;
    opacity: 0.9;
    display: block;
    margin-bottom: 6px;
    text-transform: uppercase;
}

.vendor-metric-box .value {
    font-size: 22px;
    font-weight: bold;
    display: block;
}

.vendor-notes-box {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
}

.rank-badge-large {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.3);
    color: white;
    border-radius: 50%;
    font-weight: bold;
    font-size: 24px;
    border: 3px solid rgba(255, 255, 255, 0.5);
}

/* Items Table */
.vendor-items-table thead th {
    background: #f8f9fa;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
}

/* Header */
.page-header-vendor {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .vendor-metric-box .value {
        font-size: 18px;
    }

    .page-header-vendor {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header-vendor">
        <div>
            <h1 class="page-header-title">
                <i class="tio-shopping-cart-outlined"></i> {{ translate('messages.order') }} #{{ $order->id }}
            </h1>
            <div class="mt-2">
                <span class="badge badge-soft-{{ $order->order_status == 'delivered' ? 'success' : 'primary' }}">
                    {{ translate('messages.' . $order->order_status) }}
                </span>
                @if($order->is_bargaining_order)
                <span class="badge badge-soft-success">
                    <i class="tio-trophy"></i> {{ translate('messages.bargaining_order') }}
                </span>
                @endif
            </div>
        </div>
        <div>
            <a href="{{ route('vendor.order.details', $order->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="tio-visible"></i> {{ translate('messages.view_classic_v1') }}
            </a>
        </div>
    </div>

    <!-- Vendor Bargaining Success Card (only for bargaining orders) -->
    @if($order->is_bargaining_order && $bargainingData)
    <div class="card vendor-bargaining-card">
        <div class="card-body">
            <div class="d-flex align-items-center mb-4">
                <div class="rank-badge-large mr-4">
                    #{{ $bargainingData['my_rank'] }}
                </div>
                <div>
                    <h4 class="mb-1">
                        <i class="tio-trophy"></i> {{ translate('messages.congratulations_you_won') }}!
                    </h4>
                    <p class="mb-0 opacity-90">
                        {{ translate('messages.you_beat') }} {{ $bargainingData['total_competitors'] }} {{ translate('messages.competing_stores') }}
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 col-6 mb-3">
                    <div class="vendor-metric-box">
                        <span class="label">{{ translate('messages.my_rank') }}</span>
                        <span class="value">#{{ $bargainingData['my_rank'] }}</span>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="vendor-metric-box">
                        <span class="label">{{ translate('messages.competitors') }}</span>
                        <span class="value">{{ $bargainingData['total_competitors'] }}</span>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="vendor-metric-box">
                        <span class="label">{{ translate('messages.fulfillment') }}</span>
                        <span class="value">{{ $bargainingData['fulfillment_percentage'] }}%</span>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="vendor-metric-box">
                        <span class="label">{{ translate('messages.items_available') }}</span>
                        <span class="value">{{ $bargainingData['items_available'] }}/{{ $bargainingData['items_available'] + $bargainingData['items_missing'] }}</span>
                    </div>
                </div>
            </div>

            @if($bargainingData['special_discount_given'] > 0)
            <div class="alert vendor-notes-box mt-3 mb-0">
                <i class="tio-star"></i>
                <strong>{{ translate('messages.special_discount_given') }}:</strong>
                {{ \App\CentralLogics\Helpers::format_currency($bargainingData['special_discount_given']) }}
            </div>
            @endif

            @if($bargainingData['my_notes'])
            <div class="alert vendor-notes-box mt-3 mb-0">
                <i class="tio-info-outined"></i>
                <strong>{{ translate('messages.your_notes') }}:</strong> {{ $bargainingData['my_notes'] }}
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Missing Items Warning -->
    @if($bargainingData && $bargainingData['items_missing'] > 0)
    <div class="alert alert-warning">
        <i class="tio-info-outined"></i>
        <strong>{{ $bargainingData['items_missing'] }} {{ translate('messages.items_were_unavailable') }}</strong>
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
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-shopping-cart"></i> {{ translate('messages.order_items') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless vendor-items-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ translate('messages.item') }}</th>
                                    <th class="text-center">{{ translate('messages.qty') }}</th>
                                    <th class="text-right">{{ translate('messages.price') }}</th>
                                    <th class="text-right">{{ translate('messages.total') }}</th>
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
                                    <td class="text-right">
                                        {{ \App\CentralLogics\Helpers::format_currency($detail->price) }}
                                    </td>
                                    <td class="text-right">
                                        <strong>{{ \App\CentralLogics\Helpers::format_currency($detail->price * $detail->quantity) }}</strong>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
                            <small class="text-muted">{{ translate('messages.customer') }}</small>
                        </div>
                    </div>
                    <div>
                        <p class="mb-2">
                            <i class="tio-call-talking-quiet mr-2"></i>
                            <a href="tel:{{ $order->customer->phone }}">{{ $order->customer->phone }}</a>
                        </p>
                        @if($order->customer->email)
                        <p class="mb-0">
                            <i class="tio-email mr-2"></i>
                            {{ $order->customer->email }}
                        </p>
                        @endif
                    </div>
                    @else
                    <p class="text-muted">{{ translate('messages.guest_user') }}</p>
                    @endif
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-money"></i> {{ translate('messages.order_summary') }}
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6">{{ translate('messages.subtotal') }}:</dt>
                        <dd class="col-6 text-right">{{ \App\CentralLogics\Helpers::format_currency($order->order_amount - $order->total_tax_amount - $order->delivery_charge) }}</dd>

                        @if($order->store_discount_amount > 0)
                        <dt class="col-6">{{ translate('messages.discount') }}:</dt>
                        <dd class="col-6 text-right">-{{ \App\CentralLogics\Helpers::format_currency($order->store_discount_amount) }}</dd>
                        @endif

                        <dt class="col-6">{{ translate('messages.tax') }}:</dt>
                        <dd class="col-6 text-right">{{ \App\CentralLogics\Helpers::format_currency($order->total_tax_amount) }}</dd>

                        @if($order->order_type != 'take_away')
                        <dt class="col-6">{{ translate('messages.delivery_fee') }}:</dt>
                        <dd class="col-6 text-right">{{ \App\CentralLogics\Helpers::format_currency($order->delivery_charge) }}</dd>
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

            <!-- Delivery Info Card -->
            @if($order->delivery_man)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-biking"></i> {{ translate('messages.delivery_man') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="{{ $order->delivery_man->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}"
                             class="rounded-circle mr-3" width="50" height="50" alt="{{ $order->delivery_man->f_name }}">
                        <div>
                            <h6 class="mb-1">{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}</h6>
                            <p class="mb-0">
                                <i class="tio-call-talking-quiet mr-1"></i>
                                <a href="tel:{{ $order->delivery_man->phone }}">{{ $order->delivery_man->phone }}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
