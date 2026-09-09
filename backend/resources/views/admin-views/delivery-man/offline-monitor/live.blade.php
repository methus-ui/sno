@extends('layouts.admin.app')

@section('title', translate('Live Offline Monitor'))

@push('css_or_js')
    <style>
        .live-card {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .live-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .live-card.offline {
            border-left: 4px solid #dc3545;
        }
        .live-card.online {
            border-left: 4px solid #28a745;
        }
        .live-card.warning {
            border-left: 4px solid #ffc107;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            animation: pulse 2s infinite;
        }
        .status-indicator.online {
            background-color: #28a745;
        }
        .status-indicator.offline {
            background-color: #dc3545;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        .progress-bar-wrapper {
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
        }
        .progress-bar-fill {
            height: 100%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        .refresh-timer {
            font-size: 14px;
            color: #666;
        }
        .filter-tabs {
            margin-bottom: 20px;
        }
        .filter-tabs .btn {
            margin-right: 10px;
            margin-bottom: 10px;
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
                    <i class="tio-refresh"></i> {{ translate('live_monitor') }}
                </h1>
                <p class="text-muted">{{ translate('real_time_monitoring') }}</p>
            </div>
            <div class="col-sm-auto">
                <button onclick="refreshData()" class="btn btn-primary">
                    <i class="tio-refresh"></i> {{ translate('refresh_now') }}
                </button>
                <a href="{{ route('admin.deliveryman.offline-monitor.index') }}" class="btn btn-secondary">
                    <i class="tio-arrow-backward"></i> {{ translate('back_to_dashboard') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Auto Refresh Info -->
    <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="tio-info mr-2"></i>
        <span>{{ translate('auto_refresh_info') }}. {{ translate('next_refresh_in') }}: <strong id="countdown">30</strong> {{ translate('seconds') }}</span>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <button class="btn btn-outline-primary filter-btn active" data-filter="all">
            {{ translate('all') }} (<span id="count-all">{{ count($activeDms) }}</span>)
        </button>
        <button class="btn btn-outline-danger filter-btn" data-filter="exceeded">
            {{ translate('exceeded_threshold') }} (<span id="count-exceeded">0</span>)
        </button>
        <button class="btn btn-outline-warning filter-btn" data-filter="warning">
            {{ translate('at_risk') }} (<span id="count-warning">0</span>)
        </button>
        <button class="btn btn-outline-success filter-btn" data-filter="safe">
            {{ translate('safe') }} (<span id="count-safe">0</span>)
        </button>
        <button class="btn btn-outline-secondary filter-btn" data-filter="offline-now">
            {{ translate('currently_offline') }} (<span id="count-offline-now">0</span>)
        </button>
    </div>

    <!-- Live DM Cards -->
    <div id="dm-container" class="row">
        @forelse($activeDms as $dm)
            @php
                $offlineTime = $dm['offline_time'];
                $threshold = $dm['threshold'];
                $percentage = ($offlineTime / $threshold) * 100;

                $cardClass = 'online';
                $statusClass = 'success';
                $filterClass = 'safe';
                $progressColor = '#28a745';

                if ($offlineTime > $threshold) {
                    $cardClass = 'warning';
                    $statusClass = 'danger';
                    $filterClass = 'exceeded';
                    $progressColor = '#dc3545';
                    $percentage = 100;
                } elseif ($offlineTime >= 3) {
                    $cardClass = 'warning';
                    $statusClass = 'warning';
                    $filterClass = 'warning';
                    $progressColor = '#ffc107';
                }

                if ($dm['is_offline']) {
                    $cardClass = 'offline';
                }
            @endphp
            <div class="col-md-6 col-lg-4 dm-card" data-filter="{{ $filterClass }}" data-offline-now="{{ $dm['is_offline'] ? 'true' : 'false' }}">
                <div class="live-card {{ $cardClass }}">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar avatar-sm avatar-circle mr-3">
                            <img class="avatar-img" onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                src="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="DM">
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">{{ $dm['name'] }}</h6>
                            <small class="text-muted">{{ $dm['phone'] }}</small>
                        </div>
                        <div>
                            <span class="status-indicator {{ $dm['is_offline'] ? 'offline' : 'online' }}"></span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span>{{ translate('offline_time') }}</span>
                            <strong class="text-{{ $statusClass }}">{{ $offlineTime }} / {{ $threshold }} min</strong>
                        </div>
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar-fill" style="width: {{ min($percentage, 100) }}%; background-color: {{ $progressColor }}">
                                {{ number_format($percentage, 0) }}%
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            @if($dm['incentive_eligible'])
                                <span class="badge badge-soft-success">
                                    <i class="tio-checkmark-circle"></i> {{ translate('eligible') }}
                                </span>
                            @else
                                <span class="badge badge-soft-danger">
                                    <i class="tio-clear-circle"></i> {{ translate('not_eligible') }}
                                </span>
                            @endif
                        </div>
                        <div>
                            @if($dm['is_offline'] && $dm['current_offline_duration'] > 0)
                                <small class="text-danger">
                                    <i class="tio-alarm"></i> {{ translate('offline_for') }} {{ $dm['current_offline_duration'] }} min
                                </small>
                            @endif
                        </div>
                    </div>

                    <div class="mt-2">
                        <a href="{{ route('admin.deliveryman.offline-monitor.show', $dm['id']) }}" class="btn btn-sm btn-block btn-outline-primary">
                            <i class="tio-visible"></i> {{ translate('view_details') }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <img src="{{ asset('public/assets/admin/img/empty-box.png') }}" width="100" alt="No data">
                    <p class="mt-3 text-muted">{{ translate('no_active_delivery_men') }}</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('script_2')
<script>
    let countdownTimer = 30;
    let countdownInterval;
    let currentFilter = 'all';

    // Auto-refresh countdown
    function startCountdown() {
        countdownTimer = 30;
        clearInterval(countdownInterval);

        countdownInterval = setInterval(function() {
            countdownTimer--;
            document.getElementById('countdown').textContent = countdownTimer;

            if (countdownTimer <= 0) {
                refreshData();
            }
        }, 1000);
    }

    // Refresh data
    function refreshData() {
        location.reload();
    }

    // Filter functionality
    document.querySelectorAll('.filter-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Update active button
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Filter cards
            currentFilter = this.getAttribute('data-filter');
            filterCards(currentFilter);
        });
    });

    function filterCards(filter) {
        const cards = document.querySelectorAll('.dm-card');

        cards.forEach(function(card) {
            if (filter === 'all') {
                card.style.display = 'block';
            } else if (filter === 'offline-now') {
                card.style.display = card.getAttribute('data-offline-now') === 'true' ? 'block' : 'none';
            } else {
                card.style.display = card.getAttribute('data-filter') === filter ? 'block' : 'none';
            }
        });
    }

    // Calculate counts
    function updateCounts() {
        const allCards = document.querySelectorAll('.dm-card');
        const exceededCards = document.querySelectorAll('.dm-card[data-filter="exceeded"]');
        const warningCards = document.querySelectorAll('.dm-card[data-filter="warning"]');
        const safeCards = document.querySelectorAll('.dm-card[data-filter="safe"]');
        const offlineNowCards = document.querySelectorAll('.dm-card[data-offline-now="true"]');

        document.getElementById('count-all').textContent = allCards.length;
        document.getElementById('count-exceeded').textContent = exceededCards.length;
        document.getElementById('count-warning').textContent = warningCards.length;
        document.getElementById('count-safe').textContent = safeCards.length;
        document.getElementById('count-offline-now').textContent = offlineNowCards.length;
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        startCountdown();
        updateCounts();
    });
</script>
@endpush
