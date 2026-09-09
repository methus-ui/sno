@extends('layouts.admin.app')

@section('title',translate('messages.Order List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        @php($parcel_order = Request::is('admin/parcel/orders*'))
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-xl-10 col-md-9 col-sm-8 mb-3 mb-sm-0 {{$parcel_order ? 'mb-2':''}}">
                    <h1 class="page-header-title text-capitalize m-0">
                        <span class="page-header-icon">
                            <img src="{{asset('public/assets/admin/img/order.png')}}" class="w--26" alt="">
                        </span>
                        <span>
                            @if ($parcel_order) {{translate('messages.parcel_orders')}}
                            @elseif(Request::is('admin/refund/*') ) {{translate('messages.Refund')}}  {{translate(str_replace('_',' ',$status))}}
                            @else {{translate(str_replace('_',' ',$status))}} {{translate('messages.orders')}}
                            @endif
                            <span class="badge badge-soft-dark ml-2">{{$total}}</span>
                        </span>
                    </h1>
                </div>
            </div>
            <!-- End Row -->
        </div>
        <!-- End Page Header -->



    <div class="card h-100" id="customer-carts-view">
        @include('admin-views.partials._customer-carts', ['latest_carts' => $latest_carts ?? collect(), 'users' => $users ?? collect(), 'items' => $items ?? collect()])
    </div>


<!-- Order Status Tabs -->
<div class="row g-2 mb-3">
    <div class="col-sm-6 col-lg-3">
        <a class="order--card h-100" href="{{url('admin/order/list/pending')}}">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                    <img src="{{asset('public/assets/admin/img/dashboard/grocery/unassigned.svg')}}" alt="dashboard" class="oder--card-icon">
                    <span>Pending</span>
                </h6>
                <span class="card-title text-3F8CE8">
                    {{ $status_counts['pending'] ?? 0 }}
                </span>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a class="order--card h-100" href="{{url('admin/order/list/confirmed')}}">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                    <img src="{{asset('public/assets/admin/img/dashboard/grocery/accepted.svg')}}" alt="dashboard" class="oder--card-icon">
                    <span>Confirmed</span>
                </h6>
                <span class="card-title text-success">
                    {{ $status_counts['confirmed'] ?? 0 }}
                </span>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a class="order--card h-100" href="{{url('admin/order/list/processing')}}">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                    <img src="{{asset('public/assets/admin/img/dashboard/grocery/packaging.svg')}}" alt="dashboard" class="oder--card-icon">
                    <span>Processing</span>
                </h6>
                <span class="card-title text-FFA800">
                    {{ $status_counts['processing'] ?? 0 }}
                </span>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a class="order--card h-100" href="{{url('admin/order/list/item_on_the_way')}}">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                    <img src="{{asset('public/assets/admin/img/dashboard/grocery/out-for.svg')}}" alt="dashboard" class="oder--card-icon">
                    <span>Out for Delivery</span>
                </h6>
                <span class="card-title text-success">
                    {{ $status_counts['item_on_the_way'] ?? 0 }}
                </span>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a class="order--card h-100" href="{{url('admin/order/list/delivered')}}">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                    <img src="{{asset('public/assets/admin/img/dashboard/grocery/delivered.svg')}}" alt="dashboard" class="oder--card-icon">
                    <span>Delivered</span>
                </h6>
                <span class="card-title text-success">
                    {{ $status_counts['delivered'] ?? 0 }}
                </span>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a class="order--card h-100" href="{{url('admin/order/list/canceled')}}">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                    <img src="{{asset('public/assets/admin/img/order-status/canceled.svg')}}" alt="dashboard" class="oder--card-icon">
                    <span>Canceled</span>
                </h6>
                <span class="card-title text-danger">
                    {{ $status_counts['canceled'] ?? 0 }}
                </span>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a class="order--card h-100" href="{{url('admin/order/list/refunded')}}">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                    <img src="{{asset('public/assets/admin/img/order-status/refunded.svg')}}" alt="dashboard" class="oder--card-icon">
                    <span>Refunded</span>
                </h6>
                <span class="card-title text-danger">
                    {{ $status_counts['refunded'] ?? 0 }}
                </span>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a class="order--card h-100" href="{{url('admin/order/list/failed')}}">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                    <img src="{{asset('public/assets/admin/img/order-status/payment-failed.svg')}}" alt="dashboard" class="oder--card-icon">
                    <span>Payment Failed</span>
                </h6>
                <span class="card-title text-danger">
                    {{ $status_counts['failed'] ?? 0 }}
                </span>
            </div>
        </a>
    </div>
</div>
<!-- End Order Status Tabs -->









        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-1 border-0">
                <div class="search--button-wrapper justify-content-end">
                    <form class="search-form min--260">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control h--40px"
                                    placeholder="{{ translate('messages.Ex:') }} 10010" value="{{ request()?->search ?? null}}" aria-label="{{translate('messages.search')}}">
                                    @if($parcel_order)
                                    <input type="hidden" name="parcel_order" value="{{$parcel_order}}">
                                    @endif
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>

                    @if(request()->get('search'))
                        <button type="reset" class="btn btn--primary ml-2 location-reload-to-base" data-url="{{url()->full()}}">{{translate('messages.reset')}}</button>
                    @endif

                    <!-- Datatable Info -->
                    <div id="datatableCounterInfo" class="mr-2 mb-2 mb-sm-0 initial-hidden">
                        <div class="d-flex align-items-center">
                                <span class="font-size-sm mr-3">
                                <span id="datatableCounter">0</span>
                                {{translate('messages.selected')}}
                                </span>
                        </div>
                    </div>

                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle h--40px" href="javascript:;"
                            data-hs-unfold-options='{
                                "target": "#usersExportDropdown",
                                "type": "css-animation"
                            }'>
                            <i class="tio-download-to mr-1"></i> {{translate('messages.export')}}
                        </a>

                        <div id="usersExportDropdown" class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                            <span class="dropdown-header">{{translate('messages.download_options')}}</span>
                            <a id="export-excel" class="dropdown-item" href="javascript:;">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                        src="{{asset('public/assets/admin')}}/svg/components/excel.svg"
                                        alt="Image Description">
                                {{translate('messages.excel')}}
                            </a>
                            <a id="export-csv" class="dropdown-item" href="javascript:;">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                        src="{{asset('public/assets/admin')}}/svg/components/placeholder-csv-format.svg"
                                        alt="Image Description">
                                .{{translate('messages.csv')}}
                            </a>
                        </div>
                    </div>

                    @if(Request::is('admin/refund/*'))
                    <div class="select-item">
                        <select name="slist" class="form-control js-select2-custom refund-filter" >
                            <option {{($status=='requested')?'selected':''}} value="{{ route('admin.refund.refund_attr', ['requested']) }}">{{translate('messages.Refund Requests')}}</option>
                            <option {{($status=='refunded')?'selected':''}} value="{{ route('admin.refund.refund_attr', ['refunded']) }}">{{translate('messages.Refund')}}</option>
                            <option {{($status=='rejected')?'selected':''}} value="{{ route('admin.refund.refund_attr', ['rejected']) }}">{{translate('Rejected')}}</option>
                        </select>
                    </div>
                    @endif

                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white h--40px filter-button-show" href="javascript:;">
                            <i class="tio-filter-list mr-1"></i> {{ translate('messages.filter') }} <span class="badge badge-success badge-pill ml-1" id="filter_count"></span>
                        </a>
                    </div>

                    @if ($status != 'scheduled')
                    <div class="hs-unfold">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white h--40px" href="javascript:;"
                            data-hs-unfold-options='{
                                "target": "#showHideDropdown",
                                "type": "css-animation"
                            }'>
                            <i class="tio-table mr-1"></i> {{translate('messages.columns')}}
                        </a>

                        <div id="showHideDropdown"
                                class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right dropdown-card min--240">
                            <div class="card card-sm">
                                <div class="card-body">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{translate('messages.date')}}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_date">
                                            <input type="checkbox" class="toggle-switch-input"
                                                    id="toggleColumn_date" checked>
                                            <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{translate('messages.customer')}}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm"
                                                for="toggleColumn_customer">
                                            <input type="checkbox" class="toggle-switch-input"
                                                    id="toggleColumn_customer" checked>
                                            <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{$parcel_order?translate('messages.parcel_category'):translate('messages.store')}}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm"
                                                for="toggleColumn_store">
                                            <input type="checkbox" class="toggle-switch-input"
                                                    id="toggleColumn_store" checked>
                                            <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>
                                    @if (!$parcel_order)

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{translate('messages.item_quantity')}}</span>
                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_item_quantity">
                                            <input type="checkbox" class="toggle-switch-input"
                                            id="toggleColumn_item_quantity"   checked>
                                            <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>
                                    @endif

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{translate('messages.total')}}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_total">
                                            <input type="checkbox" class="toggle-switch-input"
                                                    id="toggleColumn_total" checked>
                                            <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{translate('messages.order_status')}}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_order_status">
                                            <input type="checkbox" class="toggle-switch-input"
                                                    id="toggleColumn_order_status" checked>
                                            <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>

                                    <!-- Add Assigned To toggle -->
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{translate('messages.assigned_to')}}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_assigned_to">
                                            <input type="checkbox" class="toggle-switch-input"
                                                    id="toggleColumn_assigned_to" checked>
                                            <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="mr-2">{{translate('messages.actions')}}</span>

                                        <!-- Checkbox Switch -->
                                        <label class="toggle-switch toggle-switch-sm"
                                                for="toggleColumn_actions">
                                            <input type="checkbox" class="toggle-switch-input"
                                                    id="toggleColumn_actions" checked>
                                            <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                        <!-- End Checkbox Switch -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @endif
                    <!-- End Unfold -->
                </div>
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table id="datatable"
                       class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table fz--14px"
                       data-hs-datatables-options='{
                     "columnDefs": [{
                        "targets": [0],
                        "orderable": false
                      }],
                     "order": [],
                     "info": {
                       "totalQty": "#datatableWithPaginationInfoTotalQty"
                     },
                     "search": "#datatableSearch",
                     "entries": "#datatableEntries",
                     "isResponsive": false,
                     "isShowPaging": true,
                     "paging": true,
                     "pageLength": 25,
                     "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]]
                   }'>
                    <thead class="thead-light">
                    <tr>
                        <th class="border-0">
                            {{translate('messages.sl')}}
                        </th>
                        <th class="table-column-pl-0 border-0">{{translate('messages.order_id')}}</th>
                        <th class="border-0">{{translate('messages.order_date')}}</th>
                        @if ($status == 'scheduled')
                        <th class="border-0">{{translate('messages.scheduled_at')}}</th>
                        @endif
                        <th class="border-0">{{translate('messages.customer_information')}}</th>
                        @if ($parcel_order)
                            <th class="border-0">{{translate('messages.parcel_category')}}</th>
                        @else
                            <th class="border-0">{{translate('messages.store')}}</th>
                              <th class="text-center border-0">{{translate('messages.delivery_boy')}}</th>
                        @endif
                        <th class="border-0">{{translate('messages.total_amount')}}</th>

                        @if ($status == 'refunded')
                            <th class="text-center border-0">{{translate('messages.Refunded_order_status')}}</th>
                        @else
                            <th class="text-center border-0">{{translate('messages.order_status')}}</th>
                        @endif
                        <th class="text-center border-0">{{translate('messages.assigned_to')}}</th>
                        
                        <th class="text-center border-0">{{translate('messages.actions')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($orders as $key=>$order)

                        <tr class="status-{{$order['order_status']}} class-all" data-order-id="{{$order['id']}}">
                            <td class="">
                                {{$key+$orders->firstItem()}}
                            </td>
                            <td class="table-column-pl-0">
                                <a href="{{route($parcel_order?'admin.parcel.order.details':'admin.order.details',['id'=>$order['id']])}}">{{$order['id']}}</a>
                            </td>
                            <td>
                                <div>
                                    <div>
                                        {{ \App\CentralLogics\Helpers::date_format($order->created_at) }}
                                    </div>
                                    <div class="d-block text-uppercase">
                                        {{ \App\CentralLogics\Helpers::time_format($order->created_at) }}
                                    </div>
                                </div>
                            </td>
                            @if ($status == 'scheduled')
                            <td>
                                <div>
                                    <div>
                                        {{ \App\CentralLogics\Helpers::date_format($order->schedule_at) }}
                                    </div>
                                    <div class="d-block text-uppercase">
                                        {{ \App\CentralLogics\Helpers::time_format($order->schedule_at) }}
                                    </div>
                                </div>
                            </td>
                            @endif
                            <td>
                                @if($order->is_guest)
                                    @php($customer_details = json_decode($order['delivery_address'],true))
                                    <strong>{{$customer_details['contact_person_name']}}</strong>
                                    <a href="tel:{{$customer_details['contact_person_number']}}">
                                        <div>{{$customer_details['contact_person_number']}}</div>
                                    </a>
                                @elseif($order->customer)

                                    <a class="text-body" href="{{route('admin.customer.view',[$order['user_id']])}}">
                                        <strong>
                                            <div> {{$order->customer['f_name'].' '.$order->customer['l_name']}}</div>
                                        </strong>
                                    </a>
                                    <a href="tel:{{ $order->customer['phone'] }}" class="d-flex align-items-center text-primary text-decoration-none">
                                        <i class="tio-call mr-2"></i> <!-- Call Icon -->
                                        <span>{{ $order->customer['phone'] }}</span>
                                    </a>

                                @else
                                    <label
                                        class="badge badge-danger">{{translate('messages.invalid_customer_data')}}</label>
                                @endif
                            </td>
                                            <td>
                                                @if ($parcel_order)
                                                    <div>
                                                        {{ Str::limit($order->parcel_category ? $order->parcel_category->name : translate('messages.not_found'), 20, '...') }}
                                                    </div>
                                                @elseif ($order->store)
                                                    <div>
                                                        <!-- Store Name -->
                                                        <a class="text--title d-block"
                                                           href="{{ route('admin.store.view', $order->store_id) }}"
                                                           alt="view store">
                                                            {{ Str::limit($order->store ? $order->store->name : translate('messages.store deleted!'), 20, '...') }}
                                                        </a>

                                                        <!-- Store Phone Number -->
                                                        @if(!empty($order->store->phone))
                                                            <div class="d-flex align-items-center text-success text-decoration-none text-truncate mt-1">
                                                                <i class="tio-call mr-1"></i>
                                                                <a href="tel:{{ $order->store->phone }}" class="text-success">
                                                                    {{ $order->store->phone }}
                                                                </a>
                                                            </div>
                                                        @else
                                                            <small class="text-muted">
                                                                {{ translate('messages.no_contact_found') }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div>
                                                        {{ Str::limit(translate('messages.not_found'), 20, '...') }}
                                                    </div>
                                                @endif
                                            </td>



                            <td class="text-center border-0" data-live="delivery_man">
                                @if($order->delivery_man)
                                    <div class="d-flex align-items-center justify-content-center">
                                        <!-- Delivery Man Image -->
                                        <img class="avatar mr-2"
                                            style="width: 55px; height: 55px; border-radius: 20%; object-fit: cover;"
                                            src="{{ asset('storage/app/public/delivery-man') }}/{{ $order->delivery_man->image ?? 'def.png' }}"
                                            alt="{{ $order->delivery_man->f_name }}"
                                            onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'">

                                        <!-- Delivery Man Info -->
                                        <div class="text-left" style="max-width: 120px;">
                                            <!-- Name -->
                                            <div class="font-weight-bold text-truncate"
                                                title="{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}">
                                                {{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}
                                            </div>

                                            <!-- Phone Number with Clickable Call Icon -->
                                            <a href="tel:{{ $order->delivery_man->phone }}"
                                            class="d-flex align-items-center text-success text-decoration-none text-truncate"
                                            title="{{ $order->delivery_man->phone }}">
                                                <i class="tio-call mr-1"></i>
                                                <span>{{ $order->delivery_man->phone ?? '' }}</span>
                                            </a>
                                        </div>
                                    </div>
                                    <!-- Bill Status -->
                                    <div class="mt-1">
                                        @if($order->is_billed)
                                            <span class="badge badge-soft-success" title="{{ $order->billed_at ? \Carbon\Carbon::parse($order->billed_at)->format('d M Y, h:i A') : '' }}">
                                                <i class="tio-receipt mr-1"></i>{{ translate('messages.billed') }}
                                            </span>
                                        @else
                                            <span class="badge badge-soft-danger">
                                                <i class="tio-receipt mr-1"></i>{{ translate('messages.not_billed') }}
                                            </span>
                                        @endif
                                    </div>
                                    {{-- ✅ PERFORMANCE FIX: DM status badge without heavy calculations
                                        Distance calculations moved to live polling AJAX to reduce initial page load time --}}
                                    @if($order->delivery_man && $order->delivery_man->last_location && !in_array($order->order_status, ['delivered', 'canceled', 'refunded', 'failed']))
                                        <div class="mt-1" data-dm-status="{{$order->id}}">
                                            <span class="badge badge-soft-success">
                                                <i class="tio-run mr-1"></i>{{ translate('messages.moving') }}
                                            </span>
                                        </div>
                                    @endif
                                @else
                                    <!-- Not assigned badge -->
                                    <span class="badge badge-soft-warning">
                                        {{ translate('messages.not_assigned') }}
                                    </span>
                                @endif
                            </td>


                            <td data-live="amount">
                                <div class="text-right mw--85px">
                                    <div>
                                        {{\App\CentralLogics\Helpers::format_currency($order['order_amount'])}}
                                    </div>
                                    @if($order->payment_status=='paid')
                                    <strong class="text-success">
                                        {{translate('messages.paid')}}
                                    </strong>
                                    @elseif($order->payment_status=='partially_paid')
                                    <strong class="text-success">
                                        {{translate('messages.partially_paid')}}
                                    </strong>
                                    @else
                                    <strong class="text-danger">
                                        {{translate('messages.unpaid')}}
                                    </strong>
                                    @endif
                                </div>
                            </td>
                            <td class="text-capitalize text-center" data-live="order_status">
                                @if($order['order_status']=='pending')
                                    <span class="badge badge-soft-info">
                                      {{translate('messages.pending')}}
                                    </span>
                                @elseif($order['order_status']=='confirmed')
                                    <span class="badge badge-soft-info">
                                      {{translate('messages.confirmed')}}
                                    </span>
                                @elseif($order['order_status']=='processing')
                                    <span class="badge badge-soft-warning">
                                      {{translate('messages.processing')}}
                                    </span>
                                @elseif($order['order_status']=='picked_up')
                                    <span class="badge badge-soft-warning">
                                      {{translate('messages.out_for_delivery')}}
                                    </span>
                                @elseif($order['order_status']=='delivered')
                                    <span class="badge badge-soft-success">
                                      {{translate('messages.delivered')}}
                                    </span>
                                @elseif($order['order_status']=='failed')
                                    <span class="badge badge-soft-danger">
                                      {{translate('messages.payment_failed')}}
                                    </span>
                                @elseif($order['order_status']=='handover')
                                    <span class="badge badge-soft-danger">
                                      {{translate('messages.handover')}}
                                    </span>
                                @elseif($order['order_status']=='canceled')
                                    <span class="badge badge-soft-danger">
                                      {{translate('messages.canceled')}}
                                    </span>
                                @elseif($order['order_status']=='accepted')
                                    <span class="badge badge-soft-danger">
                                      {{translate('messages.accepted')}}
                                    </span>
                                @elseif($order['order_status']=='refund_requested')
                                    <span class="badge badge-soft-danger">
                                      {{translate('messages.refund_requested')}}
                                    </span>
                                @else
                                    <span class="badge badge-soft-danger">
                                      {{str_replace('_',' ',$order['order_status'])}}
                                    </span>
                                @endif
                                @if($order['order_type']=='take_away')
                                    <div class="text-info mt-1">
                                        {{translate('messages.take_away')}}
                                    </div>
                                @else
                                    <div class="text-title mt-1">
                                      {{translate('messages.home Delivery')}}
                                    </div>
                                @endif
                            </td>
                             <td class="text-center">
                                @if($order->assignedAdmin && in_array(auth('admin')->user()->role_id, [1, 2]))
                                    <!-- Only roles 1 and 2 can reassign already assigned orders -->
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                                id="assignedToBtn_{{$order['id']}}">
                                            {{$order->assignedAdmin->f_name}} {{$order->assignedAdmin->l_name}}
                                        </button>
                                        <div class="dropdown-menu">
                                            @foreach($admins as $admin)
                                                @if($admin->id != $order->assigned_to)
                                                <a class="dropdown-item assign-order"
                                                href="javascript:void(0)"
                                                data-order-id="{{$order['id']}}"
                                                data-admin-id="{{$admin->id}}"
                                                data-admin-name="{{$admin->f_name}} {{$admin->l_name}}">
                                                    {{$admin->f_name}} {{$admin->l_name}}
                                                </a>
                                                @endif
                                            @endforeach
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger assign-order"
                                            href="javascript:void(0)"
                                            data-order-id="{{$order['id']}}"
                                            data-admin-id=""
                                            data-admin-name="{{translate('messages.unassigned')}}">
                                                {{translate('messages.unassign')}}
                                            </a>
                                        </div>
                                    </div>
                                @elseif(!$order->assignedAdmin)
                                    <!-- Any role can assign unassigned orders -->
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                                id="assignedToBtn_{{$order['id']}}">
                                            {{translate('messages.assign_to')}}
                                        </button>
                                        <div class="dropdown-menu">
                                            @foreach($admins as $admin)
                                                <a class="dropdown-item assign-order"
                                                href="javascript:void(0)"
                                                data-order-id="{{$order['id']}}"
                                                data-admin-id="{{$admin->id}}"
                                                data-admin-name="{{$admin->f_name}} {{$admin->l_name}}">
                                                    {{$admin->f_name}} {{$admin->l_name}}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <!-- Display read-only assignment status for other roles -->
                                    <div class="text-center">
                                        <span class="badge badge-soft-primary">
                                            {{$order->assignedAdmin->f_name}} {{$order->assignedAdmin->l_name}}
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="ml-2 btn btn-sm btn--warning btn-outline-warning action-btn" href="{{route($parcel_order?'admin.parcel.order.details':'admin.order.details',['id'=>$order['id']])}}">
                                        <i class="tio-invisible"></i>
                                    </a>
                                    <a class="ml-2 btn btn-sm btn--primary btn-outline-primary action-btn" href="{{route($parcel_order?'admin.order.generate-invoice':'admin.order.generate-invoice',['id'=>$order['id']])}}">
                                        <i class="tio-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- End Table -->


            @if(count($orders) !== 0)
            <hr>
            @endif
            <div class="page-area">
                {!! $orders->appends($_GET)->links() !!}
            </div>
            @if(count($orders) === 0)
            <div class="empty--data">
                <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                <h5>
                    {{translate('no_data_found')}}
                </h5>
            </div>
            @endif
        </div>
        <!-- End Card -->
        <!-- Order Filter Modal -->
        <div id="datatableFilterSidebar" class="hs-unfold-content_ sidebar sidebar-bordered sidebar-box-shadow initial-hidden">
            <div class="card card-lg sidebar-card sidebar-footer-fixed">
                <div class="card-header">
                    <h4 class="card-header-title">{{translate('messages.order_filter')}}</h4>

                    <!-- Toggle Button -->
                    <a class="js-hs-unfold-invoker_ btn btn-icon btn-sm btn-ghost-dark ml-2 filter-button-hide" href="javascript:;">
                        <i class="tio-clear tio-lg"></i>
                    </a>
                    <!-- End Toggle Button -->
                </div>
                <?php
                $filter_count=0;
                if(isset($zone_ids) && count($zone_ids) > 0) $filter_count += 1;
                if(isset($vendor_ids) && count($vendor_ids)>0) $filter_count += 1;
                if($status=='all')
                {
                    if(isset($orderstatus) && count($orderstatus) > 0) $filter_count += 1;
                    if(isset($scheduled) && $scheduled == 1) $filter_count += 1;
                }

                if(isset($from_date) && isset($to_date)) $filter_count += 1;
                if(isset($order_type)) $filter_count += 1;

                ?>
                <!-- Body -->
                <form class="card-body sidebar-body sidebar-scrollbar" action="{{route('admin.order.filter')}}" method="POST" id="order_filter_form">
                    @csrf
                    <small class="text-cap mb-3">{{translate('messages.zone')}}</small>

                    <div class="mb-2 initial--21">
                        <select name="zone[]" id="zone_ids" class="form-control js-select2-custom" multiple="multiple">
                        @foreach(\App\Models\Zone::all() as $zone)
                            <option value="{{$zone->id}}" {{isset($zone_ids)?(in_array($zone->id, $zone_ids)?'selected':''):''}}>{{$zone->name}}</option>
                        @endforeach
                        </select>
                    </div>
                    @if (!$parcel_order)
                        <hr class="my-4">
                        <small class="text-cap mb-3">{{translate('messages.store')}}</small>
                        <div class="mb-2 initial--21">
                            <select name="vendor[]" id="vendor_ids" class="form-control js-select2-custom" multiple="multiple">
                            @foreach(\App\Models\Store::whereIn('id', $vendor_ids)->get() as $store)
                                <option value="{{$store->id}}" selected >{{$store->name}}</option>
                            @endforeach
                            </select>
                        </div>
                    @endif


                    <hr class="my-4">
                    @if($status == 'all')
                    <small class="text-cap mb-3">{{translate('messages.order_status')}}</small>

                    <!-- Custom Checkbox -->
                    <div class="custom-control custom-radio mb-2">
                        <input type="checkbox" id="orderStatus2" name="orderStatus[]" class="custom-control-input" {{isset($orderstatus)?(in_array('pending', $orderstatus)?'checked':''):''}} value="pending">
                        <label class="custom-control-label" for="orderStatus2">{{translate('messages.pending')}}</label>
                    </div>
                    <div class="custom-control custom-radio mb-2">
                        <input type="checkbox" id="orderStatus1" name="orderStatus[]" class="custom-control-input" value="confirmed" {{isset($orderstatus)?(in_array('confirmed', $orderstatus)?'checked':''):''}}>
                        <label class="custom-control-label" for="orderStatus1">{{translate('messages.confirmed')}}</label>
                    </div>
                    <div class="custom-control custom-radio mb-2">
                        <input type="checkbox" id="orderStatus3" name="orderStatus[]" class="custom-control-input" value="processing" {{isset($orderstatus)?(in_array('processing', $orderstatus)?'checked':''):''}}>
                        <label class="custom-control-label" for="orderStatus3">{{translate('messages.processing')}}</label>
                    </div>
                    <div class="custom-control custom-radio mb-2">
                        <input type="checkbox" id="orderStatus4" name="orderStatus[]" class="custom-control-input" value="picked_up" {{isset($orderstatus)?(in_array('picked_up', $orderstatus)?'checked':''):''}}>
                        <label class="custom-control-label" for="orderStatus4">{{translate('messages.out_for_delivery')}}</label>
                    </div>
                    <div class="custom-control custom-radio mb-2">
                        <input type="checkbox" id="orderStatus5" name="orderStatus[]" class="custom-control-input" value="delivered" {{isset($orderstatus)?(in_array('delivered', $orderstatus)?'checked':''):''}}>
                        <label class="custom-control-label" for="orderStatus5">{{translate('messages.delivered')}}</label>
                    </div>

                    <div class="custom-control custom-radio mb-2">
                        <input type="checkbox" id="orderStatus7" name="orderStatus[]" class="custom-control-input" value="failed" {{isset($orderstatus)?(in_array('failed', $orderstatus)?'checked':''):''}}>
                        <label class="custom-control-label" for="orderStatus7">{{translate('messages.failed')}}</label>
                    </div>
                    <div class="custom-control custom-radio mb-2">
                        <input type="checkbox" id="orderStatus8" name="orderStatus[]" class="custom-control-input" value="canceled" {{isset($orderstatus)?(in_array('canceled', $orderstatus)?'checked':''):''}}>
                        <label class="custom-control-label" for="orderStatus8">{{translate('messages.canceled')}}</label>
                    </div>
                    @if (!$parcel_order)
                    <div class="custom-control custom-radio mb-2">
                        <input type="checkbox" id="orderStatus9" name="orderStatus[]" class="custom-control-input" value="refund_requested" {{isset($orderstatus)?(in_array('refund_requested', $orderstatus)?'checked':''):''}}>
                        <label class="custom-control-label" for="orderStatus9">{{translate('messages.refundRequest')}}</label>
                    </div>
                    <div class="custom-control custom-radio mb-2">
                        <input type="checkbox" id="orderStatus10" name="orderStatus[]" class="custom-control-input" value="refunded" {{isset($orderstatus)?(in_array('refunded', $orderstatus)?'checked':''):''}}>
                        <label class="custom-control-label" for="orderStatus10">{{translate('messages.refunded')}}</label>
                    </div>
                    @endif

                    <hr class="my-4">

                    <div class="custom-control custom-radio mb-2">
                        <input type="checkbox" id="scheduled" name="scheduled" class="custom-control-input" value="1" {{isset($scheduled)?($scheduled==1?'checked':''):''}}>
                        <label class="custom-control-label text-uppercase" for="scheduled">{{translate('messages.scheduled')}}</label>
                    </div>
                    @endif
                    @if (!$parcel_order)
                        <hr class="my-4">
                        <small class="text-cap mb-3">{{translate('messages.order_type')}}</small>
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="take_away" name="order_type" class="custom-control-input" value="take_away" {{isset($order_type)?($order_type=='take_away'?'checked':''):''}}>
                            <label class="custom-control-label text-uppercase" for="take_away">{{translate('messages.take_away')}}</label>
                        </div>
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="delivery" name="order_type" class="custom-control-input" value="delivery" {{isset($order_type)?($order_type=='delivery'?'checked':''):''}}>
                            <label class="custom-control-label text-uppercase" for="delivery">{{translate('messages.delivery')}}</label>
                        </div>
                    @endif

                    <hr class="my-4">

                    <small class="text-cap mb-3">{{translate('messages.date_between')}}</small>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group m-0">
                                <input type="date" name="from_date" class="form-control" id="date_from" value="{{isset($from_date)?$from_date:''}}">
                            </div>
                        </div>
                        <div class="col-12 text-center">----{{ translate('messages.to') }}----</div>
                        <div class="col-12">
                            <div class="form-group">
                                <input type="date" name="to_date" class="form-control" id="date_to" value="{{isset($to_date)?$to_date:''}}">
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer sidebar-footer">
                        <div class="row gx-2">
                            <div class="col">
                                <button type="reset" class="btn btn-block btn-white" id="reset">{{ translate('Clear all filters') }}</button>
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-block btn-primary">{{ translate('messages.save') }}</button>
                            </div>
                        </div>
                    </div>
                    <!-- End Footer -->
                </form>
            </div>
        </div>
        <!-- End Order Filter Modal -->
@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/order-list.js"></script>
    <script>
        "use strict";
        $(document).on('ready', function () {
            @if($filter_count>0)
            $('#filter_count').html({{$filter_count}});
            @endif

            $('#vendor_ids').select2({
                ajax: {
                    url: '{{url('/')}}/admin/store/get-stores',
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            zone_ids: zone_id,
                            page: params.page
                        };
                    },
                    processResults: function (data) {
                        return {
                        results: data
                        };
                    },
                    __port: function (params, success, failure) {
                        let $request = $.ajax(params);

                        $request.then(success);
                        $request.fail(failure);

                        return $request;
                    }
                }
            });
             $('.assign-order').on('click', function() {
                var orderId = $(this).data('order-id');
                var adminId = $(this).data('admin-id');
                var adminName = $(this).data('admin-name');
                
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                
                $.ajax({
                    url: '{{route('admin.order.assign')}}',
                    method: 'POST',
                    data: {
                        order_id: orderId,
                        admin_id: adminId
                    },
                    success: function(response) {
                        if(response.success) {
                            $('#assignedToBtn_' + orderId).text(adminName);
                            toastr.success(response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 403) {
                            var response = JSON.parse(xhr.responseText);
                            toastr.error(response.message);
                        } else {
                            toastr.error('{{translate("messages.something_went_wrong")}}');
                        }
                    }
                });
            });


            // INITIALIZATION OF DATATABLES
            // =======================================================
            let datatable = $.HSCore.components.HSDatatables.init($('#datatable'), {
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copy',
                        className: 'd-none'
                    },
                    {
                        extend: 'excel',
                        className: 'd-none',
                        action: function (e, dt, node, config)
                        {
                            window.location.href = '{{route("admin.order.export",['status'=>$status,'file_type'=>'excel','type'=>$parcel_order?'parcel':'order', request()->getQueryString()])}}';
                        }
                    },
                    {
                        extend: 'csv',
                        className: 'd-none',
                        action: function (e, dt, node, config)
                        {
                            window.location.href = '{{route("admin.order.export",['status'=>$status,'file_type'=>'csv','type'=>$parcel_order?'parcel':'order', request()->getQueryString()])}}';
                        }
                    },
                    // {
                    //     extend: 'pdf',
                    //     className: 'd-none'
                    // },
                    {
                        extend: 'print',
                        className: 'd-none'
                    },
                ],
                select: {
                    style: 'multi',
                    selector: 'td:first-child input[type="checkbox"]',
                    classMap: {
                        checkAll: '#datatableCheckAll',
                        counter: '#datatableCounter',
                        counterInfo: '#datatableCounterInfo'
                    }
                },
                language: {
                    zeroRecords: '<div class="text-center p-4">' +
                        '<img class="w-7rem mb-3" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="Image Description">' +

                        '</div>'
                }
            });
            $('#export-copy').click(function () {
                datatable.button('.buttons-copy').trigger()
            });

            $('#export-excel').click(function () {
                datatable.button('.buttons-excel').trigger()
            });

            $('#export-csv').click(function () {
                datatable.button('.buttons-csv').trigger()
            });

            $('#export-print').click(function () {
                datatable.button('.buttons-print').trigger()
            });

            $('#datatableSearch').on('mouseup', function (e) {
                let $input = $(this),
                    oldValue = $input.val();

                if (oldValue == "") return;

                setTimeout(function () {
                    let newValue = $input.val();

                    if (newValue == "") {
                        // Gotcha
                        datatable.search('').draw();
                    }
                }, 1);
            });

            // $('#toggleColumn_order').change(function (e) {
            //     datatable.columns(1).visible(e.target.checked)
            // })

            $('#toggleColumn_date').change(function (e) {
                datatable.columns(2).visible(e.target.checked)
            })

            $('#toggleColumn_customer').change(function (e) {
                datatable.columns(3).visible(e.target.checked)
            })
            $('#toggleColumn_store').change(function (e) {
                datatable.columns(4).visible(e.target.checked)
            })

            $('#toggleColumn_item_quantity').change(function (e) {
                datatable.columns(5).visible(e.target.checked)
            })


            $('#toggleColumn_total').change(function (e) {
                datatable.columns({{$parcel_order ? '5': '6'  }}).visible(e.target.checked)
            })
            $('#toggleColumn_order_status').change(function (e) {
                datatable.columns({{$parcel_order ? '6': '7'  }} ).visible(e.target.checked)
            })

            $('#toggleColumn_actions').change(function (e) {
                datatable.columns({{$parcel_order ? '7': '8'  }}).visible(e.target.checked)
            })
        });


        $('#reset').on('click', function(){
            // e.preventDefault();
            location.href = '{{url('/')}}/admin/order/filter/reset';
        });

        $('#search-form').on('submit', function (e) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.get({
                url: '{{route('admin.order.search')}}',
                data: $(this).serialize(),
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    $('#set-rows').html(data.view);
                    $('.page-area').hide();
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        });

        // ── Live order status polling ──
        var statusMap = {
            'pending':          {cls: 'badge-soft-info',    label: '{{ translate("messages.pending") }}'},
            'confirmed':        {cls: 'badge-soft-info',    label: '{{ translate("messages.confirmed") }}'},
            'processing':       {cls: 'badge-soft-warning', label: '{{ translate("messages.processing") }}'},
            'picked_up':        {cls: 'badge-soft-warning', label: '{{ translate("messages.out_for_delivery") }}'},
            'delivered':        {cls: 'badge-soft-success', label: '{{ translate("messages.delivered") }}'},
            'failed':           {cls: 'badge-soft-danger',  label: '{{ translate("messages.payment_failed") }}'},
            'handover':         {cls: 'badge-soft-danger',  label: '{{ translate("messages.handover") }}'},
            'canceled':         {cls: 'badge-soft-danger',  label: '{{ translate("messages.canceled") }}'},
            'accepted':         {cls: 'badge-soft-danger',  label: '{{ translate("messages.accepted") }}'},
            'refund_requested': {cls: 'badge-soft-danger',  label: '{{ translate("messages.refund_requested") }}'}
        };
        var paymentLabels = {
            'paid':           {cls: 'text-success', label: '{{ translate("messages.paid") }}'},
            'partially_paid': {cls: 'text-success', label: '{{ translate("messages.partially_paid") }}'},
            'unpaid':         {cls: 'text-danger',  label: '{{ translate("messages.unpaid") }}'}
        };
        var dmImageBase = '{{ asset("storage/app/public/delivery-man") }}';
        var dmFallback = '{{ asset("public/assets/admin/img/160x160/img1.jpg") }}';

        function pollOrderStatuses() {
            var ids = [];
            $('#set-rows tr[data-order-id]').each(function() {
                ids.push($(this).data('order-id'));
            });
            if (ids.length === 0) return;

            $.ajax({
                url: '{{ route("admin.orders-live-status") }}',
                method: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {order_ids: ids},
                dataType: 'json',
                success: function(data) {
                    $.each(data, function(orderId, info) {
                        var $row = $('#set-rows tr[data-order-id="' + orderId + '"]');
                        if (!$row.length) return;

                        // Update order status
                        var $statusTd = $row.find('td[data-live="order_status"]');
                        if ($statusTd.length) {
                            var s = statusMap[info.order_status] || {cls: 'badge-soft-danger', label: info.order_status.replace(/_/g, ' ')};
                            $statusTd.find('.badge').first().attr('class', 'badge ' + s.cls).text(s.label);
                            // Flash on change
                            var oldStatus = $row.attr('class').match(/status-(\S+)/);
                            if (oldStatus && oldStatus[1] !== info.order_status) {
                                $row.attr('class', 'status-' + info.order_status + ' class-all');
                                $statusTd.css('background', '#fff3cd');
                                setTimeout(function() { $statusTd.css('background', ''); }, 2000);
                            }
                        }

                        // Update payment status
                        var $amountTd = $row.find('td[data-live="amount"]');
                        if ($amountTd.length) {
                            $amountTd.find('div > div').first().text(info.order_amount);
                            var p = paymentLabels[info.payment_status] || paymentLabels['unpaid'];
                            $amountTd.find('strong').attr('class', p.cls).text(p.label);
                        }

                        // Update delivery man + bill status
                        @if(!$parcel_order)
                        var $dmTd = $row.find('td[data-live="delivery_man"]');
                        if ($dmTd.length) {
                            var html = '';
                            if (info.delivery_man) {
                                var dm = info.delivery_man;
                                html += '<div class="d-flex align-items-center justify-content-center">';
                                html += '<img class="avatar mr-2" style="width:55px;height:55px;border-radius:20%;object-fit:cover;" src="' + dmImageBase + '/' + (dm.image || 'def.png') + '" alt="' + dm.name + '" onerror="this.src=\'' + dmFallback + '\'">';
                                html += '<div class="text-left" style="max-width:120px;">';
                                html += '<div class="font-weight-bold text-truncate" title="' + dm.name + '">' + dm.name + '</div>';
                                html += '<a href="tel:' + dm.phone + '" class="d-flex align-items-center text-success text-decoration-none text-truncate" title="' + dm.phone + '"><i class="tio-call mr-1"></i><span>' + (dm.phone || '') + '</span></a>';
                                html += '</div></div>';
                                // Bill status
                                html += '<div class="mt-1">';
                                if (info.is_billed) {
                                    html += '<span class="badge badge-soft-success" title="' + (info.billed_at || '') + '"><i class="tio-receipt mr-1"></i>{{ translate("messages.billed") }}</span>';
                                } else {
                                    html += '<span class="badge badge-soft-danger"><i class="tio-receipt mr-1"></i>{{ translate("messages.not_billed") }}</span>';
                                }
                                html += '</div>';
                            } else {
                                html = '<span class="badge badge-soft-warning">{{ translate("messages.not_assigned") }}</span>';
                            }
                            $dmTd.html(html);
                        }
                        @endif
                    });
                }
            });
        }

        setInterval(pollOrderStatuses, 10000);
        // Run first poll after 3s
        setTimeout(pollOrderStatuses, 3000);
    </script>
@endpush
