/**
 * RetryHandler
 *
 * Utility class for handling retries with exponential backoff.
 * Used for failed API calls, WebSocket reconnections, and message delivery.
 *
 * @class RetryHandler
 */
class RetryHandler {
    constructor(options = {}) {
        this.options = {
            maxRetries: options.maxRetries || 5,
            initialDelay: options.initialDelay || 1000, // 1 second
            maxDelay: options.maxDelay || 30000, // 30 seconds
            backoffMultiplier: options.backoffMultiplier || 2,
            jitter: options.jitter !== undefined ? options.jitter : true,
            onRetry: options.onRetry || null, // Callback on each retry
            onSuccess: options.onSuccess || null, // Callback on success
            onFailure: options.onFailure || null, // Callback on final failure
            shouldRetry: options.shouldRetry || null // Custom retry condition
        };

        this.retryCount = 0;
        this.isRetrying = false;
    }

    /**
     * Execute function with retry logic
     *
     * @param {Function} fn - Async function to execute
     * @param {Array} args - Arguments to pass to function
     * @returns {Promise<any>} Function result
     */
    async execute(fn, ...args) {
        this.retryCount = 0;
        this.isRetrying = false;

        return this._attempt(fn, args);
    }

    /**
     * Internal attempt method
     *
     * @param {Function} fn - Function to execute
     * @param {Array} args - Function arguments
     * @returns {Promise<any>}
     */
    async _attempt(fn, args) {
        try {
            const result = await fn(...args);

            // Success callback
            if (this.options.onSuccess) {
                this.options.onSuccess(result, this.retryCount);
            }

            this.isRetrying = false;
            return result;

        } catch (error) {
            // Check if we should retry
            const shouldRetry = this.options.shouldRetry
                ? this.options.shouldRetry(error, this.retryCount)
                : true;

            if (!shouldRetry || this.retryCount >= this.options.maxRetries) {
                // Max retries reached or custom condition says don't retry
                if (this.options.onFailure) {
                    this.options.onFailure(error, this.retryCount);
                }

                this.isRetrying = false;
                throw error;
            }

            // Calculate delay with exponential backoff
            const delay = this._calculateDelay();

            // Retry callback
            if (this.options.onRetry) {
                this.options.onRetry(error, this.retryCount, delay);
            }

            this.isRetrying = true;
            this.retryCount++;

            // Wait before retry
            await this._sleep(delay);

            // Retry
            return this._attempt(fn, args);
        }
    }

    /**
     * Calculate delay with exponential backoff
     *
     * @returns {number} Delay in milliseconds
     */
    _calculateDelay() {
        // Calculate base delay: initialDelay * (backoffMultiplier ^ retryCount)
        let delay = this.options.initialDelay * Math.pow(this.options.backoffMultiplier, this.retryCount);

        // Apply max delay cap
        delay = Math.min(delay, this.options.maxDelay);

        // Add jitter to prevent thundering herd
        if (this.options.jitter) {
            const jitterAmount = delay * 0.3; // 30% jitter
            const randomJitter = Math.random() * jitterAmount - (jitterAmount / 2);
            delay = delay + randomJitter;
        }

        return Math.round(delay);
    }

    /**
     * Sleep for specified duration
     *
     * @param {number} ms - Milliseconds to sleep
     * @returns {Promise<void>}
     */
    _sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Reset retry count
     */
    reset() {
        this.retryCount = 0;
        this.isRetrying = false;
    }

    /**
     * Get current retry count
     *
     * @returns {number}
     */
    getRetryCount() {
        return this.retryCount;
    }

    /**
     * Check if currently retrying
     *
     * @returns {boolean}
     */
    isCurrentlyRetrying() {
        return this.isRetrying;
    }

    /**
     * Get next retry delay (without executing)
     *
     * @returns {number} Next delay in milliseconds
     */
    getNextDelay() {
        return this._calculateDelay();
    }
}

/**
 * Predefined retry strategies
 */
class RetryStrategies {
    /**
     * Network error retry strategy
     * Retries on network failures, timeouts, 5xx errors
     *
     * @returns {Object} Retry handler options
     */
    static network() {
        return {
            maxRetries: 5,
            initialDelay: 1000,
            maxDelay: 30000,
            backoffMultiplier: 2,
            jitter: true,
            shouldRetry: (error, retryCount) => {
                // Retry on network errors
                if (error.message.includes('NetworkError') ||
                    error.message.includes('Failed to fetch') ||
                    error.message.includes('timeout')) {
                    return true;
                }

                // Retry on 5xx errors
                if (error.message.includes('HTTP 5')) {
                    return true;
                }

                // Don't retry on 4xx errors (client errors)
                if (error.message.includes('HTTP 4')) {
                    return false;
                }

                return true;
            }
        };
    }

    /**
     * WebSocket reconnection strategy
     * Aggressive retries with increasing delays
     *
     * @returns {Object} Retry handler options
     */
    static websocket() {
        return {
            maxRetries: 10,
            initialDelay: 1000,
            maxDelay: 30000,
            backoffMultiplier: 2,
            jitter: true
        };
    }

    /**
     * Message delivery strategy
     * Retries with longer delays
     *
     * @returns {Object} Retry handler options
     */
    static messageDelivery() {
        return {
            maxRetries: 3,
            initialDelay: 5000,
            maxDelay: 300000, // 5 minutes
            backoffMultiplier: 6, // 5s → 30s → 5min
            jitter: false
        };
    }

    /**
     * Quick retry strategy
     * Fast retries for minor issues
     *
     * @returns {Object} Retry handler options
     */
    static quick() {
        return {
            maxRetries: 3,
            initialDelay: 500,
            maxDelay: 2000,
            backoffMultiplier: 2,
            jitter: true
        };
    }

    /**
     * Persistent retry strategy
     * Never gives up, useful for critical operations
     *
     * @returns {Object} Retry handler options
     */
    static persistent() {
        return {
            maxRetries: Infinity,
            initialDelay: 1000,
            maxDelay: 60000, // 1 minute max
            backoffMultiplier: 2,
            jitter: true
        };
    }
}

/**
 * Helper function to retry an async function
 *
 * @param {Function} fn - Async function to retry
 * @param {Object} options - Retry options
 * @returns {Promise<any>}
 */
async function retry(fn, options = {}) {
    const handler = new RetryHandler(options);
    return handler.execute(fn);
}

/**
 * Helper function to retry with predefined strategy
 *
 * @param {Function} fn - Async function to retry
 * @param {string} strategy - Strategy name ('network', 'websocket', 'messageDelivery', 'quick', 'persistent')
 * @param {Object} overrides - Option overrides
 * @returns {Promise<any>}
 */
async function retryWithStrategy(fn, strategy, overrides = {}) {
    const strategies = {
        network: RetryStrategies.network(),
        websocket: RetryStrategies.websocket(),
        messageDelivery: RetryStrategies.messageDelivery(),
        quick: RetryStrategies.quick(),
        persistent: RetryStrategies.persistent()
    };

    const options = { ...strategies[strategy], ...overrides };
    return retry(fn, options);
}

// Export classes and helpers
window.RetryHandler = RetryHandler;
window.RetryStrategies = RetryStrategies;
window.retry = retry;
window.retryWithStrategy = retryWithStrategy;

// Usage examples (for documentation):
/*
// Example 1: Basic retry with default options
try {
    const result = await retry(async () => {
        return await fetch('/api/endpoint');
    });
} catch (error) {
    console.error('All retries failed:', error);
}

// Example 2: Retry with custom options
const result = await retry(async () => {
    return await messageService.sendMessage(userId, message);
}, {
    maxRetries: 3,
    initialDelay: 5000,
    onRetry: (error, count, delay) => {
        console.log(`Retry ${count} after ${delay}ms due to:`, error);
    }
});

// Example 3: Retry with predefined strategy
const result = await retryWithStrategy(async () => {
    return await websocket.connect();
}, 'websocket', {
    onRetry: (error, count) => {
        console.log(`WebSocket reconnection attempt ${count}`);
    }
});

// Example 4: Creating a custom retry handler
const handler = new RetryHandler({
    maxRetries: 5,
    initialDelay: 1000,
    shouldRetry: (error, retryCount) => {
        // Custom retry logic
        return error.message.includes('timeout') && retryCount < 3;
    }
});

const result = await handler.execute(myAsyncFunction, arg1, arg2);
*/
