/**
 * ScrollToBottomButton Component (Alpine.js)
 *
 * Shows a floating button when user scrolls up in message list.
 * Clicking the button scrolls back to the bottom (latest messages).
 * Shows unread count badge if there are new messages below.
 *
 * Usage:
 * <div x-data="scrollToBottomButton('messages-container-id')" x-init="init()">
 *     <button
 *         x-show="showButton"
 *         @click="scrollToBottom()"
 *         class="scroll-to-bottom-btn"
 *     >
 *         <i class="tio-chevron-down"></i>
 *         <span x-show="unreadCount > 0" x-text="unreadCount" class="badge"></span>
 *     </button>
 * </div>
 *
 * @param {string} containerId - ID of the scrollable container
 * @returns {Object} Alpine.js component
 */
function scrollToBottomButton(containerId) {
    return {
        containerId: containerId,
        container: null,
        showButton: false,
        unreadCount: 0,
        lastScrollPosition: 0,
        scrollThreshold: 300, // Show button when 300px from bottom

        /**
         * Initialize component
         */
        init() {
            console.log('ScrollToBottomButton: Initialized for container', this.containerId);

            // Get container element
            this.$nextTick(() => {
                this.container = document.getElementById(this.containerId);

                if (!this.container) {
                    console.error('ScrollToBottomButton: Container not found', this.containerId);
                    return;
                }

                // Set up scroll listener
                this.container.addEventListener('scroll', () => {
                    this.handleScroll();
                });

                // Listen for new messages
                window.addEventListener('messaging:new-message', (event) => {
                    this.handleNewMessage(event.detail);
                });

                // Initial check
                this.handleScroll();
            });
        },

        /**
         * Handle scroll event
         */
        handleScroll() {
            if (!this.container) {
                return;
            }

            const scrollTop = this.container.scrollTop;
            const scrollHeight = this.container.scrollHeight;
            const clientHeight = this.container.clientHeight;
            const distanceFromBottom = scrollHeight - scrollTop - clientHeight;

            // Show button if scrolled up more than threshold
            const shouldShow = distanceFromBottom > this.scrollThreshold;

            // Only update if state changed
            if (shouldShow !== this.showButton) {
                this.showButton = shouldShow;

                // Reset unread count when button is hidden (user is at bottom)
                if (!shouldShow) {
                    this.unreadCount = 0;
                }
            }

            this.lastScrollPosition = scrollTop;
        },

        /**
         * Handle new message event
         */
        handleNewMessage(data) {
            // If button is visible (user scrolled up), increment unread count
            if (this.showButton) {
                this.unreadCount++;

                // Animate button to draw attention
                this.animateButton();
            } else {
                // User is at bottom, auto-scroll to show new message
                this.scrollToBottom(true); // Smooth scroll
            }
        },

        /**
         * Scroll to bottom
         *
         * @param {boolean} smooth - Use smooth scrolling
         */
        scrollToBottom(smooth = true) {
            if (!this.container) {
                return;
            }

            this.container.scrollTo({
                top: this.container.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto'
            });

            // Reset unread count
            this.unreadCount = 0;

            // Hide button after scroll completes
            setTimeout(() => {
                this.showButton = false;
            }, smooth ? 500 : 0);
        },

        /**
         * Animate button to draw attention
         */
        animateButton() {
            const buttonElement = this.$el.querySelector('.scroll-to-bottom-btn');
            if (buttonElement) {
                buttonElement.classList.add('new-message-pulse');
                setTimeout(() => {
                    buttonElement.classList.remove('new-message-pulse');
                }, 1000);
            }
        },

        /**
         * Check if user is near bottom
         */
        get isNearBottom() {
            if (!this.container) {
                return true;
            }

            const scrollTop = this.container.scrollTop;
            const scrollHeight = this.container.scrollHeight;
            const clientHeight = this.container.clientHeight;
            const distanceFromBottom = scrollHeight - scrollTop - clientHeight;

            return distanceFromBottom < 100; // Within 100px of bottom
        },

        /**
         * Get button tooltip text
         */
        get tooltipText() {
            if (this.unreadCount > 0) {
                return `${this.unreadCount} new message${this.unreadCount > 1 ? 's' : ''}`;
            }
            return 'Scroll to bottom';
        }
    };
}

// Make available globally for Alpine.js
window.scrollToBottomButton = scrollToBottomButton;

// Inject CSS styles for scroll to bottom button
(function injectScrollToBottomStyles() {
    if (document.getElementById('scroll-to-bottom-styles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'scroll-to-bottom-styles';
    style.textContent = `
        .scroll-to-bottom-btn {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.3s ease;
            z-index: 1000;
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
        }

        /* Show animation */
        .scroll-to-bottom-btn[x-show="true"] {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .scroll-to-bottom-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }

        .scroll-to-bottom-btn:active {
            transform: translateY(0);
        }

        /* Unread count badge */
        .scroll-to-bottom-btn .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff6d6d;
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 6px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
            border: 2px solid white;
        }

        /* Pulse animation for new messages */
        .scroll-to-bottom-btn.new-message-pulse {
            animation: button-pulse 1s ease;
        }

        @keyframes button-pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            }
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .scroll-to-bottom-btn {
                bottom: 80px;
                right: 20px;
                width: 44px;
                height: 44px;
                font-size: 18px;
            }
        }
    `;

    document.head.appendChild(style);
})();
