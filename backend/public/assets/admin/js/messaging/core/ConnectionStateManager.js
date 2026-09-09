/**
 * ConnectionStateManager
 *
 * Manages connection state UI indicator and provides visual feedback
 * about the current connection status (connected, reconnecting, offline).
 *
 * Features:
 * - Color-coded status indicator (green, yellow, red)
 * - Tooltip with detailed status information
 * - Automatic UI updates based on connection state
 * - Network status detection (online/offline events)
 *
 * @class ConnectionStateManager
 */
class ConnectionStateManager {
    constructor(config = {}) {
        this.config = {
            containerId: config.containerId || 'connection-status',
            autoCreate: config.autoCreate !== false,
            showTooltip: config.showTooltip !== false,
            onStateChange: config.onStateChange || (() => {})
        };

        this.state = 'disconnected';
        this.container = null;
        this.statusDot = null;
        this.statusText = null;
        this.tooltip = null;

        this.initialize();
    }

    /**
     * Initialize connection state manager
     */
    initialize() {
        // Find existing container or create new one
        this.container = document.getElementById(this.config.containerId);

        if (!this.container && this.config.autoCreate) {
            this.createUI();
        }

        if (this.container) {
            this.statusDot = this.container.querySelector('.connection-status-dot');
            this.statusText = this.container.querySelector('.connection-status-text');
            this.tooltip = this.container.querySelector('.connection-status-tooltip');
        }

        // Listen to browser online/offline events
        window.addEventListener('online', () => this.handleNetworkChange(true));
        window.addEventListener('offline', () => this.handleNetworkChange(false));

        console.log('ConnectionStateManager: Initialized');
    }

    /**
     * Create UI elements if auto-create is enabled
     */
    createUI() {
        const container = document.createElement('div');
        container.id = this.config.containerId;
        container.className = 'connection-status-container';
        container.innerHTML = `
            <div class="connection-status-wrapper">
                <span class="connection-status-dot"></span>
                <span class="connection-status-text">Connecting...</span>
                ${this.config.showTooltip ? '<div class="connection-status-tooltip"></div>' : ''}
            </div>
        `;

        // Append to top-right corner of page (or messaging container)
        const messagingContainer = document.querySelector('.messaging-container') || document.body;
        messagingContainer.appendChild(container);

        this.container = container;

        // Add CSS styles
        this.injectStyles();

        console.log('ConnectionStateManager: UI created');
    }

    /**
     * Inject CSS styles for connection status
     */
    injectStyles() {
        if (document.getElementById('connection-status-styles')) {
            return; // Already injected
        }

        const style = document.createElement('style');
        style.id = 'connection-status-styles';
        style.textContent = `
            .connection-status-container {
                position: fixed;
                top: 70px;
                right: 20px;
                z-index: 9999;
            }

            .connection-status-wrapper {
                display: flex;
                align-items: center;
                background: white;
                padding: 8px 12px;
                border-radius: 20px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                cursor: pointer;
                position: relative;
            }

            .connection-status-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                margin-right: 8px;
                animation: pulse 2s infinite;
            }

            .connection-status-dot.connected {
                background-color: #10b981;
                animation: none;
            }

            .connection-status-dot.reconnecting {
                background-color: #f59e0b;
            }

            .connection-status-dot.disconnected {
                background-color: #ef4444;
            }

            .connection-status-dot.polling {
                background-color: #6b7280;
                animation: none;
            }

            .connection-status-text {
                font-size: 13px;
                font-weight: 500;
                color: #374151;
            }

            .connection-status-tooltip {
                position: absolute;
                bottom: -60px;
                right: 0;
                background: #1f2937;
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                white-space: nowrap;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            }

            .connection-status-wrapper:hover .connection-status-tooltip {
                opacity: 1;
            }

            .connection-status-tooltip::before {
                content: '';
                position: absolute;
                top: -6px;
                right: 10px;
                width: 0;
                height: 0;
                border-left: 6px solid transparent;
                border-right: 6px solid transparent;
                border-bottom: 6px solid #1f2937;
            }

            @keyframes pulse {
                0%, 100% {
                    opacity: 1;
                }
                50% {
                    opacity: 0.5;
                }
            }
        `;

        document.head.appendChild(style);
    }

    /**
     * Update connection state
     *
     * @param {string} state - New state (connected, reconnecting, disconnected, polling)
     * @param {Object} metadata - Additional state information
     */
    updateState(state, metadata = {}) {
        if (this.state === state) {
            return; // No change
        }

        console.log(`ConnectionStateManager: State changed to ${state}`, metadata);

        this.state = state;

        // Update UI
        if (this.statusDot) {
            // Remove all state classes
            this.statusDot.classList.remove('connected', 'reconnecting', 'disconnected', 'polling');
            // Add current state class
            this.statusDot.classList.add(state);
        }

        // Update status text
        if (this.statusText) {
            const statusTexts = {
                connected: 'Connected',
                reconnecting: `Reconnecting${metadata.reconnectAttempts ? ' (' + metadata.reconnectAttempts + ')' : ''}`,
                disconnected: 'Disconnected',
                polling: 'Polling Mode'
            };

            this.statusText.textContent = statusTexts[state] || 'Unknown';
        }

        // Update tooltip
        if (this.tooltip && this.config.showTooltip) {
            const tooltipTexts = {
                connected: '✓ Real-time connection active',
                reconnecting: '⟳ Attempting to reconnect...',
                disconnected: '✗ Connection lost - messages will be queued',
                polling: '⟲ Using fallback polling mode'
            };

            this.tooltip.textContent = tooltipTexts[state] || '';
        }

        // Notify listeners
        this.config.onStateChange({
            state: state,
            metadata: metadata,
            timestamp: new Date().toISOString()
        });
    }

    /**
     * Handle browser network status change
     *
     * @param {boolean} isOnline - Whether browser is online
     */
    handleNetworkChange(isOnline) {
        console.log(`ConnectionStateManager: Network ${isOnline ? 'online' : 'offline'}`);

        if (!isOnline) {
            this.updateState('disconnected', { reason: 'network_offline' });
        } else {
            // Network back online - will trigger reconnection in WebSocketManager
            console.log('ConnectionStateManager: Network back online, reconnection will be attempted');
        }
    }

    /**
     * Show temporary notification
     *
     * @param {string} message - Notification message
     * @param {string} type - Notification type (success, error, warning, info)
     * @param {number} duration - Duration in milliseconds
     */
    showNotification(message, type = 'info', duration = 3000) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `connection-notification connection-notification-${type}`;
        notification.textContent = message;

        // Add styles if not already added
        this.injectNotificationStyles();

        // Append to container
        if (this.container) {
            this.container.appendChild(notification);

            // Fade in
            setTimeout(() => notification.classList.add('show'), 10);

            // Auto remove
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }
    }

    /**
     * Inject notification styles
     */
    injectNotificationStyles() {
        if (document.getElementById('connection-notification-styles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'connection-notification-styles';
        style.textContent = `
            .connection-notification {
                position: fixed;
                top: 120px;
                right: 20px;
                background: white;
                padding: 12px 16px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                font-size: 14px;
                max-width: 300px;
                opacity: 0;
                transform: translateX(20px);
                transition: all 0.3s ease;
                z-index: 9998;
            }

            .connection-notification.show {
                opacity: 1;
                transform: translateX(0);
            }

            .connection-notification-success {
                border-left: 4px solid #10b981;
                color: #065f46;
            }

            .connection-notification-error {
                border-left: 4px solid #ef4444;
                color: #991b1b;
            }

            .connection-notification-warning {
                border-left: 4px solid #f59e0b;
                color: #92400e;
            }

            .connection-notification-info {
                border-left: 4px solid #3b82f6;
                color: #1e40af;
            }
        `;

        document.head.appendChild(style);
    }

    /**
     * Get current state
     *
     * @returns {string} Current connection state
     */
    getState() {
        return this.state;
    }

    /**
     * Check if connected
     *
     * @returns {boolean} True if connected
     */
    isConnected() {
        return this.state === 'connected';
    }

    /**
     * Destroy connection state manager
     */
    destroy() {
        window.removeEventListener('online', this.handleNetworkChange);
        window.removeEventListener('offline', this.handleNetworkChange);

        if (this.container && this.config.autoCreate) {
            this.container.remove();
        }

        console.log('ConnectionStateManager: Destroyed');
    }
}

// Export for use in other modules
window.ConnectionStateManager = ConnectionStateManager;
