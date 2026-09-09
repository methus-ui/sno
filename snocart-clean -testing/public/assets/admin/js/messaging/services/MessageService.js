/**
 * MessageService
 *
 * Service layer for message-related API calls.
 * Handles all HTTP requests for messaging operations.
 *
 * @class MessageService
 */
class MessageService {
    constructor() {
        this.baseUrl = '/admin/message';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    }

    /**
     * Send a message
     *
     * @param {number} userId - Receiver user ID
     * @param {string} message - Message text
     * @param {Array} files - Array of File objects
     * @returns {Promise<Object>}
     */
    async sendMessage(userId, message, files = []) {
        const formData = new FormData();
        formData.append('reply', message);

        files.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });

        const response = await fetch(`${this.baseUrl}/store/${userId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: formData
        });

        return this.handleResponse(response);
    }

    /**
     * Get messages for conversation
     *
     * @param {number} conversationId - Conversation ID
     * @param {number} page - Page number
     * @returns {Promise<Object>}
     */
    async getMessages(conversationId, page = 1) {
        const response = await fetch(`${this.baseUrl}/get-messages/${conversationId}?page=${page}`, {
            headers: {
                'X-CSRF-TOKEN': this.csrfToken
            }
        });

        return this.handleResponse(response);
    }

    /**
     * Edit message
     *
     * @param {number} messageId - Message ID
     * @param {string} newMessage - New message text
     * @returns {Promise<Object>}
     */
    async editMessage(messageId, newMessage) {
        const response = await fetch(`${this.baseUrl}/message/edit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                message_id: messageId,
                new_message: newMessage
            })
        });

        return this.handleResponse(response);
    }

    /**
     * Delete message (soft delete)
     *
     * @param {number} messageId - Message ID
     * @returns {Promise<Object>}
     */
    async deleteMessage(messageId) {
        const response = await fetch(`${this.baseUrl}/message/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                message_id: messageId
            })
        });

        return this.handleResponse(response);
    }

    /**
     * Mark message as read
     *
     * @param {number} messageId - Message ID
     * @returns {Promise<Object>}
     */
    async markAsRead(messageId) {
        const response = await fetch(`${this.baseUrl}/message/mark-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                message_id: messageId
            })
        });

        return this.handleResponse(response);
    }

    /**
     * Get delivery status
     *
     * @param {number} messageId - Message ID
     * @returns {Promise<Object>}
     */
    async getDeliveryStatus(messageId) {
        const response = await fetch(`${this.baseUrl}/delivery-status?message_id=${messageId}`, {
            headers: {
                'X-CSRF-TOKEN': this.csrfToken
            }
        });

        return this.handleResponse(response);
    }

    /**
     * Search messages
     *
     * @param {string} query - Search query
     * @param {Object} filters - Search filters
     * @param {number} page - Page number
     * @returns {Promise<Object>}
     */
    async searchMessages(query, filters = {}, page = 1) {
        const params = new URLSearchParams({
            query: query,
            page: page
        });

        Object.entries(filters).forEach(([key, value]) => {
            if (value) {
                params.append(key, value);
            }
        });

        const response = await fetch(`${this.baseUrl}/search?${params}`, {
            headers: {
                'X-CSRF-TOKEN': this.csrfToken
            }
        });

        return this.handleResponse(response);
    }

    /**
     * Handle API response
     *
     * @param {Response} response - Fetch response
     * @returns {Promise<Object>}
     * @throws {Error} If response is not ok
     */
    async handleResponse(response) {
        if (!response.ok) {
            const error = await response.json().catch(() => ({ message: 'Request failed' }));
            throw new Error(error.message || `HTTP ${response.status}`);
        }

        return response.json();
    }
}

// Export as singleton
window.messageService = new MessageService();
