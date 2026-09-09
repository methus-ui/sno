/**
 * Delivery Stats Charts Module
 * Handles all ApexCharts initialization and updates for delivery stats dashboard
 */

console.log('🔵 delivery-stats-charts.js loaded!');
console.log('🔵 ApexCharts available:', typeof ApexCharts);

const DeliveryStatsCharts = (function() {
    'use strict';

    console.log('🔵 DeliveryStatsCharts module initializing...');

    // Chart instances
    let charts = {
        hourly: null,
        deliveryTime: null,
        status: null,
        trend: null,
        revenue: null
    };

    // Current filters
    let currentFilters = {
        zone_id: 'all',
        module_id: null,
        date: null,
        date_range: 'today'
    };

    /**
     * Initialize hourly distribution bar chart
     */
    function initHourlyChart() {
        console.log('🔵 initHourlyChart() called');
        const container = document.querySelector('#hourly-chart');
        console.log('🔵 Hourly chart container:', container);
        if (!container) {
            console.error('❌ Hourly chart container not found!');
            return;
        }

        const options = {
            series: [{
                name: 'Orders',
                data: []
            }],
            chart: {
                type: 'bar',
                height: 400,
                background: 'transparent',
                foreColor: '#FFFFFF',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    distributed: false,
                    horizontal: false,
                    columnWidth: '70%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val > 0 ? val : '';
                },
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ["#FFFFFF"]
                }
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: [],
                labels: {
                    style: {
                        colors: '#FFFFFF',
                        fontSize: '12px'
                    }
                },
                title: {
                    text: 'Hour of Day',
                    style: {
                        color: '#FFFFFF',
                        fontSize: '14px',
                        fontWeight: 600
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Number of Orders',
                    style: {
                        color: '#FFFFFF',
                        fontSize: '14px',
                        fontWeight: 600
                    }
                },
                labels: {
                    style: {
                        colors: '#FFFFFF',
                        fontSize: '12px'
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#D8276B'],
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 0.8,
                    stops: [0, 100]
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function (val) {
                        return val + " orders";
                    }
                }
            },
            grid: {
                borderColor: '#3a3a3a',
                strokeDashArray: 3
            },
            colors: ['#D8276B']
        };

        try {
            console.log('🔵 Creating hourly chart instance...');
            charts.hourly = new ApexCharts(container, options);
            console.log('🔵 Rendering hourly chart...');
            charts.hourly.render();
            console.log('🔵 Hourly chart rendered successfully!');
        } catch (error) {
            console.error('❌ Error rendering hourly chart:', error);
        }
    }

    /**
     * Initialize delivery time distribution histogram
     */
    function initDeliveryTimeChart() {
        console.log('🔵 initDeliveryTimeChart() called');
        const container = document.querySelector('#delivery-time-chart');
        console.log('🔵 Delivery time chart container:', container);
        if (!container) {
            console.error('❌ Delivery time chart container not found!');
            return;
        }

        const options = {
            series: [{
                name: 'Deliveries',
                data: []
            }],
            chart: {
                type: 'bar',
                height: 400,
                background: 'transparent',
                foreColor: '#FFFFFF',
                toolbar: {
                    show: true
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    distributed: true,
                    horizontal: false,
                    columnWidth: '70%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    const percentage = opts.w.config.series[0].percentages ?
                        opts.w.config.series[0].percentages[opts.dataPointIndex] : 0;
                    return val > 0 ? val + ' (' + percentage + '%)' : '';
                },
                offsetY: -20,
                style: {
                    fontSize: '11px',
                    colors: ["#FFFFFF"]
                }
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: [],
                labels: {
                    style: {
                        colors: '#FFFFFF',
                        fontSize: '12px'
                    }
                },
                title: {
                    text: 'Delivery Time Range',
                    style: {
                        color: '#FFFFFF',
                        fontSize: '14px',
                        fontWeight: 600
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Number of Deliveries',
                    style: {
                        color: '#FFFFFF',
                        fontSize: '14px',
                        fontWeight: 600
                    }
                },
                labels: {
                    style: {
                        colors: '#FFFFFF',
                        fontSize: '12px'
                    }
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function (val, opts) {
                        const percentage = opts.w.config.series[0].percentages ?
                            opts.w.config.series[0].percentages[opts.dataPointIndex] : 0;
                        return val + " deliveries (" + percentage + "%)";
                    }
                }
            },
            grid: {
                borderColor: '#3a3a3a',
                strokeDashArray: 3
            },
            colors: [] // Will be set dynamically based on time ranges
        };

        try {
            console.log('🔵 Creating delivery time chart instance...');
            charts.deliveryTime = new ApexCharts(container, options);
            console.log('🔵 Rendering delivery time chart...');
            charts.deliveryTime.render();
            console.log('🔵 Delivery time chart rendered successfully!');
        } catch (error) {
            console.error('❌ Error rendering delivery time chart:', error);
        }
    }

    /**
     * Initialize order status donut chart
     */
    function initStatusChart() {
        console.log('🔵 initStatusChart() called');
        const container = document.querySelector('#status-chart');
        console.log('🔵 Status chart container:', container);
        if (!container) {
            console.error('❌ Status chart container not found!');
            return;
        }

        const options = {
            series: [],
            chart: {
                type: 'donut',
                height: 450,
                background: 'transparent',
                foreColor: '#FFFFFF',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        const status = config.w.config.labels[config.dataPointIndex];
                        console.log('Clicked status:', status);
                        // TODO: Add drill-down functionality
                    }
                }
            },
            labels: [],
            colors: [],
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '18px',
                                color: '#FFFFFF',
                                offsetY: -10
                            },
                            value: {
                                show: true,
                                fontSize: '28px',
                                color: '#D8276B',
                                fontWeight: 700,
                                offsetY: 10,
                                formatter: function (val) {
                                    return val;
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total Orders',
                                fontSize: '16px',
                                color: '#FFFFFF',
                                fontWeight: 600,
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val, opts) {
                    return opts.w.config.series[opts.seriesIndex];
                },
                style: {
                    fontSize: '14px',
                    fontWeight: 600,
                    colors: ['#FFFFFF']
                },
                dropShadow: {
                    enabled: true,
                    blur: 3,
                    opacity: 0.8
                }
            },
            legend: {
                position: 'bottom',
                fontSize: '14px',
                labels: {
                    colors: '#FFFFFF'
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 4
                },
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(val, opts) {
                        const total = opts.w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                        const percentage = ((val / total) * 100).toFixed(1);
                        return val + " orders (" + percentage + "%)";
                    }
                }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        height: 300
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        try {
            console.log('🔵 Creating status chart instance...');
            charts.status = new ApexCharts(container, options);
            console.log('🔵 Rendering status chart...');
            charts.status.render();
            console.log('🔵 Status chart rendered successfully!');
        } catch (error) {
            console.error('❌ Error rendering status chart:', error);
        }
    }

    /**
     * Initialize 90-day trend line chart
     */
    function initTrendChart() {
        console.log('🔵 initTrendChart() called');
        const container = document.querySelector('#trend-chart');
        console.log('🔵 Trend chart container:', container);
        if (!container) {
            console.error('❌ Trend chart container not found!');
            return;
        }

        const options = {
            series: [
                {
                    name: 'Total Orders',
                    type: 'area',
                    data: []
                },
                {
                    name: 'Delivered',
                    type: 'area',
                    data: []
                },
                {
                    name: 'Cancelled',
                    type: 'line',
                    data: []
                },
                {
                    name: 'Avg. Delivery Time (min)',
                    type: 'line',
                    data: []
                }
            ],
            chart: {
                height: 500,
                type: 'line',
                background: 'transparent',
                foreColor: '#FFFFFF',
                stacked: false,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                },
                zoom: {
                    enabled: true,
                    type: 'x',
                    autoScaleYaxis: true
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            stroke: {
                width: [3, 3, 2, 2],
                curve: 'smooth',
                dashArray: [0, 0, 5, 0]
            },
            fill: {
                type: ['gradient', 'gradient', 'solid', 'solid'],
                gradient: {
                    shade: 'dark',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    opacityFrom: 0.7,
                    opacityTo: 0.3,
                    stops: [0, 100]
                }
            },
            colors: ['#06b6d4', '#10b981', '#ef4444', '#8b5cf6'],
            xaxis: {
                type: 'datetime',
                labels: {
                    style: {
                        colors: '#FFFFFF',
                        fontSize: '12px'
                    },
                    datetimeFormatter: {
                        year: 'yyyy',
                        month: 'MMM \'yy',
                        day: 'dd MMM',
                        hour: 'HH:mm'
                    }
                },
                title: {
                    text: 'Date',
                    style: {
                        color: '#FFFFFF',
                        fontSize: '14px',
                        fontWeight: 600
                    }
                }
            },
            yaxis: [
                {
                    title: {
                        text: 'Number of Orders',
                        style: {
                            color: '#FFFFFF',
                            fontSize: '14px',
                            fontWeight: 600
                        }
                    },
                    labels: {
                        style: {
                            colors: '#FFFFFF',
                            fontSize: '12px'
                        }
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: 'Avg. Delivery Time (min)',
                        style: {
                            color: '#8b5cf6',
                            fontSize: '14px',
                            fontWeight: 600
                        }
                    },
                    labels: {
                        style: {
                            colors: '#8b5cf6',
                            fontSize: '12px'
                        }
                    }
                }
            ],
            tooltip: {
                theme: 'dark',
                shared: true,
                intersect: false,
                x: {
                    format: 'dd MMM yyyy'
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center',
                fontSize: '14px',
                labels: {
                    colors: '#FFFFFF'
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 4
                },
                itemMargin: {
                    horizontal: 15,
                    vertical: 5
                }
            },
            grid: {
                borderColor: '#3a3a3a',
                strokeDashArray: 3
            }
        };

        try {
            console.log('🔵 Creating trend chart instance...');
            charts.trend = new ApexCharts(container, options);
            console.log('🔵 Rendering trend chart...');
            charts.trend.render();
            console.log('🔵 Trend chart rendered successfully!');
        } catch (error) {
            console.error('❌ Error rendering trend chart:', error);
        }
    }

    /**
     * Update hourly chart with new data
     */
    function updateHourlyChart(data) {
        if (!charts.hourly) return;

        const categories = data.map(item => item.label);
        const values = data.map(item => item.count);

        // Color gradient based on volume
        const maxVal = Math.max(...values);
        const colors = values.map(val => {
            const intensity = maxVal > 0 ? val / maxVal : 0;
            if (intensity > 0.7) return '#ef4444'; // High volume - red
            if (intensity > 0.4) return '#f97316'; // Medium volume - orange
            return '#06b6d4'; // Low volume - blue
        });

        charts.hourly.updateOptions({
            xaxis: {
                categories: categories
            },
            colors: colors
        });

        charts.hourly.updateSeries([{
            name: 'Orders',
            data: values
        }]);
    }

    /**
     * Update delivery time chart with new data
     */
    function updateDeliveryTimeChart(data) {
        if (!charts.deliveryTime) return;

        const buckets = data.buckets || [];
        const categories = buckets.map(item => item.label);
        const values = buckets.map(item => item.count);
        const colors = buckets.map(item => item.color);
        const percentages = buckets.map(item => item.percentage);

        charts.deliveryTime.updateOptions({
            xaxis: {
                categories: categories
            },
            colors: colors
        });

        charts.deliveryTime.updateSeries([{
            name: 'Deliveries',
            data: values,
            percentages: percentages
        }]);
    }

    /**
     * Update status chart with new data
     */
    function updateStatusChart(data) {
        if (!charts.status) return;

        const statuses = data.statuses || [];
        const labels = statuses.map(item => item.label);
        const values = statuses.map(item => item.count);
        const colors = statuses.map(item => item.color);

        charts.status.updateOptions({
            labels: labels,
            colors: colors
        });

        charts.status.updateSeries(values);
    }

    /**
     * Update trend chart with new data
     */
    function updateTrendChart(data) {
        if (!charts.trend) return;

        const dates = data.map(item => new Date(item.date).getTime());
        const totalOrders = data.map(item => item.total_orders);
        const delivered = data.map(item => item.delivered);
        const cancelled = data.map(item => item.cancelled);
        const avgTime = data.map(item => item.avg_delivery_time);

        charts.trend.updateSeries([
            { name: 'Total Orders', data: dates.map((date, i) => [date, totalOrders[i]]) },
            { name: 'Delivered', data: dates.map((date, i) => [date, delivered[i]]) },
            { name: 'Cancelled', data: dates.map((date, i) => [date, cancelled[i]]) },
            { name: 'Avg. Delivery Time (min)', data: dates.map((date, i) => [date, avgTime[i]]) }
        ]);
    }

    /**
     * Fetch and update all charts
     */
    function fetchAndUpdateCharts(filters) {
        console.log('🔵 fetchAndUpdateCharts() called with filters:', filters);
        currentFilters = { ...currentFilters, ...filters };
        console.log('🔵 Merged filters:', currentFilters);

        // Show loading state
        showChartsLoading(true);

        console.log('🔵 Fetching chart data from APIs...');

        // Fetch all chart data in parallel
        Promise.all([
            fetchChartData('hourly', currentFilters),
            fetchChartData('delivery-time', currentFilters),
            fetchChartData('status', currentFilters),
            fetchChartData('trend', currentFilters)
        ]).then(([hourlyData, deliveryTimeData, statusData, trendData]) => {
            console.log('🔵 Chart data received:', {
                hourly: hourlyData.success ? 'SUCCESS' : 'FAILED',
                deliveryTime: deliveryTimeData.success ? 'SUCCESS' : 'FAILED',
                status: statusData.success ? 'SUCCESS' : 'FAILED',
                trend: trendData.success ? 'SUCCESS' : 'FAILED'
            });

            if (hourlyData.success) {
                console.log('🔵 Updating hourly chart with data:', hourlyData.data);
                updateHourlyChart(hourlyData.data);
            }
            if (deliveryTimeData.success) {
                console.log('🔵 Updating delivery time chart');
                updateDeliveryTimeChart(deliveryTimeData.data);
            }
            if (statusData.success) {
                console.log('🔵 Updating status chart');
                updateStatusChart(statusData.data);
            }
            if (trendData.success) {
                console.log('🔵 Updating trend chart');
                updateTrendChart(trendData.data);
            }
            showChartsLoading(false);
            console.log('🔵 All charts updated successfully!');
        }).catch(error => {
            console.error('❌ Error fetching chart data:', error);
            console.error('❌ Stack trace:', error.stack);
            showChartsLoading(false);
            showError('Failed to load chart data. Please try again.');
        });
    }

    /**
     * Fetch chart data from API
     */
    function fetchChartData(chartType, filters) {
        const baseUrl = window.location.origin + '/admin/delivery-stats/chart/';
        const params = new URLSearchParams(filters).toString();
        const url = `${baseUrl}${chartType}?${params}`;

        console.log(`🔵 Fetching ${chartType} chart from:`, url);

        return fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(response => {
            console.log(`🔵 ${chartType} response status:`, response.status);
            if (!response.ok) {
                console.error(`❌ ${chartType} failed with status:`, response.status);
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        }).then(data => {
            console.log(`🔵 ${chartType} data:`, data);
            return data;
        }).catch(error => {
            console.error(`❌ Error fetching ${chartType}:`, error);
            throw error;
        });
    }

    /**
     * Show/hide loading state on charts
     */
    function showChartsLoading(isLoading) {
        const chartContainers = [
            '#hourly-chart',
            '#delivery-time-chart',
            '#status-chart',
            '#trend-chart'
        ];

        chartContainers.forEach(selector => {
            const container = document.querySelector(selector);
            if (container) {
                if (isLoading) {
                    container.classList.add('chart-loading');
                } else {
                    container.classList.remove('chart-loading');
                }
            }
        });
    }

    /**
     * Show error message
     */
    function showError(message) {
        // You can customize this to show a toast or modal
        console.error(message);
        alert(message);
    }

    /**
     * Initialize all charts
     */
    function init(initialFilters = {}) {
        console.log('🔵 init() function called with filters:', initialFilters);

        try {
            currentFilters = { ...currentFilters, ...initialFilters };
            console.log('🔵 Current filters:', currentFilters);

            // Initialize charts
            console.log('🔵 Initializing hourly chart...');
            initHourlyChart();

            console.log('🔵 Initializing delivery time chart...');
            initDeliveryTimeChart();

            console.log('🔵 Initializing status chart...');
            initStatusChart();

            console.log('🔵 Initializing trend chart...');
            initTrendChart();

            console.log('🔵 All charts initialized! Loading data...');

            // Check for embedded chart data first (faster, no AJAX needed)
            if (typeof window.INITIAL_CHART_DATA !== 'undefined') {
                console.log('🟢 Using embedded chart data (no AJAX)');
                console.log('🟢 Embedded data:', window.INITIAL_CHART_DATA);

                // Update charts with embedded data
                if (window.INITIAL_CHART_DATA.hourly && charts.hourly) {
                    updateHourlyChart(window.INITIAL_CHART_DATA.hourly);
                }
                if (window.INITIAL_CHART_DATA.status && charts.status) {
                    updateStatusChart(window.INITIAL_CHART_DATA.status);
                }
                if (window.INITIAL_CHART_DATA.trend && charts.trend) {
                    updateTrendChart(window.INITIAL_CHART_DATA.trend);
                }
                if (window.INITIAL_CHART_DATA.deliveryTime && charts.deliveryTime) {
                    updateDeliveryTimeChart(window.INITIAL_CHART_DATA.deliveryTime);
                }

                console.log('✅ All charts updated with embedded data!');
            } else {
                console.log('🔵 No embedded data, fetching via AJAX...');
                // Fallback to AJAX if embedded data not available
                fetchAndUpdateCharts(currentFilters);
            }

            // Set up auto-refresh (every 60 seconds) - always use AJAX for updates
            setInterval(() => {
                fetchAndUpdateCharts(currentFilters);
            }, 60000);

            console.log('🔵 Charts initialization complete! Auto-refresh every 60s');
        } catch (error) {
            console.error('❌ Error in init():', error);
            console.error('❌ Stack trace:', error.stack);
        }
    }

    // Public API
    return {
        init: init,
        updateCharts: fetchAndUpdateCharts,
        getCurrentFilters: () => currentFilters
    };
})();

// Auto-initialize when DOM is ready
console.log('🔵 Auto-init check - readyState:', document.readyState);
console.log('🔵 Checking for #hourly-chart:', document.querySelector('#hourly-chart'));

if (document.readyState === 'loading') {
    console.log('🔵 DOM still loading, waiting for DOMContentLoaded...');
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔵 DOMContentLoaded fired!');
        if (document.querySelector('#hourly-chart')) {
            console.log('🔵 Chart container found, initializing charts...');
            DeliveryStatsCharts.init();
        } else {
            console.error('❌ Chart container #hourly-chart not found!');
        }
    });
} else {
    console.log('🔵 DOM already loaded, checking for chart container...');
    if (document.querySelector('#hourly-chart')) {
        console.log('🔵 Chart container found, initializing charts now...');
        DeliveryStatsCharts.init();
    } else {
        console.error('❌ Chart container #hourly-chart not found in DOM!');
        console.log('🔵 Available elements with "chart" in ID:',
            Array.from(document.querySelectorAll('[id*="chart"]')).map(el => el.id));
    }
}
