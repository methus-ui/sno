/**
 * PresenceService
 *
 * Service layer for presence-related API calls.
 * Handles online/offline status, heartbeat, and last seen tracking.
 *
 * @class PresenceService
 */
class PresenceService {
    constructor() {
        this.baseUrl = '/admin/message/presence';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.heartbeatInterval = null;
        this.heartbeatFrequency = 30000; // 30 seconds
    }

    /**
     * Send heartbeat to mark user as online
     *
     * @returns {Promise<Object>}
     */
    async heartbeat() {
        const response = await fetch(`${this.baseUrl}/heartbeat`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            }
        });

        return this.handleResponse(response);
    }

    /**
     * Get presence status for multiple users
     *
     * @param {Array<number>} userInfoIds - Array of user info IDs
     * @returns {Promise<Object>}
     */
    async getStatus(userInfoIds) {
        const response = await fetch(`${this.baseUrl}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                user_info_ids: userInfoIds
            })
        });

        return this.handleResponse(response);
    }

    /**
     * Start automatic heartbeat (keep-alive)
     *
     * Sends heartbeat every 30 seconds to maintain online status
     */
    startHeartbeat() {
        // Clear existing interval if any
        this.stopHeartbeat();

        // Send initial heartbeat
        this.heartbeat().catch(error => {
            console.error('PresenceService: Initial heartbeat failed', error);
        });

        // Set up recurring heartbeat
        this.heartbeatInterval = setInterval(() => {
            this.heartbeat().catch(error => {
                console.error('PresenceService: Heartbeat failed', error);
            });
        }, this.heartbeatFrequency);

        console.log('PresenceService: Heartbeat started (every 30s)');
    }

    /**
     * Stop automatic heartbeat
     */
    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
            console.log('PresenceService: Heartbeat stopped');
        }
    }

    /**
     * Mark user as away
     *
     * @returns {Promise<Object>}
     */
    async markAway() {
        const response = await fetch(`${this.baseUrl}/away`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            }
        });

        return this.handleResponse(response);
    }

    /**
     * Mark user as offline
     *
     * @returns {Promise<Object>}
     */
    async markOffline() {
        // Stop heartbeat
        this.stopHeartbeat();

        const response = await fetch(`${this.baseUrl}/offline`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            }
        });

        return this.handleResponse(response);
    }

    /**
     * Handle API response
     *
     * @param {Response} response - Fetch response
     * @returns {Promise<Object>}
     * @throws {Error} If response is not ok
     */
    async handleResponse(response) {
        if (!response.ok) {
            const error = await response.json().catch(() => ({ message: 'Request failed' }));
            throw new Error(error.message || `HTTP ${response.status}`);
        }

        return response.json();
    }

    /**
     * Set up page visibility handlers
     *
     * Automatically marks user as away/offline when page is hidden,
     * and resumes heartbeat when page becomes visible
     */
    setupVisibilityHandlers() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Page hidden - mark as away
                this.markAway().catch(error => {
                    console.error('PresenceService: Failed to mark as away', error);
                });
            } else {
                // Page visible - resume heartbeat
                this.startHeartbeat();
            }
        });

        console.log('PresenceService: Visibility handlers registered');
    }

    /**
     * Set up beforeunload handler
     *
     * Marks user as offline when closing tab/window
     */
    setupUnloadHandler() {
        window.addEventListener('beforeunload', () => {
            // Use sendBeacon for reliable delivery during page unload
            if (navigator.sendBeacon) {
                const formData = new FormData();
                formData.append('_token', this.csrfToken);

                navigator.sendBeacon(`${this.baseUrl}/offline`, formData);
            } else {
                // Fallback to sync XHR (not recommended but works)
                try {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', `${this.baseUrl}/offline`, false); // Synchronous
                    xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
                    xhr.send();
                } catch (error) {
                    console.error('PresenceService: Failed to mark offline on unload', error);
                }
            }
        });

        console.log('PresenceService: Unload handler registered');
    }

    /**
     * Initialize presence tracking
     *
     * Starts heartbeat, sets up visibility handlers, and unload handler
     */
    initialize() {
        this.startHeartbeat();
        this.setupVisibilityHandlers();
        this.setupUnloadHandler();

        console.log('PresenceService: Initialized');
    }

    /**
     * Get formatted last seen text
     *
     * @param {string|Date} lastSeen - Last seen timestamp
     * @returns {string} Formatted text (e.g., "Just now", "5 min ago")
     */
    formatLastSeen(lastSeen) {
        if (!lastSeen) {
            return 'Never';
        }

        const date = new Date(lastSeen);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) {
            return 'Just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} min${minutes > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 604800) {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        } else {
            return date.toLocaleDateString([], {
                month: 'short',
                day: 'numeric',
                year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
            });
        }
    }

    /**
     * Check if user is online based on last seen
     *
     * User is considered online if last seen within 60 seconds
     *
     * @param {string|Date} lastSeen - Last seen timestamp
     * @returns {boolean}
     */
    isOnline(lastSeen) {
        if (!lastSeen) {
            return false;
        }

        const date = new Date(lastSeen);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        return diffInSeconds < 60;
    }

    /**
     * Get presence status color
     *
     * @param {string|Date} lastSeen - Last seen timestamp
     * @returns {string} Color code ('green', 'yellow', 'gray')
     */
    getStatusColor(lastSeen) {
        if (!lastSeen) {
            return 'gray';
        }

        const date = new Date(lastSeen);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) {
            return 'green'; // Online
        } else if (diffInSeconds < 300) {
            return 'yellow'; // Away (within 5 minutes)
        } else {
            return 'gray'; // Offline
        }
    }
}

// Export as singleton
window.presenceService = new PresenceService();
