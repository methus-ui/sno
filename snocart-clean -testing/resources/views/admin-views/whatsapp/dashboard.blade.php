@extends('layouts.admin.app')

@section('title', translate('WhatsApp Dashboard'))

@push('css_or_js')
    <!-- WhatsApp Design System -->
    <link rel="stylesheet" href="{{ asset('assets/admin/css/whatsapp-design-system.css') }}?v={{ time() }}">
    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <i class="tio-whatsapp"></i> {{translate('WhatsApp Dashboard')}}
                </h1>
                <p class="text-muted">{{translate('Monitor your WhatsApp campaigns and customer engagement')}}</p>
            </div>
            <div class="col-sm-auto">
                <a class="wa-btn wa-btn--primary" href="{{ route('admin.whatsapp.campaigns.create') }}">
                    <i class="tio-add"></i> {{translate('New Campaign')}}
                </a>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <!-- Stats Cards -->
    <div class="row gx-2 gx-lg-3 mb-4">
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="wa-stat-card wa-stat-card--success wa-fade-in">
                <div class="wa-stat-card__icon">
                    <i class="tio-user"></i>
                </div>
                <div class="wa-stat-card__title">{{translate('Total Customers')}}</div>
                <div class="wa-stat-card__value" id="totalCustomersValue">0</div>
                <span class="wa-badge wa-badge--success">
                    <i class="tio-trending-up"></i> {{translate('Active')}}
                </span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="wa-stat-card wa-stat-card--info wa-fade-in" style="animation-delay: 0.1s;">
                <div class="wa-stat-card__icon">
                    <i class="tio-telegram"></i>
                </div>
                <div class="wa-stat-card__title">{{translate('Total Campaigns')}}</div>
                <div class="wa-stat-card__value" id="totalCampaignsValue">0</div>
                <span class="wa-badge wa-badge--info">
                    <i class="tio-checkmark-circle"></i> {{translate('All Time')}}
                </span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="wa-stat-card wa-stat-card--warning wa-fade-in" style="animation-delay: 0.2s;">
                <div class="wa-stat-card__icon">
                    <i class="tio-chart-pie-1"></i>
                </div>
                <div class="wa-stat-card__title">{{translate('Active Campaigns')}}</div>
                <div class="wa-stat-card__value" id="activeCampaignsValue">0</div>
                <span class="wa-badge wa-badge--warning">
                    <i class="tio-time"></i> {{translate('Running')}}
                </span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="wa-stat-card wa-fade-in" style="animation-delay: 0.3s;">
                <div class="wa-stat-card__icon">
                    <i class="tio-send"></i>
                </div>
                <div class="wa-stat-card__title">{{translate('Today\'s Sent')}}</div>
                <div class="wa-stat-card__value" id="todaySentValue">0</div>
                <span class="wa-badge wa-badge--success">
                    <i class="tio-done"></i> {{translate('Messages')}}
                </span>
            </div>
        </div>
    </div>
    <!-- End Stats Cards -->

    <div class="row">
        <!-- WhatsApp Account Status -->
        <div class="col-lg-4 mb-3 mb-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">
                        <i class="tio-whatsapp text-success"></i> {{translate('WhatsApp Account')}}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-cap mb-2">{{translate('Phone Number')}}</h6>
                        <h4 class="mb-0">{{ $account['phone_number'] ?? translate('Not Connected') }}</h4>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-cap mb-2 wa-text-muted">{{translate('Quality Rating')}}</h6>
                        @php
                            $quality = $account['quality_rating'] ?? 'unknown';
                            $qualityColors = [
                                'GREEN' => 'success',
                                'YELLOW' => 'warning',
                                'RED' => 'danger',
                                'unknown' => 'secondary'
                            ];
                            $badgeColor = $qualityColors[$quality] ?? 'secondary';
                        @endphp
                        <span class="wa-badge wa-badge--{{ $badgeColor }}">
                            <i class="tio-checkmark-circle"></i> {{ strtoupper($quality) }}
                        </span>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-cap mb-2 wa-text-muted">{{translate('Account Mode')}}</h6>
                        <span class="wa-badge wa-badge--{{ isset($account['account_mode']) && $account['account_mode'] === 'LIVE' ? 'success' : 'warning' }}">
                            {{ $account['account_mode'] ?? 'SANDBOX' }}
                        </span>
                    </div>
                    <hr>
                    <div class="d-grid gap-2">
                        {{-- Settings route not yet implemented --}}
                        <button class="wa-btn wa-btn--secondary wa-btn--sm" disabled>
                            <i class="tio-settings"></i> {{translate('Account Settings')}} (Coming Soon)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campaign Performance Chart -->
        <div class="col-lg-8 mb-3 mb-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">{{translate('Campaign Performance (Last 30 Days)')}}</h5>
                </div>
                <div class="card-body">
                    <div class="wa-chart-container">
                        <canvas id="campaignPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Campaigns -->
        <div class="col-lg-8 mb-3 mb-lg-5">
            <div class="card">
                <div class="card-header">
                    <div class="row justify-content-between align-items-center flex-grow-1">
                        <div class="col-sm mb-3 mb-sm-0">
                            <h5 class="card-header-title">{{translate('Recent Campaigns')}}</h5>
                        </div>
                        <div class="col-sm-auto">
                            <a class="wa-btn wa-btn--secondary wa-btn--sm" href="{{ route('admin.whatsapp.campaigns.index') }}">
                                {{translate('View All Campaigns')}}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-responsive datatable-custom">
                    <table class="wa-table">
                        <thead class="wa-table__header">
                            <tr>
                                <th>{{translate('Campaign')}}</th>
                                <th>{{translate('Status')}}</th>
                                <th class="text-center">{{translate('Total')}}</th>
                                <th class="text-center">{{translate('Sent')}}</th>
                                <th class="text-center">{{translate('Delivered')}}</th>
                                <th class="text-center">{{translate('Read')}}</th>
                                <th class="text-center">{{translate('Failed')}}</th>
                                <th>{{translate('Actions')}}</th>
                            </tr>
                        </thead>
                        <tbody class="wa-table__body">
                            @forelse($recent_campaigns ?? [] as $campaign)
                            <tr>
                                <td>
                                    <strong>{{ $campaign->name }}</strong>
                                    <br>
                                    <small class="wa-text-muted">{{ $campaign->started_at ? $campaign->started_at->diffForHumans() : translate('Not started') }}</small>
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'running' => 'primary',
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'draft' => 'secondary'
                                        ];
                                        $color = $statusColors[$campaign->status] ?? 'secondary';
                                    @endphp
                                    <span class="wa-badge wa-badge--{{ $color }}">
                                        @if($campaign->status === 'running')
                                            <span class="wa-status-dot wa-status-dot--active"></span>
                                        @elseif($campaign->status === 'completed')
                                            <i class="tio-checkmark-circle"></i>
                                        @elseif($campaign->status === 'failed')
                                            <i class="tio-error"></i>
                                        @endif
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                </td>
                                <td class="text-center">{{ number_format($campaign->total_recipients ?? 0) }}</td>
                                <td class="text-center">{{ number_format($campaign->sent_count ?? 0) }}</td>
                                <td class="text-center">{{ number_format($campaign->delivered_count ?? 0) }}</td>
                                <td class="text-center">{{ number_format($campaign->read_count ?? 0) }}</td>
                                <td class="text-center">
                                    <span class="wa-text-danger">{{ number_format($campaign->failed_count ?? 0) }}</span>
                                </td>
                                <td>
                                    <div class="wa-table-actions">
                                        <a class="wa-btn wa-btn--sm wa-btn--ghost" href="{{ route('admin.whatsapp.campaigns.show', $campaign->id) }}" title="{{translate('View Details')}}">
                                            <i class="tio-visible-outlined"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8">
                                    <div class="wa-empty-state">
                                        <div class="wa-empty-state__icon">
                                            <i class="tio-telegram"></i>
                                        </div>
                                        <h3 class="wa-empty-state__title">{{translate('No campaigns yet')}}</h3>
                                        <p class="wa-empty-state__message">{{translate('Get started by creating your first WhatsApp campaign')}}</p>
                                        <a class="wa-btn wa-btn--primary" href="{{ route('admin.whatsapp.campaigns.create') }}">
                                            <i class="tio-add"></i> {{translate('Create Campaign')}}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Performing Segments -->
        <div class="col-lg-4 mb-3 mb-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <div class="row justify-content-between align-items-center flex-grow-1">
                        <div class="col-sm mb-2 mb-sm-0">
                            <h5 class="card-header-title">{{translate('Top Segments')}}</h5>
                        </div>
                        <div class="col-sm-auto">
                            <a class="wa-btn wa-btn--secondary wa-btn--sm" href="{{ route('admin.whatsapp.segments.index') }}">
                                {{translate('Manage')}}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($top_segments ?? [] as $segment)
                    <div class="wa-campaign-card mb-3">
                        <div class="wa-campaign-card__body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">{{ $segment['name'] }}</h6>
                                <span class="wa-badge wa-badge--success">{{ number_format($segment['customer_count']) }}</span>
                            </div>
                            <div class="wa-progress mb-2">
                                <div class="wa-progress__bar" style="width: {{ $segment['engagement_rate'] }}%"></div>
                            </div>
                            <small class="wa-text-muted">{{translate('Engagement')}}: {{ number_format($segment['engagement_rate'], 1) }}%</small>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <div class="wa-empty-state__icon mx-auto mb-3" style="width: 60px; height: 60px;">
                            <i class="tio-layers"></i>
                        </div>
                        <p class="wa-text-muted mb-0">{{translate('No segment data available')}}</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<!-- WhatsApp Design System JS -->
<script src="{{ asset('assets/admin/js/whatsapp-design-system.js') }}?v={{ time() }}"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    'use strict';

    $(document).ready(function() {
        // Animate stat counters
        @php
            $totalCustomers = $stats['total_customers'] ?? 0;
            $totalCampaigns = $stats['total_campaigns'] ?? 0;
            $activeCampaigns = $stats['active_campaigns'] ?? 0;
            $todaySent = $stats['today_sent'] ?? 0;
        @endphp

        WaDesignSystem.StatCounter.animate('totalCustomersValue', {{ $totalCustomers }}, 1500);
        WaDesignSystem.StatCounter.animate('totalCampaignsValue', {{ $totalCampaigns }}, 1500);
        WaDesignSystem.StatCounter.animate('activeCampaignsValue', {{ $activeCampaigns }}, 1500);
        WaDesignSystem.StatCounter.animate('todaySentValue', {{ $todaySent }}, 2000);

        // Campaign Performance Chart
        @php
            $defaultChartData = ['labels' => [], 'sent' => [], 'delivered' => [], 'read' => []];
            $performanceData = $chart_data ?? $defaultChartData;
        @endphp
        const performanceData = @json($performanceData);

        const ctx = document.getElementById('campaignPerformanceChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: performanceData.labels,
                    datasets: [
                        {
                            label: '{{translate("Sent")}}',
                            data: performanceData.sent,
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2
                        },
                        {
                            label: '{{translate("Delivered")}}',
                            data: performanceData.delivered,
                            borderColor: '#25D366',
                            backgroundColor: 'rgba(37, 211, 102, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2
                        },
                        {
                            label: '{{translate("Read")}}',
                            data: performanceData.read,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            titleFont: {
                                size: 13,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 12
                            },
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y.toLocaleString();
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                },
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }

        // Show success message on page load (optional)
        // WaDesignSystem.showInfo('{{translate("Dashboard loaded successfully")}}');
    });
</script>
@endpush
