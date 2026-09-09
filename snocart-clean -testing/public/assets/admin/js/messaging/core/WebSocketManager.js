/**
 * WebSocketManager
 *
 * Manages Pusher WebSocket connection with automatic reconnection,
 * exponential backoff, and graceful degradation to polling.
 *
 * Features:
 * - Auto-reconnection with exponential backoff (1s → 2s → 5s → 10s → 30s)
 * - Heartbeat ping every 30 seconds
 * - Connection state tracking (connected, disconnected, reconnecting, polling)
 * - Automatic fallback to polling after 10 failed attempts
 * - Event broadcasting for state changes
 *
 * @class WebSocketManager
 */
class WebSocketManager {
    constructor(config = {}) {
        this.config = {
            pusherKey: config.pusherKey || window.pusherKey,
            cluster: config.cluster || 'ap2',
            encrypted: config.encrypted !== false,
            authEndpoint: config.authEndpoint || '/broadcasting/auth',
            maxReconnectAttempts: config.maxReconnectAttempts || 10,
            reconnectIntervals: config.reconnectIntervals || [1000, 2000, 5000, 10000, 30000], // ms
            heartbeatInterval: config.heartbeatInterval || 30000, // 30s
            onStateChange: config.onStateChange || (() => {}),
            onMessage: config.onMessage || (() => {}),
            onError: config.onError || ((error) => console.error('WebSocket error:', error))
        };

        this.pusher = null;
        this.state = 'disconnected'; // connected, disconnected, reconnecting, polling
        this.reconnectAttempts = 0;
        this.reconnectTimeout = null;
        this.heartbeatInterval = null;
        this.subscribedChannels = new Map();
        this.enabled = true;

        this.initialize();
    }

    /**
     * Initialize Pusher connection
     */
    initialize() {
        if (!this.config.pusherKey) {
            console.warn('WebSocketManager: Pusher key not configured, using polling fallback');
            this.fallbackToPolling();
            return;
        }

        try {
            // Enable Pusher logging in development
            if (window.APP_DEBUG) {
                Pusher.logToConsole = true;
            }

            this.pusher = new Pusher(this.config.pusherKey, {
                cluster: this.config.cluster,
                encrypted: this.config.encrypted,
                authEndpoint: this.config.authEndpoint,
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                }
            });

            this.setupEventHandlers();
            this.startHeartbeat();

            console.log('WebSocketManager: Initialized successfully');

        } catch (error) {
            console.error('WebSocketManager: Failed to initialize', error);
            this.config.onError(error);
            this.fallbackToPolling();
        }
    }

    /**
     * Setup Pusher event handlers
     */
    setupEventHandlers() {
        // Connection state events
        this.pusher.connection.bind('connected', () => {
            console.log('WebSocketManager: Connected');
            this.reconnectAttempts = 0;
            this.updateState('connected');
        });

        this.pusher.connection.bind('disconnected', () => {
            console.warn('WebSocketManager: Disconnected');
            this.updateState('disconnected');
            this.attemptReconnect();
        });

        this.pusher.connection.bind('unavailable', () => {
            console.error('WebSocketManager: Unavailable');
            this.updateState('disconnected');
            this.attemptReconnect();
        });

        this.pusher.connection.bind('failed', () => {
            console.error('WebSocketManager: Connection failed');
            this.updateState('disconnected');
            this.attemptReconnect();
        });

        // Error handling
        this.pusher.connection.bind('error', (error) => {
            console.error('WebSocketManager: Connection error', error);
            this.config.onError(error);
        });
    }

    /**
     * Update connection state and notify listeners
     */
    updateState(newState) {
        if (this.state !== newState) {
            const oldState = this.state;
            this.state = newState;

            console.log(`WebSocketManager: State changed from ${oldState} to ${newState}`);

            this.config.onStateChange({
                state: newState,
                oldState: oldState,
                timestamp: new Date().toISOString(),
                reconnectAttempts: this.reconnectAttempts
            });
        }
    }

    /**
     * Attempt to reconnect with exponential backoff
     */
    attemptReconnect() {
        if (!this.enabled) {
            return;
        }

        if (this.reconnectAttempts >= this.config.maxReconnectAttempts) {
            console.warn('WebSocketManager: Max reconnect attempts reached, falling back to polling');
            this.fallbackToPolling();
            return;
        }

        // Clear existing timeout
        if (this.reconnectTimeout) {
            clearTimeout(this.reconnectTimeout);
        }

        // Calculate backoff delay
        const intervalIndex = Math.min(this.reconnectAttempts, this.config.reconnectIntervals.length - 1);
        const delay = this.config.reconnectIntervals[intervalIndex];

        this.updateState('reconnecting');
        this.reconnectAttempts++;

        console.log(`WebSocketManager: Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);

        this.reconnectTimeout = setTimeout(() => {
            if (this.pusher && this.pusher.connection.state === 'disconnected') {
                console.log('WebSocketManager: Forcing reconnect...');
                this.pusher.connect();
            }
        }, delay);
    }

    /**
     * Fallback to polling mode
     */
    fallbackToPolling() {
        this.updateState('polling');
        this.enabled = false;

        // Disconnect Pusher if connected
        if (this.pusher) {
            this.pusher.disconnect();
        }

        console.warn('WebSocketManager: Switched to polling mode');

        // Start adaptive polling
        this.startAdaptivePolling();
    }

    /**
     * Start adaptive polling (smart intervals based on activity)
     */
    startAdaptivePolling() {
        // Polling intervals: 5s (active), 15s (idle), 30s (very idle), 60s (dormant)
        this.pollingIntervals = {
            active: 5000,      // User is actively using the app
            idle: 15000,       // No activity for 2 minutes
            veryIdle: 30000,   // No activity for 10 minutes
            dormant: 60000     // No activity for 30 minutes
        };

        this.currentPollingInterval = this.pollingIntervals.active;
        this.lastActivityTime = Date.now();
        this.pollingTimer = null;

        // Track user activity
        this.setupActivityTracking();

        // Start polling
        this.pollForMessages();
    }

    /**
     * Setup activity tracking to adjust polling intervals
     */
    setupActivityTracking() {
        const activityEvents = ['mousedown', 'keydown', 'scroll', 'touchstart'];
        const updateActivity = () => {
            this.lastActivityTime = Date.now();
            this.adjustPollingInterval();
        };

        activityEvents.forEach(event => {
            document.addEventListener(event, updateActivity, { passive: true });
        });
    }

    /**
     * Adjust polling interval based on activity
     */
    adjustPollingInterval() {
        const idleTime = Date.now() - this.lastActivityTime;
        let newInterval;

        if (idleTime < 120000) { // < 2 minutes
            newInterval = this.pollingIntervals.active;
        } else if (idleTime < 600000) { // < 10 minutes
            newInterval = this.pollingIntervals.idle;
        } else if (idleTime < 1800000) { // < 30 minutes
            newInterval = this.pollingIntervals.veryIdle;
        } else {
            newInterval = this.pollingIntervals.dormant;
        }

        // Only restart polling if interval changed
        if (newInterval !== this.currentPollingInterval) {
            console.log(`WebSocketManager: Adjusting polling interval from ${this.currentPollingInterval}ms to ${newInterval}ms`);
            this.currentPollingInterval = newInterval;

            // Restart polling with new interval
            if (this.pollingTimer) {
                clearTimeout(this.pollingTimer);
                this.pollForMessages();
            }
        }
    }

    /**
     * Poll for new messages
     */
    pollForMessages() {
        if (this.state !== 'polling') {
            return; // Stop polling if state changed
        }

        // Call the check endpoint (admin or vendor)
        const checkEndpoint = window.location.pathname.includes('/vendor/')
            ? '/vendor/message/check'
            : '/admin/message/check';

        fetch(checkEndpoint, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.new_messages > 0) {
                // Trigger new message event
                this.config.onMessage('new-message', {
                    count: data.new_messages,
                    messages: data.messages
                });

                // If messages received, speed up polling temporarily
                this.currentPollingInterval = this.pollingIntervals.active;
            }
        })
        .catch(error => {
            console.error('WebSocketManager: Polling failed', error);
        })
        .finally(() => {
            // Schedule next poll
            this.pollingTimer = setTimeout(() => {
                this.pollForMessages();
            }, this.currentPollingInterval);
        });
    }

    /**
     * Stop polling
     */
    stopPolling() {
        if (this.pollingTimer) {
            clearTimeout(this.pollingTimer);
            this.pollingTimer = null;
        }
    }

    /**
     * Start heartbeat to keep connection alive
     */
    startHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
        }

        this.heartbeatInterval = setInterval(() => {
            if (this.state === 'connected') {
                // Send heartbeat to presence endpoint
                fetch('/admin/message/presence/heartbeat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                }).catch(error => {
                    console.warn('WebSocketManager: Heartbeat failed', error);
                });
            }
        }, this.config.heartbeatInterval);
    }

    /**
     * Subscribe to a channel
     */
    subscribe(channelName, eventHandlers = {}) {
        if (!this.pusher) {
            console.warn('WebSocketManager: Cannot subscribe, pusher not initialized');
            return null;
        }

        if (this.subscribedChannels.has(channelName)) {
            console.log(`WebSocketManager: Already subscribed to ${channelName}`);
            return this.subscribedChannels.get(channelName);
        }

        try {
            const channel = this.pusher.subscribe(channelName);

            // Bind event handlers
            Object.entries(eventHandlers).forEach(([event, handler]) => {
                channel.bind(event, (data) => {
                    console.log(`WebSocketManager: Received event ${event} on ${channelName}`, data);
                    handler(data);
                    this.config.onMessage(event, data);
                });
            });

            this.subscribedChannels.set(channelName, channel);
            console.log(`WebSocketManager: Subscribed to ${channelName}`);

            return channel;

        } catch (error) {
            console.error(`WebSocketManager: Failed to subscribe to ${channelName}`, error);
            this.config.onError(error);
            return null;
        }
    }

    /**
     * Unsubscribe from a channel
     */
    unsubscribe(channelName) {
        if (!this.pusher) {
            return;
        }

        if (this.subscribedChannels.has(channelName)) {
            this.pusher.unsubscribe(channelName);
            this.subscribedChannels.delete(channelName);
            console.log(`WebSocketManager: Unsubscribed from ${channelName}`);
        }
    }

    /**
     * Get current connection state
     */
    getState() {
        return {
            state: this.state,
            pusherState: this.pusher?.connection?.state,
            reconnectAttempts: this.reconnectAttempts,
            subscribedChannels: Array.from(this.subscribedChannels.keys())
        };
    }

    /**
     * Check if WebSocket is connected
     */
    isConnected() {
        return this.state === 'connected' && this.pusher?.connection?.state === 'connected';
    }

    /**
     * Force reconnect
     */
    reconnect() {
        if (this.pusher) {
            console.log('WebSocketManager: Forcing manual reconnect');
            this.reconnectAttempts = 0;
            this.pusher.connect();
        }
    }

    /**
     * Disconnect and cleanup
     */
    disconnect() {
        this.enabled = false;

        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
        }

        if (this.reconnectTimeout) {
            clearTimeout(this.reconnectTimeout);
        }

        if (this.pusher) {
            this.subscribedChannels.forEach((channel, channelName) => {
                this.unsubscribe(channelName);
            });

            this.pusher.disconnect();
        }

        this.updateState('disconnected');
        console.log('WebSocketManager: Disconnected and cleaned up');
    }
}

// Export for use in other modules
window.WebSocketManager = WebSocketManager;
