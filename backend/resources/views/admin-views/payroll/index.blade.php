@extends('layouts.admin.app')

@section('title', translate('messages.payroll'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title">
                    <i class="tio-wallet"></i> {{ translate('messages.payroll_management') }}
                    <span class="badge badge-soft-dark ml-2">{{ $payrolls->total() }}</span>
                </h1>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.transactions.payroll.generate') }}" class="btn btn-primary">
                        <i class="tio-add"></i> {{ translate('messages.generate_payroll') }}
                    </a>
                    <a href="{{ route('admin.transactions.payroll.incentive-slabs.index') }}" class="btn btn-info">
                        <i class="tio-star"></i> {{ translate('messages.incentive_slabs') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.transactions.payroll.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-3">
                            <label class="form-label">{{ translate('messages.search') }}</label>
                            <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="{{ translate('messages.search_by_dm_name_phone') }}">
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label">{{ translate('messages.status') }}</label>
                            <select name="status" class="form-control">
                                <option value="all">{{ translate('messages.all') }}</option>
                                <option value="generated" {{ $status == 'generated' ? 'selected' : '' }}>{{ translate('messages.generated') }}</option>
                                <option value="paid" {{ $status == 'paid' ? 'selected' : '' }}>{{ translate('messages.paid') }}</option>
                                <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>{{ translate('messages.cancelled') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label">{{ translate('messages.period_from') }}</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label">{{ translate('messages.period_to') }}</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                        </div>
                        <div class="col-sm-3">
                            <button type="submit" class="btn btn-primary">{{ translate('messages.filter') }}</button>
                            <a href="{{ route('admin.transactions.payroll.index') }}" class="btn btn-secondary">{{ translate('messages.reset') }}</a>
                            <a href="{{ route('admin.transactions.payroll.export', ['type' => 'excel', 'status' => $status]) }}" class="btn btn-outline-info">
                                <i class="tio-download-to"></i> {{ translate('messages.export') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payroll Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-nowrap card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('messages.SL') }}</th>
                                <th>{{ translate('messages.delivery_man') }}</th>
                                <th>{{ translate('messages.period') }}</th>
                                <th>{{ translate('messages.salary') }}</th>
                                <th>{{ translate('messages.incentive') }}</th>
                                <th>{{ translate('messages.total') }}</th>
                                <th>{{ translate('messages.deductions') }}</th>
                                <th>{{ translate('messages.net_payable') }}</th>
                                <th>{{ translate('messages.status') }}</th>
                                <th>{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payrolls as $k => $payroll)
                                <tr>
                                    <td>{{ $payrolls->firstItem() + $k }}</td>
                                    <td>
                                        @if($payroll->deliveryMan)
                                            {{ $payroll->deliveryMan->f_name . ' ' . $payroll->deliveryMan->l_name }}
                                            <br><small class="text-muted">{{ $payroll->deliveryMan->phone }}</small>
                                        @else
                                            {{ translate('messages.N/A') }}
                                        @endif
                                    </td>
                                    <td>
                                        {{ $payroll->period_from->format('d M Y') }}
                                        <br><small>{{ translate('messages.to') }} {{ $payroll->period_to->format('d M Y') }}</small>
                                    </td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($payroll->salary_amount) }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($payroll->incentive_amount) }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($payroll->total_amount) }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($payroll->deductions) }}</td>
                                    <td><strong>{{ \App\CentralLogics\Helpers::format_currency($payroll->net_payable) }}</strong></td>
                                    <td>
                                        @if($payroll->status == 'generated')
                                            <span class="badge badge-soft-warning">{{ translate('messages.generated') }}</span>
                                        @elseif($payroll->status == 'paid')
                                            <span class="badge badge-soft-success">{{ translate('messages.paid') }}</span>
                                        @else
                                            <span class="badge badge-soft-danger">{{ translate('messages.cancelled') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.transactions.payroll.show', $payroll->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="tio-visible"></i>
                                        </a>
                                        @if($payroll->status == 'generated')
                                            <form method="POST" action="{{ route('admin.transactions.payroll.regenerate', $payroll->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('{{ translate('messages.are_you_sure') }}')" title="{{ translate('messages.regenerate_payroll') }}">
                                                    <i class="tio-refresh"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($payrolls->count() === 0)
                    <div class="empty--data text-center py-5">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public" width="100">
                        <h5 class="mt-3">{{ translate('messages.no_data_found') }}</h5>
                    </div>
                @endif
            </div>
            @if($payrolls->count() > 0)
                <div class="card-footer">
                    {{ $payrolls->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
