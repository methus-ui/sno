/**
 * SearchBar Component (Alpine.js)
 *
 * Advanced search bar with full-text search, filters, and result highlighting.
 *
 * Usage:
 * <div x-data="searchBar()" x-init="init()">
 *     <input type="text" x-model="query" @input="handleSearch()" placeholder="Search messages...">
 *     <div x-show="showResults" class="search-results">
 *         <!-- Search results rendered here -->
 *     </div>
 * </div>
 *
 * @returns {Object} Alpine.js component
 */
function searchBar() {
    return {
        query: '',
        results: [],
        showResults: false,
        isSearching: false,
        hasMore: false,
        currentPage: 1,
        totalResults: 0,
        debounceTimeout: null,
        filters: {
            conversationId: null,
            dateFrom: null,
            dateTo: null,
            hasAttachments: false,
            hasOrder: false
        },

        /**
         * Initialize component
         */
        init() {
            console.log('SearchBar: Initialized');

            // Close results when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.showResults = false;
                }
            });

            // Keyboard navigation
            this.$el.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.showResults = false;
                    this.query = '';
                }
            });
        },

        /**
         * Handle search input (debounced)
         */
        handleSearch() {
            // Clear existing debounce
            if (this.debounceTimeout) {
                clearTimeout(this.debounceTimeout);
            }

            // Debounce search (300ms)
            this.debounceTimeout = setTimeout(() => {
                this.performSearch();
            }, 300);
        },

        /**
         * Perform search
         */
        async performSearch(page = 1) {
            const trimmedQuery = this.query.trim();

            if (trimmedQuery.length < 2) {
                this.results = [];
                this.showResults = false;
                return;
            }

            this.isSearching = true;
            this.currentPage = page;

            try {
                const params = new URLSearchParams({
                    query: trimmedQuery,
                    page: page
                });

                // Add filters
                if (this.filters.conversationId) {
                    params.append('conversation_id', this.filters.conversationId);
                }
                if (this.filters.dateFrom) {
                    params.append('date_from', this.filters.dateFrom);
                }
                if (this.filters.dateTo) {
                    params.append('date_to', this.filters.dateTo);
                }
                if (this.filters.hasAttachments) {
                    params.append('has_attachments', '1');
                }
                if (this.filters.hasOrder) {
                    params.append('has_order', '1');
                }

                const response = await fetch(`/admin/message/search?${params}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (response.ok) {
                    const data = await response.json();

                    if (data.success) {
                        this.results = data.results.results || [];
                        this.totalResults = data.results.total || 0;
                        this.hasMore = data.results.page < data.results.total_pages;
                        this.showResults = true;

                        console.log(`SearchBar: Found ${this.totalResults} results`);
                    }
                }

            } catch (error) {
                console.error('SearchBar: Search failed', error);
                this.results = [];
                this.showResults = false;

            } finally {
                this.isSearching = false;
            }
        },

        /**
         * Load more results (pagination)
         */
        loadMore() {
            if (!this.hasMore || this.isSearching) {
                return;
            }

            this.performSearch(this.currentPage + 1);
        },

        /**
         * Jump to message in conversation
         */
        jumpToMessage(messageId, conversationId) {
            // Scroll to message and highlight it
            const messageElement = document.getElementById(`message-${messageId}`);

            if (messageElement) {
                messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                messageElement.classList.add('highlight-message');

                setTimeout(() => {
                    messageElement.classList.remove('highlight-message');
                }, 2000);

            } else {
                // Navigate to conversation if message not in current view
                window.location.href = `/admin/message/view/${conversationId}?highlight=${messageId}`;
            }

            this.showResults = false;
        },

        /**
         * Clear search
         */
        clearSearch() {
            this.query = '';
            this.results = [];
            this.showResults = false;
            this.currentPage = 1;
        },

        /**
         * Toggle filter
         */
        toggleFilter(filterName) {
            this.filters[filterName] = !this.filters[filterName];
            this.performSearch();
        },

        /**
         * Set date filter
         */
        setDateFilter(dateFrom, dateTo) {
            this.filters.dateFrom = dateFrom;
            this.filters.dateTo = dateTo;
            this.performSearch();
        },

        /**
         * Clear all filters
         */
        clearFilters() {
            this.filters = {
                conversationId: null,
                dateFrom: null,
                dateTo: null,
                hasAttachments: false,
                hasOrder: false
            };
            this.performSearch();
        },

        /**
         * Get active filters count
         */
        get activeFiltersCount() {
            let count = 0;
            if (this.filters.conversationId) count++;
            if (this.filters.dateFrom || this.filters.dateTo) count++;
            if (this.filters.hasAttachments) count++;
            if (this.filters.hasOrder) count++;
            return count;
        },

        /**
         * Format search result preview
         */
        formatPreview(result) {
            // Truncate message preview
            const maxLength = 150;
            let preview = result.message_preview || result.message_plain || '';

            if (preview.length > maxLength) {
                preview = preview.substring(0, maxLength) + '...';
            }

            return preview;
        },

        /**
         * Format timestamp
         */
        formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diffInHours = (now - date) / (1000 * 60 * 60);

            if (diffInHours < 24) {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else if (diffInHours < 168) { // 7 days
                return date.toLocaleDateString([], { weekday: 'short', hour: '2-digit', minute: '2-digit' });
            } else {
                return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
            }
        }
    };
}

// Make available globally for Alpine.js
window.searchBar = searchBar;

// Inject CSS styles for search bar
(function injectSearchStyles() {
    if (document.getElementById('search-bar-styles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'search-bar-styles';
    style.textContent = `
        .search-bar-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 10px 40px 10px 40px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .search-clear {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
        }

        .search-clear:hover {
            color: #6b7280;
        }

        .search-results {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
        }

        .search-result-item {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: background 0.2s;
        }

        .search-result-item:hover {
            background: #f9fafb;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-preview {
            font-size: 13px;
            color: #374151;
            margin: 4px 0;
            line-height: 1.5;
        }

        .search-result-preview mark {
            background: #fef3c7;
            padding: 2px 4px;
            border-radius: 2px;
        }

        .search-result-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #9ca3af;
            margin-top: 4px;
        }

        .search-filters {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .filter-badge {
            padding: 4px 12px;
            background: #f3f4f6;
            border-radius: 12px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .filter-badge:hover {
            background: #e5e7eb;
        }

        .filter-badge.active {
            background: #dbeafe;
            border-color: #3b82f6;
            color: #1e40af;
        }

        .search-stats {
            padding: 8px 12px;
            font-size: 12px;
            color: #6b7280;
            background: #f9fafb;
            border-bottom: 1px solid #f3f4f6;
        }

        .search-loading {
            padding: 20px;
            text-align: center;
            color: #9ca3af;
        }

        .search-empty {
            padding: 20px;
            text-align: center;
            color: #9ca3af;
        }

        .load-more-btn {
            width: 100%;
            padding: 12px;
            text-align: center;
            background: #f9fafb;
            color: #3b82f6;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
            border: none;
            border-top: 1px solid #f3f4f6;
        }

        .load-more-btn:hover {
            background: #f3f4f6;
        }

        .highlight-message {
            animation: highlight-flash 1s ease-in-out;
        }

        @keyframes highlight-flash {
            0%, 100% {
                background: transparent;
            }
            50% {
                background: #fef3c7;
            }
        }
    `;

    document.head.appendChild(style);
})();
