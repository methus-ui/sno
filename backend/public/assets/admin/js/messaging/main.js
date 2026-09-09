/**
 * Messaging System - Main Entry Point
 *
 * Initializes all messaging components, services, and utilities.
 * This file should be loaded after all dependencies are available.
 *
 * Dependencies:
 * - Alpine.js
 * - Pusher (for WebSocket)
 * - All messaging modules (core, components, services, utils)
 *
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * Main Messaging Application
     */
    class MessagingApp {
        constructor() {
            this.initialized = false;
            this.config = null;
            this.services = {};
            this.managers = {};
        }

        /**
         * Initialize the messaging application
         *
         * @param {Object} config - Configuration options
         */
        async init(config = {}) {
            if (this.initialized) {
                console.warn('MessagingApp: Already initialized');
                return;
            }

            console.log('MessagingApp: Initializing...');

            // Load configuration
            this.config = this._loadConfig(config);

            // Check if revamp is enabled
            if (!this.config.revampEnabled) {
                console.log('MessagingApp: Messaging revamp is disabled');
                return;
            }

            try {
                // Initialize managers and services in order
                await this._initializeIndexedDB();
                await this._initializeNotificationManager();
                this._initializePresenceService();
                this._initializeWebSocketManager();
                this._initializeConnectionStateManager();
                this._initializeMessageQueue();
                this._registerAlpineComponents();
                this._setupEventListeners();

                this.initialized = true;
                console.log('MessagingApp: Initialization complete ✓');

                // Dispatch initialization event
                this._dispatchEvent('messaging:initialized', { app: this });

            } catch (error) {
                console.error('MessagingApp: Initialization failed', error);
                this._dispatchEvent('messaging:init-failed', { error });
            }
        }

        /**
         * Load configuration from DOM and defaults
         *
         * @param {Object} overrides - Configuration overrides
         * @returns {Object}
         */
        _loadConfig(overrides) {
            // Default configuration
            const defaults = {
                revampEnabled: true,
                pusherKey: null,
                pusherCluster: 'mt1',
                enableTypingIndicators: true,
                enablePresenceTracking: true,
                enableReactions: true,
                enableDeliveryTracking: true,
                enableOfflineQueue: true,
                enableSounds: true,
                enableBrowserNotifications: true,
                heartbeatInterval: 30000, // 30 seconds
                messageQueueProcessInterval: 5000, // 5 seconds
                reconnectAttempts: 10,
                debug: false
            };

            // Try to load from meta tags
            const metaConfig = this._loadMetaConfig();

            // Merge: defaults < meta tags < overrides
            return { ...defaults, ...metaConfig, ...overrides };
        }

        /**
         * Load configuration from meta tags
         *
         * @returns {Object}
         */
        _loadMetaConfig() {
            const config = {};

            // Pusher configuration
            const pusherKey = document.querySelector('meta[name="pusher-key"]')?.content;
            if (pusherKey) {
                config.pusherKey = pusherKey;
            }

            const pusherCluster = document.querySelector('meta[name="pusher-cluster"]')?.content;
            if (pusherCluster) {
                config.pusherCluster = pusherCluster;
            }

            // Feature flags
            const revampEnabled = document.querySelector('meta[name="messaging-revamp-enabled"]')?.content;
            if (revampEnabled !== undefined) {
                config.revampEnabled = revampEnabled === 'true' || revampEnabled === '1';
            }

            return config;
        }

        /**
         * Initialize IndexedDB
         */
        async _initializeIndexedDB() {
            if (!this.config.enableOfflineQueue) {
                return;
            }

            try {
                await window.indexedDBManager.init();
                this.managers.indexedDB = window.indexedDBManager;
                console.log('MessagingApp: IndexedDB initialized');
            } catch (error) {
                console.error('MessagingApp: IndexedDB initialization failed', error);
                // Non-critical, continue without offline support
            }
        }

        /**
         * Initialize Notification Manager
         */
        async _initializeNotificationManager() {
            try {
                // NotificationManager is already a singleton, just configure it
                this.managers.notifications = window.notificationManager;

                // Don't auto-request permission on page load
                // Will be requested on first notification or via UI toggle

                console.log('MessagingApp: Notification manager initialized');
            } catch (error) {
                console.error('MessagingApp: Notification manager initialization failed', error);
            }
        }

        /**
         * Initialize Presence Service
         */
        _initializePresenceService() {
            if (!this.config.enablePresenceTracking) {
                return;
            }

            try {
                this.services.presence = window.presenceService;

                // Start heartbeat automatically
                window.presenceService.initialize();

                console.log('MessagingApp: Presence service initialized');
            } catch (error) {
                console.error('MessagingApp: Presence service initialization failed', error);
            }
        }

        /**
         * Initialize WebSocket Manager
         */
        _initializeWebSocketManager() {
            if (!this.config.pusherKey) {
                console.warn('MessagingApp: Pusher key not configured, WebSocket disabled');
                return;
            }

            try {
                this.managers.webSocket = new WebSocketManager({
                    pusherKey: this.config.pusherKey,
                    pusherCluster: this.config.pusherCluster,
                    maxReconnectAttempts: this.config.reconnectAttempts,
                    onStateChange: (state, metadata) => {
                        this._handleWebSocketStateChange(state, metadata);
                    },
                    onMessage: (event, data) => {
                        this._handleWebSocketMessage(event, data);
                    }
                });

                // Make available globally for components
                window.messagingWebSocket = this.managers.webSocket;

                console.log('MessagingApp: WebSocket manager initialized');
            } catch (error) {
                console.error('MessagingApp: WebSocket manager initialization failed', error);
            }
        }

        /**
         * Initialize Connection State Manager
         */
        _initializeConnectionStateManager() {
            try {
                this.managers.connectionState = new ConnectionStateManager();

                // Make available globally for components
                window.connectionStateManager = this.managers.connectionState;

                console.log('MessagingApp: Connection state manager initialized');
            } catch (error) {
                console.error('MessagingApp: Connection state manager initialization failed', error);
            }
        }

        /**
         * Initialize Message Queue
         */
        _initializeMessageQueue() {
            if (!this.config.enableOfflineQueue) {
                return;
            }

            try {
                this.managers.messageQueue = new MessageQueue();

                // Process pending messages periodically
                setInterval(() => {
                    this._processPendingMessages();
                }, this.config.messageQueueProcessInterval);

                // Make available globally for components
                window.messageQueue = this.managers.messageQueue;

                console.log('MessagingApp: Message queue initialized');
            } catch (error) {
                console.error('MessagingApp: Message queue initialization failed', error);
            }
        }

        /**
         * Register Alpine.js components
         */
        _registerAlpineComponents() {
            // Components are already registered globally
            // Just verify they're available

            const components = [
                'typingIndicator',
                'presenceIndicator',
                'messageReactions',
                'messageComposer',
                'searchBar',
                'deliveryReceiptIndicator',    // Phase 5
                'scrollToBottomButton',         // Phase 5
                'unreadSeparator',              // Phase 5
                'virtualScroller',              // Phase 6
                'lazyImage'                     // Phase 6
            ];

            components.forEach(component => {
                if (typeof window[component] !== 'function') {
                    console.warn(`MessagingApp: Alpine component '${component}' not found`);
                }
            });

            console.log('MessagingApp: Alpine components verified');
        }

        /**
         * Set up event listeners
         */
        _setupEventListeners() {
            // Online/offline events
            window.addEventListener('online', () => {
                console.log('MessagingApp: Network online');
                this._processPendingMessages();

                if (this.managers.webSocket) {
                    this.managers.webSocket.attemptReconnect();
                }
            });

            window.addEventListener('offline', () => {
                console.log('MessagingApp: Network offline');
            });

            // Page visibility events (handled by PresenceService)
            // But we can also use them for other purposes

            console.log('MessagingApp: Event listeners registered');
        }

        /**
         * Handle WebSocket state changes
         *
         * @param {string} state - Connection state
         * @param {Object} metadata - Additional metadata
         */
        _handleWebSocketStateChange(state, metadata) {
            if (this.config.debug) {
                console.log('MessagingApp: WebSocket state changed', state, metadata);
            }

            // Update connection state UI
            if (this.managers.connectionState) {
                this.managers.connectionState.updateState(state, metadata);
            }

            // Dispatch event
            this._dispatchEvent('messaging:websocket-state-changed', { state, metadata });
        }

        /**
         * Handle WebSocket messages
         *
         * @param {string} event - Event name
         * @param {Object} data - Event data
         */
        _handleWebSocketMessage(event, data) {
            if (this.config.debug) {
                console.log('MessagingApp: WebSocket message', event, data);
            }

            // Handle specific events
            switch (event) {
                case 'new-message':
                    this._handleNewMessage(data);
                    break;

                case 'typing':
                    this._handleTyping(data);
                    break;

                case 'presence':
                    this._handlePresence(data);
                    break;

                case 'reaction':
                    this._handleReaction(data);
                    break;

                case 'delivery-status':
                    this._handleDeliveryStatus(data);
                    break;

                default:
                    // Generic event dispatch
                    this._dispatchEvent(`messaging:${event}`, data);
            }
        }

        /**
         * Handle new message event
         *
         * @param {Object} data - Message data
         */
        _handleNewMessage(data) {
            // Play notification sound and show browser notification
            if (this.managers.notifications) {
                this.managers.notifications.notifyNewMessage(
                    data.message,
                    data.sender
                );
            }

            // Dispatch event for UI updates
            this._dispatchEvent('messaging:new-message', data);
        }

        /**
         * Handle typing event
         *
         * @param {Object} data - Typing data
         */
        _handleTyping(data) {
            this._dispatchEvent('messaging:typing', data);
        }

        /**
         * Handle presence event
         *
         * @param {Object} data - Presence data
         */
        _handlePresence(data) {
            this._dispatchEvent('messaging:presence', data);
        }

        /**
         * Handle reaction event
         *
         * @param {Object} data - Reaction data
         */
        _handleReaction(data) {
            this._dispatchEvent('messaging:reaction', data);
        }

        /**
         * Handle delivery status event
         *
         * @param {Object} data - Delivery status data
         */
        _handleDeliveryStatus(data) {
            this._dispatchEvent('messaging:delivery-status', data);
        }

        /**
         * Process pending messages in queue
         */
        async _processPendingMessages() {
            if (!this.managers.messageQueue) {
                return;
            }

            try {
                await this.managers.messageQueue.processPendingMessages(
                    async (message) => {
                        // Send callback
                        return await window.messageService.sendMessage(
                            message.userId,
                            message.message,
                            message.files || []
                        );
                    }
                );
            } catch (error) {
                console.error('MessagingApp: Failed to process pending messages', error);
            }
        }

        /**
         * Dispatch custom event
         *
         * @param {string} eventName - Event name
         * @param {Object} detail - Event detail
         */
        _dispatchEvent(eventName, detail = {}) {
            const event = new CustomEvent(eventName, { detail });
            window.dispatchEvent(event);
        }

        /**
         * Get service instance
         *
         * @param {string} name - Service name
         * @returns {Object|null}
         */
        getService(name) {
            return this.services[name] || null;
        }

        /**
         * Get manager instance
         *
         * @param {string} name - Manager name
         * @returns {Object|null}
         */
        getManager(name) {
            return this.managers[name] || null;
        }

        /**
         * Check if initialized
         *
         * @returns {boolean}
         */
        isInitialized() {
            return this.initialized;
        }

        /**
         * Get configuration
         *
         * @returns {Object}
         */
        getConfig() {
            return { ...this.config };
        }
    }

    // Create singleton instance
    window.messagingApp = new MessagingApp();

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.messagingApp.init();
        });
    } else {
        // DOM already loaded
        window.messagingApp.init();
    }

    console.log('MessagingApp: Module loaded');
})();
