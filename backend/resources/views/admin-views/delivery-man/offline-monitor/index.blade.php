@extends('layouts.admin.app')

@section('title', translate('Offline Monitor Dashboard'))

@push('css_or_js')
    <style>
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-card .stat-label {
            font-size: 14px;
            color: #666;
        }
        .status-badge-eligible {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .status-badge-ineligible {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .status-badge-warning {
            background-color: #ffc107;
            color: #000;
            padding: 5px 10px;
            border-radius: 5px;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <i class="tio-time"></i> {{ translate('offline_monitor_dashboard') }}
                </h1>
                <p class="text-muted">{{ translate('monitor_offline_time') }}</p>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.deliveryman.offline-monitor.live') }}" class="btn btn-primary">
                    <i class="tio-refresh"></i> {{ translate('live_monitor') }}
                </a>
                <a href="{{ route('admin.deliveryman.offline-monitor.daily-report') }}" class="btn btn-info">
                    <i class="tio-chart-bar-1"></i> {{ translate('daily_report') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card bg-white">
                <div class="stat-value text-primary">{{ $stats['total_dms_online_today'] }}</div>
                <div class="stat-label">{{ translate('total_dms_online_today') }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card bg-white">
                <div class="stat-value text-danger">{{ $stats['dms_exceeded_threshold'] }}</div>
                <div class="stat-label">{{ translate('exceeded_threshold') }} (>5 min)</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card bg-white">
                <div class="stat-value text-warning">{{ $stats['dms_at_risk'] }}</div>
                <div class="stat-label">{{ translate('at_risk') }} (3-5 min)</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card bg-white">
                <div class="stat-value text-info">{{ number_format($stats['avg_offline_time'], 1) }}</div>
                <div class="stat-label">{{ translate('avg_offline_time') }} ({{ translate('minutes') }})</div>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="tio-list"></i> {{ translate('todays_offline_summary') }}
            </h5>
            <span class="text-muted">{{ translate('Date') }}: {{ \Carbon\Carbon::today()->format('Y-m-d') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('#') }}</th>
                            <th>{{ translate('Delivery Man') }}</th>
                            <th>{{ translate('Phone') }}</th>
                            <th>{{ translate('punch_in') }}</th>
                            <th>{{ translate('working_hours') }}</th>
                            <th>{{ translate('offline_count') }}</th>
                            <th>{{ translate('total_offline_time') }}</th>
                            <th>{{ translate('status') }}</th>
                            <th>{{ translate('incentive_eligible') }}</th>
                            <th>{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $key => $attendance)
                            <tr>
                                <td>{{ $attendances->firstItem() + $key }}</td>
                                <td>
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-circle">
                                            <img class="avatar-img" onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                src="{{ $attendance->deliveryMan->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="Image">
                                        </div>
                                        <div class="media-body ml-3">
                                            <h5 class="text-hover-primary mb-0">
                                                {{ $attendance->deliveryMan->f_name ?? '' }} {{ $attendance->deliveryMan->l_name ?? '' }}
                                            </h5>
                                            <span class="text-muted">ID: {{ $attendance->delivery_man_id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $attendance->deliveryMan->phone ?? translate('na') }}</td>
                                <td>{{ $attendance->punch_in_time ? \Carbon\Carbon::parse($attendance->punch_in_time)->format('H:i') : translate('na') }}</td>
                                <td>{{ $attendance->working_hours ? $attendance->formatted_working_hours : translate('na') }}</td>
                                <td>
                                    <span class="badge badge-soft-secondary">{{ $attendance->offline_count ?? 0 }} {{ translate('times') }}</span>
                                </td>
                                <td>
                                    @php
                                        $offlineTime = $attendance->total_offline_minutes ?? 0;
                                        $badgeClass = 'badge-soft-success';
                                        if ($offlineTime > 5) {
                                            $badgeClass = 'badge-soft-danger';
                                        } elseif ($offlineTime >= 3) {
                                            $badgeClass = 'badge-soft-warning';
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $offlineTime }} min</span>
                                </td>
                                <td>
                                    @if($attendance->is_currently_offline)
                                        <span class="badge badge-soft-danger">
                                            <i class="tio-offline"></i> {{ translate('offline_now') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-success">
                                            <i class="tio-online"></i> {{ translate('online') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($attendance->incentive_eligible)
                                        <span class="status-badge-eligible">
                                            <i class="tio-checkmark-circle"></i> {{ translate('eligible') }}
                                        </span>
                                    @else
                                        <span class="status-badge-ineligible">
                                            <i class="tio-clear-circle"></i> {{ translate('not_eligible') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.deliveryman.offline-monitor.show', $attendance->delivery_man_id) }}"
                                       class="btn btn-sm btn-white" title="{{ translate('View Details') }}">
                                        <i class="tio-visible"></i> {{ translate('Details') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <img src="{{ asset('public/assets/admin/img/empty-box.png') }}" width="100" alt="No data">
                                    <p class="mt-3 text-muted">{{ translate('no_attendance_records_today') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {!! $attendances->links() !!}
        </div>
    </div>

    <!-- Legend -->
    <div class="card mt-3">
        <div class="card-body">
            <h6 class="card-title">{{ translate('legend') }}</h6>
            <div class="row">
                <div class="col-md-4">
                    <span class="badge badge-soft-success">0-2 min</span> {{ translate('safe_within_threshold') }}
                </div>
                <div class="col-md-4">
                    <span class="badge badge-soft-warning">3-5 min</span> {{ translate('approaching_threshold') }}
                </div>
                <div class="col-md-4">
                    <span class="badge badge-soft-danger">>5 min</span> {{ translate('exceeded_no_incentives') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
