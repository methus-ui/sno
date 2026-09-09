@extends('layouts.admin.app')
@section('title', translate('messages.time_based_incentives'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-clock"></i> {{ translate('messages.time_based_incentives') }}
            <span class="badge badge-soft-dark ml-2">{{ $incentives->total() }}</span>
        </h1>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="card-header-title">Add Time-Based Incentive</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.transactions.payroll.time-incentives.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-sm-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Lunch Rush Bonus" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Time From <span class="text-danger">*</span></label>
                        <input type="time" name="time_from" class="form-control" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Time To <span class="text-danger">*</span></label>
                        <input type="time" name="time_to" class="form-control" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Bonus Type <span class="text-danger">*</span></label>
                        <select name="bonus_type" class="form-control" required>
                            <option value="fixed">Fixed</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Bonus Value <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="bonus_value" class="form-control" required min="0">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Zone (optional)</label>
                        <select name="zone_id" class="form-control">
                            <option value="">All Zones</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Days of Week (JSON, e.g. [1,2,3,4,5])</label>
                        <input type="text" name="days_of_week" class="form-control" placeholder="[0,1,2,3,4,5,6]">
                    </div>
                    <div class="col-sm-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="tio-add"></i> Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>SL</th><th>Title</th><th>Time Window</th><th>Bonus</th><th>Zone</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($incentives as $k => $inc)
                        <tr>
                            <td>{{ $incentives->firstItem() + $k }}</td>
                            <td>{{ $inc->title }}</td>
                            <td>{{ $inc->time_from }} - {{ $inc->time_to }}</td>
                            <td>{{ $inc->bonus_type == 'fixed' ? \App\CentralLogics\Helpers::format_currency($inc->bonus_value) : $inc->bonus_value.'%' }}</td>
                            <td>{{ $inc->zone ? $inc->zone->name : 'All' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.transactions.payroll.time-incentives.toggle', $inc->id) }}" class="d-inline">
                                    @csrf
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="toggle-switch-input" onchange="this.form.submit()" {{ $inc->status ? 'checked' : '' }}>
                                        <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                                    </label>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.transactions.payroll.time-incentives.delete', $inc->id) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="tio-delete"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($incentives->count() === 0)
                <div class="empty--data text-center py-5"><h5>{{ translate('messages.no_data_found') }}</h5></div>
            @endif
        </div>
        @if($incentives->count() > 0)
            <div class="card-footer">{{ $incentives->links() }}</div>
        @endif
    </div>
</div>
@endsection
