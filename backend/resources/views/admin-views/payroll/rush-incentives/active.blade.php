@extends('layouts.admin.app')
@section('title', 'Active Rush Periods')
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title"><i class="tio-flash text-warning"></i> Active Rush Periods</h1>
            <a href="{{ route('admin.transactions.payroll.rush-incentives.index') }}" class="btn btn-secondary"><i class="tio-arrow-backward"></i> Back</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5>Manually Activate Rush</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.transactions.payroll.rush-incentives.manual-activate') }}">
                @csrf
                <div class="row">
                    <div class="col-sm-4">
                        <select name="rush_incentive_id" class="form-control" required>
                            <option value="">Select Rush Rule</option>
                            @foreach(\App\Models\DmRushIncentive::active()->get() as $rule)
                                <option value="{{ $rule->id }}">{{ $rule->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <select name="zone_id" class="form-control" required>
                            <option value="">Select Zone</option>
                            @foreach(\App\Models\Zone::all() as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-warning w-100"><i class="tio-flash"></i> Activate Rush</button>
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
                            <th>Rule</th><th>Zone</th><th>Started</th><th>Pending Orders</th><th>Notified</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activations as $act)
                        <tr>
                            <td>{{ $act->rushIncentive->title ?? 'N/A' }}</td>
                            <td>{{ $act->zone->name ?? 'N/A' }}</td>
                            <td>{{ $act->started_at->diffForHumans() }}</td>
                            <td>{{ $act->pending_orders_count }}</td>
                            <td>{!! $act->dm_notified ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.transactions.payroll.rush-incentives.deactivate', $act->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger">Deactivate</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($activations->count() === 0)
                <div class="text-center py-5"><h5>No active rush periods</h5></div>
            @endif
        </div>
        @if($activations->count() > 0)
            <div class="card-footer">{{ $activations->links() }}</div>
        @endif
    </div>
</div>
@endsection
