/**
 * Modern POS Search Implementation
 * Features:
 * - AJAX-based (no page refresh)
 * - Debounced search (reduces server load)
 * - Handles backspace/clear properly
 * - Loading states and animations
 * - Keyboard shortcuts
 */

(function() {
    'use strict';
    
    // Configuration
    const SEARCH_CONFIG = {
        debounceDelay: 300,        // Wait 300ms after typing stops
        minSearchLength: 1,        // Start searching after 1 character
        maxSearchLength: 100,      // Maximum search length
    };
    
    // State management
    let searchTimeout = null;
    let currentRequest = null;
    let lastSearchTerm = '';
    
    // DOM Elements
    const searchInput = document.getElementById('datatableSearch');
    const searchForm = document.getElementById('search-form');
    const productGrid = document.getElementById('single-list');
    const searchIcon = document.querySelector('.search-icon');
    const categorySelect = document.getElementById('category');
    const storeSelect = document.getElementById('store_select');
    
    if (!searchInput || !productGrid) {
        console.error('POS Search: Required elements not found');
        return;
    }
    
    /**
     * Get current search parameters
     */
    function getSearchParams() {
        const urlParams = new URLSearchParams(window.location.search);
        return {
            keyword: searchInput.value.trim(),
            category_id: categorySelect ? categorySelect.value : '',
            store_id: urlParams.get('store_id') || storeSelect?.value || '',
            module_id: urlParams.get('module_id') || ''
        };
    }
    
    /**
     * Show loading state
     */
    function showLoading() {
        // Add loading class to grid
        productGrid.classList.add('loading-products');
        
        // Change search icon to spinner
        if (searchIcon) {
            searchIcon.innerHTML = '<i class="tio-refresh rotating"></i>';
        }
        
        // Show skeleton loaders
        showSkeletonLoaders();
    }
    
    /**
     * Hide loading state
     */
    function hideLoading() {
        productGrid.classList.remove('loading-products');
        
        // Restore search icon
        if (searchIcon) {
            searchIcon.innerHTML = '<img width="16" height="16" src="/public/assets/admin/img/icons/search-icon.png" alt="">';
        }
    }
    
    /**
     * Show skeleton loaders while searching
     */
    function showSkeletonLoaders() {
        const skeletonHTML = `
            ${Array(8).fill().map(() => `
                <div class="order--item-box item-box">
                    <div class="skeleton-product-card">
                        <div class="skeleton-image skeleton-loader"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-title skeleton-loader"></div>
                            <div class="skeleton-price skeleton-loader"></div>
                            <div class="skeleton-button skeleton-loader"></div>
                        </div>
                    </div>
                </div>
            `).join('')}
        `;
        productGrid.innerHTML = skeletonHTML;
    }
    
    /**
     * Perform search via AJAX
     */
    function performSearch() {
        const params = getSearchParams();
        const searchTerm = params.keyword;
        
        // Don't search if term hasn't changed
        if (searchTerm === lastSearchTerm) {
            return;
        }
        
        lastSearchTerm = searchTerm;
        
        // Cancel previous request if exists
        if (currentRequest) {
            currentRequest.abort();
        }
        
        // If search is empty, reload to show all products
        if (searchTerm.length === 0) {
            loadAllProducts();
            return;
        }
        
        // Show loading state
        showLoading();
        
        // Create new request
        const xhr = new XMLHttpRequest();
        currentRequest = xhr;
        
        // Build query string
        const queryString = new URLSearchParams(params).toString();
        const url = `/admin/pos/search-products?${queryString}`;
        
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
        
        xhr.onload = function() {
            hideLoading();
            currentRequest = null;
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Update product grid
                        productGrid.innerHTML = response.view;
                        
                        // Show result count
                        showSearchResults(response.count, searchTerm);
                        
                        // Trigger animation
                        animateResults();
                        
                    } else {
                        showError(response.message || 'Search failed');
                    }
                } catch (e) {
                    console.error('POS Search: Parse error', e);
                    showError('Error processing search results');
                }
            } else if (xhr.status === 404) {
                showError('Store not available');
            } else {
                showError('Search request failed');
            }
        };
        
        xhr.onerror = function() {
            hideLoading();
            currentRequest = null;
            showError('Network error occurred');
        };
        
        xhr.send();
    }
    
    /**
     * Load all products (when search is cleared)
     */
    function loadAllProducts() {
        const params = getSearchParams();
        params.keyword = '';
        
        showLoading();
        
        const queryString = new URLSearchParams(params).toString();
        const url = `/admin/pos/single-items?${queryString}`;
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.text())
        .then(html => {
            productGrid.innerHTML = html;
            hideLoading();
            animateResults();
        })
        .catch(error => {
            console.error('POS Search: Load all products error', error);
            hideLoading();
        });
    }
    
    /**
     * Show search result count
     */
    function showSearchResults(count, term) {
        // Remove existing result message
        const existingMsg = document.querySelector('.search-results-message');
        if (existingMsg) {
            existingMsg.remove();
        }
        
        // Create new message
        const message = document.createElement('div');
        message.className = 'search-results-message';
        message.innerHTML = `
            <div class="search-result-info">
                <i class="tio-checkmark-circle-outlined"></i>
                <span>Found <strong>${count}</strong> ${count === 1 ? 'product' : 'products'} for "${term}"</span>
                <button type="button" class="btn-clear-search" onclick="clearSearch()">
                    <i class="tio-clear"></i> Clear
                </button>
            </div>
        `;
        
        productGrid.parentElement.insertBefore(message, productGrid);
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 300);
        }, 3000);
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        toastr.error(message, {
            closeButton: true,
            progressBar: true
        });
    }
    
    /**
     * Animate search results
     */
    function animateResults() {
        const items = productGrid.querySelectorAll('.order--item-box');
        items.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            setTimeout(() => {
                item.style.transition = 'all 0.3s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, index * 50);
        });
    }
    
    /**
     * Debounced search function
     */
    function debouncedSearch() {
        // Clear existing timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Set new timeout
        searchTimeout = setTimeout(() => {
            performSearch();
        }, SEARCH_CONFIG.debounceDelay);
    }
    
    /**
     * Clear search
     */
    window.clearSearch = function() {
        searchInput.value = '';
        lastSearchTerm = '';
        searchInput.focus();
        loadAllProducts();
    };
    
    /**
     * Initialize search functionality
     */
    function initializeSearch() {
        // Prevent form submission
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                performSearch();
            });
        }
        
        // Search input event listeners
        searchInput.addEventListener('input', function(e) {
            const value = e.target.value.trim();
            
            // Validate length
            if (value.length > SEARCH_CONFIG.maxSearchLength) {
                searchInput.value = value.substring(0, SEARCH_CONFIG.maxSearchLength);
                return;
            }
            
            // Trigger debounced search
            debouncedSearch();
        });
        
        // Handle Enter key
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                performSearch();
            }
        });
        
        // Clear button (X) in search input
        searchInput.addEventListener('search', function() {
            if (this.value === '') {
                clearSearch();
            }
        });
        
        // Category change
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                if (searchInput.value.trim()) {
                    performSearch();
                } else {
                    loadAllProducts();
                }
            });
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
            
            // Escape to clear search
            if (e.key === 'Escape' && document.activeElement === searchInput) {
                clearSearch();
            }
        });
        
        // Add search hint
        addSearchHint();
    }
    
    /**
     * Add search hint for keyboard shortcut
     */
    function addSearchHint() {
        const hint = document.createElement('span');
        hint.className = 'search-keyboard-hint';
        hint.innerHTML = '<kbd>Ctrl</kbd> + <kbd>K</kbd>';
        hint.title = 'Quick search shortcut';
        
        const searchContainer = searchInput.parentElement;
        if (searchContainer && !searchContainer.querySelector('.search-keyboard-hint')) {
            searchContainer.appendChild(hint);
        }
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSearch);
    } else {
        initializeSearch();
    }
    
})();