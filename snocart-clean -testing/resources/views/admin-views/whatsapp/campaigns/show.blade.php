@extends('layouts.admin.app')

@section('title', translate('Campaign Analytics'))

@push('css_or_js')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .metric-card {
            transition: transform 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-3px);
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .metric-label {
            color: #8c98a4;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        .progress-lg {
            height: 1.5rem;
            font-size: 0.875rem;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .roi-positive {
            color: #28a745;
        }
        .roi-negative {
            color: #dc3545;
        }
        .status-icon {
            font-size: 3rem;
            opacity: 0.2;
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
                        <li class="breadcrumb-item"><a class="breadcrumb-link" href="{{ route('admin.whatsapp.campaigns.index') }}">{{translate('Campaigns')}}</a></li>
                        <li class="breadcrumb-item active">{{translate('Analytics')}}</li>
                    </ol>
                </nav>
                <h1 class="page-header-title">{{ $campaign->name }}</h1>
                <div class="mt-2">
                    @php
                        $statusColors = [
                            'running' => 'primary',
                            'completed' => 'success',
                            'failed' => 'danger',
                            'draft' => 'secondary'
                        ];
                        $color = $statusColors[$campaign->status] ?? 'secondary';
                    @endphp
                    <span class="badge badge-soft-{{ $color }} me-2">{{ ucfirst($campaign->status) }}</span>
                    @if($campaign->started_at)
                        <span class="text-muted">{{translate('Started')}}: {{ $campaign->started_at->format('M d, Y H:i') }}</span>
                    @endif
                </div>
            </div>
            <div class="col-sm-auto">
                @if($campaign->status === 'running')
                <button class="btn btn-danger me-2" id="cancelCampaign">
                    <i class="tio-clear"></i> {{translate('Cancel Campaign')}}
                </button>
                @endif
                <button class="btn btn-primary" id="duplicateCampaign">
                    <i class="tio-copy"></i> {{translate('Duplicate')}}
                </button>
            </div>
        </div>
    </div>

    <!-- Campaign Summary -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <h6 class="text-cap mb-2">{{translate('Total Recipients')}}</h6>
                    <h3>{{ number_format($campaign->total_recipients) }}</h3>
                </div>
                <div class="col-md-3">
                    <h6 class="text-cap mb-2">{{translate('Segments Used')}}</h6>
                    <h3>{{ count($campaign->segments ?? []) }}</h3>
                </div>
                <div class="col-md-3">
                    <h6 class="text-cap mb-2">{{translate('Started At')}}</h6>
                    <h3>{{ $campaign->started_at ? $campaign->started_at->format('M d, H:i') : '--' }}</h3>
                </div>
                <div class="col-md-3">
                    <h6 class="text-cap mb-2">{{translate('Completed At')}}</h6>
                    <h3>{{ $campaign->completed_at ? $campaign->completed_at->format('M d, H:i') : '--' }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Metrics -->
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-label">{{translate('Sent')}}</div>
                            <div class="metric-value text-primary">{{ number_format($metrics['sent_count']) }}</div>
                            <div class="text-muted">{{ number_format($metrics['sent_percentage'], 1) }}%</div>
                        </div>
                        <i class="tio-send status-icon text-primary"></i>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-primary" style="width: {{ $metrics['sent_percentage'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-label">{{translate('Delivered')}}</div>
                            <div class="metric-value text-success">{{ number_format($metrics['delivered_count']) }}</div>
                            <div class="text-muted">{{ number_format($metrics['delivered_percentage'], 1) }}%</div>
                        </div>
                        <i class="tio-done status-icon text-success"></i>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: {{ $metrics['delivered_percentage'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-label">{{translate('Read')}}</div>
                            <div class="metric-value text-info">{{ number_format($metrics['read_count']) }}</div>
                            <div class="text-muted">{{ number_format($metrics['read_percentage'], 1) }}%</div>
                        </div>
                        <i class="tio-done-all status-icon text-info"></i>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: {{ $metrics['read_percentage'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-label">{{translate('Failed')}}</div>
                            <div class="metric-value text-danger">{{ number_format($metrics['failed_count']) }}</div>
                            <div class="text-muted">{{ number_format($metrics['failed_percentage'], 1) }}%</div>
                        </div>
                        <i class="tio-clear status-icon text-danger"></i>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-danger" style="width: {{ $metrics['failed_percentage'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Re-engagement & ROI -->
    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">{{translate('Re-engagement Metrics')}}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-cap mb-2">{{translate('Orders (24h)')}}</h6>
                                <h2 class="mb-1">{{ number_format($reengagement['orders_24h']) }}</h2>
                                <span class="badge badge-soft-success">
                                    {{translate('Revenue')}}: {{ \App\CentralLogics\Helpers::format_currency($reengagement['revenue_24h']) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-cap mb-2">{{translate('Orders (7d)')}}</h6>
                                <h2 class="mb-1">{{ number_format($reengagement['orders_7d']) }}</h2>
                                <span class="badge badge-soft-info">
                                    {{translate('Revenue')}}: {{ \App\CentralLogics\Helpers::format_currency($reengagement['revenue_7d']) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-cap mb-2">{{translate('Orders (30d)')}}</h6>
                                <h2 class="mb-1">{{ number_format($reengagement['orders_30d']) }}</h2>
                                <span class="badge badge-soft-primary">
                                    {{translate('Revenue')}}: {{ \App\CentralLogics\Helpers::format_currency($reengagement['revenue_30d']) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-cap">{{translate('Conversion Rate')}}</h6>
                            <div class="progress progress-lg">
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: {{ $reengagement['conversion_rate'] }}%">
                                    {{ number_format($reengagement['conversion_rate'], 2) }}%
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-cap">{{translate('Average Order Value')}}</h6>
                            <h3>{{ \App\CentralLogics\Helpers::format_currency($reengagement['avg_order_value']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">{{translate('Return on Investment')}}</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <h6 class="text-cap">{{translate('Campaign Cost')}}</h6>
                        <h4>{{ \App\CentralLogics\Helpers::format_currency($roi['total_cost']) }}</h4>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-cap">{{translate('Revenue Generated')}}</h6>
                        <h4 class="text-success">{{ \App\CentralLogics\Helpers::format_currency($roi['total_revenue']) }}</h4>
                    </div>
                    <hr>
                    <div>
                        <h6 class="text-cap">{{translate('ROI')}}</h6>
                        <h2 class="{{ $roi['roi_percentage'] >= 0 ? 'roi-positive' : 'roi-negative' }}">
                            {{ $roi['roi_percentage'] >= 0 ? '+' : '' }}{{ number_format($roi['roi_percentage'], 1) }}%
                        </h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">{{translate('Delivery Funnel')}}</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="funnelChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">{{translate('Hourly Delivery Pattern')}}</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">{{translate('Revenue Timeline (30d)')}}</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recipients Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-header-title">{{translate('Message Recipients')}}</h5>
        </div>
        <div class="table-responsive datatable-custom">
            <table id="recipientsTable" class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table" style="width:100%">
                <thead class="thead-light">
                    <tr>
                        <th>{{translate('Phone')}}</th>
                        <th>{{translate('Customer')}}</th>
                        <th>{{translate('Status')}}</th>
                        <th>{{translate('Sent At')}}</th>
                        <th>{{translate('Delivered At')}}</th>
                        <th>{{translate('Read At')}}</th>
                        <th>{{translate('Failed Reason')}}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    'use strict';

    const campaignId = {{ $campaign->id }};
    const chartData = @json($chart_data);

    $(document).ready(function() {
        // Funnel Chart
        new Chart(document.getElementById('funnelChart'), {
            type: 'bar',
            data: {
                labels: ['{{translate("Sent")}}', '{{translate("Delivered")}}', '{{translate("Read")}}'],
                datasets: [{
                    data: [
                        {{ $metrics['sent_count'] }},
                        {{ $metrics['delivered_count'] }},
                        {{ $metrics['read_count'] }}
                    ],
                    backgroundColor: ['#377dff', '#28a745', '#17a2b8']
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });

        // Hourly Chart
        new Chart(document.getElementById('hourlyChart'), {
            type: 'bar',
            data: {
                labels: chartData.hourly.labels,
                datasets: [{
                    label: '{{translate("Messages Sent")}}',
                    data: chartData.hourly.data,
                    backgroundColor: '#377dff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Revenue Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: chartData.revenue.labels,
                datasets: [{
                    label: '{{translate("Revenue")}}',
                    data: chartData.revenue.data,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Recipients DataTable
        $('#recipientsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("admin.whatsapp.campaigns.recipients", $campaign->id) }}'
            },
            columns: [
                { data: 'phone', name: 'phone' },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'status', name: 'status', orderable: false },
                { data: 'sent_at', name: 'sent_at' },
                { data: 'delivered_at', name: 'delivered_at' },
                { data: 'read_at', name: 'read_at' },
                { data: 'failed_reason', name: 'failed_reason', orderable: false }
            ],
            order: [[3, 'desc']],
            pageLength: 50
        });

        // Cancel Campaign
        $('#cancelCampaign').on('click', function() {
            if (!confirm('{{translate("Are you sure you want to cancel this campaign?")}}')) {
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: '{{ route("admin.whatsapp.campaigns.cancel", $campaign->id) }}',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    toastr.success(response.message || '{{translate("Campaign cancelled")}}');
                    location.reload();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || '{{translate("Failed to cancel campaign")}}');
                    $btn.prop('disabled', false);
                }
            });
        });

        // Duplicate Campaign
        $('#duplicateCampaign').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: '{{ route("admin.whatsapp.campaigns.duplicate", $campaign->id) }}',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    toastr.success(response.message || '{{translate("Campaign duplicated")}}');
                    setTimeout(() => {
                        window.location.href = '{{ route("admin.whatsapp.campaigns.index") }}';
                    }, 1500);
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || '{{translate("Failed to duplicate campaign")}}');
                    $btn.prop('disabled', false);
                }
            });
        });
    });
</script>
@endpush
