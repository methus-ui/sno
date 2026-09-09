@extends('layouts.admin.app')

@section('title', translate('request_details'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <i class="tio-shopping-basket"></i> {{ translate('request_details') }}: {{ $request->request_code }}
                </h1>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.bargaining.requests') }}" class="btn btn-outline-primary">
                    <i class="tio-arrow-back"></i> {{ translate('back') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Request Overview -->
    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">{{ translate('request_overview') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="tio-barcode" style="font-size: 2rem; color: #377dff;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ translate('request_code') }}</h6>
                                    <span class="font-weight-bold">{{ $request->request_code }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="tio-checkmark-circle" style="font-size: 2rem; color: #28a745;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ translate('status') }}</h6>
                                    @php
                                        $statusClass = [
                                            'initiated' => 'badge-soft-info',
                                            'matching' => 'badge-soft-warning',
                                            'offers_received' => 'badge-soft-primary',
                                            'awarded' => 'badge-soft-success',
                                            'accepted' => 'badge-soft-success',
                                            'cancelled' => 'badge-soft-danger',
                                            'expired' => 'badge-soft-dark',
                                        ][$request->status] ?? 'badge-soft-secondary';
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ translate($request->status) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="tio-flash" style="font-size: 2rem; color: #ffc107;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ translate('mode') }}</h6>
                                    <span class="badge {{ $request->mode === 'instant' ? 'badge-soft-info' : 'badge-soft-warning' }}">
                                        {{ translate($request->mode) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="tio-time" style="font-size: 2rem; color: #17a2b8;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ translate('created_at') }}</h6>
                                    <span>{{ $request->created_at->format('d M Y H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-6 col-md-3 text-center mb-3">
                            <h6 class="text-muted mb-1">{{ translate('total_items') }}</h6>
                            <h3 class="mb-0">{{ $request->total_cart_items }}</h3>
                        </div>
                        <div class="col-6 col-md-3 text-center mb-3">
                            <h6 class="text-muted mb-1">{{ translate('stores_matched') }}</h6>
                            <h3 class="mb-0">{{ $request->total_stores_matched }}</h3>
                        </div>
                        <div class="col-6 col-md-3 text-center mb-3">
                            <h6 class="text-muted mb-1">{{ translate('offers_received') }}</h6>
                            <h3 class="mb-0">{{ $request->total_offers_received }}</h3>
                        </div>
                        <div class="col-6 col-md-3 text-center mb-3">
                            <h6 class="text-muted mb-1">{{ translate('savings') }}</h6>
                            <h3 class="mb-0 text-success">{{ \App\CentralLogics\Helpers::format_currency($request->total_savings ?? 0) }}</h3>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6>{{ translate('original_cart_value') }}</h6>
                            <h4>{{ \App\CentralLogics\Helpers::format_currency($request->original_cart_value) }}</h4>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6>{{ translate('final_price') }}</h6>
                            <h4 class="text-success">
                                {{ $request->final_price ? \App\CentralLogics\Helpers::format_currency($request->final_price) : '--' }}
                            </h4>
                        </div>
                    </div>

                    @if($request->awardedStore)
                        <div class="alert alert-soft-success">
                            <div class="d-flex align-items-center">
                                <i class="tio-trophy mr-2" style="font-size: 1.5rem;"></i>
                                <div>
                                    <strong>{{ translate('awarded_to') }}:</strong>
                                    <a href="{{ route('admin.store.view', $request->awarded_store_id) }}" target="_blank">
                                        {{ $request->awardedStore->name }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">{{ translate('customer_details') }}</h5>
                </div>
                <div class="card-body">
                    @if($request->user_id)
                        <div class="mb-3">
                            <h6>{{ translate('customer_id') }}</h6>
                            <p class="mb-0">
                                <a href="{{ route('admin.customer.view', $request->user_id) }}" target="_blank">
                                    #{{ $request->user_id }}
                                </a>
                            </p>
                        </div>
                    @else
                        <div class="alert alert-soft-warning">
                            <i class="tio-info"></i> {{ translate('guest_user') }}
                        </div>
                    @endif

                    <div class="mb-3">
                        <h6>{{ translate('zone') }}</h6>
                        <p class="mb-0">Zone #{{ $request->zone_id }}</p>
                    </div>

                    <div class="mb-3">
                        <h6>{{ translate('module') }}</h6>
                        <p class="mb-0">Module #{{ $request->module_id }}</p>
                    </div>

                    @if($request->expires_at)
                        <div class="mb-3">
                            <h6>{{ translate('expires_at') }}</h6>
                            <p class="mb-0">
                                {{ $request->expires_at->format('d M Y H:i') }}
                                @if($request->isExpired())
                                    <span class="badge badge-soft-danger ml-2">{{ translate('expired') }}</span>
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Items -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-header-title">{{ translate('cart_items') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ translate('item_name') }}</th>
                            <th>{{ translate('barcode') }}</th>
                            <th>{{ translate('quantity') }}</th>
                            <th>{{ translate('original_price') }}</th>
                            <th>{{ translate('matches_found') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($request->cartItems as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->item_name }}</td>
                                <td>
                                    @if($item->item_barcode)
                                        <span class="badge badge-soft-info">{{ $item->item_barcode }}</span>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ \App\CentralLogics\Helpers::format_currency($item->original_price) }}</td>
                                <td>
                                    <span class="badge badge-soft-success">{{ $item->total_matches_found }} stores</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Store Offers -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-header-title">
                {{ translate('store_offers') }}
                <span class="badge badge-soft-dark ml-2">{{ $request->storeOffers->count() }}</span>
            </h5>
        </div>
        <div class="card-body">
            @forelse($request->storeOffers as $offer)
                <div class="card mb-3 {{ $offer->is_best_offer ? 'border-success' : '' }}">
                    <div class="card-header {{ $offer->is_best_offer ? 'bg-soft-success' : '' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                @if($offer->is_best_offer)
                                    <i class="tio-trophy text-success mr-2" style="font-size: 1.5rem;"></i>
                                @endif
                                <div class="avatar avatar-circle mr-3">
                                    <img class="avatar-img" src="{{ $offer->store->logo_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="{{ $offer->store->name }}">
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $offer->store->name }}</h6>
                                    <small class="text-muted">
                                        {{ translate('rank') }}: #{{ $offer->rank }}
                                        @if($offer->offer_type === 'vendor_submitted')
                                            <span class="badge badge-soft-warning ml-2">{{ translate('vendor_counter_offer') }}</span>
                                        @else
                                            <span class="badge badge-soft-info ml-2">{{ translate('auto_calculated') }}</span>
                                        @endif
                                    </small>
                                </div>
                            </div>
                            <div class="text-right">
                                <h4 class="mb-0">{{ \App\CentralLogics\Helpers::format_currency($offer->total_amount) }}</h4>
                                @if($offer->status === 'accepted')
                                    <span class="badge badge-soft-success">{{ translate('accepted') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <small class="text-muted">{{ translate('fulfillment') }}</small>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar {{ $offer->fulfillment_percentage == 100 ? 'bg-success' : 'bg-warning' }}"
                                         role="progressbar"
                                         style="width: {{ $offer->fulfillment_percentage }}%">
                                        {{ number_format($offer->fulfillment_percentage, 0) }}%
                                    </div>
                                </div>
                                <small>{{ $offer->items_available }}/{{ $request->total_cart_items }} items</small>
                            </div>
                            <div class="col-md-2 mb-2">
                                <small class="text-muted">{{ translate('subtotal') }}</small>
                                <p class="mb-0 font-weight-bold">{{ \App\CentralLogics\Helpers::format_currency($offer->subtotal) }}</p>
                            </div>
                            <div class="col-md-2 mb-2">
                                <small class="text-muted">{{ translate('discount') }}</small>
                                <p class="mb-0 text-success">-{{ \App\CentralLogics\Helpers::format_currency($offer->item_discount + $offer->store_discount) }}</p>
                            </div>
                            <div class="col-md-2 mb-2">
                                <small class="text-muted">{{ translate('delivery') }}</small>
                                <p class="mb-0">{{ \App\CentralLogics\Helpers::format_currency($offer->delivery_charge) }}</p>
                            </div>
                            <div class="col-md-3 mb-2">
                                <small class="text-muted">{{ translate('total_amount') }}</small>
                                <p class="mb-0 font-weight-bold text-primary">{{ \App\CentralLogics\Helpers::format_currency($offer->total_amount) }}</p>
                            </div>
                        </div>

                        @if($offer->vendor_notes)
                            <div class="alert alert-soft-info mt-3">
                                <strong>{{ translate('vendor_notes') }}:</strong> {{ $offer->vendor_notes }}
                            </div>
                        @endif

                        @if($offer->items_missing > 0)
                            <div class="alert alert-soft-warning mt-3">
                                <i class="tio-info"></i> {{ $offer->items_missing }} {{ translate('items_not_available') }}
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <p class="text-muted">{{ translate('no_offers_received_yet') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
