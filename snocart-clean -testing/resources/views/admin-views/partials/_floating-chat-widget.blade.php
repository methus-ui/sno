{{-- Floating Chat Widget --}}
<div id="floating-chat-widget" style="position: fixed; bottom: 24px; right: 24px; z-index: 9999;">
    {{-- Mini Popup Notification (appears above button) --}}
    <div id="chat-mini-popup"
         style="position: absolute; bottom: 75px; right: 0; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 12px 16px; min-width: 200px; display: none; animation: slideUp 0.3s ease;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite;"></div>
            <div style="flex: 1;">
                <div style="font-weight: 600; font-size: 13px; color: #1f2937;">New Message</div>
                <div id="chat-mini-popup-text" style="font-size: 12px; color: #6b7280; margin-top: 2px;">You have new messages</div>
            </div>
            <button onclick="closeMiniPopup(event)" style="background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 18px; padding: 0; line-height: 1;">×</button>
        </div>
    </div>

    {{-- Chat Button --}}
    <button id="chat-widget-button"
            onclick="openChatWidget()"
            style="position: relative; width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center;"
            onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 20px rgba(102, 126, 234, 0.6)';"
            onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.4)';">

        {{-- Chat Icon --}}
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>

        {{-- Unread Badge --}}
        <span id="chat-unread-badge"
              style="position: absolute; top: -4px; right: -4px; background: #ef4444; color: white; font-size: 11px; font-weight: bold; padding: 2px 6px; border-radius: 10px; min-width: 18px; text-align: center; display: none; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
            0
        </span>

        {{-- Pulse Animation for New Messages --}}
        <span id="chat-pulse"
              style="position: absolute; width: 100%; height: 100%; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); opacity: 0; display: none;"
              class="chat-pulse-animation">
        </span>
    </button>

</div>

{{-- Chat Modal/Popup --}}
<div id="chat-modal"
     style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; display: none; align-items: center; justify-content: center;"
     onclick="if(event.target === this) closeChatWidget()">

    <div style="width: 90%; max-width: 1200px; height: 90vh; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); overflow: hidden; position: relative;"
         onclick="event.stopPropagation()">

        {{-- Modal Header --}}
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 600;">
                💬 Chat System
            </h3>
            <button onclick="closeChatWidget()"
                    style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; transition: background 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                ×
            </button>
        </div>

        {{-- Chat iframe --}}
        <iframe id="chat-iframe"
                src="https://new.snocart.com/chat"
                style="width: 100%; height: calc(100% - 64px); border: none;">
        </iframe>
    </div>
</div>

<style>
@keyframes chatPulse {
    0% {
        transform: scale(1);
        opacity: 0.7;
    }
    50% {
        transform: scale(1.15);
        opacity: 0.4;
    }
    100% {
        transform: scale(1.3);
        opacity: 0;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.chat-pulse-animation {
    animation: chatPulse 1.5s ease-out infinite;
}

#chat-widget-button:active {
    transform: scale(0.95) !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    #chat-modal > div {
        width: 100% !important;
        height: 100% !important;
        border-radius: 0 !important;
        max-width: none !important;
    }

    #floating-chat-widget {
        bottom: 16px !important;
        right: 16px !important;
    }

    #chat-mini-popup {
        min-width: 180px !important;
        font-size: 11px !important;
    }
}
</style>

<script>
// Chat Widget State
let chatUnreadCount = 0;
let chatPollingInterval = null;
let lastMessageTime = Date.now();

// Open Chat Widget
function openChatWidget() {
    const modal = document.getElementById('chat-modal');
    const iframe = document.getElementById('chat-iframe');

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Reload iframe to ensure fresh data
    iframe.src = iframe.src;

    // Reset unread count
    updateUnreadCount(0);

    // Stop pulse animation
    document.getElementById('chat-pulse').style.display = 'none';
}

// Close Chat Widget
function closeChatWidget() {
    const modal = document.getElementById('chat-modal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Update Unread Count
function updateUnreadCount(count) {
    chatUnreadCount = count;
    const badge = document.getElementById('chat-unread-badge');

    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
    }
}

// Play Notification Sound (Simple MP3)
const notificationAudio = new Audio('{{ asset("public/assets/admin/sound/notification.mp3") }}');

function playNotificationSound() {
    try {
        notificationAudio.currentTime = 0; // Reset to start
        notificationAudio.play().catch(err => console.log('Audio play failed:', err));
    } catch (err) {
        console.log('Audio error:', err);
    }
}

// Show Mini Popup on Widget
function showMiniPopup(message) {
    const popup = document.getElementById('chat-mini-popup');
    const popupText = document.getElementById('chat-mini-popup-text');

    if (popup && popupText) {
        popupText.textContent = message;
        popup.style.display = 'block';

        // Auto-hide after 5 seconds
        setTimeout(() => {
            popup.style.display = 'none';
        }, 5000);
    }
}

// Close Mini Popup
function closeMiniPopup(event) {
    event.stopPropagation();
    const popup = document.getElementById('chat-mini-popup');
    if (popup) {
        popup.style.display = 'none';
    }
}

// Show Browser Notification
function showBrowserNotification(title, body) {
    // Check if browser supports notifications
    if (!("Notification" in window)) {
        console.log('This browser does not support notifications');
        return;
    }

    // Request permission if needed
    if (Notification.permission === "granted") {
        const notification = new Notification(title, {
            body: body,
            icon: '{{ asset('assets/admin/img/favicon.png') }}',
            badge: '{{ asset('assets/admin/img/favicon.png') }}',
            tag: 'chat-message',
            requireInteraction: false,
            vibrate: [200, 100, 200]
        });

        notification.onclick = function() {
            window.focus();
            openChatWidget();
            notification.close();
        };
    } else if (Notification.permission !== "denied") {
        Notification.requestPermission().then(function (permission) {
            if (permission === "granted") {
                showBrowserNotification(title, body);
            }
        });
    }
}


// Poll for New Messages
function pollNewMessages() {
    // TEMPORARILY DISABLED: Route admin.chat.check-new-messages not implemented yet
    // This prevents 401 errors from flooding the console
    // TODO: Implement the route /admin/chat/check-new-messages in routes/admin.php
    return;

    /* ORIGINAL CODE - UNCOMMENT WHEN ROUTE IS IMPLEMENTED
    fetch('{{ route("admin.chat.check-new-messages") }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.new_messages > 0 && data.new_messages !== chatUnreadCount) {
            // New messages arrived!
            const newCount = data.new_messages;

            // Update badge
            updateUnreadCount(newCount);

            // Play sound
            playNotificationSound();

            // Show pulse animation
            document.getElementById('chat-pulse').style.display = 'block';

            // Show mini popup on widget
            showMiniPopup(`You have ${newCount} new message${newCount > 1 ? 's' : ''}`);

            // Show browser notification
            showBrowserNotification(
                '💬 New Message',
                `You have ${newCount} unread message${newCount > 1 ? 's' : ''}`
            );

            lastMessageTime = Date.now();
        } else if (data.new_messages === 0 && chatUnreadCount > 0) {
            // Messages were read elsewhere
            updateUnreadCount(0);
            document.getElementById('chat-pulse').style.display = 'none';
        }
    })
    .catch(err => console.log('Polling error:', err));
    */
}

// Add slideIn/slideOut animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Request notification permission
    if ("Notification" in window && Notification.permission === "default") {
        Notification.requestPermission();
    }

    // Start polling every 5 seconds
    pollNewMessages(); // Initial poll
    chatPollingInterval = setInterval(pollNewMessages, 5000);

    // Keyboard shortcut: Ctrl+Shift+C to open chat
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'C') {
            e.preventDefault();
            openChatWidget();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeChatWidget();
        }
    });
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (chatPollingInterval) {
        clearInterval(chatPollingInterval);
    }
});
</script>
