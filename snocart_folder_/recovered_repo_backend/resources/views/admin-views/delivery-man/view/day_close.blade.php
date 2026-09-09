@extends('layouts.admin.app')

@section('title', translate('messages.day_close') . ' - ' . $deliveryMan->f_name . ' ' . $deliveryMan->l_name)

@section('content')
    <div class="content container-fluid pb-0">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div class="d-flex gap-2">
                    <div class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/delivery-man.png') }}" class="w--26" alt="">
                    </div>
                    <div>
                        <h1 class="page-header-title text-break mb-1">
                            <span>{{ translate('messages.day_close') }} - {{ $deliveryMan->f_name . ' ' . $deliveryMan->l_name }}</span>
                        </h1>
                        <p class="mb-0 fs-12">{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
                            @if($isToday)
                                <span class="badge badge-soft-info">{{ translate('messages.today') }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="">
                @include('admin-views.delivery-man.partials._tab_menu')
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Date Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.users.delivery-man.preview', ['id' => $deliveryMan->id, 'tab' => 'day_close']) }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-4">
                            <label class="form-label">{{ translate('messages.select_date') }}</label>
                            <input type="date" name="date" class="form-control" value="{{ $selectedDate }}" max="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-sm-4">
                            <button type="submit" class="btn btn-primary">{{ translate('messages.filter') }}</button>
                            @if(!$isToday)
                                <a href="{{ route('admin.users.delivery-man.preview', ['id' => $deliveryMan->id, 'tab' => 'day_close']) }}" class="btn btn-secondary ml-2">{{ translate('messages.reset') }}</a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Day Closed Status Badge -->
        @if($isDayClosed)
            <div class="alert alert-success mb-3">
                <strong><i class="tio-checkmark-circle"></i> {{ translate('messages.day_closed') }}</strong> — {{ translate('messages.this_day_has_been_closed') }} ({{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }})
            </div>
        @elseif(!$isToday)
            <div class="alert alert-warning mb-3">
                <strong><i class="tio-warning"></i> {{ translate('messages.day_not_closed') }}</strong> — {{ translate('messages.this_day_was_not_closed') }} ({{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }})
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="row mb-3 gy-2 row-3">
            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-1.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title">{{ $dateOrderCount }}</h2>
                        <div class="subtitle">{{ translate('messages.delivered_orders') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card color-1">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-1.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title">{{ \App\CentralLogics\Helpers::format_currency($dateTotalReceived) }}</h2>
                        <div class="subtitle">{{ translate('messages.total_amount_received') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card" style="background: linear-gradient(135deg, #28a745, #218838); color: white;">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-2.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title" style="color:white;">{{ \App\CentralLogics\Helpers::format_currency($dateItemAmount) }}</h2>
                        <div class="subtitle" style="color:rgba(255,255,255,0.85);">🛒 Total Item Amount</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card color-2">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-2.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title">{{ \App\CentralLogics\Helpers::format_currency($dateCashCollected) }}</h2>
                        <div class="subtitle">{{ translate('messages.cash_collected') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card color-3">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-3.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title">{{ \App\CentralLogics\Helpers::format_currency($dateOutsidePurchase) }}</h2>
                        <div class="subtitle">{{ translate('messages.outside_purchase') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white;">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-5.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title" style="color:white;">{{ \App\CentralLogics\Helpers::format_currency($dateWalletPayments) }}</h2>
                        <div class="subtitle" style="color:rgba(255,255,255,0.85);">💼 Wallet Payments</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card" style="background: linear-gradient(135deg, #6f42c1, #9b59b6); color: white;">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-3.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title" style="color:white;">{{ \App\CentralLogics\Helpers::format_currency($dateQRPayments) }}</h2>
                        <div class="subtitle" style="color:rgba(255,255,255,0.85);">📱 Razorpay QR Payments</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card color-4">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-4.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title {{ $dateCashInHand < 0 ? 'text-danger' : '' }}">{{ \App\CentralLogics\Helpers::format_currency($dateCashInHand) }}</h2>
                        <div class="subtitle">{{ translate('messages.cash_in_hand') }} ({{ translate('messages.after_deducting_outside_payment_and_online') }})</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card color-1">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-1.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title">{{ \App\CentralLogics\Helpers::format_currency($dateCashAlreadyCollected) }}</h2>
                        <div class="subtitle">{{ translate('messages.cash_already_collected_today') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card color-4">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-4.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title">{{ \App\CentralLogics\Helpers::format_currency($dateEarnings) }}</h2>
                        <div class="subtitle">{{ translate('messages.delivery_earnings') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card color-5">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-5.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title">{{ \App\CentralLogics\Helpers::format_currency($dateTips) }}</h2>
                        <div class="subtitle">{{ translate('messages.tips') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card color-6">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-6.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title">{{ $avgDeliveryTime ? round($avgDeliveryTime) . ' ' . translate('messages.min') : translate('messages.N/A') }}</h2>
                        <div class="subtitle">{{ translate('messages.avg_delivery_time') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card color-3">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-3.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title {{ $cashInHand < 0 ? 'text-danger' : '' }}">{{ \App\CentralLogics\Helpers::format_currency($cashInHand) }}</h2>
                        <div class="subtitle">{{ translate('messages.total_cash_in_hand') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 mb-2 col-lg-3">
                <div class="color-card color-2">
                    <div class="img-box">
                        <img class="resturant-icon w--30" src="{{ asset('public/assets/admin/img/icons/color-icon-2.png') }}" alt="img">
                    </div>
                    <div>
                        <h2 class="title">{{ \App\CentralLogics\Helpers::format_currency($totalEarning) }}</h2>
                        <div class="subtitle">{{ translate('messages.total_wallet_earning') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collect Cash Section -->
        <div class="card mb-3">
            <div class="card-header py-2 border-0">
                <h5 class="card-header-title">{{ translate('messages.collect_cash') }}</h5>
            </div>
            <div class="card-body">
                <form id="day-close-form">
                    @csrf
                    <input type="hidden" name="type" value="deliveryman">
                    <input type="hidden" name="deliveryman_id" value="{{ $deliveryMan->id }}">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">{{ translate('messages.payment_method') }}</label>
                            <select name="method" class="form-control" id="payment-method-select" required>
                                <option value="cash">{{ translate('messages.cash') }}</option>
                                <option value="bank_transfer">{{ translate('messages.bank_transfer') }}</option>
                                <option value="digital_payment">{{ translate('messages.digital_payment') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">{{ translate('messages.reference') }}</label>
                            <input type="text" name="ref" class="form-control" placeholder="{{ translate('messages.reference') }}">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">{{ translate('messages.amount') }}</label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0" max="{{ $cashInHand }}" value="{{ $cashInHand }}" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">{{ translate('messages.collect_cash') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Day Close Button (only for today and when not already closed) -->
        @if($isToday && !$isDayClosed)
        <div class="card mb-3">
            <div class="card-body">
                @if($canCloseDay)
                    <button class="btn btn-success btn-lg" id="day-close-btn">{{ translate('messages.day_close') }}</button>
                @else
                    <button class="btn btn-secondary btn-lg" disabled>{{ translate('messages.day_close') }}</button>
                    <p class="text-danger mt-2 mb-0">{{ translate('messages.collect_cash_from_delivery_man_first_to_close_day') }}</p>
                @endif
            </div>
        </div>
        @endif

        <!-- Collect Cash Transactions -->
        @if($cashTransactions->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header py-2 border-0">
                <h5 class="card-header-title">
                    {{ translate('messages.collect_cash') }} {{ translate('messages.transaction') }}
                    <span class="badge badge-soft-dark ml-2">{{ $cashTransactions->count() }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-nowrap card-table">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('messages.SL') }}</th>
                                <th class="border-0">{{ translate('messages.payment_method') }}</th>
                                <th class="border-0">{{ translate('messages.amount') }}</th>
                                <th class="border-0">{{ translate('messages.balance_before') }}</th>
                                <th class="border-0">{{ translate('messages.balance_after') }}</th>
                                <th class="border-0">{{ translate('messages.reference') }}</th>
                                <th class="border-0">{{ translate('messages.time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cashTransactions as $k => $transaction)
                                <tr>
                                    <td>{{ $k + 1 }}</td>
                                    <td>
                                        <span class="badge badge-soft-primary">
                                            {{ translate('messages.' . $transaction->method) }}
                                        </span>
                                    </td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($transaction->amount) }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($transaction->current_balance) }}</td>
                                    <td>{{ $transaction->balance_after !== null ? \App\CentralLogics\Helpers::format_currency($transaction->balance_after) : '-' }}</td>
                                    <td>{{ $transaction->ref ?? '-' }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::time_date_format($transaction->created_at) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header py-2 border-0">
                <h5 class="card-header-title">
                    {{ translate('messages.delivered_orders') }}
                    <span class="badge badge-soft-dark ml-2">{{ $dateOrderCount }}</span>
                    @if($isDayClosed)
                        <span class="badge badge-soft-success ml-2">{{ translate('messages.day_closed') }}</span>
                    @else
                        <span class="badge badge-soft-warning ml-2">{{ translate('messages.day_not_closed') }}</span>
                    @endif
                </h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.users.delivery-man.day-close-export', ['id' => $deliveryMan->id, 'type' => 'excel', 'date' => $selectedDate]) }}" class="btn btn-outline-primary btn-sm">
                        <i class="tio-download-to"></i> {{ translate('messages.export_excel') }}
                    </a>
                    <a href="{{ route('admin.users.delivery-man.day-close-export', ['id' => $deliveryMan->id, 'type' => 'csv', 'date' => $selectedDate]) }}" class="btn btn-outline-info btn-sm">
                        <i class="tio-download-to"></i> {{ translate('messages.export_csv') }}
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-nowrap card-table">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('messages.SL') }}</th>
                                <th class="border-0">{{ translate('messages.order_ID') }}</th>
                                <th class="border-0">{{ translate('messages.store') }}</th>
                                <th class="border-0">{{ translate('messages.customer') }}</th>
                                <th class="border-0">{{ translate('messages.item_amount') }}</th>
                                <th class="border-0">{{ translate('messages.order_amount') }}</th>
                                <th class="border-0">{{ translate('messages.outside_purchase') }}</th>
                                <th class="border-0">{{ translate('messages.payment_method') }}</th>
                                <th class="border-0">Wallet Paid</th>
                                <th class="border-0">{{ translate('messages.delivered_at') }}</th>
                                <th class="border-0">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dateOrders as $k => $order)
                                <tr>
                                    <td>{{ $dateOrders->firstItem() + $k }}</td>
                                    <td>
                                        <a href="{{ route('admin.order.all-details', ['id' => $order->id]) }}">{{ $order->id }}</a>
                                    </td>
                                    <td>
                                        @if ($order->store)
                                            {{ $order->store->name }}
                                        @else
                                            {{ translate('messages.store_not_found') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($order->customer)
                                            {{ $order->customer->f_name . ' ' . $order->customer->l_name }}
                                        @else
                                            {{ translate('messages.customer_not_found') }}
                                        @endif
                                    </td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($order->details->sum(fn($d) => $d->price * $d->quantity)) }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($order->order_amount + ($order->dm_tips ?? 0)) }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($order->outside_purchase_amount ?? 0) }}</td>
                                    <td>
                                        <span class="badge badge-soft-{{ $order->payment_method == 'cash_on_delivery' ? 'success' : ($order->payment_method == 'qr_payment_at_delivery' ? 'primary' : 'info') }}">
                                            {{ $order->payment_method == 'qr_payment_at_delivery' ? '📱 QR' : translate('messages.' . $order->payment_method) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($order->payment_method == 'partial_payment' && $order->partially_paid_amount > 0)
                                            <span class="badge badge-soft-warning">
                                                💳 {{ \App\CentralLogics\Helpers::format_currency($order->partially_paid_amount) }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ \App\CentralLogics\Helpers::time_date_format($order->delivered) }}</td>
                                    <td>
                                        <button type="button" class="btn btn-outline-primary btn-sm edit-outside-btn"
                                            data-toggle="modal" data-target="#editOutsidePaymentModal"
                                            data-order-id="{{ $order->id }}"
                                            data-outside-amount="{{ $order->outside_purchase_amount ?? 0 }}"
                                            data-store-id="{{ $order->details->first()?->outside_purchase_store_id }}">
                                            <i class="tio-edit"></i> {{ translate('messages.outside_purchase') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($dateOrders) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>{{ translate('messages.no_data_found') }}</h5>
                    </div>
                @endif
            </div>
            @if ($dateOrders->hasPages())
                <div class="card-footer border-0 pt-0">
                    {!! $dateOrders->links() !!}
                </div>
            @endif
        </div>
        <!-- Single Reusable Edit Outside Payment Modal -->
        <div class="modal fade" id="editOutsidePaymentModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('messages.edit_outside_purchase') }} - {{ translate('messages.order') }} #<span id="outsideModalOrderId"></span></h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form action="{{ route('admin.order.mark-full-order-outside-purchase') }}" method="POST">
                        @csrf
                        <input type="hidden" name="order_id" id="outsideModalOrderIdInput">
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="form-label">{{ translate('messages.outside_purchase') }} {{ translate('messages.amount') }}</label>
                                <input type="number" name="outside_purchase_cost" id="outsideModalCost" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('messages.store') }} ({{ translate('messages.optional') }})</label>
                                <input type="number" name="outside_purchase_store_id" id="outsideModalStoreId" class="form-control" placeholder="{{ translate('messages.store_id') }}">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('messages.close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('messages.save') }}</button>
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
        $('.edit-outside-btn').on('click', function() {
            $('#outsideModalOrderId').text($(this).data('order-id'));
            $('#outsideModalOrderIdInput').val($(this).data('order-id'));
            $('#outsideModalCost').val($(this).data('outside-amount'));
            $('#outsideModalStoreId').val($(this).data('store-id'));
        });

        $('#day-close-form').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);

            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_collect_cash') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('admin.transactions.account-transaction.store') }}',
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function(data) {
                            if (data.errors) {
                                for (let i = 0; i < data.errors.length; i++) {
                                    toastr.error(data.errors[i].message, {
                                        CloseButton: true,
                                        ProgressBar: true
                                    });
                                }
                            } else {
                                toastr.success('{{ translate('messages.cash_collected_successfully') }}', {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            }
                        },
                        error: function() {
                            toastr.error('{{ translate('messages.something_went_wrong') }}', {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    });
                }
            });
        });

        $('#day-close-btn').on('click', function() {
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_close_the_day_for_this_delivery_man') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#28a745',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('admin.users.delivery-man.day-close') }}',
                        data: {deliveryman_id: '{{ $deliveryMan->id }}'},
                        success: function(data) {
                            if (data.errors) {
                                for (let i = 0; i < data.errors.length; i++) {
                                    toastr.error(data.errors[i].message, {
                                        CloseButton: true,
                                        ProgressBar: true
                                    });
                                }
                            } else {
                                toastr.success('{{ translate('messages.day_closed_successfully') }}', {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            }
                        },
                        error: function(xhr) {
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                for (let i = 0; i < xhr.responseJSON.errors.length; i++) {
                                    toastr.error(xhr.responseJSON.errors[i].message, {
                                        CloseButton: true,
                                        ProgressBar: true
                                    });
                                }
                            } else {
                                toastr.error('{{ translate('messages.something_went_wrong') }}', {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            }
                        }
                    });
                }
            });
        });
    </script>
@endpush
