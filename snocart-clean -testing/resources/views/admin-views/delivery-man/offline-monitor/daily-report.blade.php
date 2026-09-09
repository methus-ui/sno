@extends('layouts.admin.app')

@section('title', translate('Daily Offline Report'))

@push('css_or_js')
    <style>
        .report-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .report-stat {
            text-align: center;
            padding: 15px;
        }
        .report-stat h2 {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-stat p {
            margin-bottom: 0;
            opacity: 0.9;
        }
        .section-card {
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-card .card-header {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            font-weight: bold;
        }
        .table-dm {
            font-size: 14px;
        }
        .table-dm td {
            vertical-align: middle;
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
                    <i class="tio-chart-bar-1"></i> {{ translate('daily_report') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                <button onclick="exportReport()" class="btn btn-success">
                    <i class="tio-download"></i> {{ translate('export_excel') }}
                </button>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="tio-print"></i> {{ translate('print') }}
                </button>
                <a href="{{ route('admin.deliveryman.offline-monitor.index') }}" class="btn btn-secondary">
                    <i class="tio-arrow-backward"></i> {{ translate('back') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Date Selector -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('admin.deliveryman.offline-monitor.daily-report') }}" method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label>{{ translate('select_date') }}</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}" max="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="tio-search"></i> {{ translate('generate_report') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Summary -->
    <div class="report-summary">
        <h3 class="text-white mb-4">{{ translate('report_summary') }} - {{ \Carbon\Carbon::parse($date)->format('F d, Y (l)') }}</h3>
        <div class="row">
            <div class="col-md-3">
                <div class="report-stat">
                    <h2>{{ $attendances->count() }}</h2>
                    <p>{{ translate('total_dms_online') }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-stat">
                    <h2 class="text-danger">{{ $ineligible->count() }}</h2>
                    <p>{{ translate('not_eligible') }} (>5 min)</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-stat">
                    <h2 class="text-warning">{{ $atRisk->count() }}</h2>
                    <p>{{ translate('at_risk') }} (3-5 min)</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-stat">
                    <h2 class="text-success">{{ $good->count() }}</h2>
                    <p>{{ translate('good_performance') }} (<3 min)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Not Eligible Section -->
    @if($ineligible->count() > 0)
    <div class="section-card card border-danger">
        <div class="card-header bg-danger text-white">
            <i class="tio-clear-circle"></i> {{ translate('not_eligible_for_incentives') }} ({{ $ineligible->count() }})
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dm table-borderless table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ translate('delivery_man') }}</th>
                            <th>{{ translate('phone') }}</th>
                            <th>{{ translate('punch_in') }}</th>
                            <th>{{ translate('working_hours') }}</th>
                            <th>{{ translate('offline_count') }}</th>
                            <th>{{ translate('total_offline') }}</th>
                            <th>{{ translate('actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ineligible as $key => $attendance)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs avatar-circle mr-2">
                                        <img class="avatar-img" onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                            src="{{ $attendance->deliveryMan->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="">
                                    </div>
                                    <span>{{ $attendance->deliveryMan->f_name ?? '' }} {{ $attendance->deliveryMan->l_name ?? '' }}</span>
                                </div>
                            </td>
                            <td>{{ $attendance->deliveryMan->phone ?? {{ translate('messages.na') }} }}</td>
                            <td>{{ $attendance->punch_in_time ? \Carbon\Carbon::parse($attendance->punch_in_time)->format('H:i') : {{ translate('messages.na') }} }}</td>
                            <td>{{ $attendance->working_hours ? number_format($attendance->working_hours, 1) . {{ translate('messages.hrs') }} : {{ translate('messages.na') }} }}</td>
                            <td><span class="badge badge-secondary">{{ $attendance->offline_count ?? 0 }}x</span></td>
                            <td><span class="badge badge-danger">{{ $attendance->total_offline_minutes ?? 0 }} min</span></td>
                            <td>
                                <a href="{{ route('admin.deliveryman.offline-monitor.show', $attendance->delivery_man_id) }}" class="btn btn-sm btn-white">
                                    <i class="tio-visible"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- At Risk Section -->
    @if($atRisk->count() > 0)
    <div class="section-card card border-warning">
        <div class="card-header bg-warning">
            <i class="tio-warning"></i> {{ translate('At Risk - Approaching Threshold') }} ({{ $atRisk->count() }})
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dm table-borderless table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ translate('delivery_man') }}</th>
                            <th>{{ translate('phone') }}</th>
                            <th>{{ translate('punch_in') }}</th>
                            <th>{{ translate('working_hours') }}</th>
                            <th>{{ translate('offline_count') }}</th>
                            <th>{{ translate('total_offline') }}</th>
                            <th>{{ translate('actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($atRisk as $key => $attendance)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs avatar-circle mr-2">
                                        <img class="avatar-img" onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                            src="{{ $attendance->deliveryMan->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="">
                                    </div>
                                    <span>{{ $attendance->deliveryMan->f_name ?? '' }} {{ $attendance->deliveryMan->l_name ?? '' }}</span>
                                </div>
                            </td>
                            <td>{{ $attendance->deliveryMan->phone ?? {{ translate('messages.na') }} }}</td>
                            <td>{{ $attendance->punch_in_time ? \Carbon\Carbon::parse($attendance->punch_in_time)->format('H:i') : {{ translate('messages.na') }} }}</td>
                            <td>{{ $attendance->working_hours ? number_format($attendance->working_hours, 1) . {{ translate('messages.hrs') }} : {{ translate('messages.na') }} }}</td>
                            <td><span class="badge badge-secondary">{{ $attendance->offline_count ?? 0 }}x</span></td>
                            <td><span class="badge badge-warning">{{ $attendance->total_offline_minutes ?? 0 }} min</span></td>
                            <td>
                                <a href="{{ route('admin.deliveryman.offline-monitor.show', $attendance->delivery_man_id) }}" class="btn btn-sm btn-white">
                                    <i class="tio-visible"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Good Performance Section -->
    @if($good->count() > 0)
    <div class="section-card card border-success">
        <div class="card-header bg-success text-white">
            <i class="tio-checkmark-circle"></i> {{ translate('Good Performance - Eligible') }} ({{ $good->count() }})
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dm table-borderless table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ translate('delivery_man') }}</th>
                            <th>{{ translate('phone') }}</th>
                            <th>{{ translate('punch_in') }}</th>
                            <th>{{ translate('working_hours') }}</th>
                            <th>{{ translate('offline_count') }}</th>
                            <th>{{ translate('total_offline') }}</th>
                            <th>{{ translate('actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($good as $key => $attendance)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs avatar-circle mr-2">
                                        <img class="avatar-img" onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                            src="{{ $attendance->deliveryMan->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="">
                                    </div>
                                    <span>{{ $attendance->deliveryMan->f_name ?? '' }} {{ $attendance->deliveryMan->l_name ?? '' }}</span>
                                </div>
                            </td>
                            <td>{{ $attendance->deliveryMan->phone ?? {{ translate('messages.na') }} }}</td>
                            <td>{{ $attendance->punch_in_time ? \Carbon\Carbon::parse($attendance->punch_in_time)->format('H:i') : {{ translate('messages.na') }} }}</td>
                            <td>{{ $attendance->working_hours ? number_format($attendance->working_hours, 1) . {{ translate('messages.hrs') }} : {{ translate('messages.na') }} }}</td>
                            <td><span class="badge badge-secondary">{{ $attendance->offline_count ?? 0 }}x</span></td>
                            <td><span class="badge badge-success">{{ $attendance->total_offline_minutes ?? 0 }} min</span></td>
                            <td>
                                <a href="{{ route('admin.deliveryman.offline-monitor.show', $attendance->delivery_man_id) }}" class="btn btn-sm btn-white">
                                    <i class="tio-visible"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($attendances->count() == 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <img src="{{ asset('public/assets/admin/img/empty-box.png') }}" width="100" alt="No data">
            <p class="mt-3 text-muted">{{ translate('No attendance records found for selected date') }}</p>
        </div>
    </div>
    @endif
</div>
@endsection

@push('script_2')
<script>
    function exportReport() {
        const date = '{{ $date }}';
        const url = '{{ route('admin.deliveryman.offline-monitor.export') }}?from=' + date + '&to=' + date;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                // Convert JSON to CSV
                const csv = convertToCSV(data.data);

                // Download CSV
                const blob = new Blob([csv], { type: 'text/csv' });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = data.filename;
                link.click();

                toastr.success('{{ translate('Report exported successfully') }}');
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('{{ translate('Failed to export report') }}');
            });
    }

    function convertToCSV(data) {
        if (!data || data.length === 0) return '';

        const headers = Object.keys(data[0]);
        const csvRows = [];

        // Add header row
        csvRows.push(headers.join(','));

        // Add data rows
        for (const row of data) {
            const values = headers.map(header => {
                const val = row[header];
                return `"${val}"`;
            });
            csvRows.push(values.join(','));
        }

        return csvRows.join('\n');
    }
</script>
@endpush
