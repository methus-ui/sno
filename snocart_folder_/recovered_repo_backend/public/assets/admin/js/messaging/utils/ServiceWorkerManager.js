/**
 * ServiceWorkerManager
 *
 * Manages service worker registration, updates, and lifecycle.
 * Provides offline support and caching for messaging system.
 *
 * Usage:
 * const swManager = new ServiceWorkerManager();
 * await swManager.register();
 *
 * Phase 6: Performance Optimization
 */
class ServiceWorkerManager {
    constructor(options = {}) {
        this.options = {
            swPath: options.swPath || '/service-worker.js',
            scope: options.scope || '/',
            updateInterval: options.updateInterval || 3600000, // 1 hour
            onUpdate: options.onUpdate || null,
            onOffline: options.onOffline || null,
            onOnline: options.onOnline || null
        };

        this.registration = null;
        this.updateCheckInterval = null;
    }

    /**
     * Check if service workers are supported
     */
    isSupported() {
        return 'serviceWorker' in navigator;
    }

    /**
     * Register service worker
     */
    async register() {
        if (!this.isSupported()) {
            console.warn('ServiceWorkerManager: Service workers not supported');
            return null;
        }

        try {
            this.registration = await navigator.serviceWorker.register(
                this.options.swPath,
                { scope: this.options.scope }
            );

            console.log('ServiceWorkerManager: Registered successfully', this.registration);

            // Set up update checking
            this.setupUpdateChecking();

            // Listen for updates
            this.registration.addEventListener('updatefound', () => {
                this.handleUpdate();
            });

            // Listen for controller change
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('ServiceWorkerManager: Controller changed, reloading page');
                window.location.reload();
            });

            return this.registration;
        } catch (error) {
            console.error('ServiceWorkerManager: Registration failed', error);
            return null;
        }
    }

    /**
     * Setup automatic update checking
     */
    setupUpdateChecking() {
        // Check for updates periodically
        this.updateCheckInterval = setInterval(async () => {
            if (this.registration) {
                await this.registration.update();
            }
        }, this.options.updateInterval);

        // Also check when page becomes visible
        document.addEventListener('visibilitychange', async () => {
            if (!document.hidden && this.registration) {
                await this.registration.update();
            }
        });
    }

    /**
     * Handle service worker update
     */
    handleUpdate() {
        const newWorker = this.registration.installing;

        if (!newWorker) {
            return;
        }

        console.log('ServiceWorkerManager: New version available');

        newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                // New version installed and old version is controlling

                if (this.options.onUpdate) {
                    this.options.onUpdate(newWorker);
                } else {
                    // Default: show update notification
                    this.showUpdateNotification(newWorker);
                }
            }
        });
    }

    /**
     * Show update notification to user
     */
    showUpdateNotification(newWorker) {
        const message = 'A new version is available. Click to update.';

        if (confirm(message)) {
            // Tell service worker to skip waiting
            newWorker.postMessage({ type: 'skip-waiting' });
        }
    }

    /**
     * Unregister service worker
     */
    async unregister() {
        if (!this.registration) {
            return false;
        }

        try {
            const result = await this.registration.unregister();
            console.log('ServiceWorkerManager: Unregistered', result);

            // Clear update check interval
            if (this.updateCheckInterval) {
                clearInterval(this.updateCheckInterval);
            }

            return result;
        } catch (error) {
            console.error('ServiceWorkerManager: Unregister failed', error);
            return false;
        }
    }

    /**
     * Clear all caches
     */
    async clearCaches() {
        if (!this.registration) {
            return false;
        }

        try {
            // Send message to service worker
            this.registration.active.postMessage({ type: 'clear-cache' });

            console.log('ServiceWorkerManager: Caches cleared');
            return true;
        } catch (error) {
            console.error('ServiceWorkerManager: Failed to clear caches', error);
            return false;
        }
    }

    /**
     * Get cache size estimate
     */
    async getCacheSize() {
        if (!('storage' in navigator) || !('estimate' in navigator.storage)) {
            return null;
        }

        try {
            const estimate = await navigator.storage.estimate();
            return {
                usage: this.formatBytes(estimate.usage || 0),
                quota: this.formatBytes(estimate.quota || 0),
                percentage: estimate.quota > 0
                    ? ((estimate.usage / estimate.quota) * 100).toFixed(2) + '%'
                    : '0%',
                usageBytes: estimate.usage,
                quotaBytes: estimate.quota
            };
        } catch (error) {
            console.error('ServiceWorkerManager: Failed to get cache size', error);
            return null;
        }
    }

    /**
     * Format bytes to human readable
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Send message to service worker
     */
    async sendMessage(message) {
        if (!this.registration || !this.registration.active) {
            console.warn('ServiceWorkerManager: No active service worker');
            return false;
        }

        try {
            this.registration.active.postMessage(message);
            return true;
        } catch (error) {
            console.error('ServiceWorkerManager: Failed to send message', error);
            return false;
        }
    }

    /**
     * Request push notification permission
     */
    async requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.warn('ServiceWorkerManager: Notifications not supported');
            return 'denied';
        }

        try {
            const permission = await Notification.requestPermission();
            console.log('ServiceWorkerManager: Notification permission', permission);
            return permission;
        } catch (error) {
            console.error('ServiceWorkerManager: Failed to request permission', error);
            return 'denied';
        }
    }

    /**
     * Subscribe to push notifications
     */
    async subscribeToPush() {
        if (!this.registration) {
            console.warn('ServiceWorkerManager: Service worker not registered');
            return null;
        }

        try {
            const subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                // Add your VAPID public key here
                // applicationServerKey: urlBase64ToUint8Array('YOUR_PUBLIC_KEY')
            });

            console.log('ServiceWorkerManager: Push subscription', subscription);
            return subscription;
        } catch (error) {
            console.error('ServiceWorkerManager: Push subscription failed', error);
            return null;
        }
    }

    /**
     * Get current service worker state
     */
    getState() {
        if (!this.registration) {
            return 'not_registered';
        }

        if (this.registration.active) {
            return 'active';
        } else if (this.registration.installing) {
            return 'installing';
        } else if (this.registration.waiting) {
            return 'waiting';
        }

        return 'unknown';
    }
}

// Export singleton instance
window.serviceWorkerManager = new ServiceWorkerManager();

// Auto-register if enabled
if (typeof ENABLE_SERVICE_WORKER !== 'undefined' && ENABLE_SERVICE_WORKER) {
    window.addEventListener('load', async () => {
        await window.serviceWorkerManager.register();
    });
}
