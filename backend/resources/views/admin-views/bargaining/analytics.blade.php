@extends('layouts.admin.app')

@section('title', translate('bargaining_analytics'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <i class="tio-chart-pie"></i> {{ translate('bargaining_analytics') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                <div class="d-flex gap-2">
                    <select class="form-control" id="period_select" onchange="changePeriod()">
                        <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>{{ translate('last_7_days') }}</option>
                        <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>{{ translate('last_30_days') }}</option>
                        <option value="90days" {{ $period === '90days' ? 'selected' : '' }}>{{ translate('last_90_days') }}</option>
                        <option value="year" {{ $period === 'year' ? 'selected' : '' }}>{{ translate('last_year') }}</option>
                    </select>
                    <a href="{{ route('admin.bargaining.dashboard') }}" class="btn btn-outline-primary">
                        <i class="tio-arrow-back"></i> {{ translate('dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row g-2 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('total_requests') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col">
                            <h2 class="card-title text-inherit">{{ $totalRequests }}</h2>
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
                        <div class="col">
                            <h2 class="card-title text-inherit">{{ $completedRequests }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('conversion_rate') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col">
                            <h2 class="card-title text-inherit text-success">{{ number_format($conversionRate, 1) }}%</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('avg_savings') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col">
                            <h2 class="card-title text-inherit text-success">{{ \App\CentralLogics\Helpers::format_currency($avgSavings) }}</h2>
                            <small class="text-muted">({{ number_format($avgSavingsPercentage, 1) }}% of cart value)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Store Performance -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-header-title">{{ translate('store_performance') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('rank') }}</th>
                            <th>{{ translate('store') }}</th>
                            <th>{{ translate('total_offers') }}</th>
                            <th>{{ translate('wins') }}</th>
                            <th>{{ translate('win_rate') }}</th>
                            <th>{{ translate('avg_rank') }}</th>
                            <th>{{ translate('times_best_offer') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($storePerformance as $index => $performance)
                            <tr>
                                <td>
                                    @if($index < 3)
                                        @php
                                            $medals = ['🥇', '🥈', '🥉'];
                                        @endphp
                                        <span style="font-size: 1.5rem;">{{ $medals[$index] }}</span>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-circle mr-3">
                                            <img class="avatar-img" src="{{ $performance->store->logo_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="{{ $performance->store->name }}">
                                        </div>
                                        <span class="font-weight-bold">{{ $performance->store->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $performance->total_offers }}</td>
                                <td>
                                    <span class="badge badge-soft-success">{{ $performance->wins }}</span>
                                </td>
                                <td>
                                    <div class="progress" style="width: 100px; height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $performance->win_rate }}%">
                                            {{ number_format($performance->win_rate, 1) }}%
                                        </div>
                                    </div>
                                </td>
                                <td>{{ number_format($performance->avg_rank, 1) }}</td>
                                <td>
                                    <span class="badge badge-soft-warning">{{ $performance->times_best }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">{{ translate('no_data_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <!-- Popular Items -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-header-title">{{ translate('popular_items_in_bargaining') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ translate('item_name') }}</th>
                                    <th>{{ translate('frequency') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($popularItems->take(15) as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->item_name }}</td>
                                        <td>
                                            <span class="badge badge-soft-primary">{{ $item->frequency }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">{{ translate('no_data_found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fulfillment & Peak Hours -->
        <div class="col-md-6">
            <!-- Fulfillment Stats -->
            <div class="card mb-2">
                <div class="card-header">
                    <h5 class="card-header-title">{{ translate('fulfillment_analysis') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h6 class="text-muted small">{{ translate('avg_fulfillment') }}</h6>
                            <h4>{{ number_format($fulfillmentStats->avg_fulfillment ?? 0, 1) }}%</h4>
                        </div>
                        <div class="col-4">
                            <h6 class="text-muted small">{{ translate('full_fulfillment') }}</h6>
                            <h4 class="text-success">{{ $fulfillmentStats->full_fulfillment_count ?? 0 }}</h4>
                        </div>
                        <div class="col-4">
                            <h6 class="text-muted small">{{ translate('partial_fulfillment') }}</h6>
                            <h4 class="text-warning">{{ $fulfillmentStats->partial_fulfillment_count ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Peak Hours -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-header-title">{{ translate('peak_hours') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="peakHoursChart" style="height: 200px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function changePeriod() {
        const period = document.getElementById('period_select').value;
        window.location.href = `{{ route('admin.bargaining.analytics') }}?period=${period}`;
    }

    // Peak Hours Chart
    const peakCtx = document.getElementById('peakHoursChart').getContext('2d');
    new Chart(peakCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($peakHours->pluck('hour')->map(fn($h) => $h . ':00')->toArray()) !!},
            datasets: [{
                label: '{{ translate("requests") }}',
                data: {!! json_encode($peakHours->pluck('count')->toArray()) !!},
                backgroundColor: 'rgba(55, 125, 255, 0.6)',
                borderColor: '#377dff',
                borderWidth: 1
            }]
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
