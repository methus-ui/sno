/**
 * DeliveryReceiptIndicator Component (Alpine.js)
 *
 * Shows delivery status icons for messages:
 * - ✓ (gray) - Sent to server
 * - ✓✓ (blue) - Delivered to recipient
 * - 🔵 (green) - Read by recipient
 *
 * Usage:
 * <div x-data="deliveryReceiptIndicator(messageId, initialStatus)" x-init="init()">
 *     <span x-html="statusIcon" class="delivery-status"></span>
 * </div>
 *
 * @param {number} messageId - The message ID
 * @param {string} initialStatus - Initial status ('sent', 'delivered', 'read', 'failed', 'pending')
 * @returns {Object} Alpine.js component
 */
function deliveryReceiptIndicator(messageId, initialStatus = 'pending') {
    return {
        messageId: messageId,
        status: initialStatus,
        lastChecked: null,
        checkInterval: null,

        /**
         * Initialize component
         */
        init() {
            console.log('DeliveryReceiptIndicator: Initialized for message', this.messageId);

            // Subscribe to delivery status events
            this.subscribeToDeliveryEvents();

            // Poll for status updates (every 10 seconds for pending/sent messages)
            if (this.status === 'pending' || this.status === 'sent') {
                this.startPolling();
            }
        },

        /**
         * Subscribe to delivery status events via Pusher
         */
        subscribeToDeliveryEvents() {
            if (!window.messagingWebSocket) {
                console.warn('DeliveryReceiptIndicator: WebSocket manager not available');
                return;
            }

            const channelName = `private-message-${this.messageId}`;

            window.messagingWebSocket.subscribe(channelName, {
                'delivery-status': (data) => {
                    console.log('DeliveryReceiptIndicator: Received status update', data);

                    if (data.status) {
                        this.updateStatus(data.status);
                    }
                }
            });
        },

        /**
         * Start polling for status updates
         */
        startPolling() {
            if (this.checkInterval) {
                clearInterval(this.checkInterval);
            }

            // Poll every 10 seconds
            this.checkInterval = setInterval(() => {
                this.checkDeliveryStatus();
            }, 10000);

            // Check immediately
            this.checkDeliveryStatus();
        },

        /**
         * Stop polling
         */
        stopPolling() {
            if (this.checkInterval) {
                clearInterval(this.checkInterval);
                this.checkInterval = null;
            }
        },

        /**
         * Check delivery status from server
         */
        async checkDeliveryStatus() {
            try {
                const response = await fetch(`/admin/message/delivery-status?message_id=${this.messageId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (response.ok) {
                    const data = await response.json();

                    if (data.success && data.status) {
                        this.updateStatus(data.status.status);
                    }
                }

            } catch (error) {
                console.error('DeliveryReceiptIndicator: Failed to check status', error);
            }
        },

        /**
         * Update status
         *
         * @param {string} newStatus - New status value
         */
        updateStatus(newStatus) {
            if (this.status !== newStatus) {
                const oldStatus = this.status;
                this.status = newStatus;
                this.lastChecked = new Date();

                console.log(`DeliveryReceiptIndicator: Status updated ${oldStatus} → ${newStatus}`);

                // Stop polling if status is final
                if (this.status === 'read' || this.status === 'failed') {
                    this.stopPolling();
                }

                // Trigger animation
                this.animateStatusChange();
            }
        },

        /**
         * Animate status change
         */
        animateStatusChange() {
            const element = this.$el.querySelector('.delivery-status');
            if (element) {
                element.classList.add('status-changed');
                setTimeout(() => {
                    element.classList.remove('status-changed');
                }, 500);
            }
        },

        /**
         * Get status icon HTML
         */
        get statusIcon() {
            switch (this.status) {
                case 'pending':
                    return '<i class="tio-time text-muted" title="Sending..."></i>';

                case 'sent':
                    return '<span class="text-muted" title="Sent">✓</span>';

                case 'delivered':
                    return '<span class="text-primary" title="Delivered">✓✓</span>';

                case 'read':
                    return '<span class="text-success" title="Read">🔵</span>';

                case 'failed':
                    return '<i class="tio-clear text-danger" title="Failed to send"></i>';

                default:
                    return '';
            }
        },

        /**
         * Get status text
         */
        get statusText() {
            switch (this.status) {
                case 'pending':
                    return 'Sending...';
                case 'sent':
                    return 'Sent';
                case 'delivered':
                    return 'Delivered';
                case 'read':
                    return 'Read';
                case 'failed':
                    return 'Failed';
                default:
                    return '';
            }
        },

        /**
         * Get status color class
         */
        get statusColorClass() {
            switch (this.status) {
                case 'pending':
                    return 'text-muted';
                case 'sent':
                    return 'text-muted';
                case 'delivered':
                    return 'text-primary';
                case 'read':
                    return 'text-success';
                case 'failed':
                    return 'text-danger';
                default:
                    return '';
            }
        },

        /**
         * Check if status is final (no more updates expected)
         */
        get isFinalStatus() {
            return this.status === 'read' || this.status === 'failed';
        },

        /**
         * Format last checked time
         */
        get lastCheckedFormatted() {
            if (!this.lastChecked) {
                return null;
            }

            const now = new Date();
            const diffInSeconds = Math.floor((now - this.lastChecked) / 1000);

            if (diffInSeconds < 60) {
                return 'Just now';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return `${minutes} min ago`;
            } else {
                return this.lastChecked.toLocaleTimeString();
            }
        }
    };
}

// Make available globally for Alpine.js
window.deliveryReceiptIndicator = deliveryReceiptIndicator;

// Inject CSS styles for delivery receipts
(function injectDeliveryReceiptStyles() {
    if (document.getElementById('delivery-receipt-styles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'delivery-receipt-styles';
    style.textContent = `
        .delivery-status {
            display: inline-flex;
            align-items: center;
            font-size: 12px;
            margin-left: 5px;
            transition: all 0.3s ease;
        }

        .delivery-status.status-changed {
            animation: status-pulse 0.5s ease;
        }

        @keyframes status-pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.3);
                opacity: 0.7;
            }
        }

        /* Status-specific styles */
        .delivery-status .text-muted {
            color: #6c757d;
        }

        .delivery-status .text-primary {
            color: #007bff;
        }

        .delivery-status .text-success {
            color: #28a745;
        }

        .delivery-status .text-danger {
            color: #dc3545;
        }

        /* Tooltip enhancement */
        .delivery-status [title] {
            cursor: help;
        }

        /* Loading spinner for pending */
        .delivery-status .tio-time {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
    `;

    document.head.appendChild(style);
})();
