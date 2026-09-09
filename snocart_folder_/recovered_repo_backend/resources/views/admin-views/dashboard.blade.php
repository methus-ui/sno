@extends('layouts.admin.app')

@section('title',\App\Models\BusinessSetting::where(['key'=>'business_name'])->first()->value??translate('messages.dashboard'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Business Overview Cards */
        .icon-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .bg-white-10 {
            background: rgba(255, 255, 255, 0.15);
        }
        .text-white-50 {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        /* Soft button styles */
        .btn-soft-primary {
            background-color: rgba(0, 85, 85, 0.1);
            color: #005555;
            border: none;
        }
        .btn-soft-primary:hover {
            background-color: #005555;
            color: #fff;
        }
        .btn-soft-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: none;
        }
        .btn-soft-success:hover {
            background-color: #28a745;
            color: #fff;
        }
        .btn-soft-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #d39e00;
            border: none;
        }
        .btn-soft-warning:hover {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-soft-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
            border: none;
        }
        .btn-soft-info:hover {
            background-color: #17a2b8;
            color: #fff;
        }
        .badge-soft-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        /* Gap utility */
        .gap-3 {
            gap: 1rem;
        }
        /* Card hover effects */
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        /* List group styling */
        .list-group-item {
            border-left: none;
            border-right: none;
            transition: background-color 0.2s ease;
        }
        .list-group-item:hover {
            background-color: #f8f9fa;
        }
        .list-group-item:first-child {
            border-top: none;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        @if(auth('admin')->user()->role_id == 1)
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center py-2">
                <div class="col-sm mb-2 mb-sm-0">
                    <div class="d-flex align-items-center">
                        <img src="{{asset('/public/assets/admin/img/grocery.svg')}}" alt="img">
                        <div class="w-0 flex-grow pl-2">
                            <h1 class="page-header-title mb-0">{{translate('messages.welcome')}}, {{auth('admin')->user()->f_name}}.</h1>
                            <p class="page-header-text m-0">{{translate('messages.welcome_message')}}</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-auto d-flex align-items-center">
                    <div class="text-right mr-3">
                        <div id="live-clock" style="font-size: 20px; font-weight: bold; color: #005555;"></div>
                        <div id="live-date" style="font-size: 12px; color: #666;"></div>
                    </div>
                    <select name="zone_id" class="form-control js-select2-custom fetch_data_zone_wise min--280">
                        <option value="all">{{ translate('messages.All_Zones') }}</option>
                        @foreach(\App\Models\Zone::orderBy('name')->get() as $zone)
                            <option
                                value="{{$zone['id']}}" {{$params['zone_id'] == $zone['id']?'selected':''}}>
                                {{$zone['name']}}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Stats -->
        <div class="card mb-3">
            <div class="card-body pt-0">
                <div class="d-flex flex-wrap align-items-center justify-content-end">
                    <div class="status-filter-wrap">
                        <div class="statistics-btn-grp">
                            <label>
                                <input type="radio" name="statistics" hidden checked>
                                <span>{{ translate('This_Year') }}</span>
                            </label>
                            <label>
                                <input type="radio" name="statistics" hidden>
                                <span>{{ translate('This_Month') }}</span>
                            </label>
                            <label>
                                <input type="radio" name="statistics" hidden>
                                <span>{{ translate('This_Week') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row g-2" id="order_stats">
                    <div class="col-sm-6 col-lg-3">
                        <div class="__dashboard-card-2">
                            <img src="{{asset('/public/assets/admin/img/dashboard/food/items.svg')}}" alt="dashboard/grocery">
                            <h6 class="name">Items</h6>
                            <h3 class="count">33,451</h3>
                            <div class="subtxt">12 newly added</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="__dashboard-card-2">
                            <img src="{{asset('/public/assets/admin/img/dashboard/food/orders.svg')}}" alt="dashboard/grocery">
                            <h6 class="name">Orders</h6>
                            <h3 class="count">30M+</h3>
                            <div class="subtxt">12 newly added</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="__dashboard-card-2">
                            <img src="{{asset('/public/assets/admin/img/dashboard/food/stores.svg')}}" alt="dashboard/grocery">
                            <h6 class="name">Grocery Stores</h6>
                            <h3 class="count">556</h3>
                            <div class="subtxt">12 newly added</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="__dashboard-card-2">
                            <img src="{{asset('/public/assets/admin/img/dashboard/food/customers.svg')}}" alt="dashboard/grocery">
                            <h6 class="name">Customers</h6>
                            <h3 class="count">1M+</h3>
                            <div class="subtxt">566 newly added</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row g-2">
                            <div class="col-sm-6 col-lg-3">
                                <a class="order--card h-100" href="{{route('admin.order.list',['delivered'])}}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                            <img src="{{asset('/public/assets/admin/img/dashboard/food/unassigned.svg')}}" alt="dashboard" class="oder--card-icon">
                                            <span>{{translate('messages.unassigned_orders')}}</span>
                                        </h6>
                                        <span class="card-title text-3F8CE8">
                                            {{$data['searching_for_dm']}}
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div class="col-sm-6 col-lg-3">
                                <a class="order--card h-100" href="{{route('admin.order.list',['refunded'])}}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                            <img src="{{asset('/public/assets/admin/img/dashboard/food/accepted.svg')}}" alt="dashboard" class="oder--card-icon">
                                            <span>{{translate('Accepted by Delivery Man')}}</span>
                                        </h6>
                                        <span class="card-title text-success">
                                            {{$data['accepted_by_dm']}}
                                        </span>
                                    </div>
                                </a>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <a class="order--card h-100" href="{{route('admin.order.list',['canceled'])}}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                            <img src="{{asset('/public/assets/admin/img/dashboard/food/packaging.svg')}}" alt="dashboard" class="oder--card-icon">
                                            <span>{{translate('Packaging')}}</span>
                                        </h6>
                                        <span class="card-title text-FFA800">
                                            {{$data['preparing_in_rs']}}
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div class="col-sm-6 col-lg-3">
                                <a class="order--card h-100" href="{{route('admin.order.list',['failed'])}}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                            <img src="{{asset('/public/assets/admin/img/dashboard/food/out-for.svg')}}" alt="dashboard" class="oder--card-icon">
                                            <span>{{translate('Out for Delivery')}}</span>
                                        </h6>
                                        <span class="card-title text-success">
                                            {{$data['picked_up']}}
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div class="col-sm-6 col-lg-3">
                                <a class="order--card h-100" href="{{route('admin.order.list',['delivered'])}}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                            <img src="{{asset('/public/assets/admin/img/dashboard/grocery/delivered.svg')}}" alt="dashboard" class="oder--card-icon">
                                            <span>{{translate('messages.delivered')}}</span>
                                        </h6>
                                        <span class="card-title text-success">
                                            {{$data['delivered']}}
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div class="col-sm-6 col-lg-3">
                                <a class="order--card h-100" href="{{route('admin.order.list',['canceled'])}}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                            <img src="{{asset('/public/assets/admin/img/order-status/canceled.svg')}}" alt="dashboard" class="oder--card-icon">
                                            <span>{{translate('messages.canceled')}}</span>
                                        </h6>
                                        <span class="card-title text-danger">
                                            {{$data['canceled']}}
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div class="col-sm-6 col-lg-3">
                                <a class="order--card h-100" href="{{route('admin.order.list',['refunded'])}}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                            <img src="{{asset('/public/assets/admin/img/order-status/refunded.svg')}}" alt="dashboard" class="oder--card-icon">
                                            <span>{{translate('messages.refunded')}}</span>
                                        </h6>
                                        <span class="card-title text-danger">
                                            {{$data['refunded']}}
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div class="col-sm-6 col-lg-3">
                                <a class="order--card h-100" href="{{route('admin.order.list',['failed'])}}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                            <img src="{{asset('/public/assets/admin/img/order-status/payment-failed.svg')}}" alt="dashboard" class="oder--card-icon">
                                            <span>{{translate('messages.payment_failed')}}</span>
                                        </h6>
                                        <span class="card-title text-danger">
                                            {{$data['refund_requested']}}
                                        </span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <!-- End Stats -->

        <!-- Business Overview & Delivery Boy of the Month -->
        <div class="row g-3 mb-3">
            <!-- Today's Quick Stats -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 bg-gradient-primary text-white" style="background: linear-gradient(135deg, #005555 0%, #00aa96 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h6 class="text-white-50 mb-1">{{ translate("Today's Orders") }}</h6>
                                <h2 class="text-white mb-0">{{ $data['today_orders'] ?? 0 }}</h2>
                            </div>
                            <div class="icon-circle bg-white-10">
                                <i class="tio-shopping-cart" style="font-size: 28px;"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="tio-trending-up mr-1"></i>
                            <span class="small">{{ translate('Live count') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Revenue -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h6 class="text-white-50 mb-1">{{ translate("Today's Revenue") }}</h6>
                                <h2 class="text-white mb-0">{{ \App\CentralLogics\Helpers::format_currency($data['today_revenue'] ?? 0) }}</h2>
                            </div>
                            <div class="icon-circle bg-white-10">
                                <i class="tio-dollar" style="font-size: 28px;"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="tio-checkmark-circle mr-1"></i>
                            <span class="small">{{ translate('From delivered orders') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- This Month Revenue -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h6 class="text-white-50 mb-1">{{ translate('This Month Revenue') }}</h6>
                                <h2 class="text-white mb-0">{{ \App\CentralLogics\Helpers::format_currency($data['this_month_revenue'] ?? 0) }}</h2>
                            </div>
                            <div class="icon-circle bg-white-10">
                                <i class="tio-chart-bar-4" style="font-size: 28px;"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            @if(($data['revenue_growth'] ?? 0) >= 0)
                                <i class="tio-trending-up mr-1"></i>
                                <span class="small">+{{ $data['revenue_growth'] ?? 0 }}% {{ translate('vs last month') }}</span>
                            @else
                                <i class="tio-trending-down mr-1"></i>
                                <span class="small">{{ $data['revenue_growth'] ?? 0 }}% {{ translate('vs last month') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- This Month Stats -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1">{{ translate('This Month') }}</h6>
                                <h3 class="text-white mb-0">{{ $data['this_month_orders_count'] ?? 0 }} {{ translate('Orders') }}</h3>
                            </div>
                            <div class="icon-circle bg-white-10">
                                <i class="tio-receipt" style="font-size: 28px;"></i>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span><i class="tio-checkmark-circle-outlined"></i> {{ $data['this_month_delivered'] ?? 0 }} {{ translate('Delivered') }}</span>
                            <span><i class="tio-clear-circle-outlined"></i> {{ $data['this_month_canceled'] ?? 0 }} {{ translate('Canceled') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delivery Boy of the Month -->
        <div class="row g-3 mb-3">
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 overflow-hidden">
                    <div class="card-header bg-gradient-warning text-dark" style="background: linear-gradient(135deg, #f5af19 0%, #f12711 100%) !important;">
                        <h5 class="card-title text-white mb-0">
                            <i class="tio-star mr-2"></i>{{ translate('Delivery Boy of the Month') }}
                        </h5>
                    </div>
                    <div class="card-body text-center py-4">
                        @if(isset($data['delivery_boy_of_month']) && $data['delivery_boy_of_month'])
                            @php $dm = $data['delivery_boy_of_month']; @endphp
                            <div class="position-relative d-inline-block mb-3">
                                <img class="rounded-circle border border-warning"
                                     style="width: 100px; height: 100px; object-fit: cover; border-width: 3px !important;"
                                     src="{{ $dm->image_full_url ?? asset('public/assets/admin/img/delivery_boy_map.png') }}"
                                     alt="{{ $dm->f_name }}">
                                <span class="badge badge-warning position-absolute" style="bottom: 5px; right: -5px; font-size: 16px;">
                                    <i class="tio-star"></i>
                                </span>
                            </div>
                            <h4 class="mb-1">{{ $dm->f_name }} {{ $dm->l_name }}</h4>
                            <p class="text-muted mb-2">{{ $dm->phone }}</p>
                            <div class="d-flex justify-content-center gap-3">
                                <div class="text-center px-3">
                                    <h3 class="text-success mb-0">{{ $dm->orders_count }}</h3>
                                    <small class="text-muted">{{ translate('Deliveries') }}</small>
                                </div>
                                <div class="border-left"></div>
                                <div class="text-center px-3">
                                    <h3 class="text-primary mb-0">{{ number_format($dm->avg_rating ?? 0, 1) }}</h3>
                                    <small class="text-muted">{{ translate('Rating') }}</small>
                                </div>
                            </div>
                            <a href="{{ route('admin.delivery-man.preview', $dm->id) }}" class="btn btn-outline-primary btn-sm mt-3">
                                {{ translate('View Profile') }}
                            </a>
                        @else
                            <div class="py-4">
                                <img src="{{ asset('public/assets/admin/img/delivery-man.png') }}" alt="" style="width: 80px; opacity: 0.5;">
                                <p class="text-muted mt-3 mb-0">{{ translate('No deliveries this month yet') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Top 3 Delivery Boys This Month -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="tio-bike mr-2 text-primary"></i>{{ translate('Top Performers This Month') }}
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($data['top_deliveryman']->take(5) as $index => $dm)
                            <li class="list-group-item d-flex align-items-center">
                                <span class="badge badge-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'dark') }} mr-2" style="width: 24px; height: 24px; line-height: 16px;">
                                    {{ $index + 1 }}
                                </span>
                                <img class="rounded-circle mr-2" style="width: 40px; height: 40px; object-fit: cover;"
                                     src="{{ $dm->image_full_url ?? asset('public/assets/admin/img/delivery_boy_map.png') }}" alt="">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $dm->f_name }} {{ $dm->l_name }}</h6>
                                    <small class="text-muted">{{ $dm->orders_count }} {{ translate('orders') }}</small>
                                </div>
                                <span class="badge badge-soft-success">{{ number_format($dm->avg_rating ?? 0, 1) }} <i class="tio-star text-warning"></i></span>
                            </li>
                            @empty
                            <li class="list-group-item text-center text-muted py-4">
                                {{ translate('No data available') }}
                            </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4 col-md-12">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="tio-flash mr-2 text-warning"></i>{{ translate('Quick Actions') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="{{ route('admin.order.list', ['pending']) }}" class="btn btn-soft-primary btn-block py-3">
                                    <i class="tio-shopping-cart-outlined d-block mb-1" style="font-size: 24px;"></i>
                                    <small>{{ translate('Pending Orders') }}</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('admin.users.delivery-man.list') }}" class="btn btn-soft-success btn-block py-3">
                                    <i class="tio-user-outlined d-block mb-1" style="font-size: 24px;"></i>
                                    <small>{{ translate('Delivery Men') }}</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('admin.store.list') }}" class="btn btn-soft-warning btn-block py-3">
                                    <i class="tio-shop-outlined d-block mb-1" style="font-size: 24px;"></i>
                                    <small>{{ translate('All Stores') }}</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('admin.customer.list') }}" class="btn btn-soft-info btn-block py-3">
                                    <i class="tio-users-switch-outlined d-block mb-1" style="font-size: 24px;"></i>
                                    <small>{{ translate('Customers') }}</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Business Overview -->

        <div class="row g-2">
            <div class="col-lg-8 col--xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center __gap-12px">
                            <div class="__gross-amount">
                                <h6>{{ \App\CentralLogics\Helpers::format_currency(array_sum($data['total_sell'] ?? [])) }}</h6>
                                <span>{{ translate('Gross Sale') }}</span>
                            </div>
                            <div class="chart--label __chart-label p-0 move-left-100 ml-auto">
                                <span class="indicator chart-bg-2"></span>
                                <span class="info">
                                    Sale (2022)
                                </span>
                            </div>
                            <select class="custom-select border-0 text-center w-auto ml-auto">
                                <option>
                                    {{translate('This Month')}}
                                </option>
                                <option>
                                    {{translate('This Year')}}
                                </option>
                            </select>
                        </div>
                        <div id="grow-sale-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col--xl-4">
                <!-- Card -->
                <div class="card h-100">
                    <!-- Header -->
                    <div class="card-header border-0">
                        <h5 class="card-header-title">
                            {{translate('User Statistics')}}
                        </h5>
                        <select class="custom-select border-0 text-center w-auto user_overview_stats_update" name="user_overview">
                            <option
                                value="this_month" {{$params['user_overview'] == 'this_month'?'selected':''}}>
                                {{translate('This month')}}
                            </option>
                            <option
                                value="overall" {{$params['user_overview'] == 'overall'?'selected':''}}>
                                {{translate('messages.Overall')}}
                            </option>
                        </select>
                    </div>
                    <!-- End Header -->

                    <!-- Body -->
                    <div class="card-body" id="user-overview-board">
                        <div class="position-relative pie-chart">
                            <div id="dognut-pie"></div>
                            <!-- Total Orders -->
                            <div class="total--orders">
                                <h3 class="text-uppercase mb-xxl-2">{{ $data['customer'] + $data['stores'] + $data['delivery_man'] }}</h3>
                                <span class="text-capitalize">{{translate('messages.total_users')}}</span>
                            </div>
                            <!-- Total Orders -->
                        </div>
                        <div class="d-flex flex-wrap justify-content-center mt-4">
                            <div class="chart--label">
                                <span class="indicator chart-bg-1"></span>
                                <span class="info">
                                    {{translate('messages.customer')}} {{$data['customer']}}
                                </span>
                            </div>
                            <div class="chart--label">
                                <span class="indicator chart-bg-2"></span>
                                <span class="info">
                                    {{translate('messages.store')}} {{$data['stores']}}
                                </span>
                            </div>
                            <div class="chart--label">
                                <span class="indicator chart-bg-3"></span>
                                <span class="info">
                                    {{translate('messages.delivery_man')}} {{$data['delivery_man']}}
                                </span>
                            </div>
                        </div>

                    </div>
                    <!-- End Body -->
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="top-restaurants-view">
                    @include('admin-views.partials._top-restaurants',['top_restaurants'=>$data['top_restaurants']])
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="popular-restaurants-view">
                    @include('admin-views.partials._popular-restaurants',['popular'=>$data['popular']])
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="top-selling-foods-view">
                    @include('admin-views.partials._top-selling-foods',['top_sell'=>$data['top_sell']])
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="top-rated-foods-view">
                    @include('admin-views.partials._top-rated-foods',['top_rated_foods'=>$data['top_rated_foods']])
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="top-deliveryman-view">
                    @include('admin-views.partials._top-deliveryman',['top_deliveryman'=>$data['top_deliveryman']])
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="top-customer-view">
                    @include('admin-views.partials._top-customer')
                </div>
                <!-- End Card -->
            </div>

            <!-- Sticky Notes -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-header-title"><i class="tio-bookmark-outlined mr-1"></i> {{ translate('My Notes') }}</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addNewNote()">
                            <i class="tio-add"></i>
                        </button>
                    </div>
                    <div class="card-body p-2" id="sticky-notes-container" style="max-height: 300px; overflow-y: auto;">
                        <!-- Notes loaded via JS -->
                    </div>
                </div>
            </div>
            <!-- End Sticky Notes -->

        </div>
        @else
        <!-- Employee Dashboard -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{translate('messages.welcome')}}, {{auth('admin')->user()->f_name}}.</h1>
                    <p class="page-header-text">{{translate('messages.employee_welcome_message')}}</p>
                </div>
                <div class="col-sm-auto">
                    <div class="text-right">
                        <div id="live-clock" style="font-size: 24px; font-weight: bold; color: #005555;"></div>
                        <div id="live-date" style="font-size: 14px; color: #666;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Quick Stats -->
            <div class="col-lg-8">
                <div class="row g-3">
                    @php
                        $modules = auth('admin')->user()->role ? json_decode(auth('admin')->user()->role->modules, true) : [];
                    @endphp

                    @if(in_array('order', $modules ?? []))
                    <div class="col-sm-6 col-md-4">
                        <a href="{{ route('admin.order.list', ['all']) }}" class="card h-100 text-center p-3" style="text-decoration:none;">
                            <div class="mb-2"><i class="tio-shopping-cart" style="font-size: 40px; color: #005555;"></i></div>
                            <h5 class="mb-1">{{ translate('Orders') }}</h5>
                            <small class="text-muted">{{ translate('Manage Orders') }}</small>
                        </a>
                    </div>
                    @endif

                    @if(in_array('store', $modules ?? []))
                    <div class="col-sm-6 col-md-4">
                        <a href="{{ route('admin.store.list') }}" class="card h-100 text-center p-3" style="text-decoration:none;">
                            <div class="mb-2"><i class="tio-shop" style="font-size: 40px; color: #00aa96;"></i></div>
                            <h5 class="mb-1">{{ translate('Stores') }}</h5>
                            <small class="text-muted">{{ translate('Manage Stores') }}</small>
                        </a>
                    </div>
                    @endif

                    @if(in_array('deliveryman', $modules ?? []))
                    <div class="col-sm-6 col-md-4">
                        <a href="{{ route('admin.users.delivery-man.list') }}" class="card h-100 text-center p-3" style="text-decoration:none;">
                            <div class="mb-2"><i class="tio-user" style="font-size: 40px; color: #ff6b6b;"></i></div>
                            <h5 class="mb-1">{{ translate('Delivery Men') }}</h5>
                            <small class="text-muted">{{ translate('Manage Delivery') }}</small>
                        </a>
                    </div>
                    @endif

                    @if(in_array('customerList', $modules ?? []))
                    <div class="col-sm-6 col-md-4">
                        <a href="{{ route('admin.customer.list') }}" class="card h-100 text-center p-3" style="text-decoration:none;">
                            <div class="mb-2"><i class="tio-users-switch" style="font-size: 40px; color: #ffa726;"></i></div>
                            <h5 class="mb-1">{{ translate('Customers') }}</h5>
                            <small class="text-muted">{{ translate('Manage Customers') }}</small>
                        </a>
                    </div>
                    @endif

                    @if(in_array('report', $modules ?? []))
                    <div class="col-sm-6 col-md-4">
                        <a href="{{ route('admin.report.order-report') }}" class="card h-100 text-center p-3" style="text-decoration:none;">
                            <div class="mb-2"><i class="tio-chart-bar-4" style="font-size: 40px; color: #7c4dff;"></i></div>
                            <h5 class="mb-1">{{ translate('Reports') }}</h5>
                            <small class="text-muted">{{ translate('View Reports') }}</small>
                        </a>
                    </div>
                    @endif

                    @if(in_array('withdraw_list', $modules ?? []))
                    <div class="col-sm-6 col-md-4">
                        <a href="{{ route('admin.store.withdraw_list') }}" class="card h-100 text-center p-3" style="text-decoration:none;">
                            <div class="mb-2"><i class="tio-wallet" style="font-size: 40px; color: #26c6da;"></i></div>
                            <h5 class="mb-1">{{ translate('Withdrawals') }}</h5>
                            <small class="text-muted">{{ translate('Manage Withdrawals') }}</small>
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Sticky Notes -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="tio-bookmark-outlined mr-1"></i> {{ translate('My Notes') }}</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addNewNote()">
                            <i class="tio-add"></i>
                        </button>
                    </div>
                    <div class="card-body p-2" id="sticky-notes-container" style="max-height: 350px; overflow-y: auto;">
                        <!-- Notes loaded via JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Your Permissions -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="tio-key mr-1"></i> {{ translate('Your Access Permissions') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($modules ?? [] as $module)
                    <div class="col-auto mb-2">
                        <span class="badge badge-soft-success p-2">
                            <i class="tio-checkmark-circle mr-1"></i>{{ ucwords(str_replace('_', ' ', $module)) }}
                        </span>
                    </div>
                    @endforeach
                    @if(empty($modules))
                    <div class="col-12 text-center text-muted">
                        {{ translate('No special permissions assigned') }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <!-- End Employee Dashboard -->
        @endif
    </div>
@endsection

@push('script')
    <script src="{{asset('public/assets/admin')}}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/vendor/chart.js.extensions/chartjs-extensions.js"></script>
    <script src="{{asset('public/assets/admin')}}/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js"></script>

    <!-- Apex Charts -->
    <script src="{{asset('/public/assets/admin/js/apex-charts/apexcharts.js')}}"></script>
    <!-- Apex Charts -->

@endpush


@push('script_2')

    <!-- Dognut Pie Chart -->
    <script>
        "use strict";
        let options;
        let chart;
        options = {
            series: [{{ $data['customer']}}, {{$data['stores']}}, {{$data['delivery_man']}}],
            chart: {
                width: 320,
                type: 'donut',
            },
            labels: ['{{ translate('Customer') }}', '{{ translate('Store') }}', '{{ translate('Delivery man') }}'],
            dataLabels: {
                enabled: false,
                style: {
                    colors: ['#005555', '#00aa96', '#b9e0e0',]
                }
            },
            responsive: [{
                breakpoint: 1650,
                options: {
                    chart: {
                        width: 250
                    },
                }
            }],
            colors: ['#005555','#00aa96', '#111'],
            fill: {
                colors: ['#005555','#00aa96', '#b9e0e0']
            },
            legend: {
                show: false
            },
        };

        chart = new ApexCharts(document.querySelector("#dognut-pie"), options);
        chart.render();

    options = {
          series: [{
          name: 'Gross Sale',
          data: [60, 40, 80, 31, 42, 109, 100, 50, 30, 80, 65, 35]
        }],
          chart: {
          height: 350,
          type: 'area',
          toolbar: {
            show:false
        }
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'smooth',
          width: 2,
        },
        fill: {
            type: 'gradient',
            colors: ['#76ffcd'],
        },
        xaxis: {
        //   type: 'datetime',
          categories: ["{{ translate('Jan') }}", "{{ translate('Feb') }}", "{{ translate('Mar') }}", "{{ translate('Apr') }}", "{{ translate('May') }}", "{{ translate('Jun') }}", "{{ translate('Jul') }}", "{{ translate('Aug') }}", "{{ translate('Sep') }}", "{{ translate('Oct') }}", "{{ translate('Nov') }}", "{{ translate('Dec') }}" ]
        },
        tooltip: {
          x: {
            format: 'dd/MM/yy HH:mm'
          },
        },
        };

        chart = new ApexCharts(document.querySelector("#grow-sale-chart"), options);
        chart.render();

    <!-- Dognut Pie Chart -->
        // INITIALIZATION OF CHARTJS
        // =======================================================
        Chart.plugins.unregister(ChartDataLabels);

        $('.js-chart').each(function () {
            $.HSCore.components.HSChartJS.init($(this));
        });

        let updatingChart = $.HSCore.components.HSChartJS.init($('#updatingData'));

        function order_stats_update(type) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.order')}}',
                data: {
                    statistics_type: type
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    insert_param('statistics_type',type);
                    $('#order_stats').html(data.view)
                },
                complete: function () {
                    $('#loading').hide()
                }
            });
        }

        $('.fetch_data_zone_wise').on('change', function (){
            let zone_id = $(this).val();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.zone')}}',
                data: {
                    zone_id: zone_id
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    insert_param('zone_id', zone_id);
                    $('#order_stats').html(data.order_stats);
                    $('#user-overview-board').html(data.user_overview);
                    $('#monthly-earning-graph').html(data.monthly_graph);
                    $('#popular-restaurants-view').html(data.popular_restaurants);
                    $('#top-deliveryman-view').html(data.top_deliveryman);
                    $('#top-rated-foods-view').html(data.top_rated_foods);
                    $('#top-restaurants-view').html(data.top_restaurants);
                    $('#top-selling-foods-view').html(data.top_selling_foods);
                    $('#stat_zone').html(data.stat_zone);
                },
                complete: function () {
                    $('#loading').hide()
                }
            });
        })

        $('.user_overview_stats_update').on('change', function (){
            let type = $(this).val();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.user-overview')}}',
                data: {
                    user_overview: type
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    insert_param('user_overview',type);
                    $('#user-overview-board').html(data.view)
                },
                complete: function () {
                    $('#loading').hide()
                }
            });
        })

        function insert_param(key, value) {
            key = encodeURIComponent(key);
            value = encodeURIComponent(value);
            // kvp looks like ['key1=value1', 'key2=value2', ...]
            let kvp = document.location.search.substr(1).split('&');
            let i = 0;

            for (; i < kvp.length; i++) {
                if (kvp[i].startsWith(key + '=')) {
                    let pair = kvp[i].split('=');
                    pair[1] = value;
                    kvp[i] = pair.join('=');
                    break;
                }
            }
            if (i >= kvp.length) {
                kvp[kvp.length] = [key, value].join('=');
            }
            // can return this or...
            let params = kvp.join('&');
            // change url page with new params
            window.history.pushState('page2', 'Title', '{{url()->current()}}?' + params);
        }

        // Live Clock & Sticky Notes (All Users)
        // Live Clock
        function updateClock() {
            const now = new Date();
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };

            const clockEl = document.getElementById('live-clock');
            const dateEl = document.getElementById('live-date');

            if(clockEl) clockEl.textContent = now.toLocaleTimeString('en-US', timeOptions);
            if(dateEl) dateEl.textContent = now.toLocaleDateString('en-US', dateOptions);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Sticky Notes with LocalStorage
        const NOTES_KEY = 'admin_notes_{{ auth("admin")->user()->id }}';

        function getNotes() {
            const notes = localStorage.getItem(NOTES_KEY);
            return notes ? JSON.parse(notes) : [];
        }

        function saveNotes(notes) {
            localStorage.setItem(NOTES_KEY, JSON.stringify(notes));
        }

        function renderNotes() {
            const container = document.getElementById('sticky-notes-container');
            if(!container) return;

            const notes = getNotes();
            if(notes.length === 0) {
                container.innerHTML = '<div class="text-center text-muted p-3">{{ translate("No notes yet. Click + to add one.") }}</div>';
                return;
            }

            container.innerHTML = notes.map((note, index) => `
                <div class="card mb-2" style="background: ${note.color || '#fff9c4'};">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <small class="text-muted">${note.date}</small>
                            <button class="btn btn-sm btn-link text-danger p-0" onclick="deleteNote(${index})">
                                <i class="tio-clear"></i>
                            </button>
                        </div>
                        <p class="mb-0" style="white-space: pre-wrap; font-size: 13px;">${escapeHtml(note.text)}</p>
                    </div>
                </div>
            `).join('');
        }

        function addNewNote() {
            Swal.fire({
                title: '{{ translate("Add Note") }}',
                html: `
                    <textarea id="note-text" class="form-control mb-2" rows="4" placeholder="{{ translate('Write your note...') }}"></textarea>
                    <select id="note-color" class="form-control">
                        <option value="#fff9c4">{{ translate('Yellow') }}</option>
                        <option value="#c8e6c9">{{ translate('Green') }}</option>
                        <option value="#bbdefb">{{ translate('Blue') }}</option>
                        <option value="#ffccbc">{{ translate('Orange') }}</option>
                        <option value="#f8bbd9">{{ translate('Pink') }}</option>
                    </select>
                `,
                showCancelButton: true,
                confirmButtonText: '{{ translate("Save") }}',
                cancelButtonText: '{{ translate("Cancel") }}',
                preConfirm: () => {
                    const text = document.getElementById('note-text').value.trim();
                    const color = document.getElementById('note-color').value;
                    if(!text) {
                        Swal.showValidationMessage('{{ translate("Please enter a note") }}');
                        return false;
                    }
                    return { text, color };
                }
            }).then((result) => {
                if(result.isConfirmed) {
                    const notes = getNotes();
                    notes.unshift({
                        text: result.value.text,
                        color: result.value.color,
                        date: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
                    });
                    saveNotes(notes);
                    renderNotes();
                    toastr.success('{{ translate("Note added!") }}');
                }
            });
        }

        function deleteNote(index) {
            Swal.fire({
                title: '{{ translate("Delete this note?") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: '{{ translate("Delete") }}',
                cancelButtonText: '{{ translate("Cancel") }}'
            }).then((result) => {
                if(result.isConfirmed) {
                    const notes = getNotes();
                    notes.splice(index, 1);
                    saveNotes(notes);
                    renderNotes();
                    toastr.success('{{ translate("Note deleted!") }}');
                }
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialize notes on page load
        document.addEventListener('DOMContentLoaded', renderNotes);
    </script>
@endpush
