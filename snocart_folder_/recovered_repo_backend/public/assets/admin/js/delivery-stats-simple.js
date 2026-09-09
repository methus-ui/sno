/**
 * Delivery Stats Analytics - Enhanced Version
 * Modern UI with comprehensive business insights
 * Version: 2.0 Enhanced
 */

(function() {
    'use strict';

    console.log('[Delivery Analytics] Initializing...');

    // Check if chart data exists
    if (typeof window.CHART_DATA === 'undefined') {
        console.error('[Delivery Analytics] ERROR: Chart data not found!');
        return;
    }

    console.log('[Delivery Analytics] Chart data loaded:', {
        hourly: window.CHART_DATA.hourly?.length || 0,
        status: window.CHART_DATA.status?.total || 0,
        trend: window.CHART_DATA.trend?.length || 0,
        deliveryTime: window.CHART_DATA.deliveryTime?.buckets?.length || 0
    });

    // Color palette
    const colors = {
        primary: '#D8276B',
        primaryLight: '#E8477B',
        success: '#10b981',
        info: '#06b6d4',
        warning: '#f59e0b',
        danger: '#ef4444',
        purple: '#8b5cf6',
        indigo: '#6366f1'
    };

    // Initialize all charts on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCharts);
    } else {
        initCharts();
    }

    function initCharts() {
        console.log('[Delivery Analytics] Initializing charts...');

        try {
            initHourlyChart();
            initStatusChart();
            initDeliveryTimeChart();
            initTrendChart();
            console.log('[Delivery Analytics] SUCCESS: All charts initialized!');
        } catch (error) {
            console.error('[Delivery Analytics] ERROR: Failed to initialize charts:', error);
        }
    }

    /**
     * Enhanced Hourly Distribution Chart
     */
    function initHourlyChart() {
        const container = document.querySelector('#hourly-chart');
        if (!container) {
            console.warn('[Delivery Analytics] WARNING: Hourly chart container not found');
            return;
        }

        const data = window.CHART_DATA.hourly || [];
        const categories = data.map(item => item.label);
        const counts = data.map(item => item.count);

        // Calculate peak hour for annotation
        const maxCount = Math.max(...counts);
        const peakHourIndex = counts.indexOf(maxCount);

        const options = {
            series: [{
                name: 'Orders',
                data: counts
            }],
            chart: {
                type: 'bar',
                height: 400,
                background: 'transparent',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    columnWidth: '70%',
                    dataLabels: {
                        position: 'top'
                    },
                    distributed: false
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val > 0 ? val : '';
                },
                offsetY: -25,
                style: {
                    fontSize: '12px',
                    colors: ['#e5e5e5'],
                    fontWeight: 600
                }
            },
            xaxis: {
                categories: categories,
                labels: {
                    style: {
                        colors: '#a3a3a3',
                        fontSize: '12px',
                        fontWeight: 500
                    },
                    rotate: -45,
                    rotateAlways: false
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                title: {
                    text: 'Number of Orders',
                    style: {
                        color: '#e5e5e5',
                        fontSize: '13px',
                        fontWeight: 600
                    }
                },
                labels: {
                    style: {
                        colors: '#a3a3a3',
                        fontSize: '12px'
                    },
                    formatter: function(val) {
                        return Math.round(val);
                    }
                }
            },
            colors: [colors.primary],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: [colors.primaryLight],
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 0.8,
                    stops: [0, 100]
                }
            },
            grid: {
                borderColor: 'rgba(255, 255, 255, 0.05)',
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: false
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                },
                padding: {
                    top: 0,
                    right: 10,
                    bottom: 0,
                    left: 10
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(val) {
                        return val + ' orders';
                    }
                },
                style: {
                    fontSize: '13px'
                }
            },
            annotations: {
                points: peakHourIndex >= 0 ? [{
                    x: categories[peakHourIndex],
                    y: maxCount,
                    marker: {
                        size: 8,
                        fillColor: colors.warning,
                        strokeColor: '#fff',
                        strokeWidth: 2
                    },
                    label: {
                        borderColor: colors.warning,
                        offsetY: 0,
                        style: {
                            color: '#fff',
                            background: colors.warning,
                            fontSize: '11px',
                            fontWeight: 600
                        },
                        text: 'Peak Hour'
                    }
                }] : []
            }
        };

        new ApexCharts(container, options).render();
        console.log('[Delivery Analytics] Hourly chart rendered');
    }

    /**
     * Enhanced Status Breakdown Chart
     */
    function initStatusChart() {
        const container = document.querySelector('#status-chart');
        if (!container) {
            console.warn('[Delivery Analytics] WARNING: Status chart container not found');
            return;
        }

        const statusData = window.CHART_DATA.status || {};
        const statuses = statusData.statuses || [];

        if (statuses.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 50px; color: #999;">No status data available</div>';
            return;
        }

        const series = statuses.map(s => s.count);
        const labels = statuses.map(s => s.label);
        const chartColors = statuses.map(s => s.color);

        const options = {
            series: series,
            chart: {
                type: 'donut',
                height: 400,
                background: 'transparent',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    }
                }
            },
            labels: labels,
            colors: chartColors,
            legend: {
                position: 'bottom',
                fontSize: '13px',
                fontWeight: 500,
                labels: {
                    colors: '#e5e5e5'
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 3
                },
                itemMargin: {
                    horizontal: 12,
                    vertical: 8
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val, opts) {
                    const count = opts.w.config.series[opts.seriesIndex];
                    return count;
                },
                style: {
                    fontSize: '14px',
                    fontWeight: 700,
                    colors: ['#fff']
                },
                dropShadow: {
                    enabled: true,
                    top: 1,
                    left: 1,
                    blur: 2,
                    opacity: 0.8
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        background: 'transparent',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '16px',
                                fontWeight: 600,
                                color: '#e5e5e5',
                                offsetY: -10
                            },
                            value: {
                                show: true,
                                fontSize: '32px',
                                fontWeight: 800,
                                color: colors.primary,
                                offsetY: 10,
                                formatter: function(val) {
                                    return val;
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total Orders',
                                fontSize: '14px',
                                fontWeight: 600,
                                color: '#a3a3a3',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            stroke: {
                width: 3,
                colors: ['#1a1a1a']
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(val) {
                        return val + ' orders';
                    }
                },
                style: {
                    fontSize: '13px'
                }
            },
            responsive: [{
                breakpoint: 768,
                options: {
                    chart: {
                        height: 350
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        new ApexCharts(container, options).render();
        console.log('[Delivery Analytics] Status chart rendered');
    }

    /**
     * Enhanced Delivery Time Distribution Chart
     */
    function initDeliveryTimeChart() {
        const container = document.querySelector('#delivery-time-chart');
        if (!container) {
            console.warn('[Delivery Analytics] WARNING: Delivery time chart container not found');
            return;
        }

        const timeData = window.CHART_DATA.deliveryTime || {};
        const buckets = timeData.buckets || [];

        if (buckets.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 50px; color: #999;">No delivery time data available</div>';
            return;
        }

        const categories = buckets.map(b => b.label);
        const counts = buckets.map(b => b.count);
        const chartColors = buckets.map(b => b.color);

        const options = {
            series: [{
                name: 'Deliveries',
                data: counts
            }],
            chart: {
                type: 'bar',
                height: 400,
                background: 'transparent',
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
                    dataLabels: {
                        position: 'top'
                    },
                    columnWidth: '70%'
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val > 0 ? val : '';
                },
                offsetY: -25,
                style: {
                    fontSize: '12px',
                    colors: ['#e5e5e5'],
                    fontWeight: 600
                }
            },
            xaxis: {
                categories: categories,
                labels: {
                    style: {
                        colors: '#a3a3a3',
                        fontSize: '12px',
                        fontWeight: 500
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                title: {
                    text: 'Number of Deliveries',
                    style: {
                        color: '#e5e5e5',
                        fontSize: '13px',
                        fontWeight: 600
                    }
                },
                labels: {
                    style: {
                        colors: '#a3a3a3',
                        fontSize: '12px'
                    },
                    formatter: function(val) {
                        return Math.round(val);
                    }
                }
            },
            colors: chartColors,
            legend: {
                show: false
            },
            grid: {
                borderColor: 'rgba(255, 255, 255, 0.05)',
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: false
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(val) {
                        return val + ' deliveries';
                    }
                },
                style: {
                    fontSize: '13px'
                }
            }
        };

        new ApexCharts(container, options).render();
        console.log('[Delivery Analytics] Delivery time chart rendered');
    }

    /**
     * Enhanced 90-Day Trend Chart
     */
    function initTrendChart() {
        const container = document.querySelector('#trend-chart');
        if (!container) {
            console.warn('[Delivery Analytics] WARNING: Trend chart container not found');
            return;
        }

        const trendData = window.CHART_DATA.trend || [];

        if (trendData.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 50px; color: #999;">No trend data available</div>';
            return;
        }

        const dates = trendData.map(d => d.date_formatted);
        const totalOrders = trendData.map(d => d.total_orders);
        const delivered = trendData.map(d => d.delivered);
        const cancelled = trendData.map(d => d.cancelled);

        const options = {
            series: [
                {
                    name: 'Total Orders',
                    data: totalOrders
                },
                {
                    name: 'Delivered',
                    data: delivered
                },
                {
                    name: 'Cancelled',
                    data: cancelled
                }
            ],
            chart: {
                type: 'area',
                height: 500,
                background: 'transparent',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
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
                curve: 'smooth',
                width: 3,
                lineCap: 'round'
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    opacityFrom: 0.7,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: dates,
                labels: {
                    rotate: -45,
                    style: {
                        colors: '#a3a3a3',
                        fontSize: '11px',
                        fontWeight: 500
                    },
                    rotateAlways: false
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                tickAmount: 15
            },
            yaxis: {
                title: {
                    text: 'Number of Orders',
                    style: {
                        color: '#e5e5e5',
                        fontSize: '13px',
                        fontWeight: 600
                    }
                },
                labels: {
                    style: {
                        colors: '#a3a3a3',
                        fontSize: '12px'
                    },
                    formatter: function(val) {
                        return Math.round(val);
                    }
                }
            },
            colors: [colors.info, colors.success, colors.danger],
            legend: {
                position: 'top',
                fontSize: '13px',
                fontWeight: 500,
                horizontalAlign: 'left',
                labels: {
                    colors: '#e5e5e5'
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 3
                },
                itemMargin: {
                    horizontal: 16,
                    vertical: 4
                }
            },
            grid: {
                borderColor: 'rgba(255, 255, 255, 0.05)',
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: false
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                },
                padding: {
                    top: 0,
                    right: 10,
                    bottom: 0,
                    left: 10
                }
            },
            markers: {
                size: 0,
                hover: {
                    size: 6,
                    sizeOffset: 3
                }
            },
            tooltip: {
                theme: 'dark',
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val) {
                        return val + ' orders';
                    }
                },
                style: {
                    fontSize: '13px'
                },
                marker: {
                    show: true
                }
            },
            responsive: [{
                breakpoint: 768,
                options: {
                    chart: {
                        height: 400
                    },
                    xaxis: {
                        labels: {
                            rotate: -90
                        }
                    }
                }
            }]
        };

        new ApexCharts(container, options).render();
        console.log('[Delivery Analytics] Trend chart rendered');
    }

})();
