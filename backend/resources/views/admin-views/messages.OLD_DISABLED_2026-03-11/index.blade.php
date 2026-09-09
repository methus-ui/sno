@extends('layouts.admin.app')

@section('title',translate('Messages'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
/* Modern Chat UI Styles */
.chat-container {
    background: #f8f9fa;
    border-radius: 12px;
    overflow: hidden;
}

.conversation-list-card {
    height: calc(100vh - 200px);
    overflow: hidden;
}

.conversation-list-scroll {
    height: calc(100% - 70px);
    overflow-y: auto;
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
    background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%);
    border-left: 4px solid #007bff;
}

.conversation-item.has-unread {
    background: linear-gradient(135deg, #fff3e0 0%, #fff8e1 100%);
}

.conversation-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.unread-badge {
    position: absolute;
    top: 10px;
    right: 15px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
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

.typing-indicator {
    display: none;
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

/* New Message Popup */
.new-message-popup {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    padding: 15px 20px;
    z-index: 9999;
    max-width: 350px;
    animation: slideIn 0.3s ease;
    cursor: pointer;
    border-left: 4px solid #007bff;
}

.new-message-popup:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.popup-header {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.popup-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

.popup-name {
    font-weight: 600;
    color: #333;
}

.popup-time {
    font-size: 11px;
    color: #999;
    margin-left: auto;
}

.popup-message {
    color: #666;
    font-size: 13px;
    line-height: 1.4;
}

.popup-close {
    position: absolute;
    top: 5px;
    right: 10px;
    background: none;
    border: none;
    font-size: 18px;
    color: #999;
    cursor: pointer;
}

/* Popup container for stacking */
#new-message-popups {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column-reverse;
    gap: 10px;
    pointer-events: none;
}

#new-message-popups .new-message-popup {
    position: relative;
    bottom: auto;
    right: auto;
    pointer-events: auto;
}

/* Quick Replies */
.quick-replies {
    padding: 10px 15px;
    background: #f8f9fa;
    border-top: 1px solid #eee;
}

.quick-reply-btn {
    display: inline-block;
    padding: 6px 12px;
    margin: 3px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 18px;
    font-size: 12px;
    color: #495057;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-reply-btn:hover {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

/* Notification Pulse */
.notification-pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7); }
    70% { box-shadow: 0 0 0 15px rgba(0, 123, 255, 0); }
    100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
}

/* Chat Header */
.chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 12px 12px 0 0;
}

/* Message Bubbles */
.message-incoming {
    background: #f0f2f5;
    border-radius: 18px 18px 18px 4px;
    padding: 12px 16px;
    margin: 8px 0;
    max-width: 75%;
}

.message-outgoing {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-radius: 18px 18px 4px 18px;
    padding: 12px 16px;
    margin: 8px 0;
    max-width: 75%;
    margin-left: auto;
}

/* Read Receipt */
.read-receipt {
    font-size: 11px;
    color: #999;
    text-align: right;
    margin-top: 4px;
}

.read-receipt.seen {
    color: #007bff;
}

/* Empty State */
.empty-chat-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #999;
    padding: 40px;
}

.empty-chat-state img {
    width: 150px;
    opacity: 0.6;
    margin-bottom: 20px;
}

/* Template Dropdown */
.template-dropdown {
    position: absolute;
    bottom: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    box-shadow: 0 -5px 15px rgba(0,0,0,0.1);
}

.template-dropdown.show {
    display: block;
}

.template-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.template-item:hover {
    background: #f0f2f5;
}

.template-item:last-child {
    border-bottom: none;
}

/* Floating Action Buttons */
.chat-fab {
    position: fixed;
    bottom: 80px;
    right: 20px;
    z-index: 1000;
}

.fab-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fab-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

/* Chatbot Assistant Panel */
.assistant-panel {
    position: fixed;
    right: -350px;
    top: 70px;
    width: 340px;
    height: calc(100vh - 90px);
    background: white;
    box-shadow: -5px 0 25px rgba(0,0,0,0.1);
    z-index: 1000;
    transition: right 0.3s ease;
    border-radius: 12px 0 0 12px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.assistant-panel.open {
    right: 0;
}

.assistant-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.assistant-header h5 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
}

.assistant-close {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.assistant-body {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
}

.assistant-section {
    margin-bottom: 20px;
}

.assistant-section-title {
    font-size: 12px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ai-suggestion-box {
    background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    border-left: 3px solid #667eea;
}

.ai-suggestion-text {
    font-size: 13px;
    color: #333;
    line-height: 1.5;
    margin-bottom: 8px;
}

.ai-suggestion-actions {
    display: flex;
    gap: 8px;
}

.ai-use-btn {
    background: #667eea;
    color: white;
    border: none;
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
}

.ai-regenerate-btn {
    background: #e9ecef;
    color: #495057;
    border: none;
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
}

.faq-chip {
    display: block;
    width: 100%;
    text-align: left;
    padding: 10px 12px;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    font-size: 13px;
    color: #495057;
    transition: all 0.2s;
}

.faq-chip:hover {
    border-color: #667eea;
    background: #f8f9ff;
}

.customer-insight {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px;
}

.insight-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    font-size: 13px;
    color: #495057;
    border-bottom: 1px solid #eee;
}

.insight-item:last-child {
    border-bottom: none;
}

.insight-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    border-radius: 50%;
    font-size: 11px;
    color: #667eea;
}

.assistant-toggle {
    position: fixed;
    right: 20px;
    bottom: 150px;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.assistant-toggle:hover {
    transform: scale(1.1);
}

.assistant-toggle i {
    font-size: 20px;
}

/* Conversation Stats */
.conv-stats {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.conv-stat {
    flex: 1;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
}

.conv-stat.green {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.conv-stat.orange {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.conv-stat-value {
    font-size: 20px;
    font-weight: 700;
}

.conv-stat-label {
    font-size: 11px;
    opacity: 0.9;
}
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <img width="24" height="24" src="{{asset('public/assets/admin/img/icons/conversation-icon.png')}}" alt="">
            <h1 class="page-header-title mb-0">{{ translate('messages.conversation_list') }}</h1>
            <span class="badge badge-soft-info" id="total-conversations">{{ $conversations->total() }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.chatbot.settings') }}" class="btn btn-outline-primary btn-sm">
                <i class="tio-android mr-1"></i> {{ translate('Chatbot Settings') }}
            </a>
            <button class="btn btn-primary btn-sm" onclick="openTemplateModal()">
                <i class="tio-document-text mr-1"></i> {{ translate('Templates') }}
            </button>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row g-3">
        <!-- Conversation List -->
        <div class="col-lg-4 col-md-5">
            <div class="card conversation-list-card">
                <div class="card-header border-0 py-3">
                    <div class="input-group input---group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0"><i class="tio-search"></i></span>
                        </div>
                        <input type="text" class="form-control border-left-0 pl-0" id="search-conversations"
                            placeholder="{{ translate('Search conversations...') }}" autocomplete="off">
                    </div>
                </div>
                <div class="conversation-list-scroll" id="conversation-list">
                    @include('admin-views.messages.data')
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="col-lg-8 col-md-7" id="admin-view-conversation">
            <div class="card h-100">
                <div class="empty-chat-state">
                    <img src="{{asset('public/assets/admin/img/icons/conversation-icon.png')}}" alt="">
                    <h4>{{ translate('Select a conversation') }}</h4>
                    <p>{{ translate('Choose a conversation from the list to start messaging') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Message Popup Container -->
<div id="new-message-popups"></div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Message Templates') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h6 class="mb-3">{{ translate('Create New Template') }}</h6>
                    <form id="templateForm">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="templateTitle" name="title"
                                    placeholder="{{ translate('Template Title') }}" required>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="templateContent" name="content"
                                    placeholder="{{ translate('Template Content') }}" required>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">{{ translate('Save') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
                <hr>
                <h6 class="mb-3">{{ translate('Saved Templates') }}</h6>
                <div id="templatesList" class="row"></div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notificationSound" preload="auto">
    <source src="{{asset('public/assets/admin/sounds/notification.mp3')}}" type="audio/mpeg">
    <source src="{{asset('public/assets/admin/sounds/notification.ogg')}}" type="audio/ogg">
</audio>

<!-- Chatbot Assistant Toggle -->
<button class="assistant-toggle" id="assistant-toggle" title="{{ translate('AI Assistant') }}">
    <i class="tio-robot"></i>
</button>

<!-- Chatbot Assistant Panel -->
<div class="assistant-panel" id="assistant-panel">
    <div class="assistant-header">
        <h5><i class="tio-robot mr-2"></i>{{ translate('AI Assistant') }}</h5>
        <button class="assistant-close" id="assistant-close">
            <i class="tio-clear"></i>
        </button>
    </div>
    <div class="assistant-body">
        <!-- Conversation Stats -->
        <div class="assistant-section">
            <div class="assistant-section-title">
                <i class="tio-chart-bar-1"></i> {{ translate('Today\'s Stats') }}
            </div>
            <div class="conv-stats">
                <div class="conv-stat">
                    <div class="conv-stat-value" id="stat-total">{{ $conversations->total() }}</div>
                    <div class="conv-stat-label">{{ translate('Total') }}</div>
                </div>
                <div class="conv-stat green">
                    <div class="conv-stat-value" id="stat-unread">{{ $conversations->where('unread_message_count', '>', 0)->count() }}</div>
                    <div class="conv-stat-label">{{ translate('Unread') }}</div>
                </div>
            </div>
        </div>

        <!-- AI Suggestion -->
        <div class="assistant-section" id="ai-suggestion-section" style="display:none;">
            <div class="assistant-section-title">
                <i class="tio-bulb-outlined"></i> {{ translate('AI Suggestion') }}
            </div>
            <div class="ai-suggestion-box">
                <div class="ai-suggestion-text" id="ai-suggestion-text"></div>
                <div class="ai-suggestion-actions">
                    <button class="ai-use-btn" onclick="useAiSuggestion()">
                        <i class="tio-send mr-1"></i>{{ translate('Use') }}
                    </button>
                    <button class="ai-regenerate-btn" onclick="regenerateAiSuggestion()">
                        <i class="tio-refresh mr-1"></i>{{ translate('Regenerate') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Popular FAQs -->
        <div class="assistant-section">
            <div class="assistant-section-title">
                <i class="tio-help-outlined"></i> {{ translate('Quick Answers') }}
            </div>
            <div id="popular-faqs-container">
                @php
                    $popularFaqs = \App\Models\ChatbotFaq::active()->popular()->limit(5)->get();
                @endphp
                @foreach($popularFaqs as $faq)
                    <button class="faq-chip" onclick="useFaqAnswer('{{ addslashes($faq->answer) }}')">
                        {{ Str::limit($faq->question, 50) }}
                    </button>
                @endforeach
                @if($popularFaqs->count() == 0)
                    <p class="text-muted text-center small">{{ translate('No FAQs available') }}</p>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="assistant-section">
            <div class="assistant-section-title">
                <i class="tio-flash"></i> {{ translate('Quick Actions') }}
            </div>
            <div class="d-grid gap-2">
                <a href="{{ route('admin.chatbot.settings') }}" class="btn btn-outline-primary btn-sm">
                    <i class="tio-settings-outlined mr-1"></i>{{ translate('Chatbot Settings') }}
                </a>
                <a href="{{ route('admin.chatbot.faq.index') }}" class="btn btn-outline-info btn-sm">
                    <i class="tio-help-outlined mr-1"></i>{{ translate('Manage FAQs') }}
                </a>
                <a href="{{ route('admin.chatbot.learning.index') }}" class="btn btn-outline-success btn-sm">
                    <i class="tio-chart-bar-1 mr-1"></i>{{ translate('Learning Dashboard') }}
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script_2')
<script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<script>
"use strict";

// Global variables - start from current time to only catch truly new messages
let lastCheckedTime = Math.floor(Date.now() / 1000);
let checkInterval;
let currentConversationId = null;
let pusher = null;
let channel = null;
let isFirstCheck = true;

// Initialize Pusher for real-time updates
function initPusher() {
    try {
        let pusherHost = "{{ env('PUSHER_HOST', '') }}" || window.location.hostname;
        let useTLS = "{{ env('PUSHER_SCHEME', 'https') }}" === 'https';
        pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
            cluster: "{{ env('PUSHER_APP_CLUSTER', 'mt1') }}",
            wsHost: pusherHost,
            wsPort: {{ env('PUSHER_PORT', 6001) }},
            wssPort: {{ env('PUSHER_PORT', 6001) }},
            forceTLS: useTLS,
            enabledTransports: ['ws', 'wss'],
            disableStats: true
        });

        // Subscribe to admin messages channel
        channel = pusher.subscribe('admin-messages');

        channel.bind('new-message', function(data) {
            console.log('New message received via WebSocket:', data);
            handleNewMessage(data);
        });

        channel.bind('pusher:subscription_succeeded', function() {
            console.log('Successfully subscribed to admin-messages channel');
        });

        channel.bind('pusher:subscription_error', function(error) {
            console.error('Pusher subscription error:', error);
            // Fallback to polling
            startPolling();
        });

    } catch (e) {
        console.warn('Pusher initialization failed, using polling fallback:', e);
        startPolling();
    }
}

// Fallback polling for new messages
function startPolling() {
    if (checkInterval) clearInterval(checkInterval);
    checkInterval = setInterval(checkForNewMessages, 5000);
}

// Track shown popups to avoid duplicates
let shownPopups = new Set();

// Check for new messages (polling fallback)
function checkForNewMessages() {
    $.get({
        url: "{{ route('admin.message.check') }}",
        data: { last_checked: lastCheckedTime },
        success: function(response) {
            console.log('Polling response:', response);
            if (response.new_messages > 0) {
                // Only show popups after the first check (skip existing unread messages)
                if (!isFirstCheck && response.messages && response.messages.length > 0) {
                    response.messages.forEach(function(msg) {
                        let popupKey = msg.conversation_id + '-' + msg.message;
                        if (!shownPopups.has(popupKey)) {
                            shownPopups.add(popupKey);
                            console.log('Showing popup for:', msg);
                            showNewMessagePopup(msg);
                            // Remove from set after 30 seconds
                            setTimeout(() => shownPopups.delete(popupKey), 30000);
                        }
                    });
                    playNotificationSound();
                } else if (isFirstCheck) {
                    console.log('First check - skipping popups for existing ' + response.new_messages + ' unread messages');
                }
                refreshConversationList();
            }
            lastCheckedTime = response.current_time;
            isFirstCheck = false;
        },
        error: function(xhr) {
            console.error("Error checking messages:", xhr);
        }
    });
}

// Handle new message (from WebSocket or polling)
function handleNewMessage(data) {
    playNotificationSound();
    showNewMessagePopup(data);
    refreshConversationList();

    // If we're viewing this conversation, refresh it
    if (currentConversationId && data.conversation_id == currentConversationId) {
        refreshCurrentConversation();
    }
}

// Show popup notification
function showNewMessagePopup(data) {
    console.log('Showing popup for:', data);

    let senderName = data.sender_name || '{{ translate("New Message") }}';
    let messageText = data.message || '{{ translate("New message received") }}';

    // Show in-page popup
    let popupId = 'popup-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    let popup = `
        <div class="new-message-popup" id="${popupId}" onclick="openConversation(${data.conversation_id}, ${data.sender_id})">
            <button class="popup-close" onclick="event.stopPropagation(); closePopup('${popupId}')">&times;</button>
            <div class="popup-header">
                <img src="${data.sender_image || '{{ asset("public/assets/admin/img/160x160/img1.jpg") }}'}" class="popup-avatar" alt="" onerror="this.src='{{ asset("public/assets/admin/img/160x160/img1.jpg") }}'">
                <span class="popup-name">${senderName}</span>
                <span class="popup-time">${data.time || '{{ translate("Just now") }}'}</span>
            </div>
            <div class="popup-message">${messageText.length > 80 ? messageText.substring(0, 80) + '...' : messageText}</div>
        </div>
    `;

    $('#new-message-popups').append(popup);

    // Also show browser notification
    showBrowserNotification(senderName, messageText, data.sender_image);

    // Auto-dismiss after 8 seconds
    setTimeout(function() {
        closePopup(popupId);
    }, 8000);
}

function closePopup(popupId) {
    $('#' + popupId).fadeOut(300, function() {
        $(this).remove();
    });
}

// Play notification sound
function playNotificationSound() {
    try {
        const audio = document.getElementById('notificationSound');
        if (audio) {
            audio.currentTime = 0;
            let playPromise = audio.play();
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    console.warn('Autoplay prevented:', error);
                });
            }
        }
    } catch (e) {
        console.error('Notification sound error:', e);
    }
}

// Refresh conversation list
function refreshConversationList() {
    $.get("{{ route('admin.message.list') }}", function(data) {
        if (data.html) {
            $('#conversation-list').html(data.html);
        }
    });
}

// Open conversation - make it global
window.openConversation = function(conversationId, userId) {
    console.log('Opening conversation:', conversationId, userId);
    currentConversationId = conversationId;
    let url = "{{ url('admin/message/view') }}/" + conversationId + "/" + userId;

    $('.conversation-item').removeClass('active');
    $('#conv-' + conversationId).addClass('active');

    // Show loading state
    $('#admin-view-conversation').html('<div class="card h-100 d-flex align-items-center justify-content-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');

    $.get(url, function(data) {
        console.log('Conversation loaded:', data);
        $('#admin-view-conversation').html(data.view);
        conversationList();

        // Update URL without reload
        let newUrl = "{{ route('admin.message.list') }}" + '?conversation=' + conversationId + '&user=' + userId;
        window.history.pushState('', '', newUrl);
    }).fail(function(xhr) {
        console.error('Failed to load conversation:', xhr);
        $('#admin-view-conversation').html('<div class="card h-100 d-flex align-items-center justify-content-center"><div class="text-danger"><i class="tio-warning mr-2"></i>{{ translate("Failed to load conversation") }}</div></div>');
    });
}

// Initialize conversation list click handlers using event delegation
function conversationList() {
    // Remove any existing handlers first
    $('#conversation-list').off('click', '.view-admin-conv');

    // Use event delegation for dynamic content
    $('#conversation-list').on('click', '.view-admin-conv', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let convId = $(this).data('conv-id');
        let senderId = $(this).data('sender-id');
        console.log('Clicked conversation:', convId, senderId);
        if (convId && senderId) {
            openConversation(convId, senderId);
        }
    });
}

function refreshCurrentConversation() {
    let activeConv = $('.conversation-item.active');
    if (activeConv.length) {
        let convId = activeConv.data('conv-id');
        let userId = activeConv.data('sender-id');
        openConversation(convId, userId);
    }
}

// Search conversations
let searchTimeout;
$('#search-conversations').on('keyup', function() {
    clearTimeout(searchTimeout);
    let query = $(this).val();

    searchTimeout = setTimeout(function() {
        $.get("{{ route('admin.message.list') }}", { key: query }, function(data) {
            $('#conversation-list').html(data.html);
            conversationList();
        });
    }, 300);
});

// Infinite scroll for conversation list
let page = 1;
let loading = false;
$('#conversation-list').scroll(function() {
    if (!loading && $(this).scrollTop() + $(this).innerHeight() >= this.scrollHeight - 50) {
        loading = true;
        page++;

        $.get("{{ route('admin.message.list') }}", { page: page }, function(data) {
            if (data.html && data.html.trim() !== '') {
                $('#conversation-list').append(data.html);
                conversationList();
            }
            loading = false;
        });
    }
});

// Template Management
function openTemplateModal() {
    $('#templateModal').modal('show');
    loadTemplates();
}

function loadTemplates() {
    $.get("{{ route('admin.message.templates') }}", function(response) {
        let html = '';
        if (response.templates && response.templates.length > 0) {
            response.templates.forEach(function(template) {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body p-3">
                                <h6 class="card-title mb-2">${template.title}</h6>
                                <p class="card-text small text-muted mb-2">${template.content.substring(0, 100)}${template.content.length > 100 ? '...' : ''}</p>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-success" onclick="useTemplate(\`${template.content.replace(/`/g, '\\`')}\`)">
                                        <i class="tio-send"></i> {{ translate('Use') }}
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteTemplate(${template.id})">
                                        <i class="tio-delete"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            html = '<div class="col-12 text-center text-muted py-4">{{ translate("No templates yet") }}</div>';
        }
        $('#templatesList').html(html);
    });
}

$('#templateForm').on('submit', function(e) {
    e.preventDefault();

    $.ajax({
        url: "{{ route('admin.message.templates.store') }}",
        type: 'POST',
        data: $(this).serialize(),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#templateForm')[0].reset();
                loadTemplates();
            }
        },
        error: function(xhr) {
            toastr.error('{{ translate("Error saving template") }}');
        }
    });
});

function deleteTemplate(id) {
    if (confirm('{{ translate("Delete this template?") }}')) {
        $.ajax({
            url: '/admin/message/templates/' + id,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    loadTemplates();
                }
            }
        });
    }
}

function useTemplate(content) {
    $('#templateModal').modal('hide');
    if (typeof insertTemplate === 'function') {
        insertTemplate(content);
    } else {
        // If conversation is open, insert into textarea
        let textarea = $('#conv-textarea');
        if (textarea.length) {
            // For emojioneArea
            if (textarea.data('emojioneArea')) {
                textarea.data('emojioneArea').setText(content);
            } else {
                textarea.val(content);
            }
        }
    }
}

// Enable audio on first interaction
document.addEventListener('click', function enableAudio() {
    const audio = document.getElementById('notificationSound');
    if (audio) {
        audio.play().then(() => audio.pause()).catch(() => {});
        audio.currentTime = 0;
    }
    document.removeEventListener('click', enableAudio);
}, { once: true });

// Request browser notification permission
function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

// Show browser notification
function showBrowserNotification(title, body, icon) {
    if ('Notification' in window && Notification.permission === 'granted') {
        try {
            new Notification(title, {
                body: body,
                icon: icon || '{{ asset("public/assets/admin/img/favicon.png") }}',
                tag: 'new-message',
                renotify: true
            });
        } catch (e) {
            console.log('Browser notification not supported');
        }
    }
}

// Initialize on document ready
$(document).ready(function() {
    console.log('Message page initialized');
    console.log('Starting lastCheckedTime:', lastCheckedTime);

    // Request notification permission
    requestNotificationPermission();

    initPusher();
    conversationList();
    checkForNewMessages();

    // Also start polling as backup
    startPolling();

    // Initialize assistant panel
    initAssistantPanel();

    // Debug: Test popup on Ctrl+Shift+T
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'T') {
            console.log('Testing popup...');
            showNewMessagePopup({
                conversation_id: 1,
                sender_id: 1,
                sender_name: 'Test User',
                message: 'This is a test notification popup!',
                time: 'Just now',
                sender_image: null
            });
        }
    });
});

// Assistant Panel Functions
function initAssistantPanel() {
    $('#assistant-toggle').on('click', function() {
        $('#assistant-panel').toggleClass('open');
    });

    $('#assistant-close').on('click', function() {
        $('#assistant-panel').removeClass('open');
    });
}

let currentAiSuggestion = '';

function getAiSuggestion(message) {
    if (!message) return;

    $.ajax({
        url: "{{ route('admin.chatbot.smart-replies') }}",
        method: 'POST',
        data: {
            message: message,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.replies && response.replies.length > 0) {
                currentAiSuggestion = response.replies[0].text;
                $('#ai-suggestion-text').text(currentAiSuggestion);
                $('#ai-suggestion-section').show();
            }
        }
    });
}

function useAiSuggestion() {
    if (currentAiSuggestion && typeof insertTemplate === 'function') {
        insertTemplate(currentAiSuggestion);
    } else {
        let textarea = $('#conv-textarea');
        if (textarea.length) {
            if (textarea.data('emojioneArea')) {
                textarea.data('emojioneArea').setText(currentAiSuggestion);
            } else {
                textarea.val(currentAiSuggestion);
            }
        }
    }
    $('#assistant-panel').removeClass('open');
}

function regenerateAiSuggestion() {
    let lastIncoming = $('.message-wrapper.incoming:last .message-bubble p');
    if (lastIncoming.length) {
        getAiSuggestion(lastIncoming.text().trim());
    }
}

function useFaqAnswer(answer) {
    if (typeof insertTemplate === 'function') {
        insertTemplate(answer);
    } else {
        let textarea = $('#conv-textarea');
        if (textarea.length) {
            if (textarea.data('emojioneArea')) {
                textarea.data('emojioneArea').setText(answer);
            } else {
                textarea.val(answer);
            }
        }
    }
    $('#assistant-panel').removeClass('open');
}

// Update AI suggestion when conversation is opened
$(document).on('conversationOpened', function(e, data) {
    if (data && data.lastMessage) {
        getAiSuggestion(data.lastMessage);
    }
});
</script>
@endpush
