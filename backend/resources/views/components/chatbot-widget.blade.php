@php
    $chatbotEnabled = \App\Models\ChatbotSetting::isEnabled();
    $welcomeMessage = \App\Models\ChatbotSetting::get('welcome_message', 'Hello! How can I help you today?');
    $popularFaqs = \App\Models\ChatbotFaq::active()->popular()->limit(4)->get(['id', 'question']);
@endphp

@if($chatbotEnabled)
<link rel="stylesheet" href="{{ asset('public/assets/chatbot/chatbot-widget.css') }}">

<!-- Chatbot Widget -->
<div id="chatbot-widget" class="chatbot-widget">
    <!-- Chat Toggle Button -->
    <button class="chatbot-toggle" id="chatbot-toggle" aria-label="Open chat">
        <span class="chatbot-icon-open">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </span>
        <span class="chatbot-icon-close" style="display:none;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </span>
        <span class="chatbot-unread" id="chatbot-unread" style="display:none;">1</span>
    </button>

    <!-- Chat Window -->
    <div class="chatbot-window" id="chatbot-window" style="display:none;">
        <!-- Header -->
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <div class="chatbot-avatar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="chatbot-title">{{ translate('Support Assistant') }}</h4>
                    <p class="chatbot-status">
                        <span class="status-dot"></span>
                        {{ translate('Online') }}
                    </p>
                </div>
            </div>
            <button class="chatbot-minimize" id="chatbot-minimize" aria-label="Close chat">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Messages Area -->
        <div class="chatbot-messages" id="chatbot-messages">
            <!-- Welcome Message -->
            <div class="chatbot-message bot">
                <div class="message-content">
                    <p>{{ $welcomeMessage }}</p>
                </div>
                <span class="message-time">{{ translate('Just now') }}</span>
            </div>

            @if($popularFaqs->count() > 0)
            <!-- Quick Questions -->
            <div class="chatbot-quick-questions">
                <p class="quick-label">{{ translate('Popular Questions') }}:</p>
                @foreach($popularFaqs as $faq)
                <button class="quick-question" data-question="{{ $faq->question }}">
                    {{ Str::limit($faq->question, 40) }}
                </button>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Typing Indicator -->
        <div class="chatbot-typing" id="chatbot-typing" style="display:none;">
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <!-- Input Area -->
        <div class="chatbot-input-area">
            <form id="chatbot-form" autocomplete="off">
                <div class="input-wrapper">
                    <input type="text" id="chatbot-input" class="chatbot-input"
                        placeholder="{{ translate('Type your message...') }}" maxlength="500" required>
                    <button type="submit" class="chatbot-send" id="chatbot-send" aria-label="Send">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
            </form>
            <p class="chatbot-powered">
                {{ translate('Powered by AI') }}
            </p>
        </div>
    </div>
</div>

<script>
window.chatbotConfig = {
    apiUrl: "{{ route('chatbot.api.response') }}",
    csrfToken: "{{ csrf_token() }}",
    translations: {
        typingText: "{{ translate('Typing...') }}",
        errorMessage: "{{ translate('Sorry, something went wrong. Please try again.') }}",
        justNow: "{{ translate('Just now') }}"
    }
};
</script>
<script src="{{ asset('public/assets/chatbot/chatbot-widget.js') }}"></script>
@endif
