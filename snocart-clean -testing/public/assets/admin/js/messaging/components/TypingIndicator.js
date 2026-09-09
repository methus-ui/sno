/**
 * TypingIndicator Component (Alpine.js)
 *
 * Shows "User is typing..." with animated dots.
 * Automatically triggers typing events on input with debouncing.
 *
 * Usage:
 * <div x-data="typingIndicator(conversationId)" x-init="init()">
 *     <div x-show="isTyping" x-text="typingText" class="typing-indicator"></div>
 * </div>
 *
 * @param {number} conversationId - The conversation ID
 * @returns {Object} Alpine.js component
 */
function typingIndicator(conversationId) {
    return {
        conversationId: conversationId,
        isTyping: false,
        typingText: '',
        typingUsers: [],
        debounceTimeout: null,
        stopTypingTimeout: null,
        isUserTyping: false,

        /**
         * Initialize component
         */
        init() {
            console.log('TypingIndicator: Initialized for conversation', this.conversationId);

            // Listen for typing events via Pusher
            this.subscribeToTypingEvents();

            // Auto-cleanup on destroy
            this.$el.addEventListener('alpine:cleanup', () => {
                this.cleanup();
            });
        },

        /**
         * Subscribe to typing events via Pusher
         */
        subscribeToTypingEvents() {
            if (!window.messagingWebSocket) {
                console.warn('TypingIndicator: WebSocket manager not available');
                return;
            }

            const channelName = `private-conversation-${this.conversationId}`;

            window.messagingWebSocket.subscribe(channelName, {
                'typing': (data) => {
                    console.log('TypingIndicator: Received typing event', data);

                    if (data.action === 'start') {
                        this.addTypingUser(data.user_info_id);
                    } else if (data.action === 'stop') {
                        this.removeTypingUser(data.user_info_id);
                    }
                }
            });
        },

        /**
         * Add user to typing list
         */
        addTypingUser(userInfoId) {
            // Don't show if it's the current user
            if (this.isCurrentUser(userInfoId)) {
                return;
            }

            if (!this.typingUsers.includes(userInfoId)) {
                this.typingUsers.push(userInfoId);
                this.updateTypingDisplay();
            }

            // Auto-remove after 5 seconds (in case stop event is missed)
            setTimeout(() => {
                this.removeTypingUser(userInfoId);
            }, 5000);
        },

        /**
         * Remove user from typing list
         */
        removeTypingUser(userInfoId) {
            const index = this.typingUsers.indexOf(userInfoId);
            if (index > -1) {
                this.typingUsers.splice(index, 1);
                this.updateTypingDisplay();
            }
        },

        /**
         * Update typing display text
         */
        updateTypingDisplay() {
            if (this.typingUsers.length === 0) {
                this.isTyping = false;
                this.typingText = '';
            } else if (this.typingUsers.length === 1) {
                this.isTyping = true;
                this.typingText = 'User is typing...';
            } else if (this.typingUsers.length === 2) {
                this.isTyping = true;
                this.typingText = '2 users are typing...';
            } else {
                this.isTyping = true;
                this.typingText = `${this.typingUsers.length} users are typing...`;
            }
        },

        /**
         * Handle user typing (debounced)
         */
        handleTyping() {
            // Clear existing debounce timeout
            if (this.debounceTimeout) {
                clearTimeout(this.debounceTimeout);
            }

            // Clear stop typing timeout
            if (this.stopTypingTimeout) {
                clearTimeout(this.stopTypingTimeout);
            }

            // Send "start typing" if not already typing
            if (!this.isUserTyping) {
                this.sendTypingEvent('start');
                this.isUserTyping = true;
            }

            // Debounce - send "stop typing" after 300ms of no input
            this.debounceTimeout = setTimeout(() => {
                this.handleStopTyping();
            }, 300);

            // Auto-stop after 5 seconds of continuous typing
            this.stopTypingTimeout = setTimeout(() => {
                this.handleStopTyping();
            }, 5000);
        },

        /**
         * Handle stop typing
         */
        handleStopTyping() {
            if (this.isUserTyping) {
                this.sendTypingEvent('stop');
                this.isUserTyping = false;
            }

            if (this.debounceTimeout) {
                clearTimeout(this.debounceTimeout);
            }

            if (this.stopTypingTimeout) {
                clearTimeout(this.stopTypingTimeout);
            }
        },

        /**
         * Send typing event to server
         */
        sendTypingEvent(action) {
            const endpoint = action === 'start'
                ? '/admin/message/typing/start'
                : '/admin/message/typing/stop';

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    conversation_id: this.conversationId
                })
            }).catch(error => {
                console.error('TypingIndicator: Failed to send typing event', error);
            });
        },

        /**
         * Check if user ID is current user
         */
        isCurrentUser(userInfoId) {
            // You'll need to set window.currentUserInfoId when page loads
            return window.currentUserInfoId && window.currentUserInfoId === userInfoId;
        },

        /**
         * Cleanup on component destroy
         */
        cleanup() {
            if (this.debounceTimeout) {
                clearTimeout(this.debounceTimeout);
            }

            if (this.stopTypingTimeout) {
                clearTimeout(this.stopTypingTimeout);
            }

            // Send stop typing if currently typing
            if (this.isUserTyping) {
                this.handleStopTyping();
            }

            console.log('TypingIndicator: Cleaned up');
        }
    };
}

// Make available globally for Alpine.js
window.typingIndicator = typingIndicator;
