/**
 * Real-Time Delivery Tracking Module
 *
 * Features:
 * - WebSocket live updates via Pusher
 * - Progressive disclosure (collapse/expand)
 * - Proximity alerts (store: 400m, customer: 500m)
 * - Journey progress bar
 * - ETA calculation with contextual banners
 * - Relative time updates
 *
 * @version 2.0.0
 * @date 2026-02-19
 */

var DeliveryTracking = (function() {
    'use strict';

    // ── Configuration ──────────────────────────────────────────
    var cfg = {
        orderId: null,
        deliveryManId: null,
        pusherKey: null,
        pusherCluster: null,
        pusherHost: null,
        pusherPort: null,
        pusherScheme: null,
        pusherUseTls: false,
        enableWebSocket: true
    };

    // ── State ──────────────────────────────────────────────────
    var state = {
        dmLocation: { lat: 0, lng: 0, speed: 0, heading: 0, accuracy: 0 },
        storeLocation: { lat: 0, lng: 0, name: '' },
        customerLocation: { lat: 0, lng: 0, name: '' },
        dmName: '',
        lastUpdate: null,
        isExpanded: false,
        isNearStore: false,
        isNearCustomer: false,
        hasNotifiedNearby: false,
        movementState: 'unknown',
        channel: null,
        pusher: null
    };

    // ── Constants ──────────────────────────────────────────────
    var THRESHOLDS = {
        STORE_PROXIMITY: 400,      // meters
        CUSTOMER_PROXIMITY: 500,   // meters
        AVG_SPEED: 20,             // km/h for ETA calculation
        MOVING_SPEED: 5,           // km/h minimum for "moving" state
        SLOW_SPEED: 1              // km/h minimum for "slow" state
    };

    // ══════════════════════════════════════════════════════════
    // INITIALIZATION
    // ══════════════════════════════════════════════════════════

    function init(config) {
        cfg = Object.assign({}, cfg, config);

        console.log('🚴 Delivery Tracking v2.0 Initializing...', {
            order: cfg.orderId,
            dm: cfg.deliveryManId,
            websocket: cfg.enableWebSocket
        });

        // Set initial location data
        if (config.dmLocation) {
            state.dmLocation = config.dmLocation;
        }
        if (config.storeLocation) {
            state.storeLocation = config.storeLocation;
        }
        if (config.customerLocation) {
            state.customerLocation = config.customerLocation;
        }
        if (config.dmName) {
            state.dmName = config.dmName;
        }
        if (config.lastUpdate) {
            state.lastUpdate = new Date(config.lastUpdate);
        }

        setupUI();
        updateUI();

        if (cfg.enableWebSocket && cfg.deliveryManId) {
            setupWebSocket();
        } else {
            // Fallback: poll every 30 seconds
            setInterval(pollLocation, 30000);
        }

        // Update relative time every second
        setInterval(updateRelativeTime, 1000);
    }

    // ══════════════════════════════════════════════════════════
    // WEBSOCKET
    // ══════════════════════════════════════════════════════════

    function setupWebSocket() {
        if (!window.Pusher) {
            console.warn('❌ Pusher not loaded - falling back to polling');
            setInterval(pollLocation, 30000);
            return;
        }

        if (!cfg.pusherKey) {
            console.warn('❌ Pusher key not configured - falling back to polling');
            setInterval(pollLocation, 30000);
            return;
        }

        try {
            // Initialize Pusher
            state.pusher = new Pusher(cfg.pusherKey, {
                cluster: cfg.pusherCluster,
                wsHost: cfg.pusherHost,
                wsPort: cfg.pusherPort,
                wssPort: cfg.pusherPort,
                forceTLS: cfg.pusherUseTls,
                enabledTransports: ['ws', 'wss'],
                disableStats: true
            });

            // Subscribe to delivery man location channel
            var channelName = 'delivery-man.' + cfg.deliveryManId;
            console.log('🔌 Subscribing to channel:', channelName);

            state.channel = state.pusher.subscribe(channelName);

            state.channel.bind('pusher:subscription_succeeded', function() {
                console.log('✅ Subscribed to real-time location updates');
                updateConnectionStatus('connected');
            });

            state.channel.bind('pusher:subscription_error', function(error) {
                console.error('❌ Failed to subscribe:', error);
                updateConnectionStatus('error');
            });

            // Listen for location updates
            // Event format can be either: DeliveryManLocationUpdated or .location.updated
            state.channel.bind('App\\Events\\DeliveryManLocationUpdated', handleLocationUpdate);
            state.channel.bind('.location.updated', handleLocationUpdate);
            state.channel.bind('location.updated', handleLocationUpdate);

            console.log('✅ WebSocket configured');

        } catch (error) {
            console.error('❌ Failed to setup WebSocket:', error);
            updateConnectionStatus('error');
            // Fallback to polling
            setInterval(pollLocation, 30000);
        }
    }

    function handleLocationUpdate(data) {
        console.log('📍 Location update received:', data);

        state.dmLocation = {
            lat: parseFloat(data.latitude || data.lat || 0),
            lng: parseFloat(data.longitude || data.lng || 0),
            speed: parseFloat(data.speed || 0),
            heading: parseFloat(data.heading || 0),
            accuracy: parseFloat(data.accuracy || 0)
        };
        state.lastUpdate = new Date();

        // Update UI
        updateUI();
        checkProximity();
        updateConnectionStatus('live');

        // Spin the refresh icon briefly
        var refreshIcon = document.getElementById('trackingRefreshIcon');
        if (refreshIcon) {
            refreshIcon.classList.add('spinning');
            setTimeout(function() {
                refreshIcon.classList.remove('spinning');
            }, 500);
        }
    }

    function pollLocation() {
        // Fallback: fetch latest location from server
        console.log('🔄 Polling location (WebSocket unavailable)');

        fetch('/admin/order/dm-location/' + cfg.orderId)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.location) {
                    handleLocationUpdate(data.location);
                }
            })
            .catch(function(error) {
                console.error('❌ Polling failed:', error);
            });
    }

    function updateConnectionStatus(status) {
        var badge = document.getElementById('liveBadge');
        if (!badge) return;

        switch (status) {
            case 'live':
                badge.className = 'live-badge live-badge-green';
                badge.innerHTML = '<span class="live-dot"></span> Live';
                break;
            case 'connected':
                badge.className = 'live-badge live-badge-blue';
                badge.innerHTML = '<span class="live-dot"></span> Connected';
                break;
            case 'error':
                badge.className = 'live-badge live-badge-red';
                badge.innerHTML = '<span class="live-dot"></span> Offline';
                break;
        }
    }

    // ══════════════════════════════════════════════════════════
    // DISTANCE CALCULATION (Haversine Formula)
    // ══════════════════════════════════════════════════════════

    function calculateDistance(lat1, lng1, lat2, lng2) {
        var R = 6371; // Earth radius in km
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLng = (lng2 - lng1) * Math.PI / 180;
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c; // Returns kilometers
    }

    // ══════════════════════════════════════════════════════════
    // UI SETUP
    // ══════════════════════════════════════════════════════════

    function setupUI() {
        // Attach toggle event
        var header = document.getElementById('trackingHeader');
        if (header) {
            header.addEventListener('click', toggle);
        }
    }

    function toggle() {
        state.isExpanded = !state.isExpanded;

        var body = document.getElementById('trackingBody');
        var icon = document.getElementById('toggleIcon');

        if (body) {
            body.style.display = state.isExpanded ? 'block' : 'none';
        }
        if (icon) {
            icon.className = state.isExpanded ? 'tio-chevron-up' : 'tio-chevron-down';
        }

        // Load detailed stats on first expand
        if (state.isExpanded && !state.statsLoaded) {
            loadDetailedStats();
            state.statsLoaded = true;
        }
    }

    // ══════════════════════════════════════════════════════════
    // UI UPDATES
    // ══════════════════════════════════════════════════════════

    function updateUI() {
        if (!state.dmLocation.lat || !state.dmLocation.lng) {
            showNoLocationMessage();
            return;
        }

        // Calculate distances
        var toStore = 0, toCustomer = 0;

        if (state.storeLocation.lat && state.storeLocation.lng) {
            toStore = calculateDistance(
                state.dmLocation.lat, state.dmLocation.lng,
                state.storeLocation.lat, state.storeLocation.lng
            );
        }

        if (state.customerLocation.lat && state.customerLocation.lng) {
            toCustomer = calculateDistance(
                state.dmLocation.lat, state.dmLocation.lng,
                state.customerLocation.lat, state.customerLocation.lng
            );
        }

        // Calculate progress
        var progress = 0;
        if (state.storeLocation.lat && state.customerLocation.lat) {
            var totalDist = calculateDistance(
                state.storeLocation.lat, state.storeLocation.lng,
                state.customerLocation.lat, state.customerLocation.lng
            );
            if (totalDist > 0) {
                progress = Math.min(100, Math.max(0, (toStore / totalDist) * 100));
            }
        }

        // Update distance cards
        updateDistanceCard('store', toStore);
        updateDistanceCard('customer', toCustomer);
        updateSpeedCard();

        // Update progress bar
        updateProgressBar(progress);

        // Update summary
        updateSummary(toCustomer);

        // Update banner
        updateBanner(toCustomer, toStore);
    }

    function updateDistanceCard(type, distance) {
        var distKm = distance;
        var distMeters = distance * 1000;
        var eta = Math.ceil(distance / THRESHOLDS.AVG_SPEED * 60); // minutes

        var distStr = distMeters < 900
            ? Math.round(distMeters) + ' m'
            : distKm.toFixed(1) + ' km';

        var etaStr = eta <= 1 ? '< 1 min' : '~' + eta + ' min';

        var isNearby = false;
        if (type === 'store') {
            isNearby = distMeters <= THRESHOLDS.STORE_PROXIMITY;
        } else if (type === 'customer') {
            isNearby = distMeters <= THRESHOLDS.CUSTOMER_PROXIMITY;
        }

        var distEl = document.getElementById('distance' + capitalize(type));
        var etaEl = document.getElementById('eta' + capitalize(type));
        var statusEl = document.getElementById('status' + capitalize(type));
        var cardEl = document.getElementById('card' + capitalize(type));

        if (distEl) distEl.textContent = distStr;
        if (etaEl) etaEl.textContent = etaStr;

        if (statusEl && isNearby) {
            statusEl.style.display = 'block';
        } else if (statusEl) {
            statusEl.style.display = 'none';
        }

        if (cardEl) {
            if (isNearby) {
                cardEl.classList.add('nearby');
            } else {
                cardEl.classList.remove('nearby');
            }
        }
    }

    function updateSpeedCard() {
        var speed = state.dmLocation.speed || 0;
        var speedKmh = speed; // Already in km/h from GPS

        var speedEl = document.getElementById('speedDM');
        var stateEl = document.getElementById('stateDM');
        var trendEl = document.getElementById('trendDM');

        if (speedEl) {
            speedEl.textContent = speedKmh.toFixed(0) + ' km/h';
        }

        // Determine movement state
        var movementState = 'Stopped';
        var trendIcon = '⏸️';

        if (speedKmh >= THRESHOLDS.MOVING_SPEED) {
            movementState = 'Moving';
            trendIcon = '⬆️';
        } else if (speedKmh >= THRESHOLDS.SLOW_SPEED) {
            movementState = 'Slow';
            trendIcon = '⬇️';
        }

        state.movementState = movementState;

        if (stateEl) {
            stateEl.textContent = movementState;
        }
        if (trendEl) {
            trendEl.textContent = trendIcon;
        }
    }

    function updateProgressBar(progress) {
        var fillEl = document.getElementById('progressFill');
        var percentEl = document.getElementById('progressPercent');

        if (fillEl) {
            fillEl.style.width = progress.toFixed(1) + '%';
        }
        if (percentEl) {
            percentEl.textContent = Math.round(progress) + '%';
        }
    }

    function updateSummary(distanceToCustomer) {
        var distKm = distanceToCustomer;
        var distMeters = distKm * 1000;
        var eta = Math.ceil(distKm / THRESHOLDS.AVG_SPEED * 60);

        var distStr = distMeters < 900
            ? Math.round(distMeters) + 'm away'
            : distKm.toFixed(1) + ' km away';

        var etaStr = eta <= 1 ? '< 1 min' : '~' + eta + ' mins';

        var quickDistEl = document.getElementById('quickDistance');
        var quickEtaEl = document.getElementById('quickETA');
        var nearbyBadge = document.getElementById('nearbyBadge');

        if (quickDistEl) quickDistEl.textContent = distStr;
        if (quickEtaEl) quickEtaEl.textContent = etaStr;

        if (nearbyBadge) {
            if (distMeters <= THRESHOLDS.CUSTOMER_PROXIMITY) {
                nearbyBadge.style.display = 'inline-block';
            } else {
                nearbyBadge.style.display = 'none';
            }
        }
    }

    function updateBanner(distToCustomer, distToStore) {
        var banner = document.getElementById('deliveryBanner');
        if (!banner) return;

        var distMeters = distToCustomer * 1000;
        var eta = Math.ceil(distToCustomer / THRESHOLDS.AVG_SPEED * 60);

        var storeMeters = distToStore * 1000;
        var isAtStore = storeMeters <= THRESHOLDS.STORE_PROXIMITY;
        var isNearCustomer = distMeters <= THRESHOLDS.CUSTOMER_PROXIMITY;

        var html = '';
        var className = '';

        if (isNearCustomer) {
            // Nearby state
            className = 'delivery-banner banner-nearby';
            html = '<div class="banner-icon">🎯</div>';
            html += '<div class="banner-content">';
            html += '<div class="banner-title">Delivery Man Nearby!</div>';
            html += '<div class="banner-text">Expected arrival within <strong>2-3 minutes</strong></div>';
            html += '</div>';
        } else if (isAtStore) {
            // At store
            className = 'delivery-banner banner-at-store';
            html = '<div class="banner-icon">🏪</div>';
            html += '<div class="banner-content">';
            html += '<div class="banner-title">Delivery Man at Restaurant</div>';
            html += '<div class="banner-text">Picking up your order</div>';
            html += '</div>';
        } else {
            // In transit
            className = 'delivery-banner banner-in-transit';
            var arriveTime = getArrivalTime(eta);
            html = '<div class="banner-icon">⏰</div>';
            html += '<div class="banner-content">';
            html += '<div class="banner-title">Estimated Delivery: ' + arriveTime + '</div>';
            html += '<div class="banner-text">Approximately <strong>' + eta + ' minutes</strong> away</div>';
            html += '</div>';
        }

        banner.className = className;
        banner.innerHTML = html;
    }

    function getArrivalTime(minutesFromNow) {
        var now = new Date();
        var arrivalTime = new Date(now.getTime() + minutesFromNow * 60000);
        var hours = arrivalTime.getHours();
        var minutes = arrivalTime.getMinutes();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        return hours + ':' + minutes + ' ' + ampm;
    }

    function updateRelativeTime() {
        var timeEl = document.getElementById('lastUpdateTime');
        if (!timeEl || !state.lastUpdate) return;

        var now = new Date();
        var diffSec = Math.floor((now - state.lastUpdate) / 1000);

        var timeStr = '';
        var className = '';

        if (diffSec < 10) {
            timeStr = 'Just now';
            className = 'last-update time-fresh';
        } else if (diffSec < 60) {
            timeStr = diffSec + 's ago';
            className = 'last-update time-recent';
        } else if (diffSec < 3600) {
            timeStr = Math.floor(diffSec / 60) + 'm ago';
            className = 'last-update time-stale';
        } else if (diffSec < 86400) {
            timeStr = Math.floor(diffSec / 3600) + 'h ago';
            className = 'last-update time-old';
        } else {
            // More than 24 hours - show in days
            var days = Math.floor(diffSec / 86400);
            timeStr = days + ' day' + (days > 1 ? 's' : '') + ' ago';
            className = 'last-update time-very-old';
        }

        timeEl.textContent = timeStr;
        timeEl.className = className;

        // Show warning for very stale data (> 1 hour)
        showStaleDataWarning(diffSec);
    }

    function showStaleDataWarning(diffSec) {
        var warningEl = document.getElementById('staleDataWarning');
        if (!warningEl) return;

        if (diffSec > 3600) {
            // Location is more than 1 hour old
            var hours = Math.floor(diffSec / 3600);
            var days = Math.floor(diffSec / 86400);

            var message = '';
            if (days > 0) {
                message = 'Location data is ' + days + ' day' + (days > 1 ? 's' : '') + ' old. Delivery man app may be offline.';
            } else {
                message = 'Location data is ' + hours + ' hour' + (hours > 1 ? 's' : '') + ' old. GPS tracking may be disabled.';
            }

            warningEl.innerHTML = '<i class="tio-warning"></i> ' + message;
            warningEl.style.display = 'block';
        } else {
            warningEl.style.display = 'none';
        }
    }

    function checkProximity() {
        var toCustomer = 0;
        if (state.customerLocation.lat && state.customerLocation.lng) {
            toCustomer = calculateDistance(
                state.dmLocation.lat, state.dmLocation.lng,
                state.customerLocation.lat, state.customerLocation.lng
            );
        }

        var distMeters = toCustomer * 1000;
        var wasNear = state.isNearCustomer;
        state.isNearCustomer = distMeters <= THRESHOLDS.CUSTOMER_PROXIMITY;

        // Trigger alert on first entry to nearby zone
        if (state.isNearCustomer && !wasNear && !state.hasNotifiedNearby) {
            showNearbyAlert();
            playNotificationSound();
            state.hasNotifiedNearby = true;
        }
    }

    function showNearbyAlert() {
        console.log('🎯 Delivery man is nearby!');
        // Could show a toast notification here
        if (window.toastr) {
            toastr.success('Delivery man is nearby! Expected arrival within 2-3 minutes.', 'Almost There!', {
                timeOut: 10000,
                progressBar: true
            });
        }
    }

    function playNotificationSound() {
        // Play a subtle notification sound
        try {
            var audio = new Audio('/assets/admin/sounds/notification.mp3');
            audio.volume = 0.3;
            audio.play().catch(function(e) {
                console.log('Could not play sound:', e);
            });
        } catch (e) {
            console.log('Audio not available');
        }
    }

    function showNoLocationMessage() {
        var container = document.getElementById('deliveryTracking');
        if (container) {
            container.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0"><i class="tio-info-outined mr-2"></i>Delivery man location not yet available</div></div>';
        }
    }

    function loadDetailedStats() {
        // First, show data we already have
        updateMovementStateDetail();

        // Then try to fetch additional stats from server
        fetch('/admin/order/tracking-stats/' + cfg.orderId)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Stats not available');
                }
                return response.json();
            })
            .then(function(data) {
                if (data && data.success) {
                    var el;
                    el = document.getElementById('storeArrivalTime');
                    if (el && data.store_arrival_time) {
                        el.textContent = formatDateTime(data.store_arrival_time);
                    }

                    el = document.getElementById('storeDuration');
                    if (el && data.store_duration_seconds) {
                        el.textContent = formatDuration(data.store_duration_seconds);
                    }

                    el = document.getElementById('customerArrivalTime');
                    if (el && data.customer_arrival_time) {
                        el.textContent = formatDateTime(data.customer_arrival_time);
                    }

                    el = document.getElementById('totalIdleTime');
                    if (el && data.total_idle_seconds) {
                        el.textContent = formatDuration(data.total_idle_seconds);
                    }

                    el = document.getElementById('idleCount');
                    if (el) el.textContent = data.idle_count || 0;

                    el = document.getElementById('movementStateDetail');
                    if (el && data.movement_state) {
                        el.textContent = formatMovementState(data.movement_state);
                    }
                }
            })
            .catch(function(error) {
                console.log('ℹ️ Tracking stats not available yet:', error.message);
                // Show basic info from current state
                updateBasicStats();
            });
    }

    function updateBasicStats() {
        var el;

        // Show last update time as "picked up" time if recent
        if (state.lastUpdate) {
            el = document.getElementById('storeArrivalTime');
            if (el) {
                var timeDiff = (new Date() - state.lastUpdate) / 1000;
                if (timeDiff < 3600) {
                    el.textContent = 'Recently';
                } else {
                    el.textContent = 'Not tracked';
                }
            }
        }

        // Show current movement state
        el = document.getElementById('movementStateDetail');
        if (el) {
            el.textContent = formatMovementState(state.movementState);
        }
    }

    function updateMovementStateDetail() {
        var el = document.getElementById('movementStateDetail');
        if (el) {
            el.textContent = formatMovementState(state.movementState);
        }
    }

    function formatMovementState(state) {
        var states = {
            'moving': 'Moving',
            'slow': 'Moving Slowly',
            'stopped': 'Stopped',
            'idle': 'Idle',
            'unknown': 'Unknown',
            'Moving': 'Moving',
            'Slow': 'Moving Slowly',
            'Stopped': 'Stopped',
            'Idle': 'Idle'
        };
        return states[state] || 'Unknown';
    }

    function formatDuration(seconds) {
        if (!seconds || seconds === 0) return '-';

        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);

        if (hours > 0) {
            return hours + 'h ' + minutes + 'm';
        } else if (minutes > 0) {
            return minutes + 'm';
        } else {
            return seconds + 's';
        }
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return '-';

        try {
            var date = new Date(dateStr);
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;

            return hours + ':' + minutes + ' ' + ampm;
        } catch (e) {
            return dateStr;
        }
    }

    // ══════════════════════════════════════════════════════════
    // UTILITIES
    // ══════════════════════════════════════════════════════════

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // ══════════════════════════════════════════════════════════
    // PUBLIC API
    // ══════════════════════════════════════════════════════════

    return {
        init: init,
        refresh: updateUI,
        toggle: toggle,
        getState: function() { return state; }
    };
})();
