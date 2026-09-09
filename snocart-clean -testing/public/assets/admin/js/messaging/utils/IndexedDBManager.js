/**
 * IndexedDBManager
 *
 * Utility class for managing IndexedDB operations.
 * Used for offline message queueing, caching, and local storage.
 *
 * @class IndexedDBManager
 */
class IndexedDBManager {
    constructor(dbName = 'MessagingDB', version = 1) {
        this.dbName = dbName;
        this.version = version;
        this.db = null;
        this.isInitialized = false;
    }

    /**
     * Initialize database
     *
     * Creates object stores if they don't exist
     *
     * @returns {Promise<IDBDatabase>}
     */
    async init() {
        if (this.isInitialized && this.db) {
            return this.db;
        }

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => {
                console.error('IndexedDBManager: Failed to open database', request.error);
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                this.isInitialized = true;
                console.log('IndexedDBManager: Database initialized');
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create object stores

                // Message queue store (for offline messages)
                if (!db.objectStoreNames.contains('messageQueue')) {
                    const messageQueueStore = db.createObjectStore('messageQueue', {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    messageQueueStore.createIndex('status', 'status', { unique: false });
                    messageQueueStore.createIndex('created_at', 'created_at', { unique: false });
                    messageQueueStore.createIndex('retry_count', 'retry_count', { unique: false });
                }

                // Conversation cache store
                if (!db.objectStoreNames.contains('conversations')) {
                    const conversationsStore = db.createObjectStore('conversations', {
                        keyPath: 'id'
                    });
                    conversationsStore.createIndex('updated_at', 'updated_at', { unique: false });
                }

                // Message cache store
                if (!db.objectStoreNames.contains('messages')) {
                    const messagesStore = db.createObjectStore('messages', {
                        keyPath: 'id'
                    });
                    messagesStore.createIndex('conversation_id', 'conversation_id', { unique: false });
                    messagesStore.createIndex('created_at', 'created_at', { unique: false });
                }

                // Draft messages store
                if (!db.objectStoreNames.contains('drafts')) {
                    const draftsStore = db.createObjectStore('drafts', {
                        keyPath: 'conversation_id'
                    });
                    draftsStore.createIndex('updated_at', 'updated_at', { unique: false });
                }

                console.log('IndexedDBManager: Database upgraded to version', this.version);
            };
        });
    }

    /**
     * Add item to object store
     *
     * @param {string} storeName - Object store name
     * @param {Object} data - Data to add
     * @returns {Promise<number>} Item ID
     */
    async add(storeName, data) {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.add(data);

            request.onsuccess = () => {
                resolve(request.result);
            };

            request.onerror = () => {
                console.error(`IndexedDBManager: Failed to add to ${storeName}`, request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Get item from object store by key
     *
     * @param {string} storeName - Object store name
     * @param {number|string} key - Item key
     * @returns {Promise<Object|null>}
     */
    async get(storeName, key) {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.get(key);

            request.onsuccess = () => {
                resolve(request.result || null);
            };

            request.onerror = () => {
                console.error(`IndexedDBManager: Failed to get from ${storeName}`, request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Get all items from object store
     *
     * @param {string} storeName - Object store name
     * @param {string} indexName - Optional index name for filtering
     * @param {IDBKeyRange} keyRange - Optional key range for filtering
     * @returns {Promise<Array>}
     */
    async getAll(storeName, indexName = null, keyRange = null) {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);

            let source = store;
            if (indexName) {
                source = store.index(indexName);
            }

            const request = keyRange ? source.getAll(keyRange) : source.getAll();

            request.onsuccess = () => {
                resolve(request.result || []);
            };

            request.onerror = () => {
                console.error(`IndexedDBManager: Failed to get all from ${storeName}`, request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Update item in object store
     *
     * @param {string} storeName - Object store name
     * @param {Object} data - Data to update (must include key)
     * @returns {Promise<void>}
     */
    async update(storeName, data) {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.put(data);

            request.onsuccess = () => {
                resolve();
            };

            request.onerror = () => {
                console.error(`IndexedDBManager: Failed to update in ${storeName}`, request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Delete item from object store
     *
     * @param {string} storeName - Object store name
     * @param {number|string} key - Item key
     * @returns {Promise<void>}
     */
    async delete(storeName, key) {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.delete(key);

            request.onsuccess = () => {
                resolve();
            };

            request.onerror = () => {
                console.error(`IndexedDBManager: Failed to delete from ${storeName}`, request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Clear all items from object store
     *
     * @param {string} storeName - Object store name
     * @returns {Promise<void>}
     */
    async clear(storeName) {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.clear();

            request.onsuccess = () => {
                console.log(`IndexedDBManager: Cleared ${storeName}`);
                resolve();
            };

            request.onerror = () => {
                console.error(`IndexedDBManager: Failed to clear ${storeName}`, request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Count items in object store
     *
     * @param {string} storeName - Object store name
     * @param {string} indexName - Optional index name
     * @param {IDBKeyRange} keyRange - Optional key range
     * @returns {Promise<number>}
     */
    async count(storeName, indexName = null, keyRange = null) {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);

            let source = store;
            if (indexName) {
                source = store.index(indexName);
            }

            const request = keyRange ? source.count(keyRange) : source.count();

            request.onsuccess = () => {
                resolve(request.result);
            };

            request.onerror = () => {
                console.error(`IndexedDBManager: Failed to count in ${storeName}`, request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Query items using cursor
     *
     * @param {string} storeName - Object store name
     * @param {Function} callback - Callback function for each item
     * @param {string} indexName - Optional index name
     * @param {IDBKeyRange} keyRange - Optional key range
     * @returns {Promise<void>}
     */
    async query(storeName, callback, indexName = null, keyRange = null) {
        await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);

            let source = store;
            if (indexName) {
                source = store.index(indexName);
            }

            const request = keyRange ? source.openCursor(keyRange) : source.openCursor();

            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    callback(cursor.value);
                    cursor.continue();
                } else {
                    resolve();
                }
            };

            request.onerror = () => {
                console.error(`IndexedDBManager: Failed to query ${storeName}`, request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Delete old items from object store
     *
     * @param {string} storeName - Object store name
     * @param {string} indexName - Index name for timestamp (e.g., 'created_at')
     * @param {number} maxAgeMs - Maximum age in milliseconds
     * @returns {Promise<number>} Number of items deleted
     */
    async deleteOldItems(storeName, indexName, maxAgeMs) {
        await this.init();

        const cutoffTime = new Date(Date.now() - maxAgeMs);
        const keyRange = IDBKeyRange.upperBound(cutoffTime);
        let deletedCount = 0;

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const index = store.index(indexName);
            const request = index.openCursor(keyRange);

            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    cursor.delete();
                    deletedCount++;
                    cursor.continue();
                } else {
                    console.log(`IndexedDBManager: Deleted ${deletedCount} old items from ${storeName}`);
                    resolve(deletedCount);
                }
            };

            request.onerror = () => {
                console.error(`IndexedDBManager: Failed to delete old items from ${storeName}`, request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Get database size estimate
     *
     * @returns {Promise<Object>} {usage, quota, percentage}
     */
    async getStorageEstimate() {
        if (!navigator.storage || !navigator.storage.estimate) {
            return { usage: null, quota: null, percentage: null };
        }

        try {
            const estimate = await navigator.storage.estimate();
            const usage = estimate.usage || 0;
            const quota = estimate.quota || 0;
            const percentage = quota > 0 ? (usage / quota * 100).toFixed(2) : 0;

            return {
                usage: this.formatBytes(usage),
                quota: this.formatBytes(quota),
                percentage: `${percentage}%`,
                usageBytes: usage,
                quotaBytes: quota
            };
        } catch (error) {
            console.error('IndexedDBManager: Failed to get storage estimate', error);
            return { usage: null, quota: null, percentage: null };
        }
    }

    /**
     * Format bytes to human readable size
     *
     * @param {number} bytes - Bytes
     * @returns {string} Formatted size
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Close database connection
     */
    close() {
        if (this.db) {
            this.db.close();
            this.db = null;
            this.isInitialized = false;
            console.log('IndexedDBManager: Database closed');
        }
    }

    /**
     * Delete entire database
     *
     * @returns {Promise<void>}
     */
    async deleteDatabase() {
        this.close();

        return new Promise((resolve, reject) => {
            const request = indexedDB.deleteDatabase(this.dbName);

            request.onsuccess = () => {
                console.log('IndexedDBManager: Database deleted');
                resolve();
            };

            request.onerror = () => {
                console.error('IndexedDBManager: Failed to delete database', request.error);
                reject(request.error);
            };

            request.onblocked = () => {
                console.warn('IndexedDBManager: Database deletion blocked');
            };
        });
    }
}

// Export as singleton
window.indexedDBManager = new IndexedDBManager('MessagingDB', 1);
