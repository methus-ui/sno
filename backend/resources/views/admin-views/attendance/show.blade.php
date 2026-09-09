@extends('layouts.admin.app')

@section('title', translate('messages.attendance_detail'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{translate('messages.attendance_detail')}}</h1>
                <p class="page-header-text">{{ $attendance->admin->f_name }} {{ $attendance->admin->l_name }} - {{ $attendance->attendance_date->format('d M Y') }}</p>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-primary">
                    <i class="tio-arrow-left"></i> {{translate('messages.back')}}
                </a>
            </div>
        </div>
    </div>

    <!-- Shift Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-primary">{{ $attendance->punch_in ? $attendance->punch_in->format('h:i A') : '-' }}</h5>
                    <small class="text-muted">{{translate('messages.punch_in')}}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-danger">{{ $attendance->punch_out ? $attendance->punch_out->format('h:i A') : '-' }}</h5>
                    <small class="text-muted">{{translate('messages.punch_out')}}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-info">{{ $attendance->expected_shift_end ? $attendance->expected_shift_end->format('h:i A') : '-' }}</h5>
                    <small class="text-muted">{{translate('messages.expected_shift_end')}}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="{{ $attendance->shift_completed ? 'text-success' : 'text-warning' }}">
                        {{ $attendance->actual_work_hours ?? '-' }} {{translate('messages.hrs')}}
                    </h5>
                    <small class="text-muted">{{translate('messages.actual_work_hours')}}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Break & Status Info -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5>{{ $attendance->total_break_minutes }} / {{ $attendance->allocated_break_minutes }} min</h5>
                    <small class="text-muted">{{translate('messages.total_break')}} / {{translate('messages.allocated')}}</small>
                    @if($attendance->extra_break_minutes > 0)
                        <div class="mt-1"><span class="badge badge-warning">+{{ $attendance->extra_break_minutes }} min extra</span></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    @if($attendance->shift_completed)
                        <span class="badge badge-success badge-lg p-2">{{translate('messages.shift_completed')}}</span>
                    @elseif($attendance->early_departure)
                        <span class="badge badge-danger badge-lg p-2">{{translate('messages.early_departure')}}</span>
                    @elseif(!$attendance->punch_out)
                        <span class="badge badge-warning badge-lg p-2">{{translate('messages.still_working')}}</span>
                    @else
                        <span class="badge badge-secondary badge-lg p-2">{{translate('messages.incomplete')}}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5>{{ $attendance->total_hours ?? '-' }} {{translate('messages.hrs')}}</h5>
                    <small class="text-muted">{{translate('messages.total_hours_including_breaks')}}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Break Timeline -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">{{translate('messages.break_timeline')}}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{translate('messages.break_start')}}</th>
                            <th>{{translate('messages.break_end')}}</th>
                            <th>{{translate('messages.duration')}}</th>
                            <th>{{translate('messages.notes')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendance->breaks as $key => $break)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $break->break_start->format('h:i:s A') }}</td>
                            <td>{{ $break->break_end ? $break->break_end->format('h:i:s A') : translate('messages.ongoing') }}</td>
                            <td>{{ $break->duration_minutes ? $break->duration_minutes . ' min' : '-' }}</td>
                            <td>{{ $break->notes ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-3">{{translate('messages.no_breaks_recorded')}}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
