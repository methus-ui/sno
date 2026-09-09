@extends('layouts.admin.app')

@section('title', translate('Order Details'))

@section('content')
    <?php
    $deliverman_tips = 0;
    $campaign_order = isset($order?->details[0]?->item_campaign_id )  ? true : false;
    $reasons=\App\Models\OrderCancelReason::where('status', 1)->where('user_type' ,'admin' )->get();
    $parcel_order = $order->order_type == 'parcel' ? true : false;
    $tax_included =0;
    $max_processing_time = $order->store?explode('-', $order->store['delivery_time'])[0]:0;
    ?>
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <span class="page-header-icon">
                            <img src="{{ asset('/public/assets/admin/img/shopping-basket.png') }}" class="w--20"
                                 alt="">
                        </span>
                        <span>
                            {{ translate('order_details') }} <span
                                class="badge badge-soft-dark rounded-circle ml-1">{{ $order->details->count() }}</span>
                        </span>
                    </h1>
                </div>

                <div class="col-sm-auto">
                    <a class="btn-icon btn-sm btn-soft-secondary rounded-circle mr-1"
                       href="{{ route('admin.order.details', [$order['id'] - 1]) }}" data-toggle="tooltip"
                       data-placement="top" title="{{ translate('Previous order') }}">
                        <i class="tio-chevron-left"></i>
                    </a>
                    <a class="btn-icon btn-sm btn-soft-secondary rounded-circle"
                       href="{{ route('admin.order.details', [$order['id'] + 1]) }}" data-toggle="tooltip"
                       data-placement="top" title="{{ translate('Next order') }}">
                        <i class="tio-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- Page Header -->

        @php
            $refund_amount = $order->order_amount - $order->delivery_charge - $order->dm_tips;
        @endphp
        <div class="row flex-xl-nowrap" id="printableArea">
            <div class="col-lg-8 order-print-area-left">
                <!-- Card -->
                <div class="card mb-3 mb-lg-5">
                    <!-- Header -->
                    <div class="card-header border-0 align-items-start flex-wrap">
                        <div class="order-invoice-left d-flex d-sm-block justify-content-between">
                            <div>
                                <h1 class="page-header-title d-flex align-items-center __gap-5px">
                                    {{ translate('messages.order') }} #{{ $order['id'] }}
                                    @if ($campaign_order)
                                        <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.campaign_order') }}
                                        </span>
                                    @endif
                                    @if ($order->edited)
                                        <span class="badge badge-soft-dark ml-sm-3">
                                            {{ translate('messages.edited') }}
                                        </span>
                                    @endif
                                </h1>
                                <span class="mt-2 d-block d-flex align-items-center __gap-5px">
                                    <i class="tio-date-range"></i>
                                    {{ date('d M Y ' . config('timeformat'), strtotime($order['created_at'])) }}
                                </span>
                                @if (!$parcel_order)
                                    <h6 class="mt-2 pt-1 mb-2 d-flex align-items-center __gap-5px">
                                        <i class="tio-shop"></i>
                                        <span>{{ translate('messages.store') }}</span> <span>:</span> <span
                                            class="badge badge-soft-primary">{{ Str::limit($order->store ? $order->store->name : translate('messages.store deleted!'), 25, '...') }}</span>
                                    </h6>
                                @endif
                                @if ($order->schedule_at && $order->scheduled)
                                    <h6 class="text-capitalize d-flex align-items-center __gap-5px">
                                        <span>{{ translate('messages.scheduled_at') }}</span>
                                        <span>:</span> <label
                                            class="fz--10 badge badge-soft-warning">{{ date('d M Y ' . config('timeformat'), strtotime($order['schedule_at'])) }}</label>
                                    </h6>
                                @endif
                                @if ($order->coupon)
                                    <h6 class="text-capitalize d-flex align-items-center __gap-5px"><span>{{ translate('messages.coupon') }}</span>
                                        <span>:</span> <label class="fz--10 badge badge-soft-primary">{{ $order->coupon_code }}
                                            ({{ translate('messages.' . $order->coupon->coupon_type) }})</label>
                                    </h6>
                                @endif
                                <div class="hs-unfold mt-1">
                                    <h5>
                                        <button
                                            class="btn order--details-btn-sm btn--primary btn-outline-primary btn--sm font-regular d-flex align-items-center __gap-5px"
                                            data-toggle="modal" data-target="#locationModal"><i class="tio-poi"></i>
                                            {{ translate('messages.show_locations_on_map') }}</button>
                                    </h5>
                                </div>
                                @if($order['cancellation_reason'])
                                    <h6 class="text-capitalize my-2 ml-2">
                                        <span class="text-danger">{{ translate('messages.Cancelled_By') }} :</span>
                                        {{ $order['canceled_by'] }}
                                    </h6>
                                    <h6 class=" my-2 ml-2">
                                        <span class="text-danger">{{ translate('messages.order_cancellation_reason') }} :</span>
                                        {{ $order['cancellation_reason'] }}
                                    </h6>
                                @endif
                                @if ($order['unavailable_item_note'])
                                    <h6 class="w-100 badge-soft-warning">
                                        <span class="text-dark">
                                            {{ translate('messages.order_unavailable_item_note') }} :
                                        </span>
                                        {{ $order['unavailable_item_note'] }}
                                    </h6>
                                @endif
                                @if ($order['delivery_instruction'])
                                    <h6 class="w-100 badge-soft-warning">
                                        <span class="text-dark">
                                            {{ translate('messages.order_delivery_instruction') }} :
                                        </span>
                                        {{ $order['delivery_instruction'] }}
                                    </h6>
                                @endif
                                @if ($order['order_note'])
                                    <h6>
                                        {{ translate('messages.order_note') }} :
                                        {{ $order['order_note'] }}
                                    </h6>
                                @endif
                                @if($order?->offline_payments && $order?->offline_payments->status == 'denied' && $order?->offline_payments->note )
                                    <h6 class="w-100 badge-soft-warning">
                                    <span class="text-dark">
                                        {{ translate('messages.Offline_payment_rejection_note') }} :
                                    </span>
                                        {{  $order?->offline_payments->note }}
                                    </h6>
                                @endif
                            </div>
                            <div class="d-sm-none">
                                <a class="btn btn--primary print--btn font-regular d-flex align-items-center __gap-5px"
                                   href={{ route('admin.order.generate-invoice', [$order['id']]) }}>
                                    <i class="tio-print mr-sm-1"></i> <span>{{ translate('messages.print_invoice') }}</span>
                                </a>
                            </div>
                        </div>
                        <div class="order-invoice-right mt-3 mt-sm-0">
                            <div class="btn--container ml-auto align-items-center justify-content-end">

                                @if (  !$parcel_order &&  !$editing && in_array($order->order_status, ['pending', 'confirmed', 'processing', 'accepted']) &&
                                        isset($order->store) && !$campaign_order &&
                                        $order->prescription_order == 0 && count($order?->payments) == 0 && $order?->ref_bonus_amount == 0 && $order?->flash_admin_discount_amount == 0 && ($order->payment_method == 'cash_on_delivery'))
                                    <button class="btn btn-sm btn--danger btn-outline-danger font-regular edit-order" type="button">
                                        <i class="tio-edit"></i> {{ translate('messages.edit') }}
                                    </button>
                                @endif
                                <a class="btn btn--primary print--btn font-regular d-none d-sm-block"
                                   href={{ route('admin.order.generate-invoice', [$order['id']]) }}>
                                    <i class="tio-print mr-sm-1"></i> <span>{{ translate('messages.print_invoice') }}</span>
                                </a>
                            </div>
                            <div class="text-right mt-3 order-invoice-right-contents text-capitalize">
                                <h6>
                                    <span>{{ translate('status') }}</span> <span>:</span>
                                    @if ($order['order_status'] == 'pending')
                                        <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.pending') }}
                                        </span>
                                    @elseif($order['order_status'] == 'confirmed')
                                        <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.confirmed') }}
                                        </span>
                                    @elseif($order['order_status'] == 'processing')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.processing') }}
                                        </span>
                                    @elseif($order['order_status'] == 'picked_up')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.out_for_delivery') }}
                                        </span>
                                    @elseif($order['order_status'] == 'delivered')
                                        <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.delivered') }}
                                        </span>
                                    @elseif($order['order_status'] == 'failed')
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.payment_failed') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                            {{ translate(str_replace('_', ' ', $order['order_status'])) }}
                                        </span>
                                    @endif
                                </h6>
                                <h6 class="text-capitalize">
                                    <span>{{ translate('messages.payment_method') }}</span> <span>:</span>
                                    <span>{{ translate(str_replace('_', ' ', $order['payment_method'])) }}</span>
                                </h6>

                                <!-- offline_payment -->
                                @if($order?->offline_payments)
                                    <span>{{ translate('Payment_verification') }}</span> <span>:</span>
                                    @if ($order?->offline_payments->status == 'pending')
                                        <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.pending') }}
                                            </span>
                                    @elseif ($order?->offline_payments->status == 'verified')
                                        <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.verified') }}
                                            </span>
                                    @elseif ($order?->offline_payments->status == 'denied')
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.denied') }}
                                            </span>
                                    @endif

                                    @foreach (json_decode($order->offline_payments->payment_info) as $key=>$item)
                                        @if ($key != 'method_id')
                                            <h6 class="">
                                                <div class="d-flex justify-content-sm-end text-capitalize">
                                                    <span class="title-color">{{translate($key)}} :</span>
                                                    <strong>{{ $item }}</strong>
                                                </div>
                                            </h6>
                                        @endif
                                    @endforeach
                                @endif

                                <h6 class="">
                                    @if ($order['transaction_reference'] == null)
                                        <span>{{ translate('messages.reference_code') }}</span> <span>:</span>
                                        <button class="btn btn-outline-primary btn-sm" data-toggle="modal"
                                                data-target=".bd-example-modal-sm">
                                            {{ translate('messages.add') }}
                                        </button>
                                    @else
                                        <span>{{ translate('messages.reference_code') }}</span> <span>:</span>
                                        <span>{{ $order['transaction_reference'] }}</span>
                                    @endif
                                </h6>

                                <h6 class="text-capitalize">
                                    <span>{{ translate('Order Type') }}</span>
                                    <span>:</span> <label
                                        class="fz--10 badge badge-soft-primary m-0">{{ translate(str_replace('_', ' ', $order['order_type'])) }}</label>
                                </h6>
                                <h6>
                                    <span>{{ translate('payment_status') }}</span> <span>:</span>
                                    @if ($order['payment_status'] == 'paid')
                                        <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.paid') }}
                                        </span>
                                    @elseif ($order['payment_status'] == 'partially_paid')

                                        @if ($order->payments()->where('payment_status','unpaid')->exists())
                                            <strong class="text-danger">{{ translate('messages.partially_paid') }}</strong>
                                        @else
                                            <strong class="text-success">{{ translate('messages.paid') }}</strong>
                                        @endif
                                    @else
                                        <strong class="text-danger">{{ translate('messages.unpaid') }}</strong>
                                    @endif

                                </h6>
                                @if ($order->store && $order->store->module->module_type == 'food')
                                    <h6>
                                        <span>{{ translate('cutlery') }}</span> <span>:</span>
                                        @if ($order['cutlery'] == '1')
                                            <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.yes') }}
                                        </span>
                                        @else
                                            <span class="badge badge-soft-danger ml-sm-3">
                                            {{ translate('messages.no') }}
                                        </span>
                                        @endif

                                    </h6>
                                @endif
                                @if ($order->order_attachment)
                                    @php
                                        $order_images = json_decode($order->order_attachment,true);
                                    @endphp
                                    <h5 class="text-dark">
                                        <span>{{ translate('messages.prescription') }}</span> <span>:</span>
                                    </h5>
                                    <div class="d-flex flex-wrap flex-md-row-reverse" style="gap:15px">
                                        @foreach ($order_images as $key => $item)
                                            @php($item = is_array($item)?$item:['img'=>$item,'storage'=>'public'])
                                            <div>
                                                <button class="btn w-100 px-0" data-toggle="modal"
                                                        data-target="#prescriptionimagemodal{{ $key }}"
                                                        title="{{ translate('messages.order_attachment') }}">
                                                    <div class="gallary-card ml-auto">
                                                        <img  src="{{\App\CentralLogics\Helpers::get_full_url('order', $item['img'], $item['storage']??'public') }}"
                                                              alt="{{ translate('messages.prescription') }}"
                                                              class="initial--22 object-cover">
                                                    </div>
                                                </button>
                                            </div>
                                            <div class="modal fade" id="prescriptionimagemodal{{ $key }}" tabindex="-1"
                                                 role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title" id="myModalLabel">
                                                                {{ translate('messages.prescription') }}</h4>
                                                            <button type="button" class="close"
                                                                    data-dismiss="modal"><span
                                                                    aria-hidden="true">&times;</span><span
                                                                    class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                                        </div>
                                                        <div class="modal-body scroll-bar">
                                                            <img  src="{{\App\CentralLogics\Helpers::get_full_url('order', $item['img'], $item['storage']??'public') }}"
                                                                  class="initial--22 w-100">
                                                        </div>
                                                        @php($storage = $item['storage']??'public')
                                                        @php($file = $storage == 's3'?base64_encode('order/' . $item['img']):base64_encode('public/order/' . $item['img']))
                                                        <div class="modal-footer">
                                                            <a class="btn btn-primary"
                                                               href="{{ route('admin.file-manager.download', [$file,$storage]) }}"><i
                                                                    class="tio-download"></i>
                                                                {{ translate('messages.download') }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Body -->
                    <div class="card-body px-0">
                        <!-- item cart -->
                        @if ($editing && !$campaign_order)
                            <hr>
                            <div class="row  px-4 py-5">
                                <div class="col-12">
                                    <div class="row justify-content-end">
                                        <div class="col-sm-6">
                                            <form id="search-form">
                                                <!-- Search -->
                                                <div class="input-group input--group">
                                                    <input id="datatableSearch" type="search"
                                                           value="{{ $keyword ? $keyword : '' }}" name="search"
                                                           class="form-control h--45px" placeholder="Search here"
                                                           aria-label="Search here">
                                                    <button class="btn btn--secondary h--45px"><i
                                                            class="tio-search"></i></button>
                                                </div>
                                                <!-- End Search -->
                                            </form>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="input-group header-item w-100">
                                                <select name="category" id="category"
                                                        class="form-control js-select2-custom mx-1 set-category-filter"
                                                        title="{{ translate('messages.select_category') }}">
                                                    <option value="">{{ translate('messages.all_categories') }}
                                                    </option>
                                                    @foreach ($categories as $item)
                                                        <option value="{{ $item->id }}"
                                                            {{ $category == $item->id ? 'selected' : '' }}>
                                                            {{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-5" id="items">
                                    <div class="row g-3 mb-auto justify-content-center">
                                        @foreach ($products as $product)
                                            <div class="order--item-box item-box">
                                                @include('admin-views.order.partials._single_product', [
                                                    'product' => $product,
                                                    'store_data' => $order->store,
                                                ])
                                                {{-- <hr class="d-sm-none"> --}}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-12">
                                    {!! $products->withQueryString()->links() !!}
                                </div>
                            </div>
                        @endif

                        @if ($order->order_type == 'parcel')
                                <?php
                                $coupon = null;
                                $total_addon_price = 0;
                                $product_price = 0;
                                $store_discount_amount = 0;
                                $admin_flash_discount_amount = $order['flash_admin_discount_amount'];
                                $ref_bonus_amount = $order['ref_bonus_amount'];
                                $extra_packaging_amount = $order['extra_packaging_amount'];
                                $store_flash_discount_amount = $order['flash_store_discount_amount'];
                                $del_c = $order['delivery_charge'];
                                $additional_charge = $order['additional_charge'];
                                $total_tax_amount = 0;
                                $total_addon_price = 0;
                                $coupon_discount_amount = 0;
                                $deliverman_tips = $order['dm_tips'];
                                ?>
                            <div class="mx-3">
                                <div class="media align-items-center cart--media pb-2">
                                    <div class="avatar avatar-xl mr-3"
                                         title="{{ $order->parcel_category ? $order->parcel_category->name : translate('messages.parcel_category_not_found') }}">
                                        <img class="img-fluid onerror-image"
                                             src="{{ $order->parcel_category?->image_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}">
                                    </div>
                                    <div class="media-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <strong>
                                                    {{ Str::limit($order->parcel_category ? $order->parcel_category->name : translate('messages.parcel_category_not_found'), 25, '...') }}</strong><br>
                                                <div class="font-size-sm text-body">
                                                    <span>{{ $order->parcel_category ? $order->parcel_category->description : translate('messages.parcel_category_not_found') }}</span>
                                                </div>
                                            </div>

                                            <div class="col col-md-2 align-self-center">
                                                <h6>{{ translate('messages.distance') }}</h6>
                                                <span>{{ $order->distance }} {{ translate('km') }}</span>
                                            </div>
                                            <div class="col col-md-1 align-self-center">

                                            </div>

                                            <div class="col col-md-3 align-self-center text-right">
                                                <h6>{{ translate('messages.delivery_charge') }}</h6>
                                                <span>{{ \App\CentralLogics\Helpers::format_currency($del_c) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr class="my-2">
                            </div>
                        @else
                                <?php
                                $coupon = null;
                                $total_addon_price = 0;
                                $product_price = 0;
                                if ($order->prescription_order == 1) {
                                    $product_price = $order['order_amount'] - $order['delivery_charge'] - $order['total_tax_amount'] - $order['dm_tips'] - $order['additional_charge'] + $order['store_discount_amount'];
                                    if($order->tax_status == 'included'){
                                        $product_price += $order['total_tax_amount'];
                                    }
                                }
                                $store_discount_amount = 0;
                                $admin_flash_discount_amount = $order['flash_admin_discount_amount'];
                                $ref_bonus_amount = $order['ref_bonus_amount'];
                                $extra_packaging_amount = $order['extra_packaging_amount'];
                                $store_flash_discount_amount = $order['flash_store_discount_amount'];
                                $additional_charge = $order['additional_charge'];
                                $del_c = $order['delivery_charge'];
                                if ($editing) {
                                    $del_c = $order['original_delivery_charge'];
                                }
                                if ($order->coupon_code) {
                                    $coupon = \App\Models\Coupon::where(['code' => $order['coupon_code']])->first();
                                    if ($editing && $coupon->coupon_type == 'free_delivery') {
                                        $del_c = 0;
                                        $coupon = null;
                                    }
                                }
                                $details = $order->details;
                                if ($editing) {
                                    $details = session('order_cart');
                                } else {
                                    foreach ($details as $key => $item) {
                                        $details[$key]->status = true;
                                    }
                                }
                                ?>
                            <div class="table-responsive">
                                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table dataTable no-footer mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('messages.#') }}</th>
                                        <th class="border-0">{{ translate('messages.item_details') }}</th>
                                        @if ($order->store && $order->store->module->module_type == 'food')
                                            <th class="border-0">{{ translate('messages.addons') }}</th>
                                        @endif
                                        <th class="text-right border-0">{{ translate('messages.price') }}</th>
                                        @if ($editing)
                                            <th class="text-center border-0">{{ translate('messages.actions') }}</th>
                                        @endif
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($details as $key => $detail)
                                        @if (isset($detail->item_id) && $detail->status)
                                                <?php
                                                if (!$editing) {
                                                    $detail->item = json_decode($detail->item_details, true);
                                                }
                                                $product = \App\Models\Item::where(['id' => data_get($detail->item,'id')])->first();
                                                        if(!$product){
                                                            $detail->item = json_decode($detail->item_details, true);
                                                        }
                                                ?>

                                            <tr>
                                                <td>
                                                    <!-- Static Count Number -->
                                                    <div>
                                                        {{ $key + 1 }}
                                                    </div>
                                                    <!-- Static Count Number -->
                                                </td>
                                                <td>
                                                    <div class="media media--sm">
                                                        @if ($editing)
                                                            <div class="avatar avatar-xl mr-3 cursor-pointer quick-view-cart-item" data-key="{{ $key }}"
                                                                title="{{ translate('messages.click_to_edit_this_item') }}">
                                                                    <span
                                                                        class="avatar-status avatar-lg-status avatar-status-dark"><i
                                                                            class="tio-edit"></i></span>
                                                                <img class="img-fluid rounded aspect-ratio-1 onerror-image"
                                                                    src="{{ $product?->image_full_url ??asset('public/assets/admin/img/100x100/2.png') }}"
                                                                    data-onerror-image="{{ asset('public/assets/admin/img/100x100/2.png') }}"
                                                                    alt="Image Description">
                                                            </div>
                                                        @else
                                                            <a class="avatar avatar-xl mr-3"
                                                            href="{{ route('admin.item.view', [$detail->item['id'],'module_id' => $order->module_id]) }}">
                                                                <img class="img-fluid rounded aspect-ratio-1 onerror-image"
                                                                    src="{{ $product?->image_full_url ?? asset('public/assets/admin/img/100x100/2.png') }}"
                                                                    data-onerror-image="{{ asset('public/assets/admin/img/100x100/2.png') }}"
                                                                    alt="Image Description">
                                                            </a>
                                                        @endif
                                                        <div class="media-body">
                                                            <div>
                                                                <strong class="line--limit-1">
                                                                    {{ $detail->item['name'] }}</strong>
                                                                <h6>
                                                                    {{ $detail['quantity'] }} x
                                                                    {{ \App\CentralLogics\Helpers::format_currency($detail['price']) }}
                                                                </h6>
                                                                @if ($order->store && $order->store->module->module_type == 'food')
                                                                    @if (isset($detail['variation']) ? json_decode($detail['variation'], true) : [])
                                                                        @foreach (json_decode($detail['variation'], true) as $variation)
                                                                            @if (isset($variation['name']) && isset($variation['values']))
                                                                                <span class="d-block text-capitalize">
                                                                                        <strong>
                                                                                            {{ $variation['name'] }} -
                                                                                        </strong>
                                                                                    </span>
                                                                                @foreach ($variation['values'] as $value)
                                                                                    <span
                                                                                        class="d-block text-capitalize">
                                                                                            &nbsp; &nbsp;
                                                                                            {{ $value['label'] }} :
                                                                                            <strong>{{ \App\CentralLogics\Helpers::format_currency($value['optionPrice']) }}</strong>
                                                                                        </span>
                                                                                @endforeach
                                                                            @else
                                                                                @if (isset(json_decode($detail['variation'], true)[0]))
                                                                                    <strong><u>
                                                                                            {{ translate('messages.Variation') }}
                                                                                            : </u></strong>
                                                                                    @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                                        <div
                                                                                            class="font-size-sm text-body">
                                                                                                <span>{{ $key1 }}
                                                                                                    : </span>
                                                                                            <span
                                                                                                class="font-weight-bold">{{ $variation }}</span>
                                                                                        </div>
                                                                                    @endforeach
                                                                                @endif
                                                                                {{-- @break --}}
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @else
                                                                    @if (count(json_decode($detail['variation'], true)) > 0)
                                                                        <strong><u>{{ translate('messages.variation') }}
                                                                                :
                                                                            </u></strong>
                                                                        @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                            @if ($key1 != 'stock' || ($order->store && config('module.' . $order->store->module->module_type)['stock']))
                                                                                <div class="font-size-sm text-body">
                                                                                        <span>{{ $key1 }} :
                                                                                        </span>
                                                                                    <span
                                                                                        class="font-weight-bold">{{ Str::limit($variation, 15, '...') }}</span>
                                                                                </div>
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @endif

                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                @if ($order->store && $order->store->module->module_type == 'food')
                                                    <td>
                                                        <div>
                                                            @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                                                @if ($key2 == 0)
                                                                    <strong><u>{{ translate('messages.addons') }} :
                                                                        </u></strong>
                                                                @endif
                                                                <div class="font-size-sm text-body">
                                                                        <span>{{ Str::limit($addon['name'], 20, '...') }}
                                                                            : </span>
                                                                    <span class="font-weight-bold">
                                                                            {{ $addon['quantity'] }} x
                                                                            {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                                        </span>
                                                                </div>
                                                                @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                @endif
                                                <td class="text-right">
                                                    <div>
                                                        @php($amount = $detail['price'] * $detail['quantity'])
                                                        <h5>{{ \App\CentralLogics\Helpers::format_currency($amount) }}
                                                        </h5>
                                                    </div>
                                                </td>
                                                @if ($editing)
                                                    <td class="text-center">
                                                        <div class="form-group">
                                                            <input type="number"
                                                                class="form-control form-control-sm mrp-input"
                                                                value="{{ $product?->price ?? $detail['price'] }}"
                                                                data-key="{{ $key }}"
                                                                data-item-id="{{ $detail->item['id'] }}"
                                                                step="0.01"
                                                                min="0"
                                                                placeholder="MRP">
                                                            <button type="button"
                                                                    class="btn btn-sm btn-primary mt-1 update-mrp"
                                                                    data-key="{{ $key }}"
                                                                    data-item-id="{{ $detail->item['id'] }}">
                                                                {{ translate('messages.update') }}
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                       <button type="button"
                                                            class="btn btn-sm btn-danger mark-out-of-stock"
                                                            data-key="{{ $key }}"
                                                            data-item-id="{{ $detail->item['id'] }}"
                                                            data-order-detail-id="{{ $detail->id }}">
                                                        <i class="tio-delete-outlined"></i>
                                                        Mark as Out of Stock
                                                    </button>
                                                                                                        </td>
                                                @endif
                                            </tr>

                                            @php($product_price += $amount)
                                            @php($store_discount_amount += $detail['discount_on_item'] * $detail['quantity'])
                                            <!-- End Media -->
                                        @elseif(isset($detail->item_campaign_id) && $detail->status)
                                                <?php
                                                if (!$editing) {
                                                    $detail->campaign = json_decode($detail->item_details, true);
                                                }
                                                $campaign = \App\Models\ItemCampaign::where(['id' => $detail->campaign['id']])->first();
                                                ?>
                                            <tr>
                                                <td>
                                                    <!-- Static Count Number -->
                                                    <div>
                                                        {{ $key + 1 }}
                                                    </div>
                                                    <!-- Static Count Number -->
                                                </td>
                                                <td>
                                                    <div class="media media--sm">
                                                        @if ($editing)
                                                            <div class="avatar avatar-xl mr-3  cursor-pointer quick-view-cart-item" data-key="{{ $key }}"
                                                                title="{{ translate('messages.click_to_edit_this_item') }}">
                                                                    <span
                                                                        class="avatar-status avatar-lg-status avatar-status-dark"><i
                                                                            class="tio-edit"></i></span>
                                                                    <img class="img-fluid rounded onerror-image"
                                                                        src="{{ $campaign?->image_full_url ?? asset('public/assets/admin/img/900x400/img1.jpg') }}"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                        alt="Image Description">
                                                                </div>
                                                            @else
                                                                <a class="avatar avatar-xl mr-3"
                                                                    href="{{ route('admin.campaign.view', ['item', $detail->campaign['id']]) }}">
                                                                    <img class="img-fluid rounded onerror-image"
                                                                        src="{{ $campaign?->image_full_url ?? asset('public/assets/admin/img/900x400/img1.jpg') }}"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                        alt="Image Description">
                                                                </a>
                                                            @endif

                                                        <div class="media-body">
                                                            <div>
                                                                <strong
                                                                    class="line--limit-1">{{ Str::limit($detail->campaign['name'], 20, '...') }}</strong>

                                                                <h6>
                                                                    {{ $detail['quantity'] }} x
                                                                    {{ \App\CentralLogics\Helpers::format_currency($detail['price']) }}
                                                                </h6>
                                                                @if ($order->store && $order->store->module->module_type == 'food')
                                                                    @if (isset($detail['variation']) ? json_decode($detail['variation'], true) : [])
                                                                        @foreach (json_decode($detail['variation'], true) as $variation)
                                                                            @if (isset($variation['name']) && isset($variation['values']))
                                                                                <span class="d-block text-capitalize">
                                                                                        <strong>
                                                                                            {{ $variation['name'] }} -
                                                                                        </strong>
                                                                                    </span>
                                                                                @foreach ($variation['values'] as $value)
                                                                                    <span
                                                                                        class="d-block text-capitalize">
                                                                                            &nbsp; &nbsp;
                                                                                            {{ $value['label'] }} :
                                                                                            <strong>{{ \App\CentralLogics\Helpers::format_currency($value['optionPrice']) }}</strong>
                                                                                        </span>
                                                                                @endforeach
                                                                            @else
                                                                                @if (isset(json_decode($detail['variation'], true)[0]))
                                                                                    <strong><u>
                                                                                            {{ translate('messages.Variation') }}
                                                                                            : </u></strong>
                                                                                    @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                                        <div
                                                                                            class="font-size-sm text-body">
                                                                                                <span>{{ $key1 }}
                                                                                                    : </span>
                                                                                            <span
                                                                                                class="font-weight-bold">{{ $variation }}</span>
                                                                                        </div>
                                                                                    @endforeach
                                                                                @endif
                                                                                {{-- @break --}}
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @else
                                                                    @if (count(json_decode($detail['variation'], true)) > 0)
                                                                        <strong><u>{{ translate('messages.variation') }}
                                                                                :</u></strong>
                                                                        @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                            @if ($key1 != 'stock' || ($order->store && config('module.' . $order->store->module->module_type)['stock']))
                                                                                <div class="font-size-sm text-body">
                                                                                        <span>{{ $key1 }} :
                                                                                        </span>
                                                                                    <span
                                                                                        class="font-weight-bold">{{ Str::limit($variation, 15, '...') }}</span>
                                                                                </div>
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                @if ($order->store && $order->store->module->module_type == 'food')
                                                    <td>
                                                        <div>
                                                            @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                                                @if ($key2 == 0)
                                                                    <strong><u>{{ translate('messages.addons') }} :
                                                                        </u></strong>
                                                                @endif
                                                                <div class="font-size-sm text-body">
                                                                        <span>{{ Str::limit($addon['name'], 20, '...') }}
                                                                            : </span>
                                                                    <span class="font-weight-bold">
                                                                            {{ $addon['quantity'] }} x
                                                                            {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                                        </span>
                                                                </div>
                                                                @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                @endif
                                                <td class="text-right">
                                                    <div>
                                                        @php($amount = $detail['price'] * $detail['quantity'])
                                                        <h5>{{ \App\CentralLogics\Helpers::format_currency($amount) }}
                                                        </h5>
                                                    </div>
                                                </td>
                                                @if ($editing)
                                                    <td class="text-center">
                                                        <div class="form-group">
                                                            <input type="number"
                                                                class="form-control form-control-sm mrp-input"
                                                                value="{{ $product?->price ?? $detail['price'] }}"
                                                                data-key="{{ $key }}"
                                                                data-item-id="{{ $detail->item['id'] }}"
                                                                step="0.01"
                                                                min="0"
                                                                placeholder="MRP">
                                                            <button type="button"
                                                                    class="btn btn-sm btn-primary mt-1 update-mrp"
                                                                    data-key="{{ $key }}"
                                                                    data-item-id="{{ $detail->item['id'] }}">
                                                                {{ translate('messages.update') }}
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button"
                                                                class="btn btn-sm btn-danger mark-campaign-out-of-stock"
                                                        