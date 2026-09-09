/**
 * MessageQueue
 *
 * Offline message queueing with IndexedDB persistence.
 * Automatically queues messages when offline and delivers when back online.
 *
 * Features:
 * - IndexedDB storage for persistence across page reloads
 * - Automatic retry with exponential backoff
 * - Failed message tracking
 * - Optimistic UI updates
 *
 * @class MessageQueue
 */
class MessageQueue {
    constructor(config = {}) {
        this.config = {
            dbName: config.dbName || 'snocart_messaging',
            dbVersion: config.dbVersion || 1,
            storeName: config.storeName || 'message_queue',
            maxRetries: config.maxRetries || 3,
            retryIntervals: config.retryIntervals || [5000, 30000, 300000], // 5s, 30s, 5min
            onSuccess: config.onSuccess || (() => {}),
            onError: config.onError || ((error) => console.error('MessageQueue error:', error))
        };

        this.db = null;
        this.isProcessing = false;

        this.initialize();
    }

    /**
     * Initialize IndexedDB
     */
    async initialize() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.config.dbName, this.config.dbVersion);

            request.onerror = () => {
                console.error('MessageQueue: Failed to open IndexedDB', request.error);
                this.config.onError(request.error);
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                console.log('MessageQueue: IndexedDB initialized');
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create message queue store if it doesn't exist
                if (!db.objectStoreNames.contains(this.config.storeName)) {
                    const objectStore = db.createObjectStore(this.config.storeName, {
                        keyPath: 'id',
                        autoIncrement: true
                    });

                    // Create indexes
                    objectStore.createIndex('status', 'status', { unique: false });
                    objectStore.createIndex('createdAt', 'createdAt', { unique: false });
                    objectStore.createIndex('conversationId', 'conversationId', { unique: false });

                    console.log('MessageQueue: Created object store');
                }
            };
        });
    }

    /**
     * Enqueue a message for sending
     *
     * @param {Object} messageData - Message data to send
     * @returns {Promise<Object>} Queued message with ID
     */
    async enqueue(messageData) {
        if (!this.db) {
            await this.initialize();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.config.storeName], 'readwrite');
            const objectStore = transaction.objectStore(this.config.storeName);

            const queueItem = {
                ...messageData,
                status: 'pending',
                retryCount: 0,
                createdAt: new Date().toISOString(),
                updatedAt: new Date().toISOString()
            };

            const request = objectStore.add(queueItem);

            request.onsuccess = () => {
                queueItem.id = request.result;
                console.log('MessageQueue: Enqueued message', queueItem.id);
                resolve(queueItem);
            };

            request.onerror = () => {
                console.error('MessageQueue: Failed to enqueue message', request.error);
                this.config.onError(request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Get all pending messages
     *
     * @returns {Promise<Array>} Array of pending messages
     */
    async getPendingMessages() {
        if (!this.db) {
            await this.initialize();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.config.storeName], 'readonly');
            const objectStore = transaction.objectStore(this.config.storeName);
            const index = objectStore.index('status');
            const request = index.getAll('pending');

            request.onsuccess = () => {
                resolve(request.result || []);
            };

            request.onerror = () => {
                console.error('MessageQueue: Failed to get pending messages', request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Process all pending messages
     *
     * @param {Function} sendCallback - Function to send a message (returns Promise)
     * @returns {Promise<Object>} Statistics object
     */
    async processPendingMessages(sendCallback) {
        if (this.isProcessing) {
            console.log('MessageQueue: Already processing, skipping');
            return { processed: 0, succeeded: 0, failed: 0 };
        }

        this.isProcessing = true;

        const stats = {
            processed: 0,
            succeeded: 0,
            failed: 0
        };

        try {
            const pendingMessages = await this.getPendingMessages();

            console.log(`MessageQueue: Processing ${pendingMessages.length} pending messages`);

            for (const message of pendingMessages) {
                stats.processed++;

                // Check retry limit
                if (message.retryCount >= this.config.maxRetries) {
                    console.warn(`MessageQueue: Message ${message.id} exceeded max retries`);
                    await this.updateStatus(message.id, 'failed');
                    stats.failed++;
                    continue;
                }

                // Check retry interval
                if (message.lastRetryAt) {
                    const intervalIndex = Math.min(message.retryCount, this.config.retryIntervals.length - 1);
                    const requiredInterval = this.config.retryIntervals[intervalIndex];
                    const timeSinceLastRetry = Date.now() - new Date(message.lastRetryAt).getTime();

                    if (timeSinceLastRetry < requiredInterval) {
                        console.log(`MessageQueue: Skipping message ${message.id}, retry interval not elapsed`);
                        continue;
                    }
                }

                try {
                    // Attempt to send message
                    console.log(`MessageQueue: Sending message ${message.id}`);
                    await sendCallback(message);

                    // Success - remove from queue
                    await this.remove(message.id);
                    stats.succeeded++;
                    this.config.onSuccess(message);

                    console.log(`MessageQueue: Message ${message.id} sent successfully`);

                } catch (error) {
                    console.error(`MessageQueue: Failed to send message ${message.id}`, error);

                    // Update retry count
                    await this.incrementRetry(message.id);
                    stats.failed++;
                    this.config.onError(error);
                }
            }

            console.log('MessageQueue: Processing complete', stats);

        } catch (error) {
            console.error('MessageQueue: Processing failed', error);
            this.config.onError(error);

        } finally {
            this.isProcessing = false;
        }

        return stats;
    }

    /**
     * Update message status
     *
     * @param {number} messageId - Message ID
     * @param {string} status - New status (pending, sent, failed)
     * @returns {Promise<void>}
     */
    async updateStatus(messageId, status) {
        if (!this.db) {
            await this.initialize();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.config.storeName], 'readwrite');
            const objectStore = transaction.objectStore(this.config.storeName);
            const request = objectStore.get(messageId);

            request.onsuccess = () => {
                const message = request.result;
                if (message) {
                    message.status = status;
                    message.updatedAt = new Date().toISOString();

                    const updateRequest = objectStore.put(message);

                    updateRequest.onsuccess = () => {
                        console.log(`MessageQueue: Updated message ${messageId} status to ${status}`);
                        resolve();
                    };

                    updateRequest.onerror = () => {
                        console.error('MessageQueue: Failed to update status', updateRequest.error);
                        reject(updateRequest.error);
                    };
                } else {
                    resolve(); // Message not found, already processed
                }
            };

            request.onerror = () => {
                console.error('MessageQueue: Failed to get message for update', request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Increment retry count
     *
     * @param {number} messageId - Message ID
     * @returns {Promise<void>}
     */
    async incrementRetry(messageId) {
        if (!this.db) {
            await this.initialize();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.config.storeName], 'readwrite');
            const objectStore = transaction.objectStore(this.config.storeName);
            const request = objectStore.get(messageId);

            request.onsuccess = () => {
                const message = request.result;
                if (message) {
                    message.retryCount = (message.retryCount || 0) + 1;
                    message.lastRetryAt = new Date().toISOString();
                    message.updatedAt = new Date().toISOString();

                    const updateRequest = objectStore.put(message);

                    updateRequest.onsuccess = () => {
                        console.log(`MessageQueue: Incremented retry count for message ${messageId} to ${message.retryCount}`);
                        resolve();
                    };

                    updateRequest.onerror = () => {
                        console.error('MessageQueue: Failed to increment retry', updateRequest.error);
                        reject(updateRequest.error);
                    };
                } else {
                    resolve(); // Message not found
                }
            };

            request.onerror = () => {
                console.error('MessageQueue: Failed to get message for retry increment', request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Remove message from queue
     *
     * @param {number} messageId - Message ID
     * @returns {Promise<void>}
     */
    async remove(messageId) {
        if (!this.db) {
            await this.initialize();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.config.storeName], 'readwrite');
            const objectStore = transaction.objectStore(this.config.storeName);
            const request = objectStore.delete(messageId);

            request.onsuccess = () => {
                console.log(`MessageQueue: Removed message ${messageId} from queue`);
                resolve();
            };

            request.onerror = () => {
                console.error('MessageQueue: Failed to remove message', request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Clear all messages from queue
     *
     * @returns {Promise<void>}
     */
    async clear() {
        if (!this.db) {
            await this.initialize();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.config.storeName], 'readwrite');
            const objectStore = transaction.objectStore(this.config.storeName);
            const request = objectStore.clear();

            request.onsuccess = () => {
                console.log('MessageQueue: Cleared all messages');
                resolve();
            };

            request.onerror = () => {
                console.error('MessageQueue: Failed to clear', request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Get queue statistics
     *
     * @returns {Promise<Object>} Statistics object
     */
    async getStats() {
        if (!this.db) {
            await this.initialize();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.config.storeName], 'readonly');
            const objectStore = transaction.objectStore(this.config.storeName);
            const request = objectStore.getAll();

            request.onsuccess = () => {
                const messages = request.result || [];

                const stats = {
                    total: messages.length,
                    pending: messages.filter(m => m.status === 'pending').length,
                    failed: messages.filter(m => m.status === 'failed').length,
                    oldestMessage: messages.length > 0 ? messages[0].createdAt : null
                };

                resolve(stats);
            };

            request.onerror = () => {
                console.error('MessageQueue: Failed to get stats', request.error);
                reject(request.error);
            };
        });
    }
}

// Export for use in other modules
window.MessageQueue = MessageQueue;
