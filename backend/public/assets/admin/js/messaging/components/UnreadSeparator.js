/**
 * UnreadSeparator Component (Alpine.js)
 *
 * Displays a visual separator line with "Unread Messages" label
 * between read and unread messages in a conversation.
 *
 * Usage:
 * <div x-data="unreadSeparator(conversationId)" x-init="init()">
 *     <div x-show="shouldShow" class="unread-separator">
 *         <span class="unread-separator-line"></span>
 *         <span class="unread-separator-text" x-text="labelText"></span>
 *         <span class="unread-separator-line"></span>
 *     </div>
 * </div>
 *
 * @param {number} conversationId - The conversation ID
 * @param {number} unreadCount - Number of unread messages
 * @returns {Object} Alpine.js component
 */
function unreadSeparator(conversationId, unreadCount = 0) {
    return {
        conversationId: conversationId,
        unreadCount: unreadCount,
        shouldShow: false,
        lastReadMessageId: null,

        /**
         * Initialize component
         */
        init() {
            console.log('UnreadSeparator: Initialized for conversation', this.conversationId);

            // Determine if separator should be shown
            this.shouldShow = this.unreadCount > 0;

            // Get last read message ID from localStorage
            this.lastReadMessageId = this.getLastReadMessageId();

            // Listen for messages being read
            window.addEventListener('messaging:conversation-read', (event) => {
                if (event.detail.conversation_id === this.conversationId) {
                    this.handleConversationRead();
                }
            });

            // Listen for new messages
            window.addEventListener('messaging:new-message', (event) => {
                if (event.detail.conversation_id === this.conversationId) {
                    this.handleNewMessage(event.detail);
                }
            });

            // Auto-scroll to separator if it exists
            if (this.shouldShow) {
                this.$nextTick(() => {
                    this.scrollToSeparator();
                });
            }
        },

        /**
         * Get last read message ID from localStorage
         */
        getLastReadMessageId() {
            try {
                const key = `conversation_${this.conversationId}_last_read`;
                const stored = localStorage.getItem(key);
                return stored ? parseInt(stored) : null;
            } catch (error) {
                console.error('UnreadSeparator: Failed to get last read message ID', error);
                return null;
            }
        },

        /**
         * Set last read message ID in localStorage
         */
        setLastReadMessageId(messageId) {
            try {
                const key = `conversation_${this.conversationId}_last_read`;
                localStorage.setItem(key, messageId.toString());
                this.lastReadMessageId = messageId;
            } catch (error) {
                console.error('UnreadSeparator: Failed to set last read message ID', error);
            }
        },

        /**
         * Handle conversation being read
         */
        handleConversationRead() {
            // Hide separator
            this.shouldShow = false;
            this.unreadCount = 0;

            // Update last read message ID to the latest message
            const messages = document.querySelectorAll('.message-item');
            if (messages.length > 0) {
                const lastMessage = messages[messages.length - 1];
                const messageId = lastMessage.dataset.messageId;
                if (messageId) {
                    this.setLastReadMessageId(parseInt(messageId));
                }
            }
        },

        /**
         * Handle new message
         */
        handleNewMessage(data) {
            // If separator is not shown, show it now
            if (!this.shouldShow) {
                this.shouldShow = true;
                this.unreadCount = 1;
            } else {
                this.unreadCount++;
            }
        },

        /**
         * Scroll to separator (smooth)
         */
        scrollToSeparator() {
            const separator = this.$el;
            if (separator && separator.parentElement) {
                separator.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        },

        /**
         * Get label text
         */
        get labelText() {
            if (this.unreadCount === 1) {
                return '1 Unread Message';
            } else if (this.unreadCount > 1) {
                return `${this.unreadCount} Unread Messages`;
            }
            return 'Unread Messages';
        },

        /**
         * Get CSS class based on unread count
         */
        get severityClass() {
            if (this.unreadCount > 10) {
                return 'high';
            } else if (this.unreadCount > 5) {
                return 'medium';
            }
            return 'low';
        }
    };
}

// Make available globally for Alpine.js
window.unreadSeparator = unreadSeparator;

// Inject CSS styles for unread separator
(function injectUnreadSeparatorStyles() {
    if (document.getElementById('unread-separator-styles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'unread-separator-styles';
    style.textContent = `
        .unread-separator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            padding: 10px 0;
            position: relative;
        }

        .unread-separator-line {
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent, #ff6d6d, transparent);
            opacity: 0.5;
        }

        .unread-separator-text {
            padding: 0 15px;
            font-size: 12px;
            font-weight: 600;
            color: #ff6d6d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: white;
            white-space: nowrap;
            position: relative;
        }

        /* Animated entrance */
        .unread-separator[x-show="true"] {
            animation: separator-slide-in 0.5s ease;
        }

        @keyframes separator-slide-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Severity classes */
        .unread-separator.low .unread-separator-line {
            background: linear-gradient(90deg, transparent, #ffc107, transparent);
        }

        .unread-separator.low .unread-separator-text {
            color: #ffc107;
        }

        .unread-separator.medium .unread-separator-line {
            background: linear-gradient(90deg, transparent, #ff9800, transparent);
        }

        .unread-separator.medium .unread-separator-text {
            color: #ff9800;
        }

        .unread-separator.high .unread-separator-line {
            background: linear-gradient(90deg, transparent, #f44336, transparent);
        }

        .unread-separator.high .unread-separator-text {
            color: #f44336;
            animation: text-pulse 2s infinite;
        }

        @keyframes text-pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.6;
            }
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .unread-separator {
                margin: 15px 0;
            }

            .unread-separator-text {
                font-size: 11px;
                padding: 0 10px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .unread-separator-text {
                background: #1a1a1a;
            }
        }
    `;

    document.head.appendChild(style);
})();
