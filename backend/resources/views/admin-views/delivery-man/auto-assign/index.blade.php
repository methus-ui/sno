@extends('layouts.admin.app')
@section('title', translate('messages.auto_assignment_settings'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-flash"></i> {{ translate('messages.auto_assignment_settings') }}</h1>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5>{{ translate('messages.add_update_setting') }}</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.transactions.dm-performance.auto-assign.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-sm-3">
                        <label class="form-label">{{ translate('messages.zone') }}</label>
                        <select name="zone_id" class="form-control">
                            <option value="">{{ translate('messages.all_zones') }}</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">{{ translate('messages.max_radius_km') }}</label>
                        <input type="number" step="0.5" name="max_radius_km" class="form-control" value="5" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">{{ translate('messages.fallback_seconds') }}</label>
                        <input type="number" name="fallback_to_manual_after_seconds" class="form-control" value="120" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">{{ translate('messages.max_orders_per_dm') }}</label>
                        <input type="number" name="max_orders_per_dm" class="form-control" value="3" required min="1">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">{{ translate('messages.enabled') }}</label>
                        <div class="form-check">
                            <input type="checkbox" name="is_enabled" class="form-check-input" checked>
                            <label class="form-check-label">{{ translate('messages.enable') }}</label>
                        </div>
                    </div>
                </div>
                <hr>
                <h6>{{ translate('messages.priority_weights') }} ({{ translate('messages.must_sum_to_1') }})</h6>
                <div class="row g-3">
                    <div class="col-sm-3">
                        <label class="form-label">{{ translate('messages.distance_weight') }}</label>
                        <input type="number" step="0.05" name="distance_weight" class="form-control" value="0.40" min="0" max="1">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">{{ translate('messages.rating_weight') }}</label>
                        <input type="number" step="0.05" name="rating_weight" class="form-control" value="0.25" min="0" max="1">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">{{ translate('messages.acceptance_rate_weight') }}</label>
                        <input type="number" step="0.05" name="acceptance_rate_weight" class="form-control" value="0.20" min="0" max="1">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">{{ translate('messages.tier_weight') }}</label>
                        <input type="number" step="0.05" name="tier_weight" class="form-control" value="0.15" min="0" max="1">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">{{ translate('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5>{{ translate('messages.current_settings') }}</h5></div>
        <div class="card-body p-0">
            <table class="table table-borderless table-hover">
                <thead class="thead-light"><tr>
                    <th>{{ translate('messages.zone') }}</th>
                    <th>{{ translate('messages.enabled') }}</th>
                    <th>{{ translate('messages.max_radius') }}</th>
                    <th>{{ translate('messages.max_orders') }}</th>
                    <th>{{ translate('messages.fallback') }}</th>
                    <th>{{ translate('messages.action') }}</th>
                </tr></thead>
                <tbody>
                @foreach($settings as $s)
                    <tr>
                        <td>{{ $s->zone ? $s->zone->name : 'All Zones' }}</td>
                        <td><span class="badge badge-{{ $s->is_enabled ? 'success' : 'danger' }}">{{ $s->is_enabled ? 'ON' : 'OFF' }}</span></td>
                        <td>{{ $s->max_radius_km }} km</td>
                        <td>{{ $s->max_orders_per_dm }}</td>
                        <td>{{ $s->fallback_to_manual_after_seconds }}s</td>
                        <td>
                            <form method="POST" action="{{ route('admin.transactions.dm-performance.auto-assign.delete', $s->id) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">{{ translate('messages.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
