@extends('layouts.vendor.app')

@section('title', translate('Messages'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- Messaging Revamp Configuration --}}
<meta name="pusher-key" content="{{ env('PUSHER_APP_KEY') }}">
<meta name="pusher-cluster" content="{{ env('PUSHER_APP_CLUSTER', 'mt1') }}">
<meta name="messaging-revamp-enabled" content="{{ config('messaging.revamp_enabled', false) ? 'true' : 'false' }}">

<style>
/* Vendor-Specific Modern Chat UI Styles */
.chat-container {
    background: #f8f9fa;
    border-radius: 12px;
    overflow: hidden;
}

.conversation-list-card {
    height: calc(100vh - 200px);
    overflow: hidden;
    border: 1px solid #e0e0e0;
}

.conversation-list-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
}

.conversation-list-scroll {
    height: calc(100% - 140px);
    overflow-y: auto;
    overflow-x: hidden;
}

.conversation-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
    cursor: pointer !important;
    transition: all 0.3s ease;
    position: relative;
    user-select: none;
}

.conversation-item * {
    pointer-events: none;
}

.conversation-item {
    pointer-events: auto;
}

.conversation-item:hover {
    background: #f0f2f5;
}

.conversation-item.active {
    background: #e3f2fd;
    border-left: 4px solid #667eea;
}

.conversation-item.has-unread {
    background: #fff8e6;
}

.conversation-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    position: relative;
}

.unread-badge {
    position: absolute;
    top: 10px;
    right: 15px;
    background: #ff6d6d;
    color: white;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 12px;
    min-width: 22px;
    text-align: center;
}

.online-indicator {
    width: 12px;
    height: 12px;
    background: #28a745;
    border-radius: 50%;
    border: 2px solid white;
    position: absolute;
    bottom: 2px;
    right: 2px;
}

/* Typing Indicator */
.typing-indicator {
    padding: 10px 15px;
    background: #e9ecef;
    border-radius: 18px;
    margin: 10px;
    width: fit-content;
}

.typing-indicator span {
    height: 8px;
    width: 8px;
    background: #666;
    display: inline-block;
    border-radius: 50%;
    margin: 0 2px;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) { animation-delay: 0s; }
.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-5px); }
}

/* Chat Header */
.chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
}

.chat-messages {
    height: calc(100vh - 400px);
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
}

.message-bubble {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 18px;
    margin-bottom: 10px;
    word-wrap: break-word;
}

.message-bubble.sent {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}

.message-bubble.received {
    background: white;
    color: #333;
    border: 1px solid #e0e0e0;
    border-bottom-left-radius: 4px;
}

.message-time {
    font-size: 11px;
    opacity: 0.7;
    margin-top: 5px;
}

/* Message Input */
.message-input-container {
    background: white;
    padding: 15px;
    border-top: 1px solid #e0e0e0;
    border-radius: 0 0 12px 12px;
}

.message-input {
    width: 100%;
    border: 1px solid #e0e0e0;
    border-radius: 24px;
    padding: 12px 20px;
    resize: none;
    min-height: 48px;
    max-height: 120px;
}

.message-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.send-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 24px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.send-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.send-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* Quick Templates */
.templates-section {
    background: white;
    padding: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.template-btn {
    display: inline-block;
    padding: 8px 16px;
    margin: 4px;
    background: white;
    border: 1px solid #667eea;
    border-radius: 18px;
    font-size: 13px;
    color: #667eea;
    cursor: pointer;
    transition: all 0.2s;
}

.template-btn:hover {
    background: #667eea;
    color: white;
}

/* Search Bar */
.search-container {
    padding: 15px;
    background: white;
    border-bottom: 1px solid #e0e0e0;
}

.search-input {
    width: 100%;
    padding: 10px 15px 10px 40px;
    border: 1px solid #e0e0e0;
    border-radius: 24px;
    font-size: 14px;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Bulk Actions Toolbar */
.bulk-actions-toolbar {
    background: #fff3cd;
    padding: 12px 15px;
    border-bottom: 1px solid #ffc107;
    display: none;
}

.bulk-actions-toolbar.active {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bulk-action-btn {
    padding: 6px 12px;
    margin: 0 4px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
}

.bulk-action-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

/* Reactions */
.message-reactions {
    display: flex;
    gap: 4px;
    margin-top: 8px;
}

.reaction-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 12px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.reaction-badge:hover {
    background: #f0f2f5;
    border-color: #667eea;
}

.reaction-badge.user-reacted {
    background: rgba(102, 126, 234, 0.1);
    border-color: #667eea;
    color: #667eea;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

/* Connection State Indicator */
.connection-state {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 8px 16px;
    border-radius: 24px;
    font-size: 12px;
    font-weight: 600;
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 8px;
}

.connection-state.connected {
    background: #d4edda;
    color: #155724;
}

.connection-state.reconnecting {
    background: #fff3cd;
    color: #856404;
}

.connection-state.disconnected {
    background: #f8d7da;
    color: #721c24;
}

.connection-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .conversation-list-card {
        height: 50vh;
    }

    .chat-messages {
        height: 40vh;
    }

    .message-bubble {
        max-width: 85%;
    }

    .bulk-actions-toolbar {
        flex-direction: column;
        gap: 8px;
    }
}

/* Delivery Status Icons */
.delivery-status {
    font-size: 10px;
    margin-left: 5px;
}

.delivery-status.sent { color: #999; }
.delivery-status.delivered { color: #667eea; }
.delivery-status.read { color: #28a745; }

/* Scroll to Bottom Button */
.scroll-to-bottom {
    position: absolute;
    bottom: 80px;
    right: 20px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    display: none;
}

.scroll-to-bottom.visible {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="page-header-title">
                <i class="tio-chat"></i> {{ translate('messages.conversation_list') }}
            </h1>
            <div>
                <button class="btn btn-primary" id="request-notification-permission" style="display: none;">
                    <i class="tio-notifications"></i> Enable Notifications
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Conversations List (Left Panel) -->
        <div class="col-lg-4 col-md-12">
            <div class="card conversation-list-card">
                <!-- Header with Unread Count -->
                <div class="conversation-list-header">
                    <h5 class="mb-0">
                        <i class="tio-chat-outlined"></i> Conversations
                        <span class="badge badge-light ml-2" id="unread-count">0</span>
                    </h5>
                </div>

                <!-- Search Bar with Alpine.js -->
                <div class="search-container" x-data="searchBar()" x-init="init()">
                    <div class="position-relative">
                        <i class="tio-search position-absolute" style="left: 12px; top: 12px; color: #999;"></i>
                        <input
                            type="text"
                            class="search-input"
                            x-model="query"
                            @input="handleSearch()"
                            placeholder="{{ translate('Search conversations...') }}"
                        >
                    </div>

                    <!-- Search Results Dropdown -->
                    <div x-show="showResults" class="search-results-dropdown mt-2" style="display: none;">
                        <template x-if="isSearching">
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary"></div>
                            </div>
                        </template>

                        <template x-if="!isSearching && results.length > 0">
                            <div>
                                <template x-for="result in results" :key="result.id">
                                    <div
                                        class="search-result-item p-2"
                                        @click="jumpToMessage(result.message_id, result.conversation_id)"
                                        style="cursor: pointer; border-bottom: 1px solid #eee;"
                                    >
                                        <div x-text="result.sender_name" class="font-weight-bold"></div>
                                        <div x-html="result.message_preview" class="text-muted small"></div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!isSearching && results.length === 0 && query.length >= 2">
                            <div class="text-center text-muted py-3">
                                No results found
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Bulk Actions Toolbar -->
                <div class="bulk-actions-toolbar" id="bulk-actions-toolbar">
                    <span id="selected-count">0 selected</span>
                    <div>
                        <button class="bulk-action-btn" onclick="bulkMarkAsRead()">
                            <i class="tio-done-vs"></i> Mark as Read
                        </button>
                        <button class="bulk-action-btn" onclick="bulkArchive()">
                            <i class="tio-archive"></i> Archive
                        </button>
                        <button class="bulk-action-btn" onclick="clearSelection()">
                            <i class="tio-clear"></i> Clear
                        </button>
                    </div>
                </div>

                <!-- Conversations Scroll Area -->
                <div class="conversation-list-scroll" id="conversation-list">
                    @include('vendor-views.messages.data')
                </div>
            </div>
        </div>

        <!-- Chat View (Right Panel) -->
        <div class="col-lg-8 col-md-12">
            <div id="view-conversation">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="tio-chat"></i>
                    </div>
                    <h4>{{ translate('messages.view_conversation') }}</h4>
                    <p class="text-muted">{{ translate('Select a conversation to start messaging') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Connection State Indicator (Auto-created by ConnectionStateManager) -->
<!-- Automatically injected if messaging revamp is enabled -->

@endsection

@push('script_2')
<script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

{{-- Alpine.js for reactive components --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

{{-- Messaging Revamp Modules (Conditional Loading) --}}
@if(config('messaging.revamp_enabled', false))
{{-- Core Modules --}}
<script src="{{ asset('public/assets/admin/js/messaging/core/WebSocketManager.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/messaging/core/MessageQueue.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/messaging/core/ConnectionStateManager.js') }}"></script>

{{-- Service Modules --}}
<script src="{{ asset('public/assets/admin/js/messaging/services/MessageService.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/messaging/services/ConversationService.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/messaging/services/PresenceService.js') }}"></script>

{{-- Utility Modules --}}
<script src="{{ asset('public/assets/admin/js/messaging/utils/IndexedDBManager.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/messaging/utils/RetryHandler.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/messaging/utils/NotificationManager.js') }}"></script>

{{-- Alpine.js Components --}}
<script src="{{ asset('public/assets/admin/js/messaging/components/TypingIndicator.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/messaging/components/PresenceIndicator.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/messaging/components/MessageReactions.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/messaging/components/MessageComposer.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/messaging/components/SearchBar.js') }}"></script>

{{-- Main Application Entry Point --}}
<script src="{{ asset('public/assets/admin/js/messaging/main.js') }}"></script>

<script>
"use strict";

/**
 * ==========================================================================
 * VENDOR MESSAGING SYSTEM - REVAMPED VERSION
 * ==========================================================================
 *
 * This is the modern messaging implementation using the revamped architecture.
 * All features from admin panel are available here with vendor-specific theming.
 *
 * Features:
 * - Real-time WebSocket communication
 * - Typing indicators
 * - Presence tracking (online/offline status)
 * - Message reactions
 * - Offline message queueing
 * - Advanced search with filters
 * - Message templates
 * - Bulk operations
 * - Connection state management
 * - Browser notifications with sounds
 *
 * ==========================================================================
 */

// Initialize when messaging app is ready
window.addEventListener('messaging:initialized', function(event) {
    console.log('Vendor Messaging: Revamped system initialized');

    // Request notification permission
    if (window.notificationManager && window.notificationManager.getPermission() === 'default') {
        document.getElementById('request-notification-permission').style.display = 'block';
        document.getElementById('request-notification-permission').addEventListener('click', async function() {
            const permission = await window.notificationManager.requestPermission();
            if (permission === 'granted') {
                this.style.display = 'none';
                window.notificationManager.showNotification('Notifications Enabled', {
                    body: 'You will now receive message notifications'
                });
            }
        });
    }

    // Subscribe to vendor-specific channel
    if (window.messagingWebSocket) {
        window.messagingWebSocket.subscribe('vendor-messages-{{ auth("vendor")->id() }}', {
            'new-message': function(data) {
                console.log('Vendor: New message received', data);
                refreshConversationList();
                updateUnreadCount();
            }
        });
    }

    // Update unread count on page load
    updateUnreadCount();

    // Auto-refresh conversation list every 30 seconds
    setInterval(updateUnreadCount, 30000);
});

// Update unread count
function updateUnreadCount() {
    fetch('{{ route("vendor.message.check") }}', {
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('unread-count').textContent = data.new_messages || 0;
        }
    })
    .catch(error => console.error('Failed to update unread count:', error));
}

// View conversation (enhanced)
function viewConvs(url, id_to_active, conv_id, sender_id) {
    $('.customer-list').removeClass('conv-active');
    $('#' + id_to_active).addClass('conv-active');

    let new_url = "{{ route('vendor.message.list') }}" + '?conversation=' + conv_id + '&user=' + sender_id;

    $.get({
        url: url,
        success: function(data) {
            window.history.pushState('', 'Conversation', new_url);
            $('#view-conversation').html(data.view);

            // Mark as read
            markConversationAsRead(conv_id);

            // Initialize Alpine components in the loaded view
            if (window.Alpine) {
                Alpine.initTree(document.getElementById('view-conversation'));
            }
        },
        error: function(xhr) {
            console.error('Failed to load conversation:', xhr);
            toastr.error('Failed to load conversation');
        }
    });
}

// Mark conversation as read
function markConversationAsRead(conversationId) {
    if (window.conversationService) {
        window.conversationService.markAsRead(conversationId)
            .then(() => {
                updateUnreadCount();
            })
            .catch(error => console.error('Failed to mark as read:', error));
    }
}

// Refresh conversation list
function refreshConversationList() {
    $.get({
        url: '{{ route("vendor.message.list") }}',
        success: function(data) {
            $('#conversation-list').html(data.html);
        }
    });
}

// Pagination
let page = 1;
$('#conversation-list').scroll(function() {
    if ($('#conversation-list').scrollTop() + $('#conversation-list').height() >= $('#conversation-list').height()) {
        page++;
        loadMoreData(page);
    }
});

function loadMoreData(page) {
    $.ajax({
        url: "{{ route('vendor.message.list') }}" + '?page=' + page,
        type: "get",
        beforeSend: function() {}
    })
    .done(function(data) {
        if (data.html === " ") {
            return;
        }
        $("#conversation-list").append(data.html);
    })
    .fail(function() {
        toastr.error('Failed to load more conversations');
    });
}

// Bulk selection
let selectedConversations = new Set();

function toggleConversationSelect(conversationId) {
    if (selectedConversations.has(conversationId)) {
        selectedConversations.delete(conversationId);
    } else {
        selectedConversations.add(conversationId);
    }

    updateBulkActionsToolbar();
}

function updateBulkActionsToolbar() {
    const count = selectedConversations.size;
    const toolbar = document.getElementById('bulk-actions-toolbar');
    const countSpan = document.getElementById('selected-count');

    if (count > 0) {
        toolbar.classList.add('active');
        countSpan.textContent = count + ' selected';
    } else {
        toolbar.classList.remove('active');
    }
}

function bulkMarkAsRead() {
    if (selectedConversations.size === 0) return;

    const conversationIds = Array.from(selectedConversations);

    if (window.conversationService) {
        window.conversationService.bulkMarkAsRead(conversationIds)
            .then(() => {
                toastr.success('Conversations marked as read');
                clearSelection();
                refreshConversationList();
                updateUnreadCount();
            })
            .catch(error => {
                console.error('Bulk mark as read failed:', error);
                toastr.error('Failed to mark as read');
            });
    }
}

function bulkArchive() {
    if (selectedConversations.size === 0) return;

    const conversationIds = Array.from(selectedConversations);

    if (confirm('Archive ' + conversationIds.length + ' conversations?')) {
        if (window.conversationService) {
            window.conversationService.bulkArchive(conversationIds)
                .then(() => {
                    toastr.success('Conversations archived');
                    clearSelection();
                    refreshConversationList();
                })
                .catch(error => {
                    console.error('Bulk archive failed:', error);
                    toastr.error('Failed to archive');
                });
        }
    }
}

function clearSelection() {
    selectedConversations.clear();
    updateBulkActionsToolbar();
    $('.conversation-item').removeClass('selected');
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K: Focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.querySelector('.search-input')?.focus();
    }

    // Esc: Clear search or selection
    if (e.key === 'Escape') {
        if (selectedConversations.size > 0) {
            clearSelection();
        } else {
            document.querySelector('.search-input').value = '';
        }
    }
});

console.log('Vendor Messaging UI: Loaded and ready');
</script>

@else
{{-- Legacy system fallback --}}
<script>
"use strict";

function viewConvs(url, id_to_active, conv_id, sender_id) {
    $('.customer-list').removeClass('conv-active');
    $('#' + id_to_active).addClass('conv-active');
    let new_url = "{{ route('vendor.message.list') }}" + '?conversation=' + conv_id + '&user=' + sender_id;
    $.get({
        url: url,
        success: function(data) {
            window.history.pushState('', 'New Page Title', new_url);
            $('#view-conversation').html(data.view);
        }
    });
}

let page = 1;
$('#conversation-list').scroll(function() {
    if ($('#conversation-list').scrollTop() + $('#conversation-list').height() >= $('#conversation-list').height()) {
        page++;
        loadMoreData(page);
    }
});

function loadMoreData(page) {
    $.ajax({
        url: "{{ route('vendor.message.list') }}" + '?page=' + page,
        type: "get",
        beforeSend: function() {}
    })
    .done(function(data) {
        if (data.html === " ") {
            return;
        }
        $("#conversation-list").append(data.html);
    })
    .fail(function() {
        alert('server not responding...');
    });
}

function fetch_data(page, query) {
    $.ajax({
        url: "{{ route('vendor.message.list') }}" + '?page=' + page + "&key=" + query,
        success: function(data) {
            $('#conversation-list').empty();
            $("#conversation-list").append(data.html);
        }
    })
}

$(document).on('keyup', '#serach', function() {
    let query = $('#serach').val();
    fetch_data(page, query);
});
</script>
@endif

@endpush
