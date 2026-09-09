@extends('layouts.admin.app')
@section('title', translate('messages.minimum_wage_guarantee'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-shield-check"></i> {{ translate('messages.minimum_wage_guarantee') }}</h1>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5>{{ translate('messages.configure_minimum_wage') }}</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.transactions.payroll.min-wage.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-sm-3">
                        <label class="form-label">{{ translate('messages.zone') }}</label>
                        <select name="zone_id" class="form-control">
                            <option value="">All Zones</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">{{ translate('messages.dm_type') }}</label>
                        <select name="dm_type" class="form-control">
                            <option value="all">All</option>
                            <option value="zone_wise">Freelancer</option>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">{{ translate('messages.min_daily_amount') }} *</label>
                        <input type="number" step="0.01" name="min_daily_amount" class="form-control" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">{{ translate('messages.min_hours_required') }} *</label>
                        <input type="number" step="0.5" name="min_hours_required" class="form-control" value="8" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">{{ translate('messages.status') }}</label>
                        <div class="form-check"><input type="checkbox" name="status" class="form-check-input" checked></div>
                    </div>
                </div>
                <div class="mt-3"><button type="submit" class="btn btn-primary">{{ translate('messages.save') }}</button></div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5>{{ translate('messages.current_settings') }}</h5></div>
        <div class="card-body p-0">
            <table class="table table-borderless table-hover">
                <thead class="thead-light"><tr>
                    <th>{{ translate('messages.zone') }}</th>
                    <th>{{ translate('messages.dm_type') }}</th>
                    <th>{{ translate('messages.min_amount') }}</th>
                    <th>{{ translate('messages.min_hours') }}</th>
                    <th>{{ translate('messages.status') }}</th>
                    <th>{{ translate('messages.action') }}</th>
                </tr></thead>
                <tbody>
                @foreach($settings as $s)
                    <tr>
                        <td>{{ $s->zone ? $s->zone->name : 'All' }}</td>
                        <td>{{ ucfirst($s->dm_type) }}</td>
                        <td>{{ \App\CentralLogics\Helpers::format_currency($s->min_daily_amount) }}</td>
                        <td>{{ $s->min_hours_required }}h</td>
                        <td><span class="badge badge-{{ $s->status ? 'success' : 'danger' }}">{{ $s->status ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('admin.transactions.payroll.min-wage.delete', $s->id) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">{{ translate('messages.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5>{{ translate('messages.wage_adjustments') }}</h5></div>
        <div class="card-body p-0">
            <table class="table table-borderless table-hover">
                <thead class="thead-light"><tr>
                    <th>{{ translate('messages.delivery_man') }}</th>
                    <th>{{ translate('messages.date') }}</th>
                    <th>{{ translate('messages.earned') }}</th>
                    <th>{{ translate('messages.guaranteed') }}</th>
                    <th>{{ translate('messages.adjustment') }}</th>
                    <th>{{ translate('messages.hours') }}</th>
                    <th>{{ translate('messages.status') }}</th>
                    <th>{{ translate('messages.action') }}</th>
                </tr></thead>
                <tbody>
                @foreach($adjustments as $adj)
                    <tr>
                        <td>{{ $adj->deliveryMan ? $adj->deliveryMan->f_name . ' ' . $adj->deliveryMan->l_name : 'N/A' }}</td>
                        <td>{{ $adj->date->format('d M Y') }}</td>
                        <td>{{ \App\CentralLogics\Helpers::format_currency($adj->total_earned) }}</td>
                        <td>{{ \App\CentralLogics\Helpers::format_currency($adj->min_guaranteed) }}</td>
                        <td class="text-success font-weight-bold">{{ \App\CentralLogics\Helpers::format_currency($adj->adjustment_amount) }}</td>
                        <td>{{ $adj->hours_logged }}h</td>
                        <td><span class="badge badge-{{ $adj->status === 'paid' ? 'success' : ($adj->status === 'pending' ? 'warning' : 'danger') }}">{{ ucfirst($adj->status) }}</span></td>
                        <td>
                            @if($adj->status === 'pending')
                                <form method="POST" action="{{ route('admin.transactions.payroll.min-wage.approve-adjustment', $adj->id) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-success">Pay</button></form>
                                <form method="POST" action="{{ route('admin.transactions.payroll.min-wage.reject-adjustment', $adj->id) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-danger">Reject</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $adjustments->links() }}
        </div>
    </div>
</div>
@endsection
