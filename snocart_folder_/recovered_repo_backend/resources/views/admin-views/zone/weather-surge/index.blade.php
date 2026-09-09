@extends('layouts.admin.app')

@section('title', translate('Weather-Based Surge Pricing'))

@push('css_or_js')
<style>
    .rule-card { border-left: 4px solid #e67e22; margin-bottom: 10px; }
    .rule-card.inactive { border-left-color: #95a5a6; opacity: 0.7; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{ asset('public/assets/admin/img/edit.png') }}" class="w--26" alt="">
            </span>
            <span>{{ translate('Weather-Based Surge Pricing') }}</span>
        </h1>
        <p class="page-header-description">
            {{ $zone->name }} - {{ translate('Configure delivery fee surcharges based on weather conditions') }}
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
                    <h5 class="card-title"><i class="tio-add-circle"></i> {{ translate('Add Weather Rule') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.business-settings.zone.weather-surge.store', $zone->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">{{ translate('Weather Condition') }} <span class="text-danger">*</span></label>
                            <select name="weather_condition" class="form-control" required>
                                @foreach($conditions as $condition)
                                    <option value="{{ $condition }}">{{ translate(str_replace('_', ' ', ucfirst($condition))) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Surge Percentage') }} (%) <span class="text-danger">*</span></label>
                            <input type="number" name="surge_percentage" class="form-control" step="0.01" min="0" max="500" required
                                   placeholder="{{ translate('e.g., 20') }}">
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Min Temp') }} (°C)</label>
                                    <input type="number" name="min_temp" class="form-control" step="0.1"
                                           placeholder="{{ translate('Optional') }}">
                                    <small class="text-muted">{{ translate('For extreme heat/cold') }}</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Max Temp') }} (°C)</label>
                                    <input type="number" name="max_temp" class="form-control" step="0.1"
                                           placeholder="{{ translate('Optional') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Customer Message') }}</label>
                            <input type="text" name="message" class="form-control" maxlength="255"
                                   placeholder="{{ translate('e.g., Delivery fee increased due to rain') }}">
                        </div>

                        <div class="btn--container justify-content-end">
                            <button type="reset" class="btn btn--reset">{{ translate('Reset') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('Add Rule') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow--card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-cloud"></i> {{ translate('Weather Surge Rules') }}
                        <span class="badge badge-soft-primary ml-2">{{ $rules->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($rules->count() > 0)
                        @foreach($rules as $rule)
                            <div class="card rule-card {{ !$rule->is_enabled ? 'inactive' : '' }}">
                                <div class="card-body py-2 px-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <strong>{{ translate(str_replace('_', ' ', ucfirst($rule->weather_condition))) }}</strong>
                                            @if($rule->min_temp || $rule->max_temp)
                                                <br><small class="text-muted">
                                                    {{ $rule->min_temp ? $rule->min_temp.'°C' : '' }}
                                                    {{ $rule->min_temp && $rule->max_temp ? '-' : '' }}
                                                    {{ $rule->max_temp ? $rule->max_temp.'°C' : '' }}
                                                </small>
                                            @endif
                                            <br><small class="text-muted">{{ $rule->zone_id ? translate('This zone') : translate('All zones') }}</small>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <span class="badge badge-danger">+{{ $rule->surge_percentage }}%</span>
                                        </div>
                                        <div class="col-md-3">
                                            @if($rule->message)
                                                <small class="text-muted"><i class="tio-comment-text-outlined"></i> {{ $rule->message }}</small>
                                            @endif
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <a href="{{ route('admin.business-settings.zone.weather-surge.status', ['id' => $rule->id, 'status' => $rule->is_enabled ? 0 : 1]) }}"
                                               class="btn btn-sm {{ $rule->is_enabled ? 'btn-success' : 'btn-secondary' }}">
                                                <i class="tio-{{ $rule->is_enabled ? 'checkmark-circle' : 'clear-circle' }}"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editModal{{ $rule->id }}">
                                                <i class="tio-edit"></i>
                                            </button>
                                            <form action="{{ route('admin.business-settings.zone.weather-surge.destroy', $rule->id) }}"
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
                            <div class="modal fade" id="editModal{{ $rule->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ translate('Edit Weather Rule') }}</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form action="{{ route('admin.business-settings.zone.weather-surge.update', $rule->id) }}" method="POST">
                                            @csrf @method('PUT')
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>{{ translate('Weather Condition') }}</label>
                                                    <select name="weather_condition" class="form-control" required>
                                                        @foreach($conditions as $condition)
                                                            <option value="{{ $condition }}" {{ $rule->weather_condition == $condition ? 'selected' : '' }}>
                                                                {{ translate(str_replace('_', ' ', ucfirst($condition))) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>{{ translate('Surge Percentage') }} (%)</label>
                                                    <input type="number" name="surge_percentage" class="form-control" value="{{ $rule->surge_percentage }}" step="0.01" min="0" max="500" required>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label>{{ translate('Min Temp') }} (°C)</label>
                                                            <input type="number" name="min_temp" class="form-control" value="{{ $rule->min_temp }}" step="0.1">
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label>{{ translate('Max Temp') }} (°C)</label>
                                                            <input type="number" name="max_temp" class="form-control" value="{{ $rule->max_temp }}" step="0.1">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>{{ translate('Customer Message') }}</label>
                                                    <input type="text" name="message" class="form-control" value="{{ $rule->message }}" maxlength="255">
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
                            <p class="mt-3 text-muted">{{ translate('No weather surge rules configured') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow--card mt-3">
                <div class="card-body">
                    <h6><i class="tio-info text-primary"></i> {{ translate('How it works') }}</h6>
                    <ul class="mb-0 pl-3">
                        <li>{{ translate('Weather data is fetched from Open-Meteo API (free) every 15 minutes.') }}</li>
                        <li>{{ translate('Weather surge has the highest priority — overrides time-based and static surges.') }}</li>
                        <li>{{ translate('Conditions: Rain, Heavy Rain, Storm, Snow, Extreme Heat, Fog.') }}</li>
                        <li>{{ translate('Use Min/Max Temp for extreme heat rules (e.g., surge when temperature > 42°C).') }}</li>
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
