/**
 * Messaging System - Modern UI/UX JavaScript
 * Based on dev.snocart.com design system
 * Version: 2.0
 * Date: 2026-02-27
 */

(function() {
    'use strict';

    // ==================== GLOBAL STATE ====================
    const MessagingUI = {
        currentConversationId: null,
        isLoadingMessages: false,
        isLoadingConversations: false,
        searchTimeout: null,
        activeFilter: 'all',
        conversations: [],
        unreadCount: 0
    };

    // ==================== INITIALIZATION ====================
    function init() {
        setupEventListeners();
        setupSearchInput();
        setupFilterTabs();
        setupInfiniteScroll();
        setupMessageInputHandlers();
        setupTemplateHandlers();
        setupKeyboardShortcuts();
        initializeScrollPosition();
        startRealTimeUpdates();
    }

    // ==================== EVENT LISTENERS ====================
    function setupEventListeners() {
        // Conversation item click
        $(document).on('click', '.conversation-item', function(e) {
            e.preventDefault();
            const conversationId = $(this).data('conversation-id');
            if (conversationId) {
                loadConversation(conversationId);
                markAsActive($(this));

                // Mobile: hide sidebar when conversation selected
                if (window.innerWidth <= 992) {
                    $('.conversation-sidebar').removeClass('mobile-open');
                }
            }
        });

        // Mobile back button
        $(document).on('click', '.chat-back-btn', function() {
            $('.conversation-sidebar').addClass('mobile-open');
        });

        // Message send button
        $(document).on('click', '.chat-send-btn', function(e) {
            e.preventDefault();
            sendMessage();
        });

        // Input actions (attach, emoji, etc.)
        $(document).on('click', '.chat-input-btn[data-action="attach"]', function() {
            $('#message-file-input').click();
        });

        $(document).on('click', '.chat-input-btn[data-action="emoji"]', function() {
            toggleEmojiPicker();
        });

        // File input change
        $(document).on('change', '#message-file-input', function() {
            handleFileSelection(this.files);
        });

        // Clear search
        $(document).on('click', '.conversation-search-clear', function() {
            const $input = $('.conversation-search-input');
            $input.val('').trigger('input').focus();
        });

        // Window resize handler
        $(window).on('resize', debounce(handleWindowResize, 250));
    }

    // ==================== SEARCH ====================
    function setupSearchInput() {
        const $searchInput = $('.conversation-search-input');

        $searchInput.on('input', function() {
            const query = $(this).val().trim();

            // Clear existing timeout
            if (MessagingUI.searchTimeout) {
                clearTimeout(MessagingUI.searchTimeout);
            }

            // Show/hide clear button
            if (query.length > 0) {
                $('.conversation-search-clear').css('opacity', '1').css('pointer-events', 'auto');
            } else {
                $('.conversation-search-clear').css('opacity', '0').css('pointer-events', 'none');
            }

            // Debounce search
            MessagingUI.searchTimeout = setTimeout(function() {
                performSearch(query);
            }, 300);
        });

        // Enter key to search
        $searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                const query = $(this).val().trim();
                performSearch(query);
            }
        });
    }

    function performSearch(query) {
        if (MessagingUI.isLoadingConversations) return;

        MessagingUI.isLoadingConversations = true;
        showConversationLoader();

        $.ajax({
            url: '/admin/message/search',
            method: 'GET',
            data: {
                query: query,
                filter: MessagingUI.activeFilter
            },
            success: function(response) {
                updateConversationList(response.conversations);
                updateFilterCounts(response.counts);
                MessagingUI.isLoadingConversations = false;
                hideConversationLoader();
            },
            error: function() {
                showToast('Search failed', 'error');
                MessagingUI.isLoadingConversations = false;
                hideConversationLoader();
            }
        });
    }

    // ==================== FILTERS ====================
    function setupFilterTabs() {
        $(document).on('click', '.filter-tab', function() {
            const filter = $(this).data('filter');

            // Update active state
            $('.filter-tab').removeClass('active');
            $(this).addClass('active');

            // Update filter and reload
            MessagingUI.activeFilter = filter;
            loadConversations();
        });
    }

    // ==================== CONVERSATION LOADING ====================
    function loadConversations() {
        if (MessagingUI.isLoadingConversations) return;

        MessagingUI.isLoadingConversations = true;
        showConversationLoader();

        const query = $('.conversation-search-input').val().trim();

        $.ajax({
            url: '/admin/message/list',
            method: 'GET',
            data: {
                filter: MessagingUI.activeFilter,
                query: query
            },
            success: function(response) {
                updateConversationList(response.conversations);
                updateFilterCounts(response.counts);
                MessagingUI.isLoadingConversations = false;
                hideConversationLoader();
            },
            error: function() {
                showToast('Failed to load conversations', 'error');
                MessagingUI.isLoadingConversations = false;
                hideConversationLoader();
            }
        });
    }

    function loadConversation(conversationId) {
        if (MessagingUI.isLoadingMessages) return;

        MessagingUI.isLoadingMessages = true;
        MessagingUI.currentConversationId = conversationId;

        // Show loading in chat area
        showMessageLoader();

        $.ajax({
            url: '/admin/message/view/' + conversationId,
            method: 'GET',
            success: function(html) {
                $('#admin-view-conversation').html(html);
                MessagingUI.isLoadingMessages = false;
                scrollToBottom();

                // Update unread count
                updateConversationUnread(conversationId, 0);

                // Focus textarea
                setTimeout(function() {
                    $('.chat-textarea').focus();
                }, 300);
            },
            error: function() {
                showToast('Failed to load conversation', 'error');
                MessagingUI.isLoadingMessages = false;
            }
        });
    }

    // ==================== MESSAGE SENDING ====================
    function sendMessage() {
        const $textarea = $('.chat-textarea');
        const message = $textarea.val().trim();

        if (!message && !hasAttachments()) {
            showToast('Please enter a message', 'warning');
            return;
        }

        if (!MessagingUI.currentConversationId) {
            showToast('No conversation selected', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('message', message);
        formData.append('conversation_id', MessagingUI.currentConversationId);

        // Add attachments
        const files = $('#message-file-input')[0].files;
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        // Disable send button
        $('.chat-send-btn').prop('disabled', true);

        $.ajax({
            url: '/admin/message/send',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Clear input
                $textarea.val('');
                clearAttachments();

                // Add message to chat
                appendMessage(response.message);
                scrollToBottom();

                // Update conversation preview
                updateConversationPreview(MessagingUI.currentConversationId, message);

                showToast('Message sent', 'success');
                $('.chat-send-btn').prop('disabled', false);
            },
            error: function() {
                showToast('Failed to send message', 'error');
                $('.chat-send-btn').prop('disabled', false);
            }
        });
    }

    function setupMessageInputHandlers() {
        // Auto-resize textarea
        $(document).on('input', '.chat-textarea', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';

            // Enable/disable send button
            const hasContent = $(this).val().trim().length > 0 || hasAttachments();
            $('.chat-send-btn').prop('disabled', !hasContent);
        });

        // Enter to send (Shift+Enter for new line)
        $(document).on('keydown', '.chat-textarea', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                if (!$(this).val().trim() && !hasAttachments()) return;
                sendMessage();
            }
        });

        // Typing indicator
        let typingTimeout;
        $(document).on('input', '.chat-textarea', function() {
            clearTimeout(typingTimeout);
            sendTypingIndicator(true);

            typingTimeout = setTimeout(function() {
                sendTypingIndicator(false);
            }, 1000);
        });
    }

    // ==================== TEMPLATES ====================
    function setupTemplateHandlers() {
        // Quick template click
        $(document).on('click', '.quick-template-card', function() {
            const content = $(this).data('template-content');
            if (content) {
                insertTemplate(atob(content));
                animateTemplateCard($(this));
            }
        });

        // Toggle templates visibility
        $(document).on('click', '.quick-templates-toggle', function() {
            const $grid = $('.quick-templates-grid');
            $grid.slideToggle(200);
            $(this).text($grid.is(':visible') ? 'Hide' : 'Show');
        });
    }

    function insertTemplate(content) {
        const $textarea = $('.chat-textarea');
        $textarea.val(content);
        $textarea.trigger('input');
        $textarea.focus();

        // Auto-resize
        $textarea[0].style.height = 'auto';
        $textarea[0].style.height = ($textarea[0].scrollHeight) + 'px';

        showToast('Template inserted', 'success');
    }

    function animateTemplateCard($card) {
        $card.css({
            'transform': 'scale(0.95)',
            'background': '#e3f2fd'
        });

        setTimeout(function() {
            $card.css({
                'transform': '',
                'background': ''
            });
        }, 150);
    }

    // Make insertTemplate globally accessible for template modal
    window.insertTemplate = insertTemplate;

    // ==================== INFINITE SCROLL ====================
    function setupInfiniteScroll() {
        let isLoadingMore = false;
        let currentPage = 1;
        let hasMorePages = true;

        $('.conversation-list-container').on('scroll', function() {
            const $container = $(this);
            const scrollTop = $container.scrollTop();
            const scrollHeight = $container[0].scrollHeight;
            const containerHeight = $container.height();

            // Check if near bottom
            if (scrollTop + containerHeight >= scrollHeight - 100) {
                if (!isLoadingMore && hasMorePages) {
                    isLoadingMore = true;
                    currentPage++;

                    $.ajax({
                        url: '/admin/message/list',
                        method: 'GET',
                        data: {
                            page: currentPage,
                            filter: MessagingUI.activeFilter
                        },
                        success: function(response) {
                            if (response.conversations && response.conversations.length > 0) {
                                appendConversations(response.conversations);
                            } else {
                                hasMorePages = false;
                            }
                            isLoadingMore = false;
                        },
                        error: function() {
                            isLoadingMore = false;
                        }
                    });
                }
            }
        });

        // Messages infinite scroll (scroll up to load older messages)
        $('.chat-messages-container').on('scroll', function() {
            if ($(this).scrollTop() === 0 && !isLoadingMore) {
                loadOlderMessages();
            }
        });
    }

    // ==================== REAL-TIME UPDATES ====================
    function startRealTimeUpdates() {
        // Poll for new messages every 5 seconds
        setInterval(function() {
            if (MessagingUI.currentConversationId) {
                checkNewMessages();
            }
            updateConversationList();
        }, 5000);
    }

    function checkNewMessages() {
        const $lastMessage = $('.message-bubble').last();
        const lastMessageId = $lastMessage.data('message-id') || 0;

        $.ajax({
            url: '/admin/message/check-new',
            method: 'GET',
            data: {
                conversation_id: MessagingUI.currentConversationId,
                last_message_id: lastMessageId
            },
            success: function(response) {
                if (response.has_new && response.messages) {
                    response.messages.forEach(function(message) {
                        appendMessage(message);
                    });
                    scrollToBottom();

                    // Play notification sound
                    playNotificationSound();
                }
            }
        });
    }

    // ==================== UI HELPERS ====================
    function markAsActive($item) {
        $('.conversation-item').removeClass('active');
        $item.addClass('active').removeClass('has-unread');

        // Clear unread badge
        $item.find('.conversation-unread-badge').remove();
    }

    function updateConversationList(conversations) {
        const $container = $('.conversation-list-container');

        if (!conversations || conversations.length === 0) {
            $container.html(getEmptyState('No conversations found'));
            return;
        }

        let html = '';
        conversations.forEach(function(conv) {
            html += renderConversationItem(conv);
        });

        $container.html(html);
    }

    function appendConversations(conversations) {
        const $container = $('.conversation-list-container');

        conversations.forEach(function(conv) {
            $container.append(renderConversationItem(conv));
        });
    }

    function renderConversationItem(conv) {
        const hasUnread = conv.unread_count > 0;
        const isOnline = conv.user.is_online;

        return `
            <div class="conversation-item ${hasUnread ? 'has-unread' : ''}" data-conversation-id="${conv.id}">
                <div class="conversation-avatar-wrapper">
                    <img src="${conv.user.image_full_url}"
                         alt="${conv.user.f_name}"
                         class="conversation-avatar"
                         onerror="this.src='/public/assets/admin/img/160x160/img1.jpg'">
                    <div class="conversation-avatar-badge ${isOnline ? 'online' : 'offline'}"></div>
                </div>
                <div class="conversation-content">
                    <div class="conversation-header">
                        <h6 class="conversation-name">${conv.user.f_name} ${conv.user.l_name}</h6>
                        <span class="conversation-time">${formatTime(conv.last_message_time)}</span>
                    </div>
                    <p class="conversation-preview">${conv.last_message || 'No messages yet'}</p>
                    <div class="conversation-footer">
                        <div class="conversation-meta">
                            ${conv.assigned_admin ? `
                                <div class="conversation-meta-item">
                                    <img src="${conv.assigned_admin.image}"
                                         class="conversation-assigned-avatar"
                                         title="Assigned to ${conv.assigned_admin.name}">
                                </div>
                            ` : ''}
                        </div>
                        ${hasUnread ? `<span class="conversation-unread-badge">${conv.unread_count}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    function appendMessage(message) {
        const html = renderMessage(message);
        $('.chat-messages-container').append(html);
    }

    function renderMessage(message) {
        const isOutgoing = message.is_admin;
        const time = formatTime(message.created_at);

        return `
            <div class="message-group ${isOutgoing ? 'outgoing' : 'incoming'}">
                ${!isOutgoing ? `
                    <div class="message-avatar">
                        <img src="${message.user_image}" alt="">
                    </div>
                ` : '<div class="message-avatar-spacer"></div>'}

                <div class="message-content">
                    <div class="message-bubble ${isOutgoing ? 'outgoing' : 'incoming'}" data-message-id="${message.id}">
                        ${message.message ? `<p>${escapeHtml(message.message)}</p>` : ''}
                        ${message.images ? renderMessageImages(message.images) : ''}
                    </div>
                    <span class="message-time">${time}</span>
                </div>
            </div>
        `;
    }

    function renderMessageImages(images) {
        let html = '<div class="message-images">';
        images.forEach(function(img) {
            html += `
                <a href="${img}" target="_blank" class="message-image-link">
                    <img src="${img}" class="message-image" alt="Attachment">
                </a>
            `;
        });
        html += '</div>';
        return html;
    }

    function updateConversationPreview(conversationId, message) {
        const $conv = $(`.conversation-item[data-conversation-id="${conversationId}"]`);
        if ($conv.length) {
            $conv.find('.conversation-preview').text(message);
            $conv.find('.conversation-time').text('Just now');

            // Move to top
            $conv.prependTo('.conversation-list-container');
        }
    }

    function updateConversationUnread(conversationId, count) {
        const $conv = $(`.conversation-item[data-conversation-id="${conversationId}"]`);

        if (count > 0) {
            $conv.addClass('has-unread');
            $conv.find('.conversation-unread-badge').text(count);
        } else {
            $conv.removeClass('has-unread');
            $conv.find('.conversation-unread-badge').remove();
        }
    }

    function updateFilterCounts(counts) {
        if (!counts) return;

        Object.keys(counts).forEach(function(filter) {
            $(`.filter-tab[data-filter="${filter}"] .filter-tab-count`).text(counts[filter]);
        });
    }

    function scrollToBottom(animated = true) {
        const $container = $('.chat-messages-container');
        if ($container.length) {
            const scrollHeight = $container[0].scrollHeight;
            if (animated) {
                $container.animate({ scrollTop: scrollHeight }, 300);
            } else {
                $container.scrollTop(scrollHeight);
            }
        }
    }

    function initializeScrollPosition() {
        // Scroll to bottom on page load if conversation is open
        setTimeout(function() {
            scrollToBottom(false);
        }, 100);
    }

    // ==================== LOADERS ====================
    function showConversationLoader() {
        const html = `
            <div class="conversation-skeleton">
                <div class="skeleton-avatar"></div>
                <div class="skeleton-content">
                    <div class="skeleton-line short"></div>
                    <div class="skeleton-line long"></div>
                </div>
            </div>
        `.repeat(3);

        $('.conversation-list-container').html(html);
    }

    function hideConversationLoader() {
        $('.conversation-skeleton').remove();
    }

    function showMessageLoader() {
        $('.chat-messages-container').html('<div class="text-center p-5"><i class="tio-loading fa-spin fa-2x"></i></div>');
    }

    // ==================== FILE HANDLING ====================
    function hasAttachments() {
        const files = $('#message-file-input')[0].files;
        return files && files.length > 0;
    }

    function handleFileSelection(files) {
        if (files.length === 0) return;

        // Validate file size (max 5MB per file)
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > 5 * 1024 * 1024) {
                showToast('File size must be less than 5MB', 'error');
                $('#message-file-input').val('');
                return;
            }
        }

        // Show preview
        showFilePreview(files);

        // Enable send button
        $('.chat-send-btn').prop('disabled', false);
    }

    function showFilePreview(files) {
        // Implementation for file preview UI
        console.log('Files selected:', files.length);
    }

    function clearAttachments() {
        $('#message-file-input').val('');
        $('.file-preview-container').remove();
    }

    // ==================== KEYBOARD SHORTCUTS ====================
    function setupKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + K: Focus search
            if ((e.ctrlKey || e.metaKey) && e.which === 75) {
                e.preventDefault();
                $('.conversation-search-input').focus();
            }

            // Esc: Clear search or close modals
            if (e.which === 27) {
                if ($('.conversation-search-input').val()) {
                    $('.conversation-search-clear').click();
                }
            }
        });
    }

    // ==================== UTILITIES ====================
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        // Less than 1 minute
        if (diff < 60000) {
            return 'Just now';
        }

        // Less than 1 hour
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes}m ago`;
        }

        // Less than 24 hours
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `${hours}h ago`;
        }

        // Less than 7 days
        if (diff < 604800000) {
            const days = Math.floor(diff / 86400000);
            return `${days}d ago`;
        }

        // Format as date
        return date.toLocaleDateString();
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function getEmptyState(message) {
        return `
            <div class="chat-empty-state">
                <div class="chat-empty-icon">
                    <i class="tio-chat"></i>
                </div>
                <h5 class="chat-empty-title">${message}</h5>
                <p class="chat-empty-description">Try adjusting your search or filters</p>
            </div>
        `;
    }

    function showToast(message, type = 'info') {
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }
    }

    function sendTypingIndicator(isTyping) {
        if (!MessagingUI.currentConversationId) return;

        // Send typing status to server
        $.ajax({
            url: '/admin/message/typing',
            method: 'POST',
            data: {
                conversation_id: MessagingUI.currentConversationId,
                is_typing: isTyping
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    }

    function toggleEmojiPicker() {
        // Emoji picker implementation
        console.log('Emoji picker toggled');
    }

    function playNotificationSound() {
        const audio = new Audio('/public/assets/admin/sound/notification.mp3');
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Sound play failed:', e));
    }

    function handleWindowResize() {
        // Handle responsive adjustments
        if (window.innerWidth > 992) {
            $('.conversation-sidebar').removeClass('mobile-open');
        }
    }

    function loadOlderMessages() {
        // Implementation for loading older messages
        console.log('Load older messages');
    }

    // ==================== INITIALIZE ON DOCUMENT READY ====================
    $(document).ready(function() {
        init();
        console.log('Messaging Modern UI initialized');
    });

})();
