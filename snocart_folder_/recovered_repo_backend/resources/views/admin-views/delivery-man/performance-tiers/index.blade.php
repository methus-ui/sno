@extends('layouts.admin.app')
@section('title', 'Performance Tiers')
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title"><i class="tio-medal"></i> Performance Tiers
                <span class="badge badge-soft-dark ml-2">{{ $tiers->total() }}</span>
            </h1>
            <form method="POST" action="{{ route('admin.transactions.dm-performance.tiers.recalculate') }}">
                @csrf
                <button type="submit" class="btn btn-info" onclick="return confirm('This will recalculate tiers for all delivery men. Continue?')">
                    <i class="tio-refresh"></i> Recalculate All Tiers
                </button>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="card-header-title">Add Performance Tier</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.transactions.dm-performance.tiers.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-sm-2">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Gold" required>
                    </div>
                    <div class="col-sm-1">
                        <label class="form-label">Rank</label>
                        <input type="number" name="rank_order" class="form-control" value="1" required min="1">
                    </div>
                    <div class="col-sm-1">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control" value="#FFD700">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Min Rating</label>
                        <input type="number" step="0.01" name="min_rating" class="form-control" min="0" max="5">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Min Deliveries</label>
                        <input type="number" name="min_deliveries" class="form-control" min="0">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Min Accept %</label>
                        <input type="number" name="min_acceptance_rate" class="form-control" min="0" max="100">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Bonus/Delivery <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="bonus_per_delivery" class="form-control" required min="0">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Priority Boost</label>
                        <input type="number" name="priority_boost" class="form-control" value="0" min="0">
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
                            <th>Rank</th><th>Name</th><th>Min Rating</th><th>Min Deliveries</th><th>Min Accept %</th><th>Bonus/Delivery</th><th>DMs</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tiers as $tier)
                        <tr>
                            <td><span class="badge" style="background-color:{{ $tier->color }};color:#fff">{{ $tier->rank_order }}</span></td>
                            <td><strong style="color:{{ $tier->color }}">{{ $tier->name }}</strong></td>
                            <td>{{ $tier->min_rating ?? '-' }}</td>
                            <td>{{ $tier->min_deliveries ?? '-' }}</td>
                            <td>{{ $tier->min_acceptance_rate ? $tier->min_acceptance_rate.'%' : '-' }}</td>
                            <td>{{ \App\CentralLogics\Helpers::format_currency($tier->bonus_per_delivery) }}</td>
                            <td>{{ $tier->deliveryMen()->count() }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.transactions.dm-performance.tiers.toggle', $tier->id) }}" class="d-inline">
                                    @csrf
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="toggle-switch-input" onchange="this.form.submit()" {{ $tier->status ? 'checked' : '' }}>
                                        <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                                    </label>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.transactions.dm-performance.tiers.delete', $tier->id) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="tio-delete"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($tiers->count() === 0)
                <div class="empty--data text-center py-5"><h5>{{ translate('messages.no_data_found') }}</h5></div>
            @endif
        </div>
        @if($tiers->count() > 0)
            <div class="card-footer">{{ $tiers->links() }}</div>
        @endif
    </div>
</div>
@endsection
