@extends('layouts.admin.app')

@section('title', translate('bargaining_dashboard'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <i class="tio-chart-bar-4"></i> {{ translate('bargaining_mode_dashboard') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                <div class="d-flex gap-2">
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}" id="start_date">
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}" id="end_date">
                    <button type="button" class="btn btn-primary" onclick="applyDateFilter()">
                        <i class="tio-filter-list"></i> {{ translate('filter') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-2 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('total_requests') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-6">
                            <h2 class="card-title text-inherit">{{ $stats['total_requests'] }}</h2>
                        </div>
                        <div class="col-6">
                            <div class="chartjs-custom" style="height: 3rem;">
                                <i class="tio-shopping-basket" style="font-size: 2rem; color: #377dff;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('active_requests') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-6">
                            <h2 class="card-title text-inherit">{{ $stats['active_requests'] }}</h2>
                        </div>
                        <div class="col-6">
                            <div class="chartjs-custom" style="height: 3rem;">
                                <i class="tio-time" style="font-size: 2rem; color: #ffc107;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('completed_requests') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-6">
                            <h2 class="card-title text-inherit">{{ $stats['completed_requests'] }}</h2>
                        </div>
                        <div class="col-6">
                            <div class="chartjs-custom" style="height: 3rem;">
                                <i class="tio-checkmark-circle" style="font-size: 2rem; color: #28a745;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('total_savings') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-6">
                            <h2 class="card-title text-inherit">{{ \App\CentralLogics\Helpers::format_currency($stats['total_savings']) }}</h2>
                        </div>
                        <div class="col-6">
                            <div class="chartjs-custom" style="height: 3rem;">
                                <i class="tio-money" style="font-size: 2rem; color: #00c9a7;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-2 mb-3">
        <!-- Status Breakdown -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">{{ translate('status_breakdown') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Mode Breakdown -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">{{ translate('mode_breakdown') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="modeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Trends Chart -->
    <div class="row g-2 mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-header-title">{{ translate('daily_trends') }} ({{ translate('last_30_days') }})</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyTrendsChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performing Stores -->
    <div class="row g-2 mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-header-title">{{ translate('top_performing_stores') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('rank') }}</th>
                                    <th>{{ translate('store') }}</th>
                                    <th>{{ translate('wins') }}</th>
                                    <th>{{ translate('total_offers') }}</th>
                                    <th>{{ translate('win_rate') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topStores as $index => $storeOffer)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-circle mr-3">
                                                    <img class="avatar-img" src="{{ $storeOffer->store->logo_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="{{ $storeOffer->store->name }}">
                                                </div>
                                                <span class="font-weight-bold">{{ $storeOffer->store->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-success">{{ $storeOffer->wins }}</span>
                                        </td>
                                        <td>{{ $storeOffer->total_offers }}</td>
                                        <td>
                                            <span class="badge badge-soft-info">{{ number_format(($storeOffer->wins / $storeOffer->total_offers) * 100, 1) }}%</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">{{ translate('no_data_found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Requests -->
    <div class="row g-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-header-title">{{ translate('recent_requests') }}</h5>
                    <a href="{{ route('admin.bargaining.requests') }}" class="btn btn-sm btn-outline-primary">
                        {{ translate('view_all') }} <i class="tio-chevron-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('request_code') }}</th>
                                    <th>{{ translate('status') }}</th>
                                    <th>{{ translate('mode') }}</th>
                                    <th>{{ translate('items') }}</th>
                                    <th>{{ translate('stores_matched') }}</th>
                                    <th>{{ translate('savings') }}</th>
                                    <th>{{ translate('created_at') }}</th>
                                    <th>{{ translate('action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentRequests as $request)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.bargaining.request-details', $request->id) }}">
                                                {{ $request->request_code }}
                                            </a>
                                        </td>
                                        <td>
                                            @php
                                                $statusClass = [
                                                    'initiated' => 'badge-soft-info',
                                                    'matching' => 'badge-soft-warning',
                                                    'offers_received' => 'badge-soft-primary',
                                                    'awarded' => 'badge-soft-success',
                                                    'accepted' => 'badge-soft-success',
                                                    'cancelled' => 'badge-soft-danger',
                                                    'expired' => 'badge-soft-dark',
                                                ][$request->status] ?? 'badge-soft-secondary';
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ translate($request->status) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $request->mode === 'instant' ? 'badge-soft-info' : 'badge-soft-warning' }}">
                                                {{ translate($request->mode) }}
                                            </span>
                                        </td>
                                        <td>{{ $request->total_cart_items }}</td>
                                        <td>{{ $request->total_stores_matched }}</td>
                                        <td>{{ \App\CentralLogics\Helpers::format_currency($request->total_savings ?? 0) }}</td>
                                        <td>{{ $request->created_at->format('d M Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('admin.bargaining.request-details', $request->id) }}" class="btn btn-sm btn-outline-info">
                                                <i class="tio-visible-outlined"></i> {{ translate('view') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">{{ translate('no_data_found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function applyDateFilter() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        window.location.href = `{{ route('admin.bargaining.dashboard') }}?start_date=${startDate}&end_date=${endDate}`;
    }

    // Status Breakdown Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_map(fn($key) => translate($key), array_keys($statusBreakdown->toArray()))) !!},
            datasets: [{
                data: {!! json_encode(array_values($statusBreakdown->toArray())) !!},
                backgroundColor: ['#377dff', '#ffc107', '#28a745', '#dc3545', '#6c757d', '#17a2b8']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true
        }
    });

    // Mode Breakdown Chart
    const modeCtx = document.getElementById('modeChart').getContext('2d');
    new Chart(modeCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode(array_map(fn($key) => translate($key), array_keys($modeBreakdown->toArray()))) !!},
            datasets: [{
                data: {!! json_encode(array_values($modeBreakdown->toArray())) !!},
                backgroundColor: ['#377dff', '#ffc107']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true
        }
    });

    // Daily Trends Chart
    const trendsCtx = document.getElementById('dailyTrendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($dailyTrends->pluck('date')->toArray()) !!},
            datasets: [
                {
                    label: '{{ translate("total_requests") }}',
                    data: {!! json_encode($dailyTrends->pluck('total')->toArray()) !!},
                    borderColor: '#377dff',
                    backgroundColor: 'rgba(55, 125, 255, 0.1)',
                    tension: 0.4
                },
                {
                    label: '{{ translate("completed") }}',
                    data: {!! json_encode($dailyTrends->pluck('completed')->toArray()) !!},
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
@endpush
