/**
 * Messaging System - UX Enhancements
 *
 * Features:
 * - Enhanced search with filters
 * - Filter tabs (All, Unread, Assigned, Archived)
 * - Quick actions (mark read, archive, assign)
 * - Bulk selection and operations
 * - Keyboard shortcuts
 * - Smart timestamps
 * - Skeleton screens
 */

(function(window, document) {
    'use strict';

    // Configuration
    const config = {
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        routes: {
            markRead: '/admin/message/mark-read',
            archive: '/admin/message/archive',
            assignToMe: '/admin/message/assign-to-me',
            bulkMarkRead: '/admin/message/bulk-mark-read',
            bulkArchive: '/admin/message/bulk-archive'
        },
        selectors: {
            conversationItem: '.conversation-item-modern',
            conversationList: '#conversation-list-container',
            searchInput: '#enhanced-search-input',
            filterTabs: '.filter-tab',
            bulkCheckbox: '.conversation-checkbox',
            selectAllCheckbox: '#select-all-conversations',
            bulkActionsBar: '#bulk-actions-bar'
        }
    };

    // State management
    const state = {
        selectedConversations: new Set(),
        currentFilter: 'all',
        currentSearch: '',
        keyboardShortcutsEnabled: true,
        selectedConversationIndex: -1
    };

    /**
     * Initialize all UX enhancements
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupEnhancements);
        } else {
            setupEnhancements();
        }
    }

    /**
     * Setup all enhancement features
     */
    function setupEnhancements() {
        setupQuickActions();
        setupBulkSelection();
        setupKeyboardShortcuts();
        setupSmartTimestamps();
        setupFilterTabs();
        setupEnhancedSearch();
        updateTimestampsPeriodically();
    }

    /**
     * FEATURE: Quick Actions (hover buttons)
     */
    function setupQuickActions() {
        // Add quick action buttons to conversation items
        document.querySelectorAll(config.selectors.conversationItem).forEach(item => {
            if (!item.querySelector('.quick-actions')) {
                addQuickActionsToItem(item);
            }
        });

        // Delegate event listeners
        document.addEventListener('click', function(e) {
            if (e.target.closest('.quick-action-btn')) {
                e.stopPropagation();
                handleQuickAction(e.target.closest('.quick-action-btn'));
            }
        });
    }

    function addQuickActionsToItem(item) {
        const convId = item.dataset.convId;
        const isArchived = item.classList.contains('archived');
        const hasUnread = item.classList.contains('has-unread');

        const actionsHtml = `
            <div class="quick-actions" style="display: none;">
                ${hasUnread ? `
                    <button class="quick-action-btn" data-action="mark-read" data-conv-id="${convId}" title="Mark as read">
                        <i class="tio-checkmark-circle"></i>
                    </button>
                ` : ''}
                <button class="quick-action-btn" data-action="archive" data-conv-id="${convId}" title="${isArchived ? 'Unarchive' : 'Archive'}">
                    <i class="tio-archive"></i>
                </button>
                <button class="quick-action-btn" data-action="assign" data-conv-id="${convId}" title="Assign to me">
                    <i class="tio-user-switch"></i>
                </button>
            </div>
        `;

        item.insertAdjacentHTML('beforeend', actionsHtml);

        // Show/hide on hover
        item.addEventListener('mouseenter', function() {
            const actions = this.querySelector('.quick-actions');
            if (actions) actions.style.display = 'flex';
        });

        item.addEventListener('mouseleave', function() {
            const actions = this.querySelector('.quick-actions');
            if (actions) actions.style.display = 'none';
        });
    }

    function handleQuickAction(btn) {
        const action = btn.dataset.action;
        const convId = btn.dataset.convId;

        switch(action) {
            case 'mark-read':
                markConversationAsRead(convId);
                break;
            case 'archive':
                archiveConversation(convId);
                break;
            case 'assign':
                assignConversationToMe(convId);
                break;
        }
    }

    /**
     * Quick Action: Mark as Read
     */
    function markConversationAsRead(convId) {
        const item = document.querySelector(`[data-conv-id="${convId}"]`);
        if (!item) return;

        // Optimistic UI update
        item.classList.remove('has-unread');
        const badge = item.querySelector('.unread-count-badge');
        if (badge) badge.style.display = 'none';

        // Send AJAX request
        fetch(config.routes.markRead, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({ conversation_id: convId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Marked as read', 'success');
                updateFilterCounts();
            } else {
                // Rollback optimistic update
                item.classList.add('has-unread');
                if (badge) badge.style.display = 'flex';
                showToast(data.message || 'Failed to mark as read', 'error');
            }
        })
        .catch(error => {
            // Rollback on error
            item.classList.add('has-unread');
            if (badge) badge.style.display = 'flex';
            showToast('Connection error. Please try again.', 'error');
        });
    }

    /**
     * Quick Action: Archive
     */
    function archiveConversation(convId) {
        const item = document.querySelector(`[data-conv-id="${convId}"]`);
        if (!item) return;

        const isArchived = item.classList.contains('archived');

        // Optimistic UI: fade out and remove
        item.style.transition = 'opacity 0.3s, transform 0.3s';
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';

        fetch(config.routes.archive, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({
                conversation_id: convId,
                is_archived: !isArchived
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => {
                    item.remove();
                    updateFilterCounts();
                    showToast(isArchived ? 'Conversation unarchived' : 'Conversation archived', 'success', {
                        action: 'Undo',
                        callback: () => archiveConversation(convId) // Toggle back
                    });
                }, 300);
            } else {
                // Rollback
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
                showToast(data.message || 'Failed to archive', 'error');
            }
        })
        .catch(error => {
            // Rollback on error
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
            showToast('Connection error. Please try again.', 'error');
        });
    }

    /**
     * Quick Action: Assign to Me
     */
    function assignConversationToMe(convId) {
        fetch(config.routes.assignToMe, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({ conversation_id: convId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Conversation assigned to you', 'success');
                // Refresh the conversation item to show assignment
                const item = document.querySelector(`[data-conv-id="${convId}"]`);
                if (item && data.html) {
                    item.outerHTML = data.html;
                    setupQuickActions(); // Re-attach event listeners
                }
                updateFilterCounts();
            } else {
                showToast(data.message || 'Failed to assign', 'error');
            }
        })
        .catch(error => {
            showToast('Connection error. Please try again.', 'error');
        });
    }

    /**
     * FEATURE: Bulk Selection
     */
    function setupBulkSelection() {
        // Select all checkbox
        const selectAllCheckbox = document.getElementById('select-all-conversations');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll(config.selectors.bulkCheckbox);
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                    updateSelection(cb.value, this.checked);
                });
            });
        }

        // Individual checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.matches(config.selectors.bulkCheckbox)) {
                updateSelection(e.target.value, e.target.checked);
            }
        });

        // Bulk action buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('#bulk-mark-read')) {
                bulkMarkAsRead();
            } else if (e.target.closest('#bulk-archive')) {
                bulkArchive();
            } else if (e.target.closest('#bulk-cancel')) {
                clearBulkSelection();
            }
        });
    }

    function updateSelection(convId, isSelected) {
        if (isSelected) {
            state.selectedConversations.add(convId);
        } else {
            state.selectedConversations.delete(convId);
        }

        updateBulkActionsBar();
    }

    function updateBulkActionsBar() {
        const bar = document.getElementById('bulk-actions-bar');
        const count = document.getElementById('selected-count');

        if (state.selectedConversations.size > 0) {
            bar.style.display = 'flex';
            count.textContent = state.selectedConversations.size;
        } else {
            bar.style.display = 'none';
        }
    }

    function bulkMarkAsRead() {
        const convIds = Array.from(state.selectedConversations);
        if (convIds.length === 0) return;

        fetch(config.routes.bulkMarkRead, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({ conversation_ids: convIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI for all selected conversations
                convIds.forEach(convId => {
                    const item = document.querySelector(`[data-conv-id="${convId}"]`);
                    if (item) {
                        item.classList.remove('has-unread');
                        const badge = item.querySelector('.unread-count-badge');
                        if (badge) badge.style.display = 'none';
                    }
                });
                showToast(`${convIds.length} conversations marked as read`, 'success');
                clearBulkSelection();
                updateFilterCounts();
            } else {
                showToast(data.message || 'Failed to update conversations', 'error');
            }
        })
        .catch(error => {
            showToast('Connection error. Please try again.', 'error');
        });
    }

    function bulkArchive() {
        const convIds = Array.from(state.selectedConversations);
        if (convIds.length === 0) return;

        fetch(config.routes.bulkArchive, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({ conversation_ids: convIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove all selected conversations from UI
                convIds.forEach(convId => {
                    const item = document.querySelector(`[data-conv-id="${convId}"]`);
                    if (item) {
                        item.style.transition = 'opacity 0.3s';
                        item.style.opacity = '0';
                        setTimeout(() => item.remove(), 300);
                    }
                });
                showToast(`${convIds.length} conversations archived`, 'success');
                clearBulkSelection();
                updateFilterCounts();
            } else {
                showToast(data.message || 'Failed to archive conversations', 'error');
            }
        })
        .catch(error => {
            showToast('Connection error. Please try again.', 'error');
        });
    }

    function clearBulkSelection() {
        state.selectedConversations.clear();
        document.querySelectorAll(config.selectors.bulkCheckbox).forEach(cb => {
            cb.checked = false;
        });
        const selectAll = document.getElementById('select-all-conversations');
        if (selectAll) selectAll.checked = false;
        updateBulkActionsBar();
    }

    /**
     * FEATURE: Keyboard Shortcuts
     */
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            if (!state.keyboardShortcutsEnabled) return;

            // Ignore if typing in input/textarea
            if (['INPUT', 'TEXTAREA'].includes(e.target.tagName) && e.key !== 'Escape') {
                return;
            }

            switch(e.key) {
                case '/':
                    e.preventDefault();
                    focusSearch();
                    break;
                case 'Escape':
                    e.preventDefault();
                    handleEscape();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    navigateConversations('up');
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    navigateConversations('down');
                    break;
                case 'Enter':
                    if (!['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
                        e.preventDefault();
                        openSelectedConversation();
                    }
                    break;
                case 'r':
                    if (!['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
                        e.preventDefault();
                        markSelectedAsRead();
                    }
                    break;
                case 'a':
                    if (!['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
                        e.preventDefault();
                        archiveSelected();
                    }
                    break;
                case '?':
                    e.preventDefault();
                    showKeyboardShortcutsModal();
                    break;
            }

            // Ctrl+K for template selector
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                // TODO: Implement template selector (Phase 4)
            }

            // Ctrl+Enter to send message
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const sendBtn = document.querySelector('.message-send-btn');
                if (sendBtn) sendBtn.click();
            }
        });
    }

    function focusSearch() {
        const searchInput = document.querySelector(config.selectors.searchInput);
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }

    function handleEscape() {
        // Clear search if search is focused
        const searchInput = document.querySelector(config.selectors.searchInput);
        if (searchInput === document.activeElement && searchInput.value) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            return;
        }

        // Close conversation view (if mobile)
        const chatArea = document.querySelector('.chat-area');
        if (chatArea && window.innerWidth < 768) {
            chatArea.style.display = 'none';
        }

        // Blur any focused element
        document.activeElement?.blur();
    }

    function navigateConversations(direction) {
        const items = Array.from(document.querySelectorAll(config.selectors.conversationItem));
        if (items.length === 0) return;

        if (direction === 'down') {
            state.selectedConversationIndex = Math.min(state.selectedConversationIndex + 1, items.length - 1);
        } else {
            state.selectedConversationIndex = Math.max(state.selectedConversationIndex - 1, 0);
        }

        // Remove previous selection highlight
        items.forEach(item => item.classList.remove('keyboard-selected'));

        // Add highlight to current selection
        const selectedItem = items[state.selectedConversationIndex];
        if (selectedItem) {
            selectedItem.classList.add('keyboard-selected');
            selectedItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function openSelectedConversation() {
        const items = Array.from(document.querySelectorAll(config.selectors.conversationItem));
        const selectedItem = items[state.selectedConversationIndex];
        if (selectedItem) {
            selectedItem.click();
        }
    }

    function markSelectedAsRead() {
        const items = Array.from(document.querySelectorAll(config.selectors.conversationItem));
        const selectedItem = items[state.selectedConversationIndex];
        if (selectedItem) {
            const convId = selectedItem.dataset.convId;
            markConversationAsRead(convId);
        }
    }

    function archiveSelected() {
        const items = Array.from(document.querySelectorAll(config.selectors.conversationItem));
        const selectedItem = items[state.selectedConversationIndex];
        if (selectedItem) {
            const convId = selectedItem.dataset.convId;
            archiveConversation(convId);
        }
    }

    function showKeyboardShortcutsModal() {
        const modal = document.getElementById('keyboard-shortcuts-modal');
        if (modal) {
            modal.classList.add('show');
            modal.style.display = 'block';
        }
    }

    /**
     * FEATURE: Smart Timestamps
     */
    function setupSmartTimestamps() {
        updateAllTimestamps();
    }

    function updateAllTimestamps() {
        document.querySelectorAll('.conversation-time').forEach(timeEl => {
            const timestamp = timeEl.dataset.timestamp;
            if (timestamp) {
                timeEl.textContent = formatSmartTimestamp(timestamp);
            }
        });
    }

    function formatSmartTimestamp(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 60) {
            // < 1 hour: "5m", "30m"
            return diffMins <= 0 ? 'now' : `${diffMins}m`;
        } else if (diffHours < 24) {
            // < 24 hours: "2:30 PM"
            return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        } else if (diffDays < 7) {
            // < 7 days: "Mon 3:45 PM"
            return date.toLocaleString('en-US', { weekday: 'short', hour: 'numeric', minute: '2-digit', hour12: true });
        } else {
            // > 7 days: "Feb 15"
            return date.toLocaleString('en-US', { month: 'short', day: 'numeric' });
        }
    }

    function updateTimestampsPeriodically() {
        // Update timestamps every minute
        setInterval(updateAllTimestamps, 60000);
    }

    /**
     * FEATURE: Filter Tabs
     */
    function setupFilterTabs() {
        document.querySelectorAll(config.selectors.filterTabs).forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.dataset.filter;
                switchFilter(filter);
            });
        });
    }

    function switchFilter(filter) {
        state.currentFilter = filter;

        // Update active tab
        document.querySelectorAll(config.selectors.filterTabs).forEach(tab => {
            tab.classList.toggle('active', tab.dataset.filter === filter);
        });

        // Filter conversations
        filterConversationList();
    }

    function filterConversationList() {
        const items = document.querySelectorAll(config.selectors.conversationItem);

        items.forEach(item => {
            let shouldShow = true;

            switch(state.currentFilter) {
                case 'unread':
                    shouldShow = item.classList.contains('has-unread');
                    break;
                case 'assigned':
                    shouldShow = item.querySelector('.assigned-admin-avatar') !== null;
                    break;
                case 'archived':
                    shouldShow = item.classList.contains('archived');
                    break;
                case 'all':
                default:
                    shouldShow = true;
            }

            item.style.display = shouldShow ? 'block' : 'none';
        });

        updateFilterCounts();
    }

    function updateFilterCounts() {
        const items = document.querySelectorAll(config.selectors.conversationItem);
        const counts = {
            all: items.length,
            unread: document.querySelectorAll('.has-unread').length,
            assigned: document.querySelectorAll('.assigned-admin-avatar').length,
            archived: document.querySelectorAll('.archived').length
        };

        // Update count badges
        Object.keys(counts).forEach(filter => {
            const countEl = document.querySelector(`[data-filter="${filter}"] .filter-count`);
            if (countEl) {
                countEl.textContent = `(${counts[filter]})`;
            }
        });
    }

    /**
     * FEATURE: Enhanced Search
     */
    function setupEnhancedSearch() {
        const searchInput = document.querySelector(config.selectors.searchInput);
        const clearBtn = document.querySelector('.search-clear-btn');
        const filterToggle = document.querySelector('.search-filter-toggle');
        const filterPanel = document.querySelector('.search-filter-panel');

        if (searchInput) {
            searchInput.addEventListener('input', debounce(handleSearch, 300));
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (searchInput) {
                    searchInput.value = '';
                    handleSearch();
                }
            });
        }

        if (filterToggle && filterPanel) {
            filterToggle.addEventListener('click', function() {
                filterPanel.classList.toggle('open');
            });
        }
    }

    function handleSearch() {
        const searchInput = document.querySelector(config.selectors.searchInput);
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        state.currentSearch = searchTerm;

        const items = document.querySelectorAll(config.selectors.conversationItem);

        items.forEach(item => {
            const name = item.querySelector('.conversation-name')?.textContent.toLowerCase() || '';
            const preview = item.querySelector('.preview-text')?.textContent.toLowerCase() || '';
            const phone = item.dataset.phone?.toLowerCase() || '';

            const matches = !searchTerm || name.includes(searchTerm) || preview.includes(searchTerm) || phone.includes(searchTerm);

            item.style.display = matches ? 'block' : 'none';

            // Highlight matching text
            if (matches && searchTerm) {
                highlightMatch(item, searchTerm);
            } else {
                removeHighlight(item);
            }
        });

        // Show/hide clear button
        const clearBtn = document.querySelector('.search-clear-btn');
        if (clearBtn) {
            clearBtn.style.display = searchTerm ? 'block' : 'none';
        }
    }

    function highlightMatch(item, term) {
        // TODO: Implement text highlighting (Phase 1 optional)
    }

    function removeHighlight(item) {
        // TODO: Remove text highlighting
    }

    /**
     * UTILITY: Toast Notifications
     */
    function showToast(message, type = 'info', options = {}) {
        // Use existing toast system or create simple one
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }

        // Show undo action if provided
        if (options.action && options.callback) {
            // TODO: Implement undo button in toast (Phase 3)
        }
    }

    /**
     * UTILITY: Debounce
     */
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

    // Expose public API
    window.MessagingUX = {
        init,
        markConversationAsRead,
        archiveConversation,
        assignConversationToMe,
        switchFilter,
        updateFilterCounts,
        clearBulkSelection
    };

    // Auto-initialize
    init();

})(window, document);
