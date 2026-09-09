@extends('layouts.admin.app')

@section('title', translate('Demand-Based Surge Pricing'))

@push('css_or_js')
<style>
    .level-card { border-left: 4px solid #8e44ad; margin-bottom: 10px; }
    .level-card.inactive { border-left-color: #95a5a6; opacity: 0.7; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{ asset('public/assets/admin/img/edit.png') }}" class="w--26" alt="">
            </span>
            <span>{{ translate('Demand-Based Surge Pricing') }}</span>
        </h1>
        <p class="page-header-description">
            {{ $zone->name }} - {{ translate('Auto-increase delivery fee when demand is high and drivers are low') }}
        </p>
    </div>

    <div class="mb-3">
        <a href="{{ route('admin.business-settings.zone.module-setup', $zone->id) }}" class="btn btn-secondary">
            <i class="tio-arrow-left"></i> {{ translate('Back to Zone Settings') }}
        </a>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow--card">
                <div class="card-header">
                    <h5 class="card-title"><i class="tio-add-circle"></i> {{ translate('Add Demand Level') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.business-settings.zone.demand-surge.store', $zone->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">{{ translate('Min Pending Orders') }} <span class="text-danger">*</span></label>
                            <input type="number" name="min_pending_orders" class="form-control" min="1" required
                                   placeholder="{{ translate('e.g., 5') }}">
                            <small class="text-muted">{{ translate('Surge triggers when pending orders >= this') }}</small>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Max Available Drivers') }} <span class="text-danger">*</span></label>
                            <input type="number" name="max_available_dms" class="form-control" min="0" required
                                   placeholder="{{ translate('e.g., 3') }}">
                            <small class="text-muted">{{ translate('Surge triggers when active drivers <= this') }}</small>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Surge Percentage') }} (%) <span class="text-danger">*</span></label>
                            <input type="number" name="surge_percentage" class="form-control" step="0.01" min="0" max="500" required
                                   placeholder="{{ translate('e.g., 25') }}">
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Customer Message') }}</label>
                            <input type="text" name="message" class="form-control" maxlength="255"
                                   placeholder="{{ translate('e.g., High demand in your area') }}">
                        </div>

                        <div class="btn--container justify-content-end">
                            <button type="reset" class="btn btn--reset">{{ translate('Reset') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('Add Level') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow--card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-trending-up"></i> {{ translate('Demand Surge Levels') }}
                        <span class="badge badge-soft-primary ml-2">{{ $levels->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($levels->count() > 0)
                        @foreach($levels as $level)
                            <div class="card level-card {{ !$level->is_enabled ? 'inactive' : '' }}">
                                <div class="card-body py-2 px-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <strong>{{ translate('Orders') }} >= {{ $level->min_pending_orders }}</strong>
                                            <br><small class="text-muted">{{ translate('Drivers') }} <= {{ $level->max_available_dms }}</small>
                                            <br><small class="text-muted">{{ $level->zone_id ? translate('This zone') : translate('All zones') }}</small>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <span class="badge badge-danger">+{{ $level->surge_percentage }}%</span>
                                        </div>
                                        <div class="col-md-2">
                                            @if($level->message)
                                                <small class="text-muted">{{ $level->message }}</small>
                                            @endif
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <a href="{{ route('admin.business-settings.zone.demand-surge.status', ['id' => $level->id, 'status' => $level->is_enabled ? 0 : 1]) }}"
                                               class="btn btn-sm {{ $level->is_enabled ? 'btn-success' : 'btn-secondary' }}">
                                                <i class="tio-{{ $level->is_enabled ? 'checkmark-circle' : 'clear-circle' }}"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editLevel{{ $level->id }}">
                                                <i class="tio-edit"></i>
                                            </button>
                                            <form action="{{ route('admin.business-settings.zone.demand-surge.destroy', $level->id) }}"
                                                  method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('{{ translate('Are you sure?') }}')">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editLevel{{ $level->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ translate('Edit Demand Level') }}</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form action="{{ route('admin.business-settings.zone.demand-surge.update', $level->id) }}" method="POST">
                                            @csrf @method('PUT')
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>{{ translate('Min Pending Orders') }}</label>
                                                    <input type="number" name="min_pending_orders" class="form-control" value="{{ $level->min_pending_orders }}" min="1" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>{{ translate('Max Available Drivers') }}</label>
                                                    <input type="number" name="max_available_dms" class="form-control" value="{{ $level->max_available_dms }}" min="0" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>{{ translate('Surge Percentage') }} (%)</label>
                                                    <input type="number" name="surge_percentage" class="form-control" value="{{ $level->surge_percentage }}" step="0.01" min="0" max="500" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>{{ translate('Customer Message') }}</label>
                                                    <input type="text" name="message" class="form-control" value="{{ $level->message }}" maxlength="255">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                                                <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <img src="{{ asset('public/assets/admin/img/empty-box.png') }}" alt="" width="120">
                            <p class="mt-3 text-muted">{{ translate('No demand surge levels configured') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow--card mt-3">
                <div class="card-body">
                    <h6><i class="tio-info text-primary"></i> {{ translate('How it works') }}</h6>
                    <ul class="mb-0 pl-3">
                        <li>{{ translate('Demand surge activates when pending orders exceed the threshold AND active drivers are below the limit.') }}</li>
                        <li>{{ translate('If multiple levels match, the highest surge percentage is applied.') }}</li>
                        <li>{{ translate('Demand surge has second priority after weather surge.') }}</li>
                        <li>{{ translate('Real-time: checks pending orders and active drivers at the time of order placement.') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    "use strict";
    $(document).ready(function() { $('[data-toggle="tooltip"]').tooltip(); });
</script>
@endpush
