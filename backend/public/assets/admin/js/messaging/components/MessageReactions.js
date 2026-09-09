/**
 * MessageReactions Component (Alpine.js)
 *
 * Emoji reaction picker and display for messages.
 * Supports 6 reactions: thumbs_up, heart, laugh, sad, angry, wow
 *
 * Usage:
 * <div x-data="messageReactions(messageId)" x-init="init()">
 *     <button @click="togglePicker()">React</button>
 *     <div x-show="showPicker" class="reaction-picker">
 *         <!-- Reaction buttons rendered here -->
 *     </div>
 *     <div class="reactions-display">
 *         <!-- Active reactions displayed here -->
 *     </div>
 * </div>
 *
 * @param {number} messageId - The message ID
 * @returns {Object} Alpine.js component
 */
function messageReactions(messageId) {
    return {
        messageId: messageId,
        showPicker: false,
        reactions: [],
        userReaction: null,
        isLoading: false,

        /**
         * Available reaction types
         */
        availableReactions: [
            { type: 'thumbs_up', emoji: '👍', label: 'Thumbs Up' },
            { type: 'heart', emoji: '❤️', label: 'Heart' },
            { type: 'laugh', emoji: '😂', label: 'Laugh' },
            { type: 'sad', emoji: '😢', label: 'Sad' },
            { type: 'angry', emoji: '😠', label: 'Angry' },
            { type: 'wow', emoji: '😮', label: 'Wow' }
        ],

        /**
         * Initialize component
         */
        init() {
            console.log('MessageReactions: Initialized for message', this.messageId);

            // Load existing reactions
            this.loadReactions();

            // Subscribe to reaction events
            this.subscribeToReactionEvents();

            // Close picker when clicking outside
            this.$el.addEventListener('click', (e) => {
                if (!e.target.closest('.reaction-picker') && !e.target.closest('.reaction-trigger')) {
                    this.showPicker = false;
                }
            });
        },

        /**
         * Subscribe to reaction events via Pusher
         */
        subscribeToReactionEvents() {
            if (!window.messagingWebSocket) {
                console.warn('MessageReactions: WebSocket manager not available');
                return;
            }

            const channelName = `private-message-${this.messageId}`;

            window.messagingWebSocket.subscribe(channelName, {
                'reaction': (data) => {
                    console.log('MessageReactions: Received reaction event', data);

                    if (data.action === 'added' || data.action === 'updated') {
                        this.loadReactions(); // Reload to get updated counts
                    } else if (data.action === 'removed') {
                        this.loadReactions();
                    }
                }
            });
        },

        /**
         * Load reactions from server
         */
        async loadReactions() {
            try {
                const response = await fetch(`/admin/message/reaction/list?message_id=${this.messageId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (response.ok) {
                    const data = await response.json();

                    if (data.success) {
                        this.reactions = data.reactions || [];
                        this.updateUserReaction();
                    }
                }

            } catch (error) {
                console.error('MessageReactions: Failed to load reactions', error);
            }
        },

        /**
         * Update user's current reaction
         */
        updateUserReaction() {
            // Check if current user has reacted
            if (!window.currentUserInfoId) {
                return;
            }

            this.userReaction = null;

            this.reactions.forEach(reaction => {
                const userInReaction = reaction.users?.find(u => u.id === window.currentUserInfoId);
                if (userInReaction) {
                    this.userReaction = reaction.type;
                }
            });
        },

        /**
         * Toggle reaction picker
         */
        togglePicker() {
            this.showPicker = !this.showPicker;
        },

        /**
         * Add or toggle reaction
         */
        async addReaction(reactionType) {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;
            this.showPicker = false;

            try {
                const response = await fetch('/admin/message/reaction/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({
                        message_id: this.messageId,
                        reaction_type: reactionType
                    })
                });

                if (response.ok) {
                    const data = await response.json();

                    if (data.success) {
                        // Optimistic update
                        await this.loadReactions();
                        console.log('MessageReactions: Reaction added/toggled successfully');
                    }
                }

            } catch (error) {
                console.error('MessageReactions: Failed to add reaction', error);
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Remove user's reaction
         */
        async removeReaction() {
            if (this.isLoading || !this.userReaction) {
                return;
            }

            this.isLoading = true;

            try {
                const response = await fetch('/admin/message/reaction/remove', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({
                        message_id: this.messageId
                    })
                });

                if (response.ok) {
                    const data = await response.json();

                    if (data.success) {
                        await this.loadReactions();
                        console.log('MessageReactions: Reaction removed successfully');
                    }
                }

            } catch (error) {
                console.error('MessageReactions: Failed to remove reaction', error);
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Get reaction count by type
         */
        getReactionCount(reactionType) {
            const reaction = this.reactions.find(r => r.type === reactionType);
            return reaction ? reaction.count : 0;
        },

        /**
         * Check if user has reacted with specific type
         */
        hasUserReacted(reactionType) {
            return this.userReaction === reactionType;
        },

        /**
         * Get total reactions count
         */
        get totalReactions() {
            return this.reactions.reduce((sum, r) => sum + (r.count || 0), 0);
        },

        /**
         * Get top 3 reactions for compact display
         */
        get topReactions() {
            return [...this.reactions]
                .sort((a, b) => (b.count || 0) - (a.count || 0))
                .slice(0, 3);
        },

        /**
         * Get reaction emoji by type
         */
        getEmoji(reactionType) {
            const reaction = this.availableReactions.find(r => r.type === reactionType);
            return reaction ? reaction.emoji : '❓';
        },

        /**
         * Format reaction tooltip (shows who reacted)
         */
        getReactionTooltip(reaction) {
            if (!reaction.users || reaction.users.length === 0) {
                return '';
            }

            const names = reaction.users.slice(0, 5).map(u => u.name).join(', ');
            const remaining = reaction.users.length - 5;

            if (remaining > 0) {
                return `${names} and ${remaining} more`;
            }

            return names;
        }
    };
}

// Make available globally for Alpine.js
window.messageReactions = messageReactions;

// Inject CSS styles for reactions
(function injectReactionStyles() {
    if (document.getElementById('message-reactions-styles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'message-reactions-styles';
    style.textContent = `
        .reaction-picker {
            position: absolute;
            bottom: 100%;
            left: 0;
            background: white;
            border-radius: 24px;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            gap: 4px;
            z-index: 1000;
            margin-bottom: 8px;
        }

        .reaction-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .reaction-btn:hover {
            background: #f3f4f6;
            transform: scale(1.2);
        }

        .reaction-btn:active {
            transform: scale(0.95);
        }

        .reactions-display {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .reaction-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            background: #f3f4f6;
            border-radius: 12px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .reaction-badge:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
        }

        .reaction-badge.user-reacted {
            background: #dbeafe;
            border-color: #3b82f6;
            color: #1e40af;
        }

        .reaction-badge .emoji {
            font-size: 14px;
        }

        .reaction-badge .count {
            font-weight: 500;
            font-size: 12px;
        }

        .reaction-trigger {
            padding: 4px 8px;
            background: transparent;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .reaction-trigger:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

        /* Reaction animation */
        @keyframes reaction-pop {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }

        .reaction-badge.new {
            animation: reaction-pop 0.3s ease;
        }
    `;

    document.head.appendChild(style);
})();
