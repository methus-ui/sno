/**
 * ConversationService
 *
 * Service layer for conversation-related API calls.
 * Handles all HTTP requests for conversation operations.
 *
 * @class ConversationService
 */
class ConversationService {
    constructor() {
        this.baseUrl = '/admin/message';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    }

    /**
     * Get conversations list
     *
     * @param {Object} params - Query parameters
     * @returns {Promise<Object>}
     */
    async getConversations(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${this.baseUrl}/list?${queryString}` : `${this.baseUrl}/list`;

        const response = await fetch(url, {
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        return this.handleResponse(response);
    }

    /**
     * Check for new messages
     *
     * @returns {Promise<Object>}
     */
    async checkNewMessages() {
        const response = await fetch(`${this.baseUrl}/check`, {
            headers: {
                'X-CSRF-TOKEN': this.csrfToken
            }
        });

        return this.handleResponse(response);
    }

    /**
     * Mark conversation as read
     *
     * @param {number} conversationId - Conversation ID
     * @returns {Promise<Object>}
     */
    async markAsRead(conversationId) {
        const response = await fetch(`${this.baseUrl}/mark-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                conversation_id: conversationId
            })
        });

        return this.handleResponse(response);
    }

    /**
     * Archive conversation
     *
     * @param {number} conversationId - Conversation ID
     * @returns {Promise<Object>}
     */
    async archiveConversation(conversationId) {
        const response = await fetch(`${this.baseUrl}/archive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                conversation_id: conversationId
            })
        });

        return this.handleResponse(response);
    }

    /**
     * Assign conversation to me
     *
     * @param {number} conversationId - Conversation ID
     * @returns {Promise<Object>}
     */
    async assignToMe(conversationId) {
        const response = await fetch(`${this.baseUrl}/assign-to-me`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                conversation_id: conversationId
            })
        });

        return this.handleResponse(response);
    }

    /**
     * Bulk mark as read
     *
     * @param {Array<number>} conversationIds - Array of conversation IDs
     * @returns {Promise<Object>}
     */
    async bulkMarkAsRead(conversationIds) {
        const response = await fetch(`${this.baseUrl}/bulk-mark-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                conversation_ids: conversationIds
            })
        });

        return this.handleResponse(response);
    }

    /**
     * Bulk archive
     *
     * @param {Array<number>} conversationIds - Array of conversation IDs
     * @returns {Promise<Object>}
     */
    async bulkArchive(conversationIds) {
        const response = await fetch(`${this.baseUrl}/bulk-archive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                conversation_ids: conversationIds
            })
        });

        return this.handleResponse(response);
    }

    /**
     * Get message templates
     *
     * @returns {Promise<Object>}
     */
    async getTemplates() {
        const response = await fetch(`${this.baseUrl}/templates`, {
            headers: {
                'X-CSRF-TOKEN': this.csrfToken
            }
        });

        return this.handleResponse(response);
    }

    /**
     * Create message template
     *
     * @param {Object} templateData - Template data
     * @returns {Promise<Object>}
     */
    async createTemplate(templateData) {
        const response = await fetch(`${this.baseUrl}/templates`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify(templateData)
        });

        return this.handleResponse(response);
    }

    /**
     * Update message template
     *
     * @param {number} templateId - Template ID
     * @param {Object} templateData - Template data
     * @returns {Promise<Object>}
     */
    async updateTemplate(templateId, templateData) {
        const response = await fetch(`${this.baseUrl}/templates/${templateId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify(templateData)
        });

        return this.handleResponse(response);
    }

    /**
     * Delete message template
     *
     * @param {number} templateId - Template ID
     * @returns {Promise<Object>}
     */
    async deleteTemplate(templateId) {
        const response = await fetch(`${this.baseUrl}/templates/${templateId}`, {
            method: 'DELETE',
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
window.conversationService = new ConversationService();
