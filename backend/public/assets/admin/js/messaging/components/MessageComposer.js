/**
 * MessageComposer Component (Alpine.js)
 *
 * Enhanced message input area with typing indicators, file upload,
 * optimistic UI updates, and offline queueing.
 *
 * Usage:
 * <div x-data="messageComposer(conversationId)" x-init="init()">
 *     <textarea x-model="message" @input="handleTyping()" @keydown.enter.prevent="sendMessage()"></textarea>
 *     <button @click="sendMessage()" :disabled="!canSend">Send</button>
 * </div>
 *
 * @param {number} conversationId - The conversation ID
 * @param {number} userId - The receiver user ID
 * @returns {Object} Alpine.js component
 */
function messageComposer(conversationId, userId) {
    return {
        conversationId: conversationId,
        userId: userId,
        message: '',
        files: [],
        isSending: false,
        isOffline: false,
        optimisticMessages: [],

        /**
         * Initialize component
         */
        init() {
            console.log('MessageComposer: Initialized for conversation', this.conversationId);

            // Initialize typing indicator
            if (window.typingIndicator) {
                this.typingIndicatorComponent = window.typingIndicator(this.conversationId);
            }

            // Listen for connection state changes
            if (window.connectionStateManager) {
                window.connectionStateManager.config.onStateChange = (state) => {
                    this.isOffline = state.state !== 'connected';
                };
            }

            // Auto-focus on textarea
            this.$nextTick(() => {
                const textarea = this.$el.querySelector('textarea');
                if (textarea) {
                    textarea.focus();
                }
            });
        },

        /**
         * Handle typing event (triggers typing indicator)
         */
        handleTyping() {
            if (this.typingIndicatorComponent) {
                this.typingIndicatorComponent.handleTyping();
            }
        },

        /**
         * Send message
         */
        async sendMessage() {
            if (!this.canSend) {
                return;
            }

            const messageText = this.message.trim();

            if (!messageText && this.files.length === 0) {
                return;
            }

            // Stop typing indicator
            if (this.typingIndicatorComponent) {
                this.typingIndicatorComponent.handleStopTyping();
            }

            // Create optimistic message
            const optimisticMessage = {
                id: 'temp-' + Date.now(),
                message: messageText,
                files: [...this.files],
                status: 'sending',
                timestamp: new Date().toISOString()
            };

            this.optimisticMessages.push(optimisticMessage);

            // Clear input immediately (optimistic UI)
            const messageCopy = this.message;
            const filesCopy = [...this.files];
            this.message = '';
            this.files = [];

            this.isSending = true;

            try {
                // Check if online or offline
                if (this.isOffline || !navigator.onLine) {
                    // Queue message for offline sending
                    await this.queueOfflineMessage(messageCopy, filesCopy);
                    optimisticMessage.status = 'queued';
                    this.showNotification('Message queued (offline)', 'warning');
                } else {
                    // Send message immediately
                    await this.sendMessageToServer(messageCopy, filesCopy, optimisticMessage);
                }

            } catch (error) {
                console.error('MessageComposer: Failed to send message', error);

                // Restore message to input on error
                this.message = messageCopy;
                this.files = filesCopy;

                // Remove optimistic message
                const index = this.optimisticMessages.findIndex(m => m.id === optimisticMessage.id);
                if (index > -1) {
                    this.optimisticMessages.splice(index, 1);
                }

                this.showNotification('Failed to send message', 'error');

            } finally {
                this.isSending = false;
            }
        },

        /**
         * Send message to server
         */
        async sendMessageToServer(message, files, optimisticMessage) {
            const formData = new FormData();
            formData.append('reply', message);

            // Add files if any
            files.forEach((file, index) => {
                formData.append(`files[${index}]`, file);
            });

            const response = await fetch(`/admin/message/store/${this.userId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Failed to send message');
            }

            const data = await response.json();

            // Update optimistic message with real ID
            optimisticMessage.status = 'sent';
            optimisticMessage.realId = data.message_id;

            // Remove optimistic message after 2 seconds (real message will appear via Pusher)
            setTimeout(() => {
                const index = this.optimisticMessages.findIndex(m => m.id === optimisticMessage.id);
                if (index > -1) {
                    this.optimisticMessages.splice(index, 1);
                }
            }, 2000);

            console.log('MessageComposer: Message sent successfully');
        },

        /**
         * Queue message for offline sending
         */
        async queueOfflineMessage(message, files) {
            if (!window.messageQueue) {
                throw new Error('Message queue not available');
            }

            await window.messageQueue.enqueue({
                conversationId: this.conversationId,
                userId: this.userId,
                message: message,
                files: files,
                timestamp: new Date().toISOString()
            });

            console.log('MessageComposer: Message queued for offline sending');
        },

        /**
         * Handle file selection
         */
        handleFileSelect(event) {
            const selectedFiles = Array.from(event.target.files || []);

            // Validate file count
            const maxFiles = 5;
            if (this.files.length + selectedFiles.length > maxFiles) {
                this.showNotification(`Maximum ${maxFiles} files allowed`, 'error');
                return;
            }

            // Validate file size (10MB per file)
            const maxSize = 10 * 1024 * 1024; // 10MB
            const oversizedFiles = selectedFiles.filter(f => f.size > maxSize);

            if (oversizedFiles.length > 0) {
                this.showNotification('Some files exceed 10MB limit', 'error');
                return;
            }

            // Add files
            this.files = [...this.files, ...selectedFiles];
        },

        /**
         * Remove file
         */
        removeFile(index) {
            this.files.splice(index, 1);
        },

        /**
         * Check if can send message
         */
        get canSend() {
            return !this.isSending && (this.message.trim().length > 0 || this.files.length > 0);
        },

        /**
         * Get character count
         */
        get characterCount() {
            return this.message.length;
        },

        /**
         * Check if approaching character limit
         */
        get isApproachingLimit() {
            const limit = 5000;
            return this.characterCount > limit * 0.9;
        },

        /**
         * Check if exceeded character limit
         */
        get isOverLimit() {
            const limit = 5000;
            return this.characterCount > limit;
        },

        /**
         * Show notification
         */
        showNotification(message, type = 'info') {
            if (window.connectionStateManager) {
                window.connectionStateManager.showNotification(message, type);
            } else {
                console.log(`Notification (${type}): ${message}`);
            }
        },

        /**
         * Format file size
         */
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';

            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));

            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        /**
         * Insert template message
         */
        insertTemplate(templateText) {
            this.message = templateText;

            // Focus textarea
            this.$nextTick(() => {
                const textarea = this.$el.querySelector('textarea');
                if (textarea) {
                    textarea.focus();
                }
            });
        }
    };
}

// Make available globally for Alpine.js
window.messageComposer = messageComposer;

// Inject CSS styles for message composer
(function injectComposerStyles() {
    if (document.getElementById('message-composer-styles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'message-composer-styles';
    style.textContent = `
        .message-composer {
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 16px;
        }

        .message-composer textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
            font-size: 14px;
            resize: vertical;
            min-height: 60px;
            max-height: 200px;
            font-family: inherit;
        }

        .message-composer textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .message-composer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
        }

        .message-composer-files {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .file-preview {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f3f4f6;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
        }

        .file-preview-remove {
            color: #ef4444;
            cursor: pointer;
            font-weight: bold;
        }

        .character-count {
            font-size: 12px;
            color: #6b7280;
        }

        .character-count.warning {
            color: #f59e0b;
        }

        .character-count.error {
            color: #ef4444;
        }

        .send-button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .send-button:hover:not(:disabled) {
            background: #2563eb;
        }

        .send-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .optimistic-message {
            opacity: 0.6;
            position: relative;
        }

        .optimistic-message::after {
            content: '⟳';
            position: absolute;
            right: 8px;
            top: 8px;
            animation: spin 1s linear infinite;
        }

        .optimistic-message.sent::after {
            content: '✓';
            animation: none;
        }

        .optimistic-message.queued::after {
            content: '⏸';
            animation: none;
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
