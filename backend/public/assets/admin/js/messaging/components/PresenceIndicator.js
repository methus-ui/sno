/**
 * PresenceIndicator Component (Alpine.js)
 *
 * Shows online/offline status with green/gray dot.
 * Updates in real-time via Pusher presence events.
 *
 * Usage:
 * <div x-data="presenceIndicator(userInfoId, userType)" x-init="init()">
 *     <span x-bind:class="statusClass" class="presence-dot"></span>
 *     <span x-text="statusText"></span>
 * </div>
 *
 * @param {number} userInfoId - The user info ID
 * @param {string} userType - User type (admin, vendor, customer, delivery_man)
 * @returns {Object} Alpine.js component
 */
function presenceIndicator(userInfoId, userType) {
    return {
        userInfoId: userInfoId,
        userType: userType,
        isOnline: false,
        lastSeen: null,
        statusClass: 'presence-offline',
        statusText: 'Offline',
        checkInterval: null,

        /**
         * Initialize component
         */
        init() {
            console.log('PresenceIndicator: Initialized for user', this.userInfoId, this.userType);

            // Initial status check
            this.checkPresenceStatus();

            // Subscribe to presence events
            this.subscribeToPresenceEvents();

            // Periodic status check (every 60 seconds)
            this.checkInterval = setInterval(() => {
                this.checkPresenceStatus();
            }, 60000);

            // Cleanup on destroy
            this.$el.addEventListener('alpine:cleanup', () => {
                this.cleanup();
            });
        },

        /**
         * Subscribe to presence events via Pusher
         */
        subscribeToPresenceEvents() {
            if (!window.messagingWebSocket) {
                console.warn('PresenceIndicator: WebSocket manager not available');
                return;
            }

            const channelName = `private-${this.userType}-${this.userInfoId}`;

            window.messagingWebSocket.subscribe(channelName, {
                'presence': (data) => {
                    console.log('PresenceIndicator: Received presence event', data);

                    if (data.user_info_id === this.userInfoId) {
                        this.updatePresence(data.status === 'online', data.last_seen);
                    }
                }
            });
        },

        /**
         * Check presence status via API
         */
        async checkPresenceStatus() {
            try {
                const response = await fetch('/admin/message/presence/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({
                        users: [{
                            user_info_id: this.userInfoId,
                            user_type: this.userType
                        }]
                    })
                });

                if (response.ok) {
                    const data = await response.json();

                    if (data.success && data.users && data.users.length > 0) {
                        const userPresence = data.users[0];
                        this.updatePresence(userPresence.is_online, userPresence.last_seen);
                    }
                }

            } catch (error) {
                console.error('PresenceIndicator: Failed to check presence status', error);
            }
        },

        /**
         * Update presence status
         */
        updatePresence(isOnline, lastSeen) {
            this.isOnline = isOnline;
            this.lastSeen = lastSeen;

            if (isOnline) {
                this.statusClass = 'presence-online';
                this.statusText = 'Online';
            } else {
                this.statusClass = 'presence-offline';
                this.statusText = this.formatLastSeen(lastSeen);
            }
        },

        /**
         * Format last seen timestamp
         */
        formatLastSeen(lastSeen) {
            if (!lastSeen) {
                return 'Offline';
            }

            const now = new Date();
            const lastSeenDate = new Date(lastSeen);
            const diffInSeconds = Math.floor((now - lastSeenDate) / 1000);

            if (diffInSeconds < 60) {
                return 'Just now';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return `${minutes} min ago`;
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600);
                return `${hours}h ago`;
            } else if (diffInSeconds < 604800) {
                const days = Math.floor(diffInSeconds / 86400);
                return `${days}d ago`;
            } else {
                return lastSeenDate.toLocaleDateString();
            }
        },

        /**
         * Get presence dot color class
         */
        get dotClass() {
            return this.isOnline ? 'bg-green-500' : 'bg-gray-400';
        },

        /**
         * Get full status with tooltip
         */
        get fullStatus() {
            if (this.isOnline) {
                return 'Online now';
            } else if (this.lastSeen) {
                return `Last seen ${this.formatLastSeen(this.lastSeen)}`;
            } else {
                return 'Offline';
            }
        },

        /**
         * Cleanup on component destroy
         */
        cleanup() {
            if (this.checkInterval) {
                clearInterval(this.checkInterval);
            }

            console.log('PresenceIndicator: Cleaned up');
        }
    };
}

// Make available globally for Alpine.js
window.presenceIndicator = presenceIndicator;

// Inject CSS styles for presence indicators
(function injectPresenceStyles() {
    if (document.getElementById('presence-indicator-styles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'presence-indicator-styles';
    style.textContent = `
        .presence-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .presence-online {
            background-color: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }

        .presence-offline {
            background-color: #9ca3af;
        }

        .presence-indicator-wrapper {
            display: inline-flex;
            align-items: center;
            font-size: 13px;
        }

        .presence-status-text {
            color: #6b7280;
            font-size: 12px;
        }

        /* Pulse animation for online status */
        @keyframes presence-pulse {
            0%, 100% {
                box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            }
            50% {
                box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
            }
        }

        .presence-online {
            animation: presence-pulse 2s infinite;
        }
    `;

    document.head.appendChild(style);
})();
