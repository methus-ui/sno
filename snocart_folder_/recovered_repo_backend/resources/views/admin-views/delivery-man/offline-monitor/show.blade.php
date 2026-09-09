@extends('layouts.admin.app')

@section('title', translate('Delivery Man Offline Details'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/daterangepicker.css') }}">
    <style>
        .info-card {
            border-radius: 10px;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .session-timeline {
            position: relative;
            padding-left: 30px;
        }
        .session-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }
        .session-item {
            position: relative;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .session-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #007bff;
            border: 3px solid white;
        }
        .session-item.warning {
            border-left-color: #ffc107;
        }
        .session-item.warning::before {
            background: #ffc107;
        }
        .session-item.danger {
            border-left-color: #dc3545;
        }
        .session-item.danger::before {
            background: #dc3545;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-no-gutter">
                        <li class="breadcrumb-item">
                            <a class="breadcrumb-link" href="{{ route('admin.deliveryman.offline-monitor.index') }}">
                                {{ translate('offline_monitor') }}
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ translate('details') }}</li>
                    </ol>
                </nav>
                <h1 class="page-header-title">{{ $dm->f_name }} {{ $dm->l_name }} - {{ translate('offline_history') }}</h1>
            </div>
        </div>
    </div>

    <!-- DM Info Card -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 text-center">
                    <img class="avatar avatar-xxl avatar-circle"
                         onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                         src="{{ $dm->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}"
                         alt="DM Image">
                </div>
                <div class="col-md-10">
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="text-muted">{{ translate('name') }}</h6>
                            <p>{{ $dm->f_name }} {{ $dm->l_name }}</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">{{ translate('phone') }}</h6>
                            <p>{{ $dm->phone }}</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">{{ translate('rating') }}</h6>
                            <p>
                                <i class="tio-star text-warning"></i>
                                {{ $dm->rating ? number_format($dm->rating[0]->average ?? 0, 1) : translate('na') }}
                            </p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">{{ translate('wallet_balance') }}</h6>
                            <p>{{ \App\CentralLogics\Helpers::currency_symbol() }}{{ $dm->wallet ? number_format($dm->wallet->collected_cash ?? 0, 2) : '0.00' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('admin.deliveryman.offline-monitor.show', $dm->id) }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label>{{ translate('from_date') }}</label>
                        <input type="date" name="from" class="form-control" value="{{ $dateFrom }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label>{{ translate('to_date') }}</label>
                        <input type="date" name="to" class="form-control" value="{{ $dateTo }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="tio-filter-list"></i> {{ translate('filter') }}
                        </button>
                        <a href="{{ route('admin.deliveryman.offline-monitor.show', $dm->id) }}" class="btn btn-secondary">
                            {{ translate('reset') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="info-card">
                <h3 class="text-primary">{{ $summary['total_days'] }}</h3>
                <p class="text-muted mb-0">{{ translate('total_days') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-card">
                <h3 class="text-danger">{{ $summary['days_exceeded'] }}</h3>
                <p class="text-muted mb-0">{{ translate('days_exceeded') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-card">
                <h3 class="text-warning">{{ $summary['total_offline_minutes'] }} min</h3>
                <p class="text-muted mb-0">{{ translate('total_offline_minutes') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-card">
                <h3 class="text-info">{{ number_format($summary['avg_offline_per_day'], 1) }} min</h3>
                <p class="text-muted mb-0">{{ translate('avg_per_day') }}</p>
            </div>
        </div>
    </div>

    <!-- Daily Attendance Records -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="tio-calendar"></i> {{ translate('daily_attendance_records') }}
            </h5>
        </div>
        <div class="card-body">
            @forelse($attendances as $attendance)
                <div class="card mb-3 {{ $attendance->incentive_eligible ? '' : 'border-danger' }}">
                    <div class="card-header {{ $attendance->incentive_eligible ? 'bg-light' : 'bg-danger text-white' }}">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <strong>{{ translate('date') }}: {{ $attendance->date->format('Y-m-d (l)') }}</strong>
                            </div>
                            <div class="col-md-2">
                                {{ translate('punch_in') }}: {{ $attendance->punch_in_time ? \Carbon\Carbon::parse($attendance->punch_in_time)->format('H:i') : translate('na') }}
                            </div>
                            <div class="col-md-2">
                                {{ translate('punch_out') }}: {{ $attendance->punch_out_time ? \Carbon\Carbon::parse($attendance->punch_out_time)->format('H:i') : translate('na') }}
                            </div>
                            <div class="col-md-2">
                                {{ translate('working') }}: {{ $attendance->working_hours ? $attendance->formatted_working_hours : translate('na') }}
                            </div>
                            <div class="col-md-3 text-right">
                                <span class="badge {{ $attendance->incentive_eligible ? 'badge-success' : 'badge-danger' }}">
                                    {{ $attendance->total_offline_minutes ?? 0 }} min offline
                                    @if(!$attendance->incentive_eligible)
                                        - {{ translate('not_eligible') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($attendance->offline_sessions && count($attendance->offline_sessions) > 0)
                        <div class="card-body">
                            <h6 class="mb-3">{{ translate('offline_sessions') }} ({{ $attendance->offline_count ?? 0 }} {{ translate('times') }})</h6>
                            <div class="session-timeline">
                                @foreach($attendance->offline_sessions as $session)
                                    @php
                                        $duration = $session['duration_minutes'] ?? 0;
                                        $class = '';
                                        if ($duration >= 5) {
                                            $class = 'danger';
                                        } elseif ($duration >= 3) {
                                            $class = 'warning';
                                        }
                                    @endphp
                                    <div class="session-item {{ $class }}">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <i class="tio-time"></i> <strong>{{ translate('start') }}:</strong>
                                                {{ \Carbon\Carbon::parse($session['start'])->format('H:i:s') }}
                                            </div>
                                            <div class="col-md-4">
                                                <i class="tio-time"></i> <strong>{{ translate('end') }}:</strong>
                                                {{ \Carbon\Carbon::parse($session['end'])->format('H:i:s') }}
                                            </div>
                                            <div class="col-md-4">
                                                <i class="tio-alarm"></i> <strong>{{ translate('duration') }}:</strong>
                                                <span class="badge badge-{{ $class == 'danger' ? 'danger' : ($class == 'warning' ? 'warning' : 'info') }}">
                                                    {{ $duration }} {{ translate('minutes') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="card-body">
                            <p class="text-muted text-center mb-0">
                                <i class="tio-checkmark-circle-outlined"></i> {{ translate('no_offline_sessions') }}
                            </p>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-5">
                    <img src="{{ asset('public/assets/admin/img/empty-box.png') }}" width="100" alt="No data">
                    <p class="mt-3 text-muted">{{ translate('no_attendance_records_range') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/daterangepicker.min.js') }}"></script>
@endpush
