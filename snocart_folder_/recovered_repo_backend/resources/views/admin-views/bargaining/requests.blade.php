@extends('layouts.admin.app')

@section('title', translate('bargaining_requests'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <i class="tio-shopping-basket"></i> {{ translate('bargaining_requests') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.bargaining.dashboard') }}" class="btn btn-outline-primary">
                    <i class="tio-arrow-back"></i> {{ translate('back_to_dashboard') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('admin.bargaining.requests') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="input-label">{{ translate('status') }}</label>
                        <select name="status" class="form-control">
                            <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                            <option value="initiated" {{ request('status') === 'initiated' ? 'selected' : '' }}>{{ translate('initiated') }}</option>
                            <option value="matching" {{ request('status') === 'matching' ? 'selected' : '' }}>{{ translate('matching') }}</option>
                            <option value="offers_received" {{ request('status') === 'offers_received' ? 'selected' : '' }}>{{ translate('offers_received') }}</option>
                            <option value="awarded" {{ request('status') === 'awarded' ? 'selected' : '' }}>{{ translate('awarded') }}</option>
                            <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>{{ translate('accepted') }}</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ translate('cancelled') }}</option>
                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ translate('expired') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="input-label">{{ translate('mode') }}</label>
                        <select name="mode" class="form-control">
                            <option value="all" {{ request('mode') === 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                            <option value="instant" {{ request('mode') === 'instant' ? 'selected' : '' }}>{{ translate('instant') }}</option>
                            <option value="wait" {{ request('mode') === 'wait' ? 'selected' : '' }}>{{ translate('wait') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="input-label">{{ translate('search') }}</label>
                        <input type="text" name="search" class="form-control" placeholder="{{ translate('search_by_request_code') }}" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="input-label d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="tio-filter-list"></i> {{ translate('filter') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-header-title">
                {{ translate('requests_list') }}
                <span class="badge badge-soft-dark ml-2">{{ $requests->total() }}</span>
            </h5>
            <div>
                <a href="{{ route('admin.bargaining.export', ['format' => 'csv'] + request()->all()) }}" class="btn btn-sm btn-outline-success">
                    <i class="tio-download"></i> {{ translate('export_csv') }}
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('request_code') }}</th>
                            <th>{{ translate('customer') }}</th>
                            <th>{{ translate('status') }}</th>
                            <th>{{ translate('mode') }}</th>
                            <th>{{ translate('items') }}</th>
                            <th>{{ translate('original_value') }}</th>
                            <th>{{ translate('final_price') }}</th>
                            <th>{{ translate('savings') }}</th>
                            <th>{{ translate('stores_matched') }}</th>
                            <th>{{ translate('offers') }}</th>
                            <th>{{ translate('awarded_to') }}</th>
                            <th>{{ translate('created_at') }}</th>
                            <th class="text-center">{{ translate('action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.bargaining.request-details', $request->id) }}" class="font-weight-bold">
                                        {{ $request->request_code }}
                                    </a>
                                </td>
                                <td>
                                    @if($request->user_id)
                                        <a href="{{ route('admin.customer.view', $request->user_id) }}" target="_blank">
                                            {{ translate('customer') }} #{{ $request->user_id }}
                                        </a>
                                    @else
                                        <span class="badge badge-soft-secondary">{{ translate('guest') }}</span>
                                    @endif
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    <span class="badge {{ $request->mode === 'instant' ? 'badge-soft-info' : 'badge-soft-warning' }}">
                                        {{ translate($request->mode) }}
                                    </span>
                                </td>
                                <td>{{ $request->total_cart_items }}</td>
                                <td>{{ \App\CentralLogics\Helpers::format_currency($request->original_cart_value) }}</td>
                                <td>
                                    @if($request->final_price)
                                        {{ \App\CentralLogics\Helpers::format_currency($request->final_price) }}
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>
                                    @if($request->total_savings)
                                        <span class="text-success font-weight-bold">
                                            {{ \App\CentralLogics\Helpers::format_currency($request->total_savings) }}
                                        </span>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>{{ $request->total_stores_matched }}</td>
                                <td>{{ $request->total_offers_received }}</td>
                                <td>
                                    @if($request->awardedStore)
                                        <a href="{{ route('admin.store.view', $request->awarded_store_id) }}" target="_blank">
                                            {{ $request->awardedStore->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-nowrap">{{ $request->created_at->format('d M Y') }}</span><br>
                                    <small class="text-muted">{{ $request->created_at->format('H:i') }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.bargaining.request-details', $request->id) }}"
                                           class="btn btn-sm btn-outline-info"
                                           title="{{ translate('view_details') }}">
                                            <i class="tio-visible-outlined"></i>
                                        </a>
                                        @if(in_array($request->status, ['initiated', 'matching', 'offers_received']))
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="cancelRequest({{ $request->id }})"
                                                    title="{{ translate('cancel') }}">
                                                <i class="tio-clear"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-5">
                                    <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="Image" style="width: 7rem;">
                                    <p class="mt-3">{{ translate('no_data_found') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    function cancelRequest(requestId) {
        Swal.fire({
            title: '{{ translate("are_you_sure") }}',
            text: '{{ translate("you_want_to_cancel_this_request") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ translate("yes_cancel_it") }}',
            cancelButtonText: '{{ translate("no") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/bargaining/requests') }}/${requestId}/cancel`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ translate("cancelled") }}',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate("error") }}',
                            text: xhr.responseJSON?.error || '{{ translate("something_went_wrong") }}'
                        });
                    }
                });
            }
        });
    }
</script>
@endpush
