/**
 * NotificationManager
 *
 * Utility class for managing browser notifications, sounds, and visual alerts.
 * Handles permission requests, notification display, and sound playback.
 *
 * @class NotificationManager
 */
class NotificationManager {
    constructor(options = {}) {
        this.options = {
            enableBrowserNotifications: options.enableBrowserNotifications !== false,
            enableSounds: options.enableSounds !== false,
            enableVisualAlerts: options.enableVisualAlerts !== false,
            soundPath: options.soundPath || '/assets/admin/sound/notification.mp3',
            badgeIcon: options.badgeIcon || '/assets/admin/img/favicon.png',
            autoRequestPermission: options.autoRequestPermission !== false
        };

        this.permission = 'default';
        this.soundEnabled = true;
        this.notificationsEnabled = true;
        this.audioContext = null;
        this.audioBuffer = null;

        // Initialize
        this._init();
    }

    /**
     * Initialize notification manager
     */
    _init() {
        // Check if browser supports notifications
        if (!('Notification' in window)) {
            console.warn('NotificationManager: Browser does not support notifications');
            this.options.enableBrowserNotifications = false;
        } else {
            this.permission = Notification.permission;
        }

        // Load notification sound
        if (this.options.enableSounds) {
            this._loadSound();
        }

        // Auto-request permission if enabled
        if (this.options.autoRequestPermission && this.permission === 'default') {
            // Don't auto-request on page load, wait for user interaction
            // Will be requested on first notification attempt
        }

        // Load preferences from localStorage
        this._loadPreferences();

        console.log('NotificationManager: Initialized', {
            permission: this.permission,
            soundEnabled: this.soundEnabled,
            notificationsEnabled: this.notificationsEnabled
        });
    }

    /**
     * Load user preferences from localStorage
     */
    _loadPreferences() {
        try {
            const soundPref = localStorage.getItem('messaging_sound_enabled');
            if (soundPref !== null) {
                this.soundEnabled = soundPref === 'true';
            }

            const notifPref = localStorage.getItem('messaging_notifications_enabled');
            if (notifPref !== null) {
                this.notificationsEnabled = notifPref === 'true';
            }
        } catch (error) {
            console.error('NotificationManager: Failed to load preferences', error);
        }
    }

    /**
     * Save user preferences to localStorage
     */
    _savePreferences() {
        try {
            localStorage.setItem('messaging_sound_enabled', this.soundEnabled.toString());
            localStorage.setItem('messaging_notifications_enabled', this.notificationsEnabled.toString());
        } catch (error) {
            console.error('NotificationManager: Failed to save preferences', error);
        }
    }

    /**
     * Load notification sound
     */
    async _loadSound() {
        try {
            // Create audio context
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioContext = new AudioContext();

            // Load sound file
            const response = await fetch(this.options.soundPath);
            const arrayBuffer = await response.arrayBuffer();
            this.audioBuffer = await this.audioContext.decodeAudioData(arrayBuffer);

            console.log('NotificationManager: Sound loaded successfully');
        } catch (error) {
            console.error('NotificationManager: Failed to load sound', error);
            this.options.enableSounds = false;
        }
    }

    /**
     * Request notification permission
     *
     * @returns {Promise<string>} Permission status ('granted', 'denied', or 'default')
     */
    async requestPermission() {
        if (!this.options.enableBrowserNotifications) {
            return 'denied';
        }

        if (this.permission === 'granted') {
            return 'granted';
        }

        try {
            this.permission = await Notification.requestPermission();
            console.log('NotificationManager: Permission', this.permission);
            return this.permission;
        } catch (error) {
            console.error('NotificationManager: Failed to request permission', error);
            return 'denied';
        }
    }

    /**
     * Show browser notification
     *
     * @param {string} title - Notification title
     * @param {Object} options - Notification options
     * @returns {Promise<Notification|null>}
     */
    async showNotification(title, options = {}) {
        if (!this.notificationsEnabled || !this.options.enableBrowserNotifications) {
            return null;
        }

        // Request permission if needed
        if (this.permission === 'default') {
            await this.requestPermission();
        }

        if (this.permission !== 'granted') {
            console.warn('NotificationManager: Permission not granted');
            return null;
        }

        // Don't show notification if tab is focused
        if (!document.hidden && options.onlyWhenHidden !== false) {
            return null;
        }

        try {
            const notification = new Notification(title, {
                icon: options.icon || this.options.badgeIcon,
                badge: options.badge || this.options.badgeIcon,
                body: options.body || '',
                tag: options.tag || 'messaging',
                requireInteraction: options.requireInteraction || false,
                silent: options.silent !== false, // Don't use browser sound (we use custom)
                data: options.data || {}
            });

            // Handle notification click
            notification.onclick = () => {
                window.focus();
                if (options.onClick) {
                    options.onClick(notification);
                }
                notification.close();
            };

            // Auto-close after 5 seconds (unless requireInteraction is true)
            if (!options.requireInteraction) {
                setTimeout(() => {
                    notification.close();
                }, 5000);
            }

            console.log('NotificationManager: Notification shown', title);
            return notification;

        } catch (error) {
            console.error('NotificationManager: Failed to show notification', error);
            return null;
        }
    }

    /**
     * Play notification sound
     *
     * @returns {Promise<void>}
     */
    async playSound() {
        if (!this.soundEnabled || !this.options.enableSounds || !this.audioBuffer) {
            return;
        }

        try {
            // Resume audio context if suspended (required by browsers)
            if (this.audioContext.state === 'suspended') {
                await this.audioContext.resume();
            }

            // Create source and play
            const source = this.audioContext.createBufferSource();
            source.buffer = this.audioBuffer;
            source.connect(this.audioContext.destination);
            source.start(0);

            console.log('NotificationManager: Sound played');
        } catch (error) {
            console.error('NotificationManager: Failed to play sound', error);
        }
    }

    /**
     * Show visual alert (title flash)
     *
     * @param {string} message - Message to flash in title
     * @param {number} count - Number of flashes (default: 3)
     */
    showVisualAlert(message, count = 3) {
        if (!this.options.enableVisualAlerts) {
            return;
        }

        const originalTitle = document.title;
        let flashCount = 0;
        let isOriginal = true;

        const interval = setInterval(() => {
            document.title = isOriginal ? message : originalTitle;
            isOriginal = !isOriginal;
            flashCount++;

            if (flashCount >= count * 2) {
                clearInterval(interval);
                document.title = originalTitle;
            }
        }, 1000);
    }

    /**
     * Show new message notification
     *
     * @param {Object} message - Message object
     * @param {Object} sender - Sender object
     * @returns {Promise<void>}
     */
    async notifyNewMessage(message, sender) {
        // Play sound
        await this.playSound();

        // Show browser notification
        await this.showNotification(`New message from ${sender.name}`, {
            body: message.message || 'Sent an attachment',
            tag: `message-${message.id}`,
            data: {
                messageId: message.id,
                conversationId: message.conversation_id
            },
            onClick: (notification) => {
                // Navigate to conversation
                const conversationId = notification.data.conversationId;
                if (conversationId) {
                    window.location.href = `/admin/message/view/${conversationId}`;
                }
            }
        });

        // Show visual alert
        this.showVisualAlert(`💬 ${sender.name}`);
    }

    /**
     * Enable sounds
     */
    enableSounds() {
        this.soundEnabled = true;
        this._savePreferences();
        console.log('NotificationManager: Sounds enabled');
    }

    /**
     * Disable sounds
     */
    disableSounds() {
        this.soundEnabled = false;
        this._savePreferences();
        console.log('NotificationManager: Sounds disabled');
    }

    /**
     * Toggle sounds
     *
     * @returns {boolean} New state
     */
    toggleSounds() {
        this.soundEnabled = !this.soundEnabled;
        this._savePreferences();
        console.log('NotificationManager: Sounds', this.soundEnabled ? 'enabled' : 'disabled');
        return this.soundEnabled;
    }

    /**
     * Enable notifications
     */
    enableNotifications() {
        this.notificationsEnabled = true;
        this._savePreferences();
        console.log('NotificationManager: Notifications enabled');
    }

    /**
     * Disable notifications
     */
    disableNotifications() {
        this.notificationsEnabled = false;
        this._savePreferences();
        console.log('NotificationManager: Notifications disabled');
    }

    /**
     * Toggle notifications
     *
     * @returns {boolean} New state
     */
    toggleNotifications() {
        this.notificationsEnabled = !this.notificationsEnabled;
        this._savePreferences();
        console.log('NotificationManager: Notifications', this.notificationsEnabled ? 'enabled' : 'disabled');
        return this.notificationsEnabled;
    }

    /**
     * Check if notifications are supported
     *
     * @returns {boolean}
     */
    isSupported() {
        return 'Notification' in window;
    }

    /**
     * Check if notifications are enabled
     *
     * @returns {boolean}
     */
    isEnabled() {
        return this.notificationsEnabled && this.permission === 'granted';
    }

    /**
     * Get current permission status
     *
     * @returns {string}
     */
    getPermission() {
        return this.permission;
    }

    /**
     * Check if sounds are enabled
     *
     * @returns {boolean}
     */
    areSoundsEnabled() {
        return this.soundEnabled;
    }

    /**
     * Update badge count (for PWA)
     *
     * @param {number} count - Unread count
     */
    updateBadge(count) {
        if ('setAppBadge' in navigator) {
            if (count > 0) {
                navigator.setAppBadge(count).catch(error => {
                    console.error('NotificationManager: Failed to set badge', error);
                });
            } else {
                navigator.clearAppBadge().catch(error => {
                    console.error('NotificationManager: Failed to clear badge', error);
                });
            }
        }
    }

    /**
     * Clear all notifications
     */
    clearAll() {
        // Note: Cannot programmatically clear notifications in most browsers
        // This is a browser security feature
        console.log('NotificationManager: Clear all called (browser may not support)');
    }
}

// Export as singleton
window.notificationManager = new NotificationManager();

// Usage examples (for documentation):
/*
// Example 1: Request permission
await notificationManager.requestPermission();

// Example 2: Show notification
await notificationManager.showNotification('Hello', {
    body: 'This is a test notification',
    onClick: () => {
        console.log('Notification clicked');
    }
});

// Example 3: Notify new message
await notificationManager.notifyNewMessage(
    { id: 1, message: 'Hello there!', conversation_id: 5 },
    { name: 'John Doe' }
);

// Example 4: Play sound only
await notificationManager.playSound();

// Example 5: Toggle sounds
const soundsEnabled = notificationManager.toggleSounds();
console.log('Sounds:', soundsEnabled ? 'ON' : 'OFF');

// Example 6: Update badge count
notificationManager.updateBadge(5); // Shows "5" on app icon
*/
