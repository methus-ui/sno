// Standalone WebSocket handler for order tracking
// This file loads independently to avoid being affected by other JS errors

console.log('%c🔥 STANDALONE WEBSOCKET LOADED - VERSION 1.0', 'background: #ec4899; color: white; font-size: 14px; padding: 8px; font-weight: bold;');

(function() {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWebSocket);
    } else {
        initWebSocket();
    }

    function initWebSocket() {
        // Get order data from page
        var orderDataEl = document.getElementById('websocketOrderData');
        if (!orderDataEl) {
            console.warn('⚠️ No websocket order data found');
            return;
        }

        var deliveryManId = orderDataEl.getAttribute('data-dm-id');
        var pusherKey = orderDataEl.getAttribute('data-pusher-key');
        var pusherCluster = orderDataEl.getAttribute('data-pusher-cluster');

        if (!deliveryManId || !pusherKey) {
            console.warn('⚠️ Missing WebSocket configuration');
            return;
        }

        console.log('🚴 Standalone WebSocket initializing for DM #' + deliveryManId);

        try {
            // Initialize Pusher
            var trackingPusher = new Pusher(pusherKey, {
                cluster: pusherCluster,
                forceTLS: true,
                enabledTransports: ['ws', 'wss'],
                disableStats: true
            });

            // Subscribe to channel
            var channel = trackingPusher.subscribe('delivery-man.' + deliveryManId);

            channel.bind('pusher:subscription_succeeded', function() {
                console.log('✅ Standalone WebSocket subscribed successfully');
            });

            channel.bind('pusher:subscription_error', function(error) {
                console.error('❌ Standalone WebSocket subscription failed:', error);
            });

            // Listen for location updates
            channel.bind('location.updated', function(data) {
                try {
                    console.log('📍 STANDALONE: Location update received:', data);

                    if (data.location && data.location.latitude && data.location.longitude) {
                        // Update UI elements directly
                        updateUIElements(data.location);

                        // Show visual feedback
                        showUpdateFeedback();
                    }
                } catch (error) {
                    console.error('❌ Error processing update:', error);
                }
            });

            trackingPusher.connection.bind('connected', function() {
                console.log('🔌 Standalone WebSocket connected');
            });

        } catch (e) {
            console.error('❌ Failed to initialize standalone WebSocket:', e);
        }
    }

    // Update UI elements with new location data
    function updateUIElements(location) {
        var speed = parseFloat(location.speed) || 0;
        var newLat = parseFloat(location.latitude);
        var newLng = parseFloat(location.longitude);

        // Update speed display
        var speedEl = document.getElementById('speedDM');
        if (speedEl) {
            speedEl.textContent = speed.toFixed(1) + ' km/h';
            addUpdateAnimation(speedEl);
        }

        // Calculate and update distance
        var customerLat = parseFloat(document.getElementById('websocketOrderData')?.getAttribute('data-customer-lat'));
        var customerLng = parseFloat(document.getElementById('websocketOrderData')?.getAttribute('data-customer-lng'));

        if (customerLat && customerLng) {
            var distance = haversineDistance(newLat, newLng, customerLat, customerLng);
            var distanceEl = document.getElementById('quickDistance');

            if (distanceEl) {
                if (distance < 1) {
                    distanceEl.textContent = (distance * 1000).toFixed(0) + 'm';
                } else {
                    distanceEl.textContent = distance.toFixed(2) + ' km';
                }
                addUpdateAnimation(distanceEl);
            }

            // Update ETA
            var eta = speed > 0 ? Math.ceil((distance / speed) * 60) : Math.ceil(distance * 3);
            var etaEl = document.getElementById('quickETA');
            if (etaEl) {
                etaEl.textContent = '~' + eta + ' mins';
                addUpdateAnimation(etaEl);
            }
        }

        console.log('📍 UI updated - Speed:', speed.toFixed(1), 'km/h');
    }

    // Add visual update animation
    function addUpdateAnimation(element) {
        if (!element) return;

        element.style.transition = 'all 0.3s ease';
        element.style.transform = 'scale(1.1)';
        element.style.backgroundColor = 'rgba(16, 185, 129, 0.2)';
        element.style.color = '#10b981';
        element.style.fontWeight = '700';

        setTimeout(function() {
            element.style.transform = 'scale(1)';
            element.style.backgroundColor = '';
            element.style.color = '';
            element.style.fontWeight = '';
        }, 600);
    }

    // Show update feedback
    function showUpdateFeedback() {
        var feedbackEl = document.getElementById('websocketFeedback');
        if (feedbackEl) {
            feedbackEl.style.display = 'block';
            setTimeout(function() {
                feedbackEl.style.display = 'none';
            }, 2000);
        }
    }

    // Haversine distance formula
    function haversineDistance(lat1, lng1, lat2, lng2) {
        var R = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLng = (lng2 - lng1) * Math.PI / 180;
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

})();
