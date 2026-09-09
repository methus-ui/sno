@extends('layouts.admin.app')

@section('title', translate('offline_monitor_settings'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <i class="tio-settings"></i> {{ translate('offline_monitor_settings') }}
                </h1>
                <p class="text-muted">{{ translate('configure_offline_tracking') }}</p>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.deliveryman.offline-monitor.index') }}" class="btn btn-secondary">
                    <i class="tio-arrow-backward"></i> {{ translate('back') }}
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.deliveryman.offline-monitor.settings-update') }}" method="POST">
        @csrf

        <!-- Offline Threshold Settings -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="tio-time"></i> {{ translate('offline_threshold_minutes') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="offline_threshold">
                                {{ translate('offline_threshold_minutes') }}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control"
                                   id="offline_threshold"
                                   name="offline_threshold"
                                   value="{{ $threshold }}"
                                   min="1"
                                   max="60"
                                   required>
                            <small class="form-text text-muted">
                                {{ translate('offline_threshold_desc') }}
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <strong><i class="tio-info"></i> {{ translate('current_setting') }}:</strong>
                            <p class="mb-0">{{ translate('delivery_men_offline_up_to') }} <strong>{{ $threshold }} {{ translate('minutes') }}</strong> {{ translate('before_losing_eligibility') }}.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Break Time Settings -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="tio-pause"></i> {{ translate('break_time_allowance') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="form-group">
                            <label class="toggle-switch d-flex align-items-center mb-3" for="break_time_enabled">
                                <input type="checkbox"
                                       class="toggle-switch-input"
                                       id="break_time_enabled"
                                       name="break_time_enabled"
                                       value="1"
                                       {{ $break_enabled ? 'checked' : '' }}>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                                <span class="toggle-switch-content">
                                    <span class="d-block">{{ translate('enable_break_time') }}</span>
                                    <small class="d-block text-muted">{{ translate('break_time_desc') }}</small>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6" id="break_time_settings" style="display: {{ $break_enabled ? 'block' : 'none' }};">
                        <div class="form-group">
                            <label for="break_time_minutes">
                                {{ translate('max_break_minutes') }}
                            </label>
                            <input type="number"
                                   class="form-control"
                                   id="break_time_minutes"
                                   name="break_time_minutes"
                                   value="{{ $break_minutes }}"
                                   min="0"
                                   max="120">
                            <small class="form-text text-muted">
                                {{ translate('break_time_not_counted') }}
                            </small>
                        </div>
                    </div>

                    <div class="col-md-6" id="break_info" style="display: {{ $break_enabled ? 'block' : 'none' }};">
                        <div class="alert alert-warning">
                            <strong><i class="tio-info"></i> {{ translate('how_it_works') }}:</strong>
                            <ul class="mb-0 pl-3">
                                <li>{{ translate('dm_can_take_breaks') }}</li>
                                <li>{{ translate('break_time_excluded') }}</li>
                                <li>{{ translate('maximum') }} {{ $break_minutes }} {{ translate('minutes') }} {{ translate('per_day') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning Thresholds -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="tio-notifications-alert"></i> {{ translate('notification_settings') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>{{ translate('automatic_notifications') }}:</strong>
                    <ul class="mb-0">
                        <li>{{ translate('60_percent_warning') }}: {{ translate('at') }} {{ round($threshold * 0.6) }} {{ translate('minutes') }}</li>
                        <li>{{ translate('80_percent_critical') }}: {{ translate('at') }} {{ round($threshold * 0.8) }} {{ translate('minutes') }}</li>
                        <li>{{ translate('threshold_exceeded') }}: {{ translate('at') }} {{ $threshold }} {{ translate('minutes') }}</li>
                    </ul>
                    <small class="text-muted d-block mt-2">{{ translate('push_notifications_sent_auto') }}</small>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="card">
            <div class="card-body">
                <div class="btn-group" role="group">
                    <button type="submit" class="btn btn-primary">
                        <i class="tio-save"></i> {{ translate('save_changes') }}
                    </button>
                    <a href="{{ route('admin.deliveryman.offline-monitor.index') }}" class="btn btn-secondary">
                        {{ translate('cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script>
    // Show/hide break time settings based on toggle
    $('#break_time_enabled').on('change', function() {
        if($(this).is(':checked')) {
            $('#break_time_settings').slideDown();
            $('#break_info').slideDown();
        } else {
            $('#break_time_settings').slideUp();
            $('#break_info').slideUp();
        }
    });

    // Form validation
    $('form').on('submit', function(e) {
        const threshold = parseInt($('#offline_threshold').val());

        if(threshold < 1 || threshold > 60) {
            e.preventDefault();
            toastr.error('{{ translate("threshold_must_be_between") }}');
            return false;
        }

        if($('#break_time_enabled').is(':checked')) {
            const breakTime = parseInt($('#break_time_minutes').val());
            if(breakTime >= threshold) {
                e.preventDefault();
                toastr.error('{{ translate("break_time_cannot_exceed_threshold") }}');
                return false;
            }
        }
    });
</script>
@endpush
