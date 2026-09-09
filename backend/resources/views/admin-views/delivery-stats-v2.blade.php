<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Delivery Analytics') }} - SnoCart</title>

    <!-- FontAwesome Icons (Local) -->
    <link rel="stylesheet" href="{{ asset('public/assets/admin/vendor/fontawesome-free/css/all.min.css') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #D8276B;
            --primary-light: #E8477B;
            --primary-dark: #B81E56;
            --success: #10b981;
            --info: #06b6d4;
            --warning: #f59e0b;
            --danger: #ef4444;
            --purple: #8b5cf6;
            --indigo: #6366f1;
            --bg-primary: #0a0a0a;
            --bg-secondary: #0f0f0f;
            --bg-card: #1a1a1a;
            --text-primary: #ffffff;
            --text-secondary: #e5e5e5;
            --text-muted: #a3a3a3;
            --border: #2a2a2a;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            padding: 0;
            min-height: 100vh;
            background-image:
                radial-gradient(circle at 20% 50%, rgba(216, 39, 107, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.05) 0%, transparent 50%);
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        /* Modern Header */
        .dashboard-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #252525 100%);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--purple) 50%, var(--info) 100%);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-title {
            flex: 1;
        }

        .header-title h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .header-title p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(216, 39, 107, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(216, 39, 107, 0.4);
        }

        .btn-secondary {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: #252525;
            border-color: var(--primary);
        }

        /* Modern Filter Section */
        .filter-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #252525 100%);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group label {
            display: block;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(216, 39, 107, 0.1);
        }

        /* Modern Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #252525 100%);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-color);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
            border-color: var(--accent-color);
        }

        .stat-card.primary { --accent-color: var(--primary); }
        .stat-card.success { --accent-color: var(--success); }
        .stat-card.info { --accent-color: var(--info); }
        .stat-card.warning { --accent-color: var(--warning); }
        .stat-card.danger { --accent-color: var(--danger); }
        .stat-card.purple { --accent-color: var(--purple); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: var(--accent-color);
            opacity: 0.15;
        }

        .stat-icon i {
            color: var(--accent-color);
            opacity: 1;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--accent-color);
            line-height: 1;
            margin: 12px 0;
        }

        .stat-change {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .stat-change.up {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .stat-change.down {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--primary);
        }

        /* Modern Chart Cards */
        .chart-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #252525 100%);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .chart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--purple) 100%);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-title i {
            color: var(--primary);
        }

        .chart-legend {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        /* Grid Layouts */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        /* Insights Section */
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .insight-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #252525 100%);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            position: relative;
        }

        .insight-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-color);
        }

        .insight-title {
            font-size: 14px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .insight-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent-color);
            margin-bottom: 8px;
        }

        .insight-description {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-title h1 {
                font-size: 1.75rem;
            }

            .stat-value {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        .skeleton {
            animation: shimmer 2s infinite;
            background: linear-gradient(90deg, #1a1a1a 25%, #252525 50%, #1a1a1a 75%);
            background-size: 1000px 100%;
        }

        /* ApexCharts Dark Theme */
        .apexcharts-canvas {
            background: transparent !important;
        }

        .apexcharts-tooltip {
            background: var(--bg-card) !important;
            border: 1px solid var(--border) !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4) !important;
        }

        .apexcharts-tooltip-title {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
            border: none !important;
            color: white !important;
            font-weight: 600 !important;
        }

        .apexcharts-tooltip-text-y-value {
            color: var(--text-primary) !important;
            font-weight: 700 !important;
        }

        .apexcharts-legend-text {
            color: var(--text-primary) !important;
        }

        .apexcharts-gridline {
            stroke: rgba(255, 255, 255, 0.05) !important;
        }

        .apexcharts-xaxis-label,
        .apexcharts-yaxis-label {
            fill: var(--text-muted) !important;
        }

        /* Icon fallback styles - if FontAwesome fails to load */
        .fas::before {
            display: inline-block;
            margin-right: 0.25rem;
        }

        /* Loading indicator */
        .loading-indicator {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg-card);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid var(--border);
            z-index: 9999;
            text-align: center;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Indicator -->
    <div class="loading-indicator" id="loadingIndicator">
        <div class="loading-spinner"></div>
        <div style="color: var(--text-secondary);">Loading Analytics...</div>
    </div>
<div class="container">
    <!-- Modern Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-title">
                <h1>Delivery Analytics</h1>
                <p>Real-time insights into your delivery operations</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-download"></i> Export Report
                </button>
                <button class="btn btn-primary" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh Data
                </button>
            </div>
        </div>
    </div>

    <!-- Modern Filters -->
    <div class="filter-section">
        <form method="GET" action="{{ route('admin.delivery-stats') }}" id="filterForm">
            <div class="filter-grid">
                <div class="filter-group">
                    <label><i class="fas fa-map-marker-alt"></i> Zone</label>
                    <select name="zone_id">
                        <option value="all">All Zones</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt"></i> Date Range</label>
                    <select name="date_range">
                        <option value="today" {{ ($params['date_range'] ?? 'today') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ ($params['date_range'] ?? '') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="last_7_days" {{ ($params['date_range'] ?? '') == 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="last_30_days" {{ ($params['date_range'] ?? '') == 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="last_90_days" {{ ($params['date_range'] ?? '') == 'last_90_days' ? 'selected' : '' }}>Last 90 Days</option>
                    </select>
                </div>

                <div class="filter-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>

                <div class="filter-group">
                    <a href="{{ route('admin.delivery-stats') }}" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Key Performance Indicators -->
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-chart-line"></i> Key Performance Indicators
        </h2>
    </div>

    <div class="stats-grid">
        <!-- Today's Orders -->
        <div class="stat-card primary">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Today's Orders</div>
                    <div class="stat-value">{{ $data['today_orders'] ?? 0 }}</div>
                    <span class="stat-change up">
                        <i class="fas fa-arrow-up"></i> 12%
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>

        <!-- Delivered Today -->
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Delivered Today</div>
                    <div class="stat-value">{{ $data['delivered_today'] ?? 0 }}</div>
                    @php
                        $successRate = $data['today_orders'] > 0 ? round(($data['delivered_today'] / $data['today_orders']) * 100, 1) : 0;
                    @endphp
                    <span class="stat-change {{ $successRate >= 80 ? 'up' : 'down' }}">
                        <i class="fas fa-{{ $successRate >= 80 ? 'arrow-up' : 'arrow-down' }}"></i> {{ $successRate }}%
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <!-- Avg Delivery Time -->
        <div class="stat-card info">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Avg Delivery Time</div>
                    <div class="stat-value">{{ round($data['avg_delivery_time'] ?? 0, 0) }}<span style="font-size: 1rem;">min</span></div>
                    <span class="stat-change {{ ($data['avg_delivery_time'] ?? 0) <= 30 ? 'up' : 'down' }}">
                        <i class="fas fa-{{ ($data['avg_delivery_time'] ?? 0) <= 30 ? 'check' : 'clock' }}"></i>
                        {{ ($data['avg_delivery_time'] ?? 0) <= 30 ? 'Excellent' : 'Needs Improvement' }}
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>

        <!-- Avg Processing Time -->
        <div class="stat-card purple">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Avg Processing Time</div>
                    <div class="stat-value">{{ round($data['avg_processing_time'] ?? 0, 0) }}<span style="font-size: 1rem;">min</span></div>
                    <span class="stat-change {{ ($data['avg_processing_time'] ?? 0) <= 25 ? 'up' : 'down' }}">
                        <i class="fas fa-{{ ($data['avg_processing_time'] ?? 0) <= 25 ? 'check' : 'exclamation-circle' }}"></i>
                        {{ ($data['avg_processing_time'] ?? 0) <= 25 ? 'Fast Prep' : 'Review Process' }}
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-concierge-bell"></i>
                </div>
            </div>
        </div>

        <!-- Out for Delivery -->
        <div class="stat-card warning">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Out for Delivery</div>
                    <div class="stat-value">{{ $data['out_for_delivery'] ?? 0 }}</div>
                    <span class="stat-change up">
                        <i class="fas fa-shipping-fast"></i> Active Now
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-motorcycle"></i>
                </div>
            </div>
        </div>

        <!-- Processing Orders -->
        <div class="stat-card purple">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Processing</div>
                    <div class="stat-value">{{ $data['processing'] ?? 0 }}</div>
                    <span class="stat-change up">
                        <i class="fas fa-cogs"></i> In Kitchen
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-utensils"></i>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="stat-card warning">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Pending</div>
                    <div class="stat-value">{{ $data['pending_orders'] ?? 0 }}</div>
                    <span class="stat-change {{ ($data['pending_orders'] ?? 0) > 5 ? 'down' : 'up' }}">
                        <i class="fas fa-hourglass-half"></i> Awaiting
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-pause-circle"></i>
                </div>
            </div>
        </div>

        <!-- Ready for Pickup -->
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Ready for Pickup</div>
                    <div class="stat-value">{{ $data['ready_for_pickup'] ?? 0 }}</div>
                    <span class="stat-change up">
                        <i class="fas fa-box-open"></i> Prepared
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-shopping-basket"></i>
                </div>
            </div>
        </div>

        <!-- Failed Deliveries -->
        <div class="stat-card danger">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Failed Today</div>
                    <div class="stat-value">{{ $data['failed_today'] ?? 0 }}</div>
                    @php
                        $failureRate = $data['today_orders'] > 0 ? round(($data['failed_today'] / $data['today_orders']) * 100, 1) : 0;
                    @endphp
                    <span class="stat-change {{ $failureRate <= 5 ? 'up' : 'down' }}">
                        <i class="fas fa-exclamation-triangle"></i> {{ $failureRate }}%
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Insights -->
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-lightbulb"></i> Business Insights
        </h2>
    </div>

    <div class="insights-grid">
        <!-- Success Rate -->
        <div class="insight-card success">
            @php
                $successRate = $data['today_orders'] > 0 ? round(($data['delivered_today'] / $data['today_orders']) * 100, 1) : 0;
            @endphp
            <div class="insight-title">Delivery Success Rate</div>
            <div class="insight-value">{{ $successRate }}%</div>
            <div class="insight-description">
                {{ $successRate >= 90 ? '🎉 Excellent performance!' : ($successRate >= 75 ? '👍 Good performance' : '⚠️ Needs improvement') }}
                {{ $data['delivered_today'] ?? 0 }} out of {{ $data['today_orders'] ?? 0 }} orders delivered successfully.
            </div>
        </div>

        <!-- Avg Order Value -->
        <div class="insight-card info">
            <div class="insight-title">Avg Order Value</div>
            <div class="insight-value">₹{{ round($data['avg_order_value'] ?? 0, 0) }}</div>
            <div class="insight-description">
                Average value per order today.
                {{ ($data['avg_order_value'] ?? 0) >= 300 ? '📈 Above target' : '📊 Track closely' }}
            </div>
        </div>

        <!-- Peak Hour Performance -->
        <div class="insight-card warning">
            <div class="insight-title">Peak Hour Today</div>
            <div class="insight-value">
                {{ ($data['peak_hour'] ?? null) !== null ? date('g A', mktime($data['peak_hour'], 0)) : '--' }}
            </div>
            <div class="insight-description">
                {{ $data['peak_hour_orders'] ?? 0 }} orders during peak hour.
                {{ ($data['peak_hour_orders'] ?? 0) > 20 ? '🔥 High demand period' : '📊 Regular traffic' }}
            </div>
        </div>

        <!-- On-Time Delivery Rate -->
        <div class="insight-card purple">
            <div class="insight-title">On-Time Delivery</div>
            @php
                $onTimeRate = ($data['avg_delivery_time'] ?? 0) <= 30 ? 95 : (($data['avg_delivery_time'] ?? 0) <= 45 ? 80 : 65);
            @endphp
            <div class="insight-value">~{{ $onTimeRate }}%</div>
            <div class="insight-description">
                Based on {{ round($data['avg_delivery_time'] ?? 0, 0) }} min average delivery time.
                {{ $onTimeRate >= 90 ? '✅ Meeting SLA' : '⏰ Monitor closely' }}
            </div>
        </div>

        <!-- Scheduled Orders -->
        <div class="insight-card info">
            <div class="insight-title">Scheduled for Later</div>
            <div class="insight-value">{{ $data['scheduled_orders'] ?? 0 }}</div>
            <div class="insight-description">
                Pre-orders scheduled for later today.
                {{ ($data['scheduled_orders'] ?? 0) > 10 ? '📅 Good planning ahead' : '📋 Few pre-orders' }}
            </div>
        </div>

        <!-- Returned Orders -->
        <div class="insight-card danger">
            <div class="insight-title">Returns Today</div>
            <div class="insight-value">{{ $data['returned_today'] ?? 0 }}</div>
            <div class="insight-description">
                Refund requests from customers today.
                {{ ($data['returned_today'] ?? 0) <= 2 ? '✅ Low return rate' : '⚠️ Monitor quality' }}
            </div>
        </div>

        <!-- NEW INSIGHTS: 8 Additional Business Metrics -->

        <!-- 1. Today's Revenue -->
        <div class="insight-card success">
            <div class="insight-title">Today's Revenue</div>
            <div class="insight-value">₹{{ number_format($data['today_revenue'] ?? 0, 0) }}</div>
            <div class="insight-description">
                Total revenue from {{ $data['delivered_today'] ?? 0 }} delivered orders.
                {{ ($data['today_revenue'] ?? 0) >= 10000 ? '💰 Strong performance' : '📊 Track closely' }}
            </div>
        </div>

        <!-- 2. Delivery Boy Utilization -->
        <div class="insight-card info">
            <div class="insight-title">Delivery Boy Utilization</div>
            <div class="insight-value">{{ round($data['dm_utilization'] ?? 0, 1) }}</div>
            <div class="insight-description">
                Orders per active delivery boy. {{ $data['active_delivery_personnel'] ?? 0 }} delivery boys active.
                {{ ($data['dm_utilization'] ?? 0) >= 8 ? '🏍 Optimal load' : (($data['dm_utilization'] ?? 0) >= 5 ? '📊 Good load' : '⚠️ Underutilized') }}
            </div>
        </div>

        <!-- 3. Average Preparation Time -->
        <div class="insight-card warning">
            <div class="insight-title">Avg Preparation Time</div>
            <div class="insight-value">{{ round($data['avg_prep_time'] ?? 0, 0) }} <span style="font-size: 1rem;">min</span></div>
            <div class="insight-description">
                Time from order to ready for pickup.
                {{ ($data['avg_prep_time'] ?? 0) <= 15 ? '⚡ Fast preparation' : (($data['avg_prep_time'] ?? 0) <= 25 ? '✅ On target' : '⏰ Slow - investigate') }}
            </div>
        </div>

        <!-- 4. Top Performing Zone -->
        <div class="insight-card purple">
            <div class="insight-title">Top Performing Zone</div>
            <div class="insight-value">{{ $data['top_zone_name'] ?? 'N/A' }}</div>
            <div class="insight-description">
                {{ $data['top_zone_orders'] ?? 0 }} orders with {{ $data['top_zone_rate'] ?? 0 }}% success rate.
                {{ ($data['top_zone_rate'] ?? 0) >= 90 ? '🏆 Excellent zone' : '👍 Good zone' }}
            </div>
        </div>

        <!-- 5. Customer Wait Time (End-to-End) -->
        <div class="insight-card info">
            <div class="insight-title">Customer Wait Time</div>
            <div class="insight-value">{{ round($data['avg_wait_time'] ?? 0, 0) }} <span style="font-size: 1rem;">min</span></div>
            <div class="insight-description">
                End-to-end time from order to delivery.
                {{ ($data['avg_wait_time'] ?? 0) <= 45 ? '✅ Meeting target' : (($data['avg_wait_time'] ?? 0) <= 60 ? '⏰ Acceptable' : '⚠️ Too slow') }}
            </div>
        </div>

        <!-- 6. Order Fulfillment Rate -->
        <div class="insight-card success">
            <div class="insight-title">Order Fulfillment Rate</div>
            <div class="insight-value">{{ round($data['fulfillment_rate'] ?? 0, 1) }}%</div>
            <div class="insight-description">
                Successfully completed orders vs failures.
                {{ ($data['fulfillment_rate'] ?? 0) >= 95 ? '🎯 Excellent efficiency' : (($data['fulfillment_rate'] ?? 0) >= 85 ? '✅ Good efficiency' : '⚠️ Needs improvement') }}
            </div>
        </div>

        <!-- 7. Repeat Customer Rate -->
        <div class="insight-card purple">
            <div class="insight-title">Repeat Customer Rate</div>
            <div class="insight-value">{{ round($data['repeat_customer_rate'] ?? 0, 1) }}%</div>
            <div class="insight-description">
                Customers placing multiple orders today.
                {{ ($data['repeat_customer_rate'] ?? 0) >= 60 ? '❤️ High loyalty' : (($data['repeat_customer_rate'] ?? 0) >= 40 ? '👍 Good retention' : '📈 Build loyalty') }}
            </div>
        </div>

        <!-- 8. Capacity Utilization -->
        <div class="insight-card warning">
            <div class="insight-title">Capacity Utilization</div>
            <div class="insight-value">{{ round($data['capacity_utilization'] ?? 0, 0) }}%</div>
            <div class="insight-description">
                Current load vs capacity ({{ $data['theoretical_capacity'] ?? 0 }} orders max).
                {{ ($data['capacity_utilization'] ?? 0) >= 90 ? '🔴 Near capacity - add DMs' : (($data['capacity_utilization'] ?? 0) >= 70 ? '🟡 Optimal load' : '🟢 Good capacity') }}
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-chart-bar"></i> Detailed Analytics
        </h2>
    </div>

    <!-- Hourly Distribution -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <i class="fas fa-chart-line"></i> Hourly Order Distribution
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <span class="legend-dot" style="background: #D8276B;"></span>
                    <span>Order Volume</span>
                </div>
            </div>
        </div>
        <div id="hourly-chart" style="height: 400px;"></div>
    </div>

    <!-- Status & Delivery Time Charts -->
    <div class="grid-2">
        <!-- Order Status Breakdown -->
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-chart-pie"></i> Order Status Breakdown
                </div>
            </div>
            <div id="status-chart" style="height: 400px;"></div>
        </div>

        <!-- Delivery Time Distribution -->
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-clock"></i> Delivery Time Distribution
                </div>
            </div>
            <div id="delivery-time-chart" style="height: 400px;"></div>
        </div>
    </div>

    <!-- 90-Day Trend -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <i class="fas fa-chart-area"></i> 90-Day Historical Trends
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <span class="legend-dot" style="background: #0088FF;"></span>
                    <span>Total Orders</span>
                </div>
                <div class="legend-item">
                    <span class="legend-dot" style="background: #10b981;"></span>
                    <span>Delivered</span>
                </div>
                <div class="legend-item">
                    <span class="legend-dot" style="background: #ef4444;"></span>
                    <span>Cancelled</span>
                </div>
            </div>
        </div>
        <div id="trend-chart" style="height: 500px;"></div>
    </div>
</div>

<!-- Embed Chart Data -->
<script>
    window.CHART_DATA = @json($chartData);
    console.log('[Delivery Analytics] Chart data embedded:', window.CHART_DATA);

    // Icon and asset loading verification
    document.addEventListener('DOMContentLoaded', function() {
        // Check if FontAwesome loaded
        const testIcon = document.createElement('i');
        testIcon.className = 'fas fa-check';
        testIcon.style.display = 'none';
        document.body.appendChild(testIcon);

        setTimeout(function() {
            const computedStyle = window.getComputedStyle(testIcon, ':before');
            const content = computedStyle.getPropertyValue('content');

            if (!content || content === 'none' || content === '') {
                console.warn('[Delivery Analytics] WARNING: FontAwesome icons may not be loading correctly');
                console.warn('[Delivery Analytics] Recommendation: Check browser console for CORS or CDN errors');
            } else {
                console.log('[Delivery Analytics] FontAwesome icons loaded successfully');
            }

            document.body.removeChild(testIcon);
        }, 100);
    });
</script>

<!-- ApexCharts Library -->
<script src="{{ asset('public/assets/admin/js/apex-charts/apexcharts.js') }}"></script>

<!-- Enhanced Chart Initialization -->
<script src="{{ asset('public/assets/admin/js/delivery-stats-simple.js') }}"></script>

</body>
</html>
