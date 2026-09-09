@extends('layouts.admin.app')
@section('title', translate('messages.fuel_incentives'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-car"></i> {{ translate('messages.fuel_incentives') }}</h1>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5>{{ translate('messages.add_fuel_incentive') }}</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.transactions.payroll.fuel-incentives.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-sm-3">
                        <label class="form-label">{{ translate('messages.title') }} *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">{{ translate('messages.rate_per_km') }} *</label>
                        <input type="number" step="0.01" name="rate_per_km" class="form-control" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">{{ translate('messages.fuel_price_ref') }}</label>
                        <input type="number" step="0.01" name="fuel_price_reference" class="form-control">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">{{ translate('messages.zone') }}</label>
                        <select name="zone_id" class="form-control">
                            <option value="">All</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">{{ translate('messages.effective_from') }}</label>
                        <input type="date" name="effective_from" class="form-control">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">{{ translate('messages.effective_to') }}</label>
                        <input type="date" name="effective_to" class="form-control">
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

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-borderless table-hover">
                <thead class="thead-light"><tr>
                    <th>{{ translate('messages.title') }}</th>
                    <th>{{ translate('messages.rate_per_km') }}</th>
                    <th>{{ translate('messages.zone') }}</th>
                    <th>{{ translate('messages.period') }}</th>
                    <th>{{ translate('messages.status') }}</th>
                    <th>{{ translate('messages.action') }}</th>
                </tr></thead>
                <tbody>
                @foreach($incentives as $inc)
                    <tr>
                        <td>{{ $inc->title }}</td>
                        <td>{{ $inc->rate_per_km }}/km</td>
                        <td>{{ $inc->zone ? $inc->zone->name : 'All' }}</td>
                        <td>{{ $inc->effective_from ? $inc->effective_from->format('d M') . ' - ' . ($inc->effective_to ? $inc->effective_to->format('d M') : 'ongoing') : 'Always' }}</td>
                        <td><span class="badge badge-{{ $inc->status ? 'success' : 'danger' }}">{{ $inc->status ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('admin.transactions.payroll.fuel-incentives.delete', $inc->id) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">{{ translate('messages.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $incentives->links() }}
        </div>
    </div>
</div>
@endsection
