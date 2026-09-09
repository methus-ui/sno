@extends('layouts.admin.app')

@section('title',translate('low_stock_list'))
@section('low_stock_list')
active
@endsection

@push('css_or_js')
<style>
    .product-image {
        width: 130px;
        height: 130px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .modal-product-image {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .qty-btn {
        background: #007bff;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        margin: 2px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .qty-btn:hover {
        background: #0056b3;
    }
    
    .shortcuts-info {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
        font-size: 13px;
    }
    
    .product-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    /* Best Seller Badge Styles */
    .best-seller-badge {
        position: relative;
        display: inline-block;
        background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        color: white;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-left: 8px;
        box-shadow: 0 2px 4px rgba(238, 90, 36, 0.3);
        animation: pulse 2s infinite;
    }
    
    .best-seller-badge:before {
        content: '🔥';
        margin-right: 3px;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 2px 4px rgba(238, 90, 36, 0.3); }
        50% { box-shadow: 0 4px 8px rgba(238, 90, 36, 0.5); }
        100% { box-shadow: 0 2px 4px rgba(238, 90, 36, 0.3); }
    }
    
    .product-name-container {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .best-seller-row {
        background: linear-gradient(135deg, #fff5f5, #ffebeb);
        border-left: 4px solid #ff6b6b;
    }
    
    .best-seller-row:hover {
        background: linear-gradient(135deg, #ffebeb, #ffe0e0);
    }
</style>
@endpush

@section('content')

<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/report.png')}}" class="w--22" alt="">
            </span>
            <span>
                {{translate('low_stock_list')}}
                <span class="badge badge-soft-secondary" id="">{{ $items->total() }}</span>
            </span>
        </h1>
    </div>
    <!-- End Page Header -->
    
    <!-- Card -->
    <div class="card mt-3">
        <!-- Header -->
        <div class="card-header border-0 py-2">
            <div class="search--button-wrapper justify-content-end">
                <form class="search-form">
                    <!-- Search -->
                    <div class="input-group input--group">
                        <input id="datatableSearch" name="search" type="search" class="form-control" placeholder="{{translate('ex_:_search_name')}}" aria-label="{{translate('messages.search_here')}}" value="{{request()->query('search')}}">
                        <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                    </div>
                    <!-- End Search -->
                </form>
                <div class="min--200">
                    <select name="zone_id" class="form-control js-select2-custom set-filter" data-url="{{ url()->full() }}" data-filter="zone_id" id="zone">
                        <option value="all">{{translate('All Zones')}}</option>
                        @foreach(\App\Models\Zone::orderBy('name')->get() as $z)
                            <option value="{{$z['id']}}" {{isset($zone) && $zone->id == $z['id']?'selected':''}}>
                                {{($z['name'])}}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="min--200">
                    <select name="store_id" data-placeholder="{{translate('messages.select_store')}}" class="js-data-example-ajax form-control set-filter" data-url="{{ url()->full() }}" data-filter="store_id">
                        @if(isset($store))
                            <option value="{{$store->id}}" selected>{{$store->name}}</option>
                        @else
                            <option value="all" selected>{{translate('messages.all_stores')}}</option>
                        @endif
                    </select>
                </div>
                <!-- Unfold -->
                <div class="hs-unfold mr-2">
                    <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40" href="javascript:;"
                        data-hs-unfold-options='{
                                "target": "#usersExportDropdown",
                                "type": "css-animation"
                            }'>
                        <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                    </a>

                    <div id="usersExportDropdown"
                        class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                        <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                        <a id="export-excel" class="dropdown-item" href="{{route('admin.transactions.report.stock-wise-report-export', ['type'=>'excel',request()->getQueryString()])}}">
                            <img class="avatar avatar-xss avatar-4by3 mr-2"
                                src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                alt="Image Description">
                            {{ translate('messages.excel') }}
                        </a>
                        <a id="export-csv" class="dropdown-item" href="{{route('admin.transactions.report.stock-wise-report-export', ['type'=>'csv',request()->getQueryString()])}}">
                            <img class="avatar avatar-xss avatar-4by3 mr-2"
                                src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                alt="Image Description">
                            .{{ translate('messages.csv') }}
                        </a>
                    </div>
                </div>
                <!-- End Unfold -->
            </div>
            <!-- End Row -->
        </div>
        <!-- End Header -->

        <!-- Table -->
        <div class="table-responsive datatable-custom" id="table-div">
            <table id="datatable" class="table table-borderless table-thead-bordered table-nowrap card-table">
                <thead class="thead-light">
                    <tr>
                        <th class="border-0">{{translate('sl')}}</th>
                        <th class="border-0 w--2">{{translate('messages.name')}}</th>
                        <th class="border-0 w--2">{{translate('messages.store')}}</th>
                        <th class="border-0">{{translate('messages.zone')}}</th>
                        <th class="border-0">{{translate('Current stock')}}</th>
                        <th class="border-0">{{translate('messages.action')}}</th>
                    </tr>
                </thead>

                <tbody id="set-rows">
                    @foreach($items as $key=>$item)
                    <tr class="{{ $item->is_best_seller ? 'best-seller-row' : '' }}">
                        <td>{{$key+$items->firstItem()}}</td>
                        <td>
                            <a class="media align-items-center" href="{{route('admin.item.view',[$item['id'],'module_id'=>$item['module_id']])}}">
                                <img class="product-image mr-3 onerror-image"
                                    src="{{ $item['image_full_url'] ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                    data-onerror-image="{{asset('public/assets/admin/img/160x160/img2.jpg')}}" 
                                    alt="{{$item->name}} image">
                                <div class="media-body">
                                    <div class="product-name-container">
                                        <h5 class="text-hover-primary mb-0 max-width-200px word-break line--limit-2">{{$item['name']}}</h5>
                                        @if($item->is_best_seller)
                                            <span class="best-seller-badge">Best Seller</span>
                                        @endif
                                    </div>
                                    @if($item->total_sold > 0)
                                        <small class="text-muted">Sold: {{ $item->total_sold }} times</small>
                                    @endif
                                </div>
                            </a>
                        </td>
                        <td>
                            @if($item->store)
                            {{Str::limit($item->store->name,25,'...')}}
                            @else
                            {{translate('messages.store_deleted')}}
                            @endif
                        </td>
                        <td>
                            @if($item->store)
                            {{$item->store?->zone?->name}}
                            @else
                            {{translate('messages.not_found')}}
                            @endif
                        </td>
                        <td>
                            <span class="{{ $item->stock <= 5 ? 'text-danger font-weight-bold' : '' }}">
                                {{$item->stock}}
                            </span>
                        </td>
                        <td>
                            <a class="btn action-btn btn--primary btn-outline-primary update-quantity" href="javascript:" title="{{translate('messages.edit_quantity')}}" data-id="{{ $item->id }}" data-toggle="modal" data-target="#update-quantity"><i class="tio-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
            @if(count($items) !== 0)
            <hr>
            @endif
            <div class="page-area">
                {!! $items->links() !!}
            </div>
            @if(count($items) === 0)
            <div class="empty--data">
                <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                <h5>
                    {{translate('no_data_found')}}
                </h5>
            </div>
            @endif
        <!-- End Table -->
    </div>
    <!-- End Card -->
</div>

<!-- Update Quantity Modal -->
<div class="modal fade update-quantity-modal" id="update-quantity" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{translate('messages.update_stock')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-0">
                <form action="{{route('admin.item.stock-update')}}" method="post" id="stock-form">
                    @csrf
                    
                    <!-- Shortcuts Info -->
                    <div class="shortcuts-info">
                        <strong>Keyboard Shortcuts:</strong> 
                        Press <kbd>Q</kbd> for +20, <kbd>R</kbd> for +10, <kbd>Enter</kbd> to update
                    </div>
                    
                    <div class="mt-2 rest-part w-100"></div>
                    
                    <div class="btn--container justify-content-end">
                        <button type="reset" data-dismiss="modal" aria-label="Close" class="btn btn--reset">{{translate('cancel')}}</button>
                        <button type="submit" id="submit_new_customer" class="btn btn--primary">{{translate('update_stock')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script_2')
<script>
"use strict";

$('.update-quantity').on('click', function (){
    let val = $(this).data('id');
    $.get({
        url: '{{url('/')}}/admin/item/get-variations?id='+val,
        dataType: 'json',
        success: function (data) {
            $('.rest-part').empty().html(data.view);
            addQuantityButtons();
            update_qty();
        },
    });
})

function addQuantityButtons() {
    // Add quantity buttons to each stock input
    $('input[name^="stock_"], input[name="current_stock"]').each(function() {
        let input = $(this);
        let buttonsHtml = `
            <div class="mt-2">
                <button type="button" class="qty-btn" data-qty="5">+5</button>
                <button type="button" class="qty-btn" data-qty="10">+10</button>
                <button type="button" class="qty-btn" data-qty="20">+20</button>
                <button type="button" class="qty-btn" data-qty="50">+50</button>
            </div>
        `;
        input.after(buttonsHtml);
    });
}

// Quantity button clicks
$(document).on('click', '.qty-btn', function() {
    let qty = parseInt($(this).data('qty'));
    let input = $(this).prev('input');
    let currentValue = parseInt(input.val()) || 0;
    input.val(currentValue + qty);
    update_qty();
});

// Keyboard shortcuts
$(document).on('keydown', function(e) {
    if ($('#update-quantity').hasClass('show')) {
        let stockInput = $('input[name="current_stock"]');
        if (stockInput.length && !$(e.target).is('input, textarea, select')) {
            let currentValue = parseInt(stockInput.val()) || 0;
            
            if (e.key.toLowerCase() === 'q') {
                e.preventDefault();
                stockInput.val(currentValue + 20);
                update_qty();
            } else if (e.key.toLowerCase() === 'r') {
                e.preventDefault();
                stockInput.val(currentValue + 10);
                update_qty();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                $('#stock-form').submit();
            }
        }
    }
});

function update_qty() {
    let total_qty = 0;
    let qty_elements = $('input[name^="stock_"]');
    for (let i = 0; i < qty_elements.length; i++) {
        total_qty += parseInt(qty_elements.eq(i).val());
    }
    if(qty_elements.length > 0) {
        $('input[name="current_stock"]').attr("readonly", 'readonly');
        $('input[name="current_stock"]').val(total_qty);
    } else {
        $('input[name="current_stock"]').attr("readonly", false);
    }
}

$(document).ready(function() {
    $('.js-data-example-ajax').select2({
        ajax: {
            url: '{{url('/')}}/admin/store/get-stores',
            data: function(params) {
                return {
                    q: params.term,
                    all:true,
                    @if(isset($zone))
                        zone_ids: [{{$zone->id}}],
                    @endif
                    @if(Config::get('module.current_module_id'))
                    module_id: {{Config::get('module.current_module_id')}},
                    @endif
                    page: params.page
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
        }
    });
});

</script>
@endpush