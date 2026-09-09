@extends('layouts.admin.app')

@section('title', translate('messages.payroll_details'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title">
                    <i class="tio-wallet"></i> {{ translate('messages.payroll_details') }} #{{ $payroll->id }}
                </h1>
                <a href="{{ route('admin.transactions.payroll.index') }}" class="btn btn-secondary">
                    <i class="tio-arrow-backward"></i> {{ translate('messages.back') }}
                </a>
            </div>
        </div>

        <div class="row gy-3">
            <!-- DM Info & Period -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="card-header-title">{{ translate('messages.delivery_man_info') }}</h5></div>
                    <div class="card-body">
                        @if($payroll->deliveryMan)
                            <p><strong>{{ translate('messages.name') }}:</strong> {{ $payroll->deliveryMan->f_name . ' ' . $payroll->deliveryMan->l_name }}</p>
                            <p><strong>{{ translate('messages.phone') }}:</strong> {{ $payroll->deliveryMan->phone }}</p>
                            <p><strong>{{ translate('messages.email') }}:</strong> {{ $payroll->deliveryMan->email ?? translate('messages.N/A') }}</p>
                        @endif
                        <hr>
                        <p><strong>{{ translate('messages.period') }}:</strong> {{ $payroll->period_from->format('d M Y') }} — {{ $payroll->period_to->format('d M Y') }}</p>
                        <p><strong>{{ translate('messages.total_deliveries') }}:</strong> {{ $payroll->total_deliveries }}</p>
                        <p><strong>{{ translate('messages.avg_delivery_time') }}:</strong> {{ $payroll->avg_delivery_time ? round($payroll->avg_delivery_time) . ' ' . translate('messages.min') : translate('messages.N/A') }}</p>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="card-header-title">{{ translate('messages.financial_summary') }}</h5></div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td>{{ translate('messages.base_salary') }}</td>
                                <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($payroll->salary_amount) }}</td>
                            </tr>
                            <tr>
                                <td>{{ translate('messages.incentive') }}</td>
                                <td class="text-right text-success">+ {{ \App\CentralLogics\Helpers::format_currency($payroll->incentive_amount) }}</td>
                            </tr>
                            <tr>
                                <td>{{ translate('messages.total') }}</td>
                                <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($payroll->total_amount) }}</td>
                            </tr>
                            <tr>
                                <td>{{ translate('messages.deductions') }}</td>
                                <td class="text-right text-danger">- {{ \App\CentralLogics\Helpers::format_currency($payroll->deductions) }}</td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>{{ translate('messages.net_payable') }}</strong></td>
                                <td class="text-right"><strong>{{ \App\CentralLogics\Helpers::format_currency($payroll->net_payable) }}</strong></td>
                            </tr>
                        </table>
                        <div class="mt-3">
                            @if($payroll->status == 'generated')
                                <span class="badge badge-soft-warning p-2">{{ translate('messages.generated') }}</span>
                            @elseif($payroll->status == 'paid')
                                <span class="badge badge-soft-success p-2">{{ translate('messages.paid') }}</span>
                                <br><small class="text-muted">{{ translate('messages.paid_on') }}: {{ $payroll->paid_at ? $payroll->paid_at->format('d M Y, h:i A') : '' }}</small>
                                <br><small class="text-muted">{{ translate('messages.method') }}: {{ $payroll->paid_method }}</small>
                                @if($payroll->paid_ref)
                                    <br><small class="text-muted">{{ translate('messages.reference') }}: {{ $payroll->paid_ref }}</small>
                                @endif
                            @else
                                <span class="badge badge-soft-danger p-2">{{ translate('messages.cancelled') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Incentive Breakdown -->
            @if($payroll->incentive_breakdown && count($payroll->incentive_breakdown) > 0)
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h5 class="card-header-title">{{ translate('messages.incentive_breakdown') }}</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-borderless table-thead-bordered card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('messages.slab_title') }}</th>
                                    <th>{{ translate('messages.type') }}</th>
                                    <th>{{ translate('messages.actual_value') }}</th>
                                    <th>{{ translate('messages.bonus_amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payroll->incentive_breakdown as $item)
                                    <tr>
                                        <td>{{ $item['title'] }}</td>
                                        <td>
                                            @if($item['type'] == 'delivery_count')
                                                <span class="badge badge-soft-primary">{{ translate('messages.delivery_count') }}</span>
                                            @else
                                                <span class="badge badge-soft-info">{{ translate('messages.avg_delivery_time') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $item['actual_value'] }}
                                            {{ $item['type'] == 'avg_delivery_time' ? translate('messages.min') : translate('messages.deliveries') }}
                                        </td>
                                        <td>{{ \App\CentralLogics\Helpers::format_currency($item['bonus_amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Mark as Paid / Cancel -->
            @if($payroll->status == 'generated')
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-header-title">{{ translate('messages.mark_as_paid') }}</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.transactions.payroll.mark-paid', $payroll->id) }}">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">{{ translate('messages.payment_method') }} <span class="text-danger">*</span></label>
                                <select name="paid_method" class="form-control" required>
                                    <option value="cash">{{ translate('messages.cash') }}</option>
                                    <option value="bank_transfer">{{ translate('messages.bank_transfer') }}</option>
                                    <option value="digital_payment">{{ translate('messages.digital_payment') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('messages.reference') }}</label>
                                <input type="text" name="paid_ref" class="form-control" placeholder="{{ translate('messages.reference') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('messages.note') }}</label>
                                <textarea name="note" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="tio-checkmark-circle"></i> {{ translate('messages.mark_as_paid') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-header-title">{{ translate('messages.actions') }}</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.transactions.payroll.regenerate', $payroll->id) }}" class="mb-3">
                            @csrf
                            <p>{{ translate('messages.regenerate_payroll_description') }}</p>
                            <button type="submit" class="btn btn-warning" onclick="return confirm('{{ translate('messages.are_you_sure') }}')">
                                <i class="tio-refresh"></i> {{ translate('messages.regenerate_payroll') }}
                            </button>
                        </form>
                        <hr>
                        <form method="POST" action="{{ route('admin.transactions.payroll.cancel', $payroll->id) }}">
                            @csrf
                            <p>{{ translate('messages.cancel_payroll_description') }}</p>
                            <button type="submit" class="btn btn-danger" onclick="return confirm('{{ translate('messages.are_you_sure') }}')">
                                <i class="tio-clear"></i> {{ translate('messages.cancel_payroll') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
