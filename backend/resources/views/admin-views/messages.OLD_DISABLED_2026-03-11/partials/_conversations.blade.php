<div class="card h-100 chat-card">
    <!-- Chat Header -->
    <div class="card-header chat-header-simple">
        <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center">
                <img class="rounded-circle mr-3 onerror-image"
                    src="{{ $user['image_full_url'] }}"
                    data-onerror-image="{{ asset('public/assets/admin') }}/img/160x160/img1.jpg"
                    style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #e9ecef;"
                    alt="{{ $user['f_name'] }}">
                <div>
                    <h5 class="mb-0 text-capitalize text-dark">{{ $user['f_name'] . ' ' . $user['l_name'] }}</h5>
                    <small class="text-muted"><i class="tio-call-talking mr-1"></i>{{ $user['phone'] }}</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light rounded-circle" data-toggle="dropdown" title="{{ translate('More options') }}">
                        <i class="tio-more-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.users.customer.view', [$user->user->id ?? 0]) }}">
                                <i class="tio-user mr-2"></i>{{ translate('View Customer') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @if($conversation->assignedAdmin)
        <div class="mt-2 pt-2 border-top">
            <small class="text-muted">
                <i class="tio-user-switch mr-1"></i>{{ translate('Assigned to') }}:
                <strong>{{ $conversation->assignedAdmin->f_name }}</strong>
            </small>
        </div>
        @endif
    </div>

    <!-- Messages Container -->
    <div class="card-body chat-messages-container" id="messages-scroll">
        @foreach($convs as $con)
            @if($con->sender_id == $receiver->id)
                {{-- Incoming Message (Customer) --}}
                @if ($con?->order)
                    <div class="order-card-wrapper mb-3">
                        <div class="card order-info-card shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="tio-shopping-basket mr-1"></i>{{ translate('Order') }} #{{ $con?->order?->id }}
                                        </h6>
                                        @php
                                            $statusClass = in_array($con?->order?->order_status, ['delivered']) ? 'success' :
                                                (in_array($con?->order?->order_status, ['canceled', 'failed', 'refunded']) ? 'danger' : 'info');
                                        @endphp
                                        <span class="badge badge-soft-{{ $statusClass }}">
                                            {{ translate($con?->order?->order_status) }}
                                        </span>
                                    </div>
                                    <span class="text-success font-weight-bold">
                                        {{ \App\CentralLogics\Helpers::format_currency($con?->order?->order_amount) }}
                                    </span>
                                </div>
                                @php($delivery_address = json_decode($con?->order?->delivery_address, true))
                                @if($delivery_address)
                                <div class="small text-muted">
                                    <i class="tio-poi mr-1"></i>{{ data_get($delivery_address, 'address') }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <div class="message-wrapper incoming mb-3">
                    <div class="message-bubble incoming">
                        @if($con->message)
                            <p class="mb-0">{{ $con->message }}</p>
                        @endif
                        @if($con->file != null)
                            <div class="message-images mt-2">
                                @foreach ($con->file_full_url as $img)
                                    <a href="{{ $img }}" target="_blank" class="message-image-link">
                                        <img src="{{ $img }}" class="message-image" alt="Attachment">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="message-meta">
                        <small class="text-muted">
                            {{ date('M d, Y', strtotime($con->created_at)) }} {{ translate('at') }}
                            {{ date(config('timeformat'), strtotime($con->created_at)) }}
                        </small>
                    </div>
                </div>
            @else
                {{-- Outgoing Message (Admin) --}}
                <div class="message-wrapper outgoing mb-3">
                    <div class="message-bubble outgoing">
                        @if($con->message)
                            <p class="mb-0">{{ $con->message }}</p>
                        @endif
                        @if($con->file != null)
                            <div class="message-images mt-2">
                                @foreach ($con->file_full_url as $img)
                                    <a href="{{ $img }}" target="_blank" class="message-image-link">
                                        <img src="{{ $img }}" class="message-image" alt="Attachment">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="message-meta text-right">
                        <small class="text-muted">
                            {{ date('M d, Y', strtotime($con->created_at)) }} {{ translate('at') }}
                            {{ date(config('timeformat'), strtotime($con->created_at)) }}
                        </small>
                        @if ($con->is_seen == 1)
                            <i class="tio-done-all text-primary ml-1" title="{{ translate('Seen') }}"></i>
                        @else
                            <i class="tio-done text-muted ml-1" title="{{ translate('Sent') }}"></i>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
        <div id="scroll-here"></div>
    </div>

    <!-- Templates & Suggestions Section - Always Visible -->
    <div class="templates-suggestions-panel">
        <!-- Quick Greeting Section -->
        <div class="panel-section quick-greeting-section">
            <div class="panel-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                <i class="tio-chat mr-2"></i>
                <strong>{{ translate('Quick Greeting') }}</strong>
            </div>
            <div class="panel-content" style="padding: 15px 20px;">
                <button type="button" class="btn btn-block btn-greeting" onclick="sendGreetingMessage()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 500; transition: all 0.3s; box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);">
                    <i class="tio-send mr-2"></i>{{ translate('Send Greeting to Customer') }}
                </button>
                <small class="text-muted d-block mt-2 text-center" style="font-size: 11px;">
                    <i class="tio-info-outlined mr-1"></i>{{ translate('Sends: Assalamualaikum, how can I assist you today?') }}
                </small>
            </div>
        </div>

        <!-- Templates Tab -->
        @if(isset($templates) && $templates->count() > 0)
        <div class="panel-section templates-section">
            <div class="panel-header">
                <i class="tio-document-text mr-2"></i>
                <strong>{{ translate('Quick Templates') }}</strong>
                <button type="button" class="btn btn-sm btn-link ml-auto p-0" onclick="openTemplateModal()">
                    <small>{{ translate('Manage') }}</small>
                </button>
            </div>
            <div class="panel-content">
                <div class="template-list">
                    @foreach($templates as $template)
                    <div class="template-card" data-template-content="{{ base64_encode($template->content) }}">
                        <div class="template-card-title">{{ $template->title }}</div>
                        <div class="template-card-preview">{{ Str::limit($template->content, 60) }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Smart Suggestions -->
        <div class="panel-section suggestions-section" id="smart-suggestions" style="display:none;">
            <div class="panel-header">
                <i class="tio-bulb mr-2"></i>
                <strong>{{ translate('Suggested Replies') }}</strong>
                <button type="button" class="btn btn-sm btn-link ml-auto p-0" onclick="hideSuggestions()">
                    <i class="tio-clear"></i>
                </button>
            </div>
            <div class="panel-content">
                <div id="suggestions-list" class="suggestions-list">
                    <!-- Suggestions will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Message Input -->
    <div class="card-footer chat-input-section">
        <form action="javascript:" method="post" id="reply-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="chat-input-wrapper">
                <div class="upload-preview" id="upload-preview"></div>
                <div class="d-flex align-items-end">
                    <div class="chat-input-container flex-grow-1">
                        <textarea id="conv-textarea" class="chat-input" name="reply" rows="1"
                            placeholder="{{ translate('Type your message...') }}"></textarea>
                    </div>
                    <div class="chat-input-actions ml-2">
                        <label class="action-btn m-0" title="{{ translate('Attach Image') }}">
                            <i class="tio-attachment-diagonal"></i>
                            <input type="file" name="images[]" class="d-none upload-images"
                                id="image-upload-input" multiple accept="image/jpeg, image/png, image/jpg, image/gif" data-max="2">
                        </label>
                        <button type="submit" class="send-btn" id="send-message-btn" title="{{ translate('Send') }}">
                            <i class="tio-send"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Chat Card Styles */
.chat-card {
    display: flex;
    flex-direction: column;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.chat-header-simple {
    background: #ffffff;
    color: #333;
    padding: 16px 24px;
    border-bottom: 2px solid #f0f2f5;
}

.chat-messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
    min-height: 400px;
    max-height: calc(100vh - 450px);
}

/* Message Bubbles */
.message-wrapper {
    display: flex;
    flex-direction: column;
    max-width: 75%;
}

.message-wrapper.incoming {
    align-items: flex-start;
}

.message-wrapper.outgoing {
    align-items: flex-end;
    margin-left: auto;
}

.message-bubble {
    padding: 12px 16px;
    border-radius: 12px;
    word-wrap: break-word;
    max-width: 100%;
    position: relative;
}

.message-bubble.incoming {
    background: white;
    border-radius: 12px 12px 12px 2px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.message-bubble.outgoing {
    background: #007bff;
    color: white;
    border-radius: 12px 12px 2px 12px;
}

.message-meta {
    margin-top: 4px;
    padding: 0 8px;
}

/* Message Images */
.message-images {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.message-image {
    max-width: 200px;
    max-height: 150px;
    border-radius: 8px;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.2s;
}

.message-image:hover {
    transform: scale(1.02);
}

/* Order Card */
.order-info-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: none;
    border-radius: 12px;
    max-width: 350px;
}

/* Templates & Suggestions Panel */
.templates-suggestions-panel {
    border-top: 2px solid #e9ecef;
    background: white;
    max-height: 250px;
    overflow-y: auto;
}

.panel-section {
    border-bottom: 1px solid #f0f2f5;
}

.panel-header {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    font-size: 13px;
    color: #495057;
}

.panel-content {
    padding: 12px 20px;
}

/* Template Cards */
.template-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 180px;
    overflow-y: auto;
}

.template-card {
    padding: 10px 12px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}

.template-card:hover {
    background: #e9ecef;
    border-color: #007bff;
    box-shadow: 0 2px 4px rgba(0,123,255,0.1);
}

.template-card:active {
    transform: scale(0.98);
}

.template-card-title {
    font-size: 13px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 4px;
}

.template-card-preview {
    font-size: 12px;
    color: #6c757d;
    line-height: 1.4;
}

/* Template list scrollbar */
.template-list::-webkit-scrollbar {
    width: 4px;
}

.template-list::-webkit-scrollbar-track {
    background: transparent;
}

.template-list::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 2px;
}

.template-list::-webkit-scrollbar-thumb:hover {
    background: #999;
}

/* Chat Input */
.chat-input-section {
    padding: 15px 20px;
    background: white;
    border-top: 1px solid #eee;
}

.chat-input-wrapper {
    position: relative;
}

.chat-input-container {
    position: relative;
}

.chat-input {
    width: 100%;
    border: 1px solid #ced4da;
    border-radius: 20px;
    padding: 10px 18px;
    resize: none;
    max-height: 100px;
    transition: border-color 0.2s;
    font-size: 14px;
    line-height: 1.5;
}

.chat-input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.15);
}

.chat-input-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s;
    color: #6c757d;
}

.action-btn:hover {
    background: #e9ecef;
    color: #495057;
}

.send-btn {
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 50%;
    background: #007bff;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.send-btn:hover {
    background: #0056b3;
}

.send-btn:active {
    transform: scale(0.95);
}

.send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #6c757d;
}

/* Upload Preview */
.upload-preview {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.upload-preview:empty {
    margin-bottom: 0;
}

.preview-item {
    position: relative;
    width: 60px;
    height: 60px;
}

.preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
}

.preview-remove {
    position: absolute;
    top: -5px;
    right: -5px;
    width: 20px;
    height: 20px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Typing Indicator */
.typing-indicator-wrapper {
    padding: 10px 0;
}

.typing-dots {
    display: inline-flex;
    align-items: center;
    background: #e9ecef;
    padding: 10px 16px;
    border-radius: 18px;
}

.typing-dots span {
    width: 8px;
    height: 8px;
    background: #6c757d;
    border-radius: 50%;
    margin: 0 2px;
    animation: typingBounce 1.4s infinite ease-in-out;
}

.typing-dots span:nth-child(1) { animation-delay: 0s; }
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typingBounce {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-5px); }
}

/* Scrollbar */
.chat-messages-container::-webkit-scrollbar {
    width: 6px;
}

.chat-messages-container::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages-container::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}

.chat-messages-container::-webkit-scrollbar-thumb:hover {
    background: #aaa;
}

/* Suggestions List */
.suggestions-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.suggestion-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.suggestion-item:hover {
    background: #e9ecef;
    border-color: #007bff;
}

.suggestion-badge {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 4px;
    text-transform: uppercase;
    font-weight: 600;
    white-space: nowrap;
    background: #007bff;
    color: white;
}

.suggestion-text {
    flex: 1;
    font-size: 13px;
    color: #212529;
    line-height: 1.4;
}

.suggestion-use-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.2s;
}

.suggestion-use-btn:hover {
    background: #0056b3;
}

.suggestions-loading {
    text-align: center;
    padding: 20px;
    color: #666;
}

.suggestions-loading .spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #e9ecef;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    display: inline-block;
    margin-right: 8px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Loading state for messages */
.message-sending {
    opacity: 0.6;
}

/* Scrollbar for templates panel */
.templates-suggestions-panel::-webkit-scrollbar {
    width: 5px;
}

.templates-suggestions-panel::-webkit-scrollbar-track {
    background: #f8f9fa;
}

.templates-suggestions-panel::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}

/* Quick Greeting Button */
.btn-greeting {
    transition: all 0.3s ease;
}

.btn-greeting:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4) !important;
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
}

.btn-greeting:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3) !important;
}

.btn-greeting:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none !important;
}

.quick-greeting-section {
    border-bottom: 2px solid #667eea;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .chat-header-simple {
        padding: 12px 16px;
    }

    .chat-messages-container {
        padding: 16px;
        max-height: calc(100vh - 500px);
    }

    .message-bubble {
        max-width: 85%;
    }

    .templates-suggestions-panel {
        max-height: 200px;
    }
}
</style>

<script src="{{ asset('public/assets/admin') }}/js/view-pages/common.js"></script>

<script>
"use strict";

// Global variables
let uploadedImages = [];

// Initialize on page load
$(document).ready(function() {
    console.log('Chat initialized');
    console.log('jQuery version:', $.fn.jquery);
    console.log('Form found:', $('#reply-form').length > 0);
    console.log('Textarea found:', $('#conv-textarea').length > 0);
    console.log('Send button found:', $('#send-message-btn').length > 0);
    console.log('CSRF token:', $('meta[name="csrf-token"]').attr('content') ? 'Present' : 'Missing');

    // Scroll to bottom
    scrollToBottom();

    // Update send button initial state
    updateSendButton();

    // Test template cards
    console.log('Template cards found:', $('.template-card').length);
});

// Scroll to bottom function
function scrollToBottom() {
    let container = document.getElementById('messages-scroll');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

// Insert text into textarea
function insertQuickReply(text) {
    let textarea = $('#conv-textarea');
    textarea.val(text);
    textarea.trigger('input');
    textarea.focus();
    console.log('Template inserted:', text.substring(0, 50) + '...');
}

// Global function for templates
function insertTemplate(content) {
    insertQuickReply(content);
}

// Template card click handler - Using event delegation
$(document).on('click', '.template-card', function(e) {
    e.preventDefault();
    let encodedContent = $(this).data('template-content');
    if (!encodedContent) {
        console.error('No template content found');
        return;
    }

    try {
        let content = atob(encodedContent);
        console.log('Inserting template:', content.substring(0, 50));
        insertTemplate(content);

        // Visual feedback
        $(this).css('background', '#007bff');
        $(this).css('color', 'white');
        setTimeout(() => {
            $(this).css('background', '');
            $(this).css('color', '');
        }, 200);
    } catch (error) {
        console.error('Error decoding template:', error);
        toastr.error('{{ translate("Failed to load template") }}');
    }
});

// Image upload preview
$(document).on('change', '.upload-images', function(e) {
    let files = Array.from(e.target.files).slice(0, 2);
    uploadedImages = files;

    let preview = $('#upload-preview');
    preview.empty();

    if (files.length > 0) {
        files.forEach((file, index) => {
            let reader = new FileReader();
            reader.onload = function(e) {
                preview.append(`
                    <div class="preview-item" data-index="${index}">
                        <img src="${e.target.result}" alt="">
                        <button type="button" class="preview-remove" data-index="${index}">&times;</button>
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        });
    }

    // Update send button state
    updateSendButton();
});

// Remove image handler
$(document).on('click', '.preview-remove', function() {
    let index = $(this).data('index');
    uploadedImages.splice(index, 1);
    $(this).closest('.preview-item').remove();

    // Update file input
    let dt = new DataTransfer();
    uploadedImages.forEach(file => dt.items.add(file));
    let fileInput = $('.upload-images')[0];
    if (fileInput) {
        fileInput.files = dt.files;
    }

    // Update send button state
    updateSendButton();
});

// Smart Suggestions functionality
let lastCustomerMessage = null;

function fetchSmartSuggestions(message) {
    if (!message || message === lastCustomerMessage) return;
    lastCustomerMessage = message;

    // Show loading
    $('#smart-suggestions').show();
    $('#suggestions-list').html('<div class="suggestions-loading"><span class="spinner"></span>{{ translate("Getting suggestions...") }}</div>');

    $.ajax({
        url: "{{ route('admin.chatbot.smart-replies') }}",
        method: 'POST',
        data: {
            message: message,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.replies && response.replies.length > 0) {
                renderSuggestions(response.replies);
            } else {
                // Try template suggestions
                fetchTemplateSuggestions(message);
            }
        },
        error: function() {
            fetchTemplateSuggestions(message);
        }
    });
}

function fetchTemplateSuggestions(message) {
    $.ajax({
        url: "{{ route('admin.chatbot.suggest-templates') }}",
        method: 'POST',
        data: {
            message: message,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.templates && response.templates.length > 0) {
                let replies = response.templates.map(function(t) {
                    return { text: t.content, source: 'template', confidence: 'medium' };
                });
                renderSuggestions(replies);
            } else {
                $('#smart-suggestions').hide();
            }
        },
        error: function() {
            $('#smart-suggestions').hide();
        }
    });
}

function renderSuggestions(replies) {
    let html = '';
    replies.forEach(function(reply) {
        let badgeLabel = reply.source === 'ai' ? 'AI' : (reply.source === 'faq' ? 'FAQ' : 'Template');

        html += `
            <div class="suggestion-item" onclick="useSuggestion(this)">
                <span class="suggestion-badge">${badgeLabel}</span>
                <span class="suggestion-text">${escapeHtml(reply.text)}</span>
                <button type="button" class="suggestion-use-btn">{{ translate('Use') }}</button>
            </div>
        `;
    });
    $('#suggestions-list').html(html);
}

function useSuggestion(element) {
    let text = $(element).find('.suggestion-text').text().trim();
    insertQuickReply(text);
    hideSuggestions();
}

function hideSuggestions() {
    $('#smart-suggestions').slideUp(200);
}

// Utility functions
function escapeHtml(text) {
    let div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add rotating animation for loading
if (!document.getElementById('rotating-animation-style')) {
    $('<style id="rotating-animation-style">.tio-reload { animation: rotate 1s linear infinite; } @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>').appendTo('head');
}

// Auto-fetch suggestions for the last customer message
$(document).ready(function() {
    // Find the last incoming (customer) message
    let lastIncoming = $('.message-wrapper.incoming:last .message-bubble p');
    if (lastIncoming.length) {
        let msg = lastIncoming.text().trim();
        if (msg.length >= 3) {
            fetchSmartSuggestions(msg);
        }
    }
});

// Send greeting message function
function sendGreetingMessage() {
    let adminName = "{{ auth('admin')->user()->f_name ?? 'Admin' }}";
    let greetingMessage = `Assalamualaikum, I'm ${adminName}. How can I assist you today?`;

    let textarea = $('#conv-textarea');
    let button = $('.btn-greeting');

    // Visual feedback
    button.prop('disabled', true);
    button.html('<i class="tio-reload mr-2"></i>{{ translate("Sending...") }}');

    // Insert greeting message
    textarea.val(greetingMessage);
    textarea.trigger('input');

    // Wait a moment for visual feedback, then submit
    setTimeout(function() {
        $('#reply-form').submit();

        // Reset button after submission
        setTimeout(function() {
            button.prop('disabled', false);
            button.html('<i class="tio-send mr-2"></i>{{ translate("Send Greeting to Customer") }}');
        }, 1000);
    }, 300);
}

// Enable/disable send button based on content
function updateSendButton() {
    let hasContent = $('#conv-textarea').val().trim().length > 0 || uploadedImages.length > 0;
    $('#send-message-btn').prop('disabled', !hasContent);
}

// Auto-resize textarea
$(document).on('input', '#conv-textarea', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
    updateSendButton();
});

// Handle Enter key to send message (Shift+Enter for new line)
$(document).on('keydown', '#conv-textarea', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        let submitBtn = $('#send-message-btn');
        if (!submitBtn.prop('disabled')) {
            $('#reply-form').submit();
        }
    }
});

// Form submission with improved UX
$(document).on('submit', '#reply-form', function(e) {
    e.preventDefault();

    console.log('Form submitted');

    let textarea = $('#conv-textarea');
    let message = textarea.val().trim();
    let submitBtn = $('#send-message-btn');
    let messagesContainer = $('#messages-scroll');

    // Validate message
    if (!message && uploadedImages.length === 0) {
        toastr.warning('{{ translate("Please enter a message or attach an image") }}');
        return;
    }

    // Disable button and show loading state
    submitBtn.prop('disabled', true);
    let originalHtml = submitBtn.html();
    submitBtn.html('<i class="tio-reload"></i>');

    // Prepare form data
    let formData = new FormData(this);

    console.log('Sending message to:', '{{ route("admin.message.store", [$user->user_id]) }}');

    $.ajax({
        url: '{{ route("admin.message.store", [$user->user_id]) }}',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(data) {
            console.log('Message sent successfully', data);

            if (data.errors && data.errors.length > 0) {
                toastr.error('{{ translate("Please enter a message or attach an image") }}');
                submitBtn.prop('disabled', false);
                submitBtn.html(originalHtml);
            } else {
                toastr.success('{{ translate("Message sent") }}');

                // Update conversation view
                if (data.view) {
                    $('#admin-view-conversation').html(data.view);
                } else {
                    // Reload the page if view is not returned
                    location.reload();
                }

                // Update conversation list if function exists
                if (typeof conversationList === 'function') {
                    conversationList();
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error sending message:', status, error, xhr);

            let errorMsg = '{{ translate("Failed to send message. Please try again.") }}';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                console.error('Response:', xhr.responseText);
            }

            toastr.error(errorMsg);

            // Reset button
            submitBtn.prop('disabled', false);
            submitBtn.html(originalHtml);
        }
    });
});
</script>
