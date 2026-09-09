@extends('layouts.admin.app')

@section('title', translate('Time-Based Delivery Fee Schedules'))

@push('css_or_js')
<style>
    .schedule-card {
        border-left: 4px solid #3498db;
        margin-bottom: 10px;
    }
    .schedule-card.inactive {
        border-left-color: #95a5a6;
        opacity: 0.7;
    }
    .schedule-card .badge-increase {
        background-color: #e74c3c;
    }
    .schedule-card .badge-decrease {
        background-color: #27ae60;
    }
    .day-section {
        margin-bottom: 20px;
    }
    .day-header {
        background-color: #f8f9fa;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 10px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{ asset('public/assets/admin/img/edit.png') }}" class="w--26" alt="">
            </span>
            <span>
                {{ translate('Time-Based Delivery Fee Schedules') }}
            </span>
        </h1>
        <p class="page-header-description">
            {{ $zone->name }} - {{ translate('Manage automated delivery fee adjustments based on day and time') }}
        </p>
    </div>
    <!-- End Page Header -->

    <!-- Back Button -->
    <div class="mb-3">
        <a href="{{ route('admin.business-settings.zone.module-setup', $zone->id) }}" class="btn btn-secondary">
            <i class="tio-arrow-left"></i> {{ translate('Back to Zone Settings') }}
        </a>
    </div>

    <div class="row">
        <!-- Add New Schedule Form -->
        <div class="col-lg-4">
            <div class="card shadow--card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-add-circle"></i> {{ translate('Add New Schedule') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.business-settings.zone.delivery-fee-schedule.store', $zone->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label" for="title">{{ translate('Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control"
                                   placeholder="{{ translate('e.g., Lunch Rush Hour') }}" required maxlength="100">
                        </div>

                        <div class="form-group">
                            <label class="input-label" for="day">{{ translate('Day of Week') }} <span class="text-danger">*</span></label>
                            <select name="day" id="day" class="form-control" required>
                                @foreach($dayNames as $key => $name)
                                    <option value="{{ $key }}">{{ translate($name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="input-label" for="start_time">{{ translate('Start Time') }} <span class="text-danger">*</span></label>
                                    <input type="time" name="start_time" id="start_time" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="input-label" for="end_time">{{ translate('End Time') }} <span class="text-danger">*</span></label>
                                    <input type="time" name="end_time" id="end_time" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="input-label" for="fee_percentage">
                                {{ translate('Fee Percentage') }} (%) <span class="text-danger">*</span>
                                <span data-toggle="tooltip" data-placement="right"
                                      data-original-title="{{ translate('Positive value = surcharge (e.g., +20 means 20% increase). Negative value = discount (e.g., -10 means 10% decrease).') }}"
                                      class="input-label-secondary">
                                    <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="">
                                </span>
                            </label>
                            <input type="number" name="fee_percentage" id="fee_percentage" class="form-control"
                                   step="0.01" min="-100" max="500" placeholder="{{ translate('e.g., 20 or -10') }}" required>
                            <small class="form-text text-muted">
                                {{ translate('Use positive for surcharge, negative for discount') }}
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="input-label" for="message">
                                {{ translate('Customer Message') }}
                                <span data-toggle="tooltip" data-placement="right"
                                      data-original-title="{{ translate('This message will be shown to customers when this schedule is active.') }}"
                                      class="input-label-secondary">
                                    <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="">
                                </span>
                            </label>
                            <input type="text" name="message" id="message" class="form-control"
                                   placeholder="{{ translate('e.g., Peak hour delivery fee') }}" maxlength="255">
                        </div>

                        <div class="form-group">
                            <label class="input-label" for="priority">
                                {{ translate('Priority') }}
                                <span data-toggle="tooltip" data-placement="right"
                                      data-original-title="{{ translate('Higher priority wins when schedules overlap. Default is 0.') }}"
                                      class="input-label-secondary">
                                    <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="">
                                </span>
                            </label>
                            <input type="number" name="priority" id="priority" class="form-control"
                                   min="0" max="255" value="0" placeholder="{{ translate('e.g., 0') }}">
                        </div>

                        <div class="btn--container justify-content-end">
                            <button type="reset" class="btn btn--reset">{{ translate('Reset') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('Add Schedule') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Schedules List -->
        <div class="col-lg-8">
            <div class="card shadow--card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-time"></i> {{ translate('Configured Schedules') }}
                        <span class="badge badge-soft-primary ml-2">{{ $schedules->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($schedules->count() > 0)
                        @foreach($dayNames as $dayKey => $dayName)
                            @php
                                $daySchedules = $schedules->where('day', $dayKey);
                            @endphp
                            @if($daySchedules->count() > 0)
                                <div class="day-section">
                                    <div class="day-header">
                                        <strong>{{ translate($dayName) }}</strong>
                                        <span class="badge badge-soft-secondary ml-2">{{ $daySchedules->count() }} {{ translate('schedule(s)') }}</span>
                                    </div>
                                    @foreach($daySchedules as $schedule)
                                        <div class="card schedule-card {{ !$schedule->status ? 'inactive' : '' }}">
                                            <div class="card-body py-2 px-3">
                                                <div class="row align-items-center">
                                                    <div class="col-md-4">
                                                        <strong>{{ $schedule->title }}</strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} -
                                                            {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                                                        </small>
                                                    </div>
                                                    <div class="col-md-2 text-center">
                                                        <span class="badge {{ $schedule->fee_percentage >= 0 ? 'badge-increase' : 'badge-decrease' }}">
                                                            {{ $schedule->fee_percentage >= 0 ? '+' : '' }}{{ $schedule->fee_percentage }}%
                                                        </span>
                                                    </div>
                                                    <div class="col-md-2 text-center">
                                                        <small>{{ translate('Priority') }}: {{ $schedule->priority }}</small>
                                                    </div>
                                                    <div class="col-md-4 text-right">
                                                        <!-- Status Toggle -->
                                                        <a href="{{ route('admin.business-settings.zone.delivery-fee-schedule.status', ['id' => $schedule->id, 'status' => $schedule->status ? 0 : 1]) }}"
                                                           class="btn btn-sm {{ $schedule->status ? 'btn-success' : 'btn-secondary' }}"
                                                           title="{{ $schedule->status ? translate('Active') : translate('Inactive') }}">
                                                            <i class="tio-{{ $schedule->status ? 'checkmark-circle' : 'clear-circle' }}"></i>
                                                        </a>
                                                        <!-- Edit Button -->
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                                data-toggle="modal" data-target="#editModal{{ $schedule->id }}"
                                                                title="{{ translate('Edit') }}">
                                                            <i class="tio-edit"></i>
                                                        </button>
                                                        <!-- Delete Button -->
                                                        <form action="{{ route('admin.business-settings.zone.delivery-fee-schedule.destroy', $schedule->id) }}"
                                                              method="POST" class="d-inline delete-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                    title="{{ translate('Delete') }}"
                                                                    onclick="return confirm('{{ translate('Are you sure you want to delete this schedule?') }}')">
                                                                <i class="tio-delete"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                                @if($schedule->message)
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            <i class="tio-comment-text-outlined"></i> {{ $schedule->message }}
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal{{ $schedule->id }}" tabindex="-1" role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">{{ translate('Edit Schedule') }}</h5>
                                                        <button type="button" class="close" data-dismiss="modal">
                                                            <span>&times;</span>
                                                        </button>
                                                    </div>
                                                    <form action="{{ route('admin.business-settings.zone.delivery-fee-schedule.update', $schedule->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label>{{ translate('Title') }} <span class="text-danger">*</span></label>
                                                                <input type="text" name="title" class="form-control"
                                                                       value="{{ $schedule->title }}" required maxlength="100">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ translate('Day of Week') }} <span class="text-danger">*</span></label>
                                                                <select name="day" class="form-control" required>
                                                                    @foreach($dayNames as $key => $name)
                                                                        <option value="{{ $key }}" {{ $schedule->day == $key ? 'selected' : '' }}>
                                                                            {{ translate($name) }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label>{{ translate('Start Time') }} <span class="text-danger">*</span></label>
                                                                        <input type="time" name="start_time" class="form-control"
                                                                               value="{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}" required>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label>{{ translate('End Time') }} <span class="text-danger">*</span></label>
                                                                        <input type="time" name="end_time" class="form-control"
                                                                               value="{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}" required>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ translate('Fee Percentage') }} (%) <span class="text-danger">*</span></label>
                                                                <input type="number" name="fee_percentage" class="form-control"
                                                                       value="{{ $schedule->fee_percentage }}" step="0.01" min="-100" max="500" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ translate('Customer Message') }}</label>
                                                                <input type="text" name="message" class="form-control"
                                                                       value="{{ $schedule->message }}" maxlength="255">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ translate('Priority') }}</label>
                                                                <input type="number" name="priority" class="form-control"
                                                                       value="{{ $schedule->priority }}" min="0" max="255">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                                {{ translate('Cancel') }}
                                                            </button>
                                                            <button type="submit" class="btn btn-primary">
                                                                {{ translate('Update') }}
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <img src="{{ asset('public/assets/admin/img/empty-box.png') }}" alt="" width="120">
                            <p class="mt-3 text-muted">{{ translate('No schedules configured yet') }}</p>
                            <p class="text-muted small">{{ translate('Add a schedule using the form on the left') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Info Card -->
            <div class="card shadow--card mt-3">
                <div class="card-body">
                    <h6><i class="tio-info text-primary"></i> {{ translate('How it works') }}</h6>
                    <ul class="mb-0 pl-3">
                        <li>{{ translate('Time-based schedules take priority over the static delivery fee setting.') }}</li>
                        <li>{{ translate('If multiple schedules overlap, the one with higher priority is used.') }}</li>
                        <li>{{ translate('If no schedule matches the current time, the static zone setting applies.') }}</li>
                        <li>{{ translate('Positive percentage = surcharge, Negative percentage = discount.') }}</li>
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
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@endpush
