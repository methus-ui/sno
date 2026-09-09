/**
 * Delivery Stats Filters Module
 * Handles filter UI interactions and URL parameter management
 */

console.log('🟢 delivery-stats-filters.js loaded!');

const DeliveryStatsFilters = (function() {
    'use strict';

    console.log('🟢 DeliveryStatsFilters module initializing...');

    // Current filter state
    let filters = {
        zone_id: 'all',
        module_id: null,
        date: null,
        date_range: 'today',
        status: null
    };

    // Filter change callbacks
    let callbacks = [];

    /**
     * Initialize filters from URL parameters
     */
    function initFromURL() {
        const params = new URLSearchParams(window.location.search);

        filters.zone_id = params.get('zone_id') || 'all';
        filters.module_id = params.get('module_id') || null;
        filters.date = params.get('date') || null;
        filters.date_range = params.get('date_range') || 'today';
        filters.status = params.get('status') || null;

        // Update UI to match URL parameters
        updateFilterUI();
    }

    /**
     * Update filter UI elements to match current state
     */
    function updateFilterUI() {
        // Update zone selector
        const zoneSelect = document.querySelector('#filter-zone');
        if (zoneSelect) {
            zoneSelect.value = filters.zone_id;
        }

        // Update date range selector
        const dateRangeSelect = document.querySelector('#filter-date-range');
        if (dateRangeSelect) {
            dateRangeSelect.value = filters.date_range;
        }

        // Update custom date picker
        const datePicker = document.querySelector('#filter-custom-date');
        if (datePicker && filters.date) {
            datePicker.value = filters.date;
        }

        // Update status selector
        const statusSelect = document.querySelector('#filter-status');
        if (statusSelect && filters.status) {
            statusSelect.value = filters.status;
        }

        // Show/hide custom date picker
        toggleCustomDatePicker(filters.date_range === 'custom');
    }

    /**
     * Show/hide custom date picker based on date range selection
     */
    function toggleCustomDatePicker(show) {
        const customDateContainer = document.querySelector('#custom-date-container');
        if (customDateContainer) {
            customDateContainer.style.display = show ? 'block' : 'none';
        }
    }

    /**
     * Handle zone filter change
     */
    function handleZoneChange(event) {
        filters.zone_id = event.target.value;
        applyFilters();
    }

    /**
     * Handle date range filter change
     */
    function handleDateRangeChange(event) {
        filters.date_range = event.target.value;

        if (filters.date_range === 'custom') {
            toggleCustomDatePicker(true);
            // Don't apply filters yet, wait for custom date selection
            return;
        }

        toggleCustomDatePicker(false);

        // Calculate date based on range
        const today = new Date();
        switch (filters.date_range) {
            case 'today':
                filters.date = formatDate(today);
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(today.getDate() - 1);
                filters.date = formatDate(yesterday);
                break;
            case 'last_7_days':
                const week = new Date(today);
                week.setDate(today.getDate() - 6);
                filters.start_date = formatDate(week);
                filters.end_date = formatDate(today);
                break;
            case 'last_30_days':
                const month = new Date(today);
                month.setDate(today.getDate() - 29);
                filters.start_date = formatDate(month);
                filters.end_date = formatDate(today);
                break;
            case 'last_90_days':
                const quarter = new Date(today);
                quarter.setDate(today.getDate() - 89);
                filters.start_date = formatDate(quarter);
                filters.end_date = formatDate(today);
                break;
        }

        applyFilters();
    }

    /**
     * Handle custom date selection
     */
    function handleCustomDateChange(event) {
        filters.date = event.target.value;
        filters.date_range = 'custom';
        applyFilters();
    }

    /**
     * Handle status filter change
     */
    function handleStatusChange(event) {
        filters.status = event.target.value || null;
        applyFilters();
    }

    /**
     * Handle module filter change (if applicable)
     */
    function handleModuleChange(event) {
        filters.module_id = event.target.value || null;
        applyFilters();
    }

    /**
     * Format date as YYYY-MM-DD
     */
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Apply current filters
     */
    function applyFilters() {
        // Update URL without page reload
        updateURL();

        // Show loading state
        showFilterLoading(true);

        // Trigger all registered callbacks
        callbacks.forEach(callback => {
            try {
                callback(getCleanFilters());
            } catch (error) {
                console.error('Error in filter callback:', error);
            }
        });

        // Hide loading state after a brief delay
        setTimeout(() => showFilterLoading(false), 500);
    }

    /**
     * Get clean filters object (remove null/undefined values)
     */
    function getCleanFilters() {
        const cleanFilters = {};
        Object.keys(filters).forEach(key => {
            if (filters[key] !== null && filters[key] !== undefined && filters[key] !== '') {
                cleanFilters[key] = filters[key];
            }
        });
        return cleanFilters;
    }

    /**
     * Update URL with current filters
     */
    function updateURL() {
        const params = new URLSearchParams();

        Object.keys(filters).forEach(key => {
            if (filters[key] !== null && filters[key] !== undefined && filters[key] !== '') {
                params.set(key, filters[key]);
            }
        });

        const newURL = `${window.location.pathname}?${params.toString()}`;
        window.history.pushState({ filters: filters }, '', newURL);
    }

    /**
     * Show/hide loading state
     */
    function showFilterLoading(isLoading) {
        const loadingIndicator = document.querySelector('#filter-loading');
        if (loadingIndicator) {
            loadingIndicator.style.display = isLoading ? 'inline-block' : 'none';
        }

        // Disable filter controls during loading
        const filterControls = document.querySelectorAll('.filter-control');
        filterControls.forEach(control => {
            control.disabled = isLoading;
        });
    }

    /**
     * Reset all filters to default
     */
    function resetFilters() {
        filters = {
            zone_id: 'all',
            module_id: null,
            date: null,
            date_range: 'today',
            status: null
        };

        updateFilterUI();
        applyFilters();
    }

    /**
     * Register a callback to be called when filters change
     */
    function onChange(callback) {
        if (typeof callback === 'function') {
            callbacks.push(callback);
        }
    }

    /**
     * Initialize filter event listeners
     */
    function initEventListeners() {
        // Zone filter
        const zoneSelect = document.querySelector('#filter-zone');
        if (zoneSelect) {
            zoneSelect.addEventListener('change', handleZoneChange);
        }

        // Date range filter
        const dateRangeSelect = document.querySelector('#filter-date-range');
        if (dateRangeSelect) {
            dateRangeSelect.addEventListener('change', handleDateRangeChange);
        }

        // Custom date picker
        const datePicker = document.querySelector('#filter-custom-date');
        if (datePicker) {
            datePicker.addEventListener('change', handleCustomDateChange);
        }

        // Status filter
        const statusSelect = document.querySelector('#filter-status');
        if (statusSelect) {
            statusSelect.addEventListener('change', handleStatusChange);
        }

        // Module filter
        const moduleSelect = document.querySelector('#filter-module');
        if (moduleSelect) {
            moduleSelect.addEventListener('change', handleModuleChange);
        }

        // Reset button
        const resetButton = document.querySelector('#filter-reset');
        if (resetButton) {
            resetButton.addEventListener('click', function(e) {
                e.preventDefault();
                resetFilters();
            });
        }

        // Handle browser back/forward
        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.filters) {
                filters = event.state.filters;
                updateFilterUI();
                applyFilters();
            }
        });
    }

    /**
     * Initialize the filters module
     */
    function init() {
        // Initialize from URL parameters
        initFromURL();

        // Set up event listeners
        initEventListeners();

        // Apply initial filters
        if (callbacks.length > 0) {
            callbacks.forEach(callback => callback(getCleanFilters()));
        }
    }

    /**
     * Get current filters
     */
    function getCurrentFilters() {
        return getCleanFilters();
    }

    /**
     * Set filters programmatically
     */
    function setFilters(newFilters) {
        filters = { ...filters, ...newFilters };
        updateFilterUI();
        applyFilters();
    }

    // Public API
    return {
        init: init,
        onChange: onChange,
        getCurrentFilters: getCurrentFilters,
        setFilters: setFilters,
        resetFilters: resetFilters
    };
})();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelector('#filter-zone')) {
            DeliveryStatsFilters.init();

            // Connect filters to charts
            DeliveryStatsFilters.onChange(function(filters) {
                if (typeof DeliveryStatsCharts !== 'undefined') {
                    DeliveryStatsCharts.updateCharts(filters);
                }
            });
        }
    });
} else {
    if (document.querySelector('#filter-zone')) {
        DeliveryStatsFilters.init();

        // Connect filters to charts
        DeliveryStatsFilters.onChange(function(filters) {
            if (typeof DeliveryStatsCharts !== 'undefined') {
                DeliveryStatsCharts.updateCharts(filters);
            }
        });
    }
}
