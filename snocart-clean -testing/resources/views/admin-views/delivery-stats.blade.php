<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SnoCart Live Dashboard - TV Display</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #D8276B;
            --primary-light: #E8477B;
            --primary-dark: #B81E56;
            --secondary: #7c3aed;
            --success: #10b981;
            --info: #06b6d4;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-primary: #0a0a0a;
            --bg-secondary: #141414;
            --bg-card: #1a1a1a;
            --bg-card-hover: #202020;
            --text-primary: #ffffff;
            --text-secondary: #e5e5e5;
            --text-muted: #a3a3a3;
            --border: #2a2a2a;
            --border-hover: #3a3a3a;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            --gradient-secondary: linear-gradient(135deg, #1a1a1a 0%, #252525 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            padding: 20px;
            color: var(--text-primary);
            overflow-x: hidden;
            font-size: 16px;
            background-image:
                radial-gradient(circle at 20% 50%, rgba(216, 39, 107, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(216, 39, 107, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(216, 39, 107, 0.02) 0%, transparent 50%);
        }

        .dashboard-container {
            max-width: 1900px;
            margin: 0 auto;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--gradient-secondary);
            border-radius: 12px;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
        }

        .header-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .brand-logo {
            height: 45px;
            width: auto;
            transition: var(--transition);
        }

        .dashboard-header h1 {
            color: var(--text-primary);
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .clock-display {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .digital-clock {
            background: var(--bg-card);
            color: var(--primary);
            padding: 15px 25px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            font-weight: 700;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            min-width: 200px;
        }

        .current-date-small {
            background: var(--bg-card);
            color: var(--text-secondary);
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--border);
            font-size: clamp(0.9rem, 1.5vw, 1.1rem);
            font-weight: 500;
        }

        .header-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .zone-display {
            padding: 10px 18px;
            border-radius: 8px;
            border: 1px solid var(--border);
            font-size: clamp(0.9rem, 1.2vw, 1rem);
            background: var(--bg-card);
            color: var(--text-primary);
            min-width: 140px;
            text-align: center;
            font-weight: 500;
            transition: var(--transition);
        }

        .auto-refresh-indicator {
            display: flex;
            align-items: center;
            background: var(--gradient-primary);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            font-size: clamp(0.8rem, 1vw, 0.9rem);
        }

        .auto-refresh-indicator i {
            margin-right: 6px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .connection-status {
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 500;
            font-size: clamp(0.8rem, 1vw, 0.85rem);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .connection-status.online {
            background: var(--success);
            color: white;
        }

        .connection-status.offline {
            background: var(--danger);
            color: white;
        }

        .todays-section {
            margin-bottom: 50px;
        }

        .section-title {
            text-align: center;
            color: var(--text-primary);
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 700;
            margin-bottom: 30px;
            letter-spacing: -0.02em;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }

        .todays-highlight {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 10px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--gradient-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            text-align: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card.highlight {
            padding: 36px;
            border-color: var(--primary);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            background: var(--bg-card-hover);
        }

        .stat-card.orders { --accent: var(--primary); }
        .stat-card.delivery { --accent: var(--info); }
        .stat-card.stores { --accent: var(--success); }
        .stat-card.today { --accent: var(--primary); }
        .stat-card.processing { --accent: var(--warning); }
        .stat-card.out { --accent: #22c55e; }
        .stat-card.delivered { --accent: var(--success); }
        .stat-card.cancelled { --accent: var(--danger); }
        .stat-card.metric-time { --accent: #8b5cf6; }
        .stat-card.metric-pending { --accent: #f59e0b; }
        .stat-card.metric-ready { --accent: #14b8a6; }
        .stat-card.metric-failed { --accent: #ef4444; }
        .stat-card.metric-value { --accent: #10b981; }
        .stat-card.metric-peak { --accent: #f97316; }
        .stat-card.metric-scheduled { --accent: #06b6d4; }
        .stat-card.metric-returned { --accent: #64748b; }

        .stat-card h3 {
            color: var(--text-secondary);
            margin-top: 0;
            font-size: clamp(1rem, 1.6vw, 1.2rem);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-weight: 700;
        }

        .stat-card h3 i {
            color: var(--accent);
            font-size: 1.1em;
        }

        .stat-card .value {
            font-size: clamp(3rem, 6vw, 4.5rem);
            font-weight: 900;
            color: var(--accent);
            margin: 16px 0;
            transition: var(--transition);
            line-height: 1;
            letter-spacing: -0.03em;
        }

        .stat-card.highlight .value {
            font-size: clamp(3.5rem, 7vw, 5.5rem);
        }

        .stat-card .subtext {
            color: var(--text-muted);
            font-size: clamp(0.9rem, 1.3vw, 1rem);
            font-weight: 500;
            margin-bottom: 16px;
        }

        .stat-card .datetime-info {
            background: var(--bg-card);
            border: 1px solid var(--border-hover);
            color: var(--text-secondary);
            padding: 10px 16px;
            border-radius: 10px;
            font-size: clamp(0.8rem, 1.1vw, 0.9rem);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .stat-card .datetime-info i {
            color: var(--accent);
        }

        .last-updated {
            text-align: center;
            color: var(--text-secondary);
            font-size: clamp(1rem, 1.4vw, 1.1rem);
            background: var(--gradient-secondary);
            border: 1px solid var(--border);
            padding: 20px;
            border-radius: 16px;
            font-weight: 500;
        }

        .pulse {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--primary);
            margin-right: 12px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.3); background: var(--primary-light); }
            100% { opacity: 1; transform: scale(1); }
        }

        .value-updated {
            animation: valueFlash 0.6s ease-in-out;
        }

        @keyframes valueFlash {
            0% { color: var(--accent); transform: scale(1); }
            30% { color: var(--primary); transform: scale(1.05); }
            60% { color: var(--primary-light); transform: scale(1.02); }
            100% { color: var(--accent); transform: scale(1); }
        }

        .fullscreen-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--gradient-primary);
            border: none;
            color: white;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            transition: var(--transition);
            font-size: 1.3rem;
        }

        .error-message {
            background: var(--danger);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
            font-weight: 600;
            display: none;
            font-size: clamp(0.9rem, 1.3vw, 1rem);
        }

        @media (max-width: 1024px) {
            .stats-container { grid-template-columns: repeat(4, 1fr); gap: 16px; }
            .todays-highlight, .performance-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
        }

        @media (max-width: 768px) {
            .stats-container { grid-template-columns: repeat(4, 1fr); gap: 12px; }
            .todays-highlight, .performance-grid { grid-template-columns: 1fr; gap: 16px; }
            .stat-card { padding: 16px; }
            .stat-card.highlight { padding: 20px; }
        }

        * {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="header-brand">
                <img src="https://new.snocart.com/storage/app/public/on_black_1@4x.png" alt="SnoCart Logo" class="brand-logo" onerror="this.style.display='none'">
                <h1>Live Dashboard</h1>
            </div>
            <div class="clock-display">
                <div class="digital-clock" id="digital-clock">00:00:00</div>
                <div class="current-date-small" id="current-date-small">Loading...</div>
            </div>
            <div class="header-controls">
                <div class="zone-display"><i class="fas fa-map-marker-alt"></i> All Zones</div>
                <div class="auto-refresh-indicator"><i class="fas fa-sync-alt"></i> Auto-refresh: 5s</div>
                <div class="connection-status online" id="connection-status"><i class="fas fa-wifi"></i> Online</div>
            </div>
        </div>

        <div class="error-message" id="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <span id="error-text">Connection error. Retrying...</span>
        </div>

        <div class="todays-section">
            <h2 class="section-title">📊 Today's Performance</h2>
            <div class="todays-highlight">
                <div class="stat-card today highlight">
                    <h3><i class="fas fa-calendar-day"></i> Today's Orders</h3>
                    <div class="value" id="today-orders">0</div>
                    <div class="subtext">Total orders received today</div>
                    <div class="datetime-info"><i class="fas fa-clock"></i> <span id="today-time-display">--:--</span></div>
                </div>
                <div class="stat-card delivered highlight">
                    <h3><i class="fas fa-check-circle"></i> Delivered Today</h3>
                    <div class="value" id="delivered-today">0</div>
                    <div class="subtext">Successfully completed</div>
                    <div class="datetime-info"><i class="fas fa-trophy"></i> Success Rate: <span id="success-rate">0%</span></div>
                </div>
                <div class="stat-card out highlight">
                    <h3><i class="fas fa-shipping-fast"></i> Out for Delivery</h3>
                    <div class="value" id="out-for-delivery">0</div>
                    <div class="subtext">Currently en route</div>
                    <div class="datetime-info"><i class="fas fa-motorcycle"></i> Active deliveries</div>
                </div>
                <div class="stat-card processing highlight">
                    <h3><i class="fas fa-cogs"></i> Processing Now</h3>
                    <div class="value" id="processing-orders">0</div>
                    <div class="subtext">Being prepared</div>
                    <div class="datetime-info"><i class="fas fa-hourglass-half"></i> In progress</div>
                </div>
            </div>
        </div>

        <div class="todays-section">
            <h2 class="section-title">⚡ Performance Metrics</h2>
            <div class="performance-grid">
                <div class="stat-card metric-time highlight">
                    <h3><i class="fas fa-clock"></i> Avg. Delivery Time</h3>
                    <div class="value" id="avg-delivery-time">--</div>
                    <div class="subtext">Minutes per delivery</div>
                    <div class="datetime-info"><i class="fas fa-tachometer-alt"></i> <span id="delivery-speed">Standard</span></div>
                </div>
                <div class="stat-card metric-pending highlight">
                    <h3><i class="fas fa-hourglass-start"></i> Pending Orders</h3>
                    <div class="value" id="pending-orders">0</div>
                    <div class="subtext">Awaiting confirmation</div>
                    <div class="datetime-info"><i class="fas fa-bell"></i> Needs attention</div>
                </div>
                <div class="stat-card metric-ready highlight">
                    <h3><i class="fas fa-box-open"></i> Ready for Pickup</h3>
                    <div class="value" id="ready-pickup">0</div>
                    <div class="subtext">Waiting for riders</div>
                    <div class="datetime-info"><i class="fas fa-check-double"></i> Prepared</div>
                </div>
                <div class="stat-card metric-failed highlight">
                    <h3><i class="fas fa-exclamation-triangle"></i> Failed Today</h3>
                    <div class="value" id="failed-today">0</div>
                    <div class="subtext">Unsuccessful attempts</div>
                    <div class="datetime-info"><i class="fas fa-redo"></i> May retry</div>
                </div>
            </div>
        </div>

        <div class="todays-section">
            <h2 class="section-title">📈 Overall Statistics</h2>
            <div class="stats-container">
                <div class="stat-card orders">
                    <h3><i class="fas fa-shopping-cart"></i> Total Orders</h3>
                    <div class="value" id="total-orders">0</div>
                    <div class="subtext" id="new-orders">0 new today</div>
                </div>
                <div class="stat-card delivery">
                    <h3><i class="fas fa-motorcycle"></i> Delivery Team</h3>
                    <div class="value" id="delivery-personnel">0</div>
                    <div class="subtext" id="active-delivery">0 active now</div>
                </div>
                <div class="stat-card stores">
                    <h3><i class="fas fa-store"></i> Partner Stores</h3>
                    <div class="value" id="total-stores">0</div>
                    <div class="subtext" id="new-stores">0 new this month</div>
                </div>
                <div class="stat-card cancelled">
                    <h3><i class="fas fa-times-circle"></i> Cancelled Today</h3>
                    <div class="value" id="cancelled-today">0</div>
                    <div class="subtext">Canceled orders</div>
                </div>
                <div class="stat-card metric-value">
                    <h3><i class="fas fa-rupee-sign"></i> Avg. Order Value</h3>
                    <div class="value" id="avg-order-value">₹0</div>
                    <div class="subtext">Per transaction</div>
                </div>
                <div class="stat-card metric-peak">
                    <h3><i class="fas fa-chart-line"></i> Peak Hour Today</h3>
                    <div class="value" id="peak-hour">--</div>
                    <div class="subtext" id="peak-orders">0 orders</div>
                </div>
                <div class="stat-card metric-scheduled">
                    <h3><i class="fas fa-calendar-check"></i> Scheduled</h3>
                    <div class="value" id="scheduled-orders">0</div>
                    <div class="subtext">For later today</div>
                </div>
                <div class="stat-card metric-returned">
                    <h3><i class="fas fa-undo"></i> Returned Today</h3>
                    <div class="value" id="returned-today">0</div>
                    <div class="subtext">Customer unavailable</div>
                </div>
            </div>
        </div>

        <div class="last-updated">
            <span class="pulse"></span>
            Last updated: <span id="last-updated">Never</span> | Live data updates every 5 seconds
        </div>
    </div>

    <button class="fullscreen-button" onclick="toggleFullscreen()">
        <i class="fas fa-expand" id="fullscreen-icon"></i>
    </button>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let refreshInterval;
        let retryCount = 0;
        const maxRetries = 3;
        const refreshRate = 5000;
        const previousValues = {};

        function updateDateTime() {
            const now = new Date();
            const dateOptions = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
            const dateString = now.toLocaleDateString('en-US', dateOptions);
            const timeString = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const todayTimeString = now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit' });
            
            document.getElementById('digital-clock').textContent = timeString;
            document.getElementById('current-date-small').textContent = dateString;
            document.getElementById('today-time-display').textContent = todayTimeString;
        }

        function calculateSuccessRate(delivered, total) {
            if (total === 0) return 0;
            return Math.round((delivered / total) * 100);
        }

        function formatDeliveryTime(minutes) {
            if (!minutes || minutes === 0) return '--';
            if (minutes < 60) return Math.round(minutes) + ' min';
            const hours = Math.floor(minutes / 60);
            const mins = Math.round(minutes % 60);
            return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
        }

        function getDeliverySpeed(minutes) {
            if (!minutes || minutes === 0) return 'N/A';
            if (minutes <= 20) return 'Express';
            if (minutes <= 30) return 'Fast';
            if (minutes <= 45) return 'Standard';
            return 'Slow';
        }

        function formatCurrency(amount) {
            if (!amount || amount === 0) return '₹0';
            return '₹' + Math.round(amount).toLocaleString('en-IN');
        }

        function formatTime(hour) {
            if (!hour || hour < 0 || hour > 23) return '--';
            const period = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour} ${period}`;
        }

        function showError(message) {
            const errorDiv = document.getElementById('error-message');
            const errorText = document.getElementById('error-text');
            errorText.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => { errorDiv.style.display = 'none'; }, 5000);
        }

        function updateConnectionStatus(online) {
            const statusDiv = document.getElementById('connection-status');
            if (online) {
                statusDiv.className = 'connection-status online';
                statusDiv.innerHTML = '<i class="fas fa-wifi"></i> Online';
                retryCount = 0;
            } else {
                statusDiv.className = 'connection-status offline';
                statusDiv.innerHTML = '<i class="fas fa-wifi-slash"></i> Offline';
            }
        }

        function animateValueChange(elementId, newValue) {
            const element = document.getElementById(elementId);
            if (!element) return;
            const oldValue = previousValues[elementId];
            if (oldValue !== undefined && oldValue !== newValue) {
                element.classList.add('value-updated');
                setTimeout(() => { element.classList.remove('value-updated'); }, 800);
            }
            element.textContent = newValue;
            previousValues[elementId] = newValue;
        }

        function fetchDeliveryData() {
            const moduleId = new URLSearchParams(window.location.search).get('module_id') || '2';
            const possibleUrls = [
                `delivery-stats/data?zone_id=all&module_id=${moduleId}&t=${Date.now()}`,
                `delivery-stats?zone_id=all&module_id=${moduleId}&ajax=1&t=${Date.now()}`,
                `admin/delivery-stats/data?zone_id=all&module_id=${moduleId}&t=${Date.now()}`,
                `admin/delivery-stats?zone_id=all&module_id=${moduleId}&ajax=1&t=${Date.now()}`
            ];
            
            let urlIndex = 0;
            
            function tryFetch() {
                const url = possibleUrls[urlIndex];
                console.log(`🔄 Trying URL: ${url}`);
                
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    return response.json();
                })
                .then(data => {
                    console.log('✅ Data received:', data);
                    updateConnectionStatus(true);
                    
                    // Today's Performance
                    animateValueChange('total-orders', data.total_orders || 0);
                    animateValueChange('delivery-personnel', data.delivery_personnel_count || 0);
                    animateValueChange('total-stores', data.total_stores || 0);
                    animateValueChange('today-orders', data.today_orders || 0);
                    animateValueChange('processing-orders', data.processing || 0);
                    animateValueChange('out-for-delivery', data.out_for_delivery || 0);
                    animateValueChange('delivered-today', data.delivered_today || 0);
                    animateValueChange('cancelled-today', data.cancelled_today || 0);

                    // Performance Metrics
                    const avgTime = data.avg_delivery_time || 0;
                    animateValueChange('avg-delivery-time', formatDeliveryTime(avgTime));
                    document.getElementById('delivery-speed').textContent = getDeliverySpeed(avgTime);
                    animateValueChange('pending-orders', data.pending_orders || 0);
                    animateValueChange('ready-pickup', data.ready_for_pickup || 0);
                    animateValueChange('failed-today', data.failed_today || 0);

                    // Overall Statistics
                    animateValueChange('avg-order-value', formatCurrency(data.avg_order_value || 0));
                    animateValueChange('peak-hour', formatTime(data.peak_hour));
                    document.getElementById('peak-orders').textContent = `${data.peak_hour_orders || 0} orders`;
                    animateValueChange('scheduled-orders', data.scheduled_orders || 0);
                    animateValueChange('returned-today', data.returned_today || 0);

                    // Update subtexts
                    document.getElementById('new-orders').textContent = `${data.new_orders || 0} new today`;
                    document.getElementById('active-delivery').textContent = `${data.active_delivery_personnel || 0} active now`;
                    document.getElementById('new-stores').textContent = `${data.new_stores || 0} new this month`;

                    // Success rate
                    const successRate = calculateSuccessRate(data.delivered_today || 0, data.today_orders || 0);
                    document.getElementById('success-rate').textContent = `${successRate}%`;

                    document.getElementById('last-updated').textContent = new Date().toLocaleString();
                    retryCount = 0;
                })
                .catch(error => {
                    console.error(`❌ Error with URL ${url}:`, error);
                    urlIndex++;
                    if (urlIndex < possibleUrls.length) {
                        tryFetch();
                        return;
                    }
                    updateConnectionStatus(false);
                    retryCount++;
                    if (retryCount <= maxRetries) {
                        showError(`Connection error. Retry ${retryCount}/${maxRetries}...`);
                    } else {
                        showError('Unable to connect. Please check the URL and server status.');
                    }
                });
            }
            tryFetch();
        }

        function startAutoRefresh() {
            if (refreshInterval) clearInterval(refreshInterval);
            fetchDeliveryData();
            refreshInterval = setInterval(fetchDeliveryData, refreshRate);
            console.log('✅ Auto-refresh started - updating every 5 seconds');
        }

        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                console.log('🛑 Auto-refresh stopped');
            }
        }

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) stopAutoRefresh();
            else startAutoRefresh();
        });

        window.addEventListener('focus', startAutoRefresh);
        window.addEventListener('blur', stopAutoRefresh);

        startAutoRefresh();
        setInterval(updateDateTime, 1000);
        updateDateTime();

        window.addEventListener('beforeunload', stopAutoRefresh);
    });

    function toggleFullscreen() {
        const icon = document.getElementById('fullscreen-icon');
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().then(() => {
                icon.className = 'fas fa-compress';
            }).catch(err => console.error('Error entering fullscreen:', err));
        } else {
            document.exitFullscreen().then(() => {
                icon.className = 'fas fa-expand';
            }).catch(err => console.error('Error exiting fullscreen:', err));
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'F11') {
            e.preventDefault();
            toggleFullscreen();
        }
        if (e.key === 'F5') {
            e.preventDefault();
            location.reload();
        }
    });
    </script>
</body>
</html>
