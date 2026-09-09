"use strict";

(function() {
    // Configuration
    const config = window.chatbotConfig || {};
    const API_URL = config.apiUrl || '/chatbot/response';
    const CSRF_TOKEN = config.csrfToken || '';
    const translations = config.translations || {};

    // DOM Elements
    let toggle, window_, messages, input, form, typing, minimize;

    // State
    let isOpen = false;
    let isLoading = false;
    let messageHistory = [];

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', init);

    function init() {
        // Get DOM elements
        toggle = document.getElementById('chatbot-toggle');
        window_ = document.getElementById('chatbot-window');
        messages = document.getElementById('chatbot-messages');
        input = document.getElementById('chatbot-input');
        form = document.getElementById('chatbot-form');
        typing = document.getElementById('chatbot-typing');
        minimize = document.getElementById('chatbot-minimize');

        if (!toggle || !window_) {
            console.warn('Chatbot widget elements not found');
            return;
        }

        // Event listeners
        toggle.addEventListener('click', toggleChat);
        minimize.addEventListener('click', closeChat);
        form.addEventListener('submit', handleSubmit);

        // Quick question buttons
        document.querySelectorAll('.quick-question').forEach(btn => {
            btn.addEventListener('click', function() {
                const question = this.getAttribute('data-question');
                if (question) {
                    sendMessage(question);
                }
            });
        });

        // Enter key to send
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.dispatchEvent(new Event('submit'));
            }
        });

        // Load message history from session storage
        loadHistory();

        // Auto-open chat after delay if user hasn't interacted
        setTimeout(autoOpenChat, 30000);
    }

    function toggleChat() {
        if (isOpen) {
            closeChat();
        } else {
            openChat();
        }
    }

    function openChat() {
        isOpen = true;
        window_.style.display = 'flex';
        toggle.querySelector('.chatbot-icon-open').style.display = 'none';
        toggle.querySelector('.chatbot-icon-close').style.display = 'block';
        toggle.classList.add('chatbot-open');

        // Hide unread badge
        const unread = document.getElementById('chatbot-unread');
        if (unread) unread.style.display = 'none';

        // Focus input
        setTimeout(() => {
            input.focus();
            scrollToBottom();
        }, 300);

        // Save state
        sessionStorage.setItem('chatbot_opened', 'true');
    }

    function closeChat() {
        isOpen = false;
        window_.style.display = 'none';
        toggle.querySelector('.chatbot-icon-open').style.display = 'block';
        toggle.querySelector('.chatbot-icon-close').style.display = 'none';
        toggle.classList.remove('chatbot-open');
    }

    function autoOpenChat() {
        if (!sessionStorage.getItem('chatbot_auto_opened') && !isOpen) {
            // Show unread indicator instead of auto-opening
            const unread = document.getElementById('chatbot-unread');
            if (unread) {
                unread.style.display = 'flex';
                unread.textContent = '1';
            }
            sessionStorage.setItem('chatbot_auto_opened', 'true');
        }
    }

    function handleSubmit(e) {
        e.preventDefault();
        const message = input.value.trim();
        if (message && !isLoading) {
            sendMessage(message);
        }
    }

    function sendMessage(message) {
        if (isLoading) return;

        // Add user message to UI
        addMessage(message, 'user');
        input.value = '';

        // Hide quick questions after first message
        const quickQuestions = document.querySelector('.chatbot-quick-questions');
        if (quickQuestions) {
            quickQuestions.style.display = 'none';
        }

        // Show typing indicator
        showTyping();
        isLoading = true;

        // Send to API
        fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            hideTyping();
            isLoading = false;

            if (data.success && data.response) {
                addMessage(data.response, 'bot', data.source);
            } else {
                addMessage(data.message || translations.errorMessage || 'Sorry, I could not process your request.', 'bot', 'error');
            }
        })
        .catch(error => {
            console.error('Chatbot error:', error);
            hideTyping();
            isLoading = false;
            addMessage(translations.errorMessage || 'Sorry, something went wrong. Please try again.', 'bot', 'error');
        });
    }

    function addMessage(text, type, source = null) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${type}`;

        const time = getFormattedTime();

        messageDiv.innerHTML = `
            <div class="message-content">
                <p>${escapeHtml(text)}</p>
            </div>
            <span class="message-time">${time}</span>
        `;

        messages.appendChild(messageDiv);
        scrollToBottom();

        // Save to history
        messageHistory.push({ text, type, time, source });
        saveHistory();
    }

    function showTyping() {
        typing.style.display = 'block';
        scrollToBottom();
    }

    function hideTyping() {
        typing.style.display = 'none';
    }

    function scrollToBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function getFormattedTime() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${minutes} ${ampm}`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function saveHistory() {
        try {
            // Keep only last 50 messages
            const historyToSave = messageHistory.slice(-50);
            sessionStorage.setItem('chatbot_history', JSON.stringify(historyToSave));
        } catch (e) {
            console.warn('Could not save chat history:', e);
        }
    }

    function loadHistory() {
        try {
            const saved = sessionStorage.getItem('chatbot_history');
            if (saved) {
                messageHistory = JSON.parse(saved);

                // Clear existing messages except welcome
                const welcomeMsg = messages.querySelector('.chatbot-message.bot');
                const quickQuestions = messages.querySelector('.chatbot-quick-questions');

                // If we have history, hide quick questions and show history
                if (messageHistory.length > 0) {
                    if (quickQuestions) quickQuestions.style.display = 'none';

                    // Remove welcome message if we have history
                    if (welcomeMsg) welcomeMsg.remove();

                    // Add messages from history
                    messageHistory.forEach(msg => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `chatbot-message ${msg.type}`;
                        messageDiv.innerHTML = `
                            <div class="message-content">
                                <p>${escapeHtml(msg.text)}</p>
                            </div>
                            <span class="message-time">${msg.time}</span>
                        `;
                        messages.appendChild(messageDiv);
                    });
                }
            }
        } catch (e) {
            console.warn('Could not load chat history:', e);
        }
    }

    // Expose API for external use
    window.ChatbotWidget = {
        open: openChat,
        close: closeChat,
        toggle: toggleChat,
        send: sendMessage,
        clearHistory: function() {
            messageHistory = [];
            sessionStorage.removeItem('chatbot_history');
            location.reload();
        }
    };
})();
