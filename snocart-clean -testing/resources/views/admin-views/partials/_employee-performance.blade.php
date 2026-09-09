@if(isset($employee_performance))
<?php $current_period = $current_period ?? 'today'; ?>
<div class="card mb-3">
    <div class="card-header border-0">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0">
                <i class="tio-user-big-outlined mr-2"></i>
                @if($employee_performance['is_super_admin'])
                    {{ translate('Employee Performance') }}
                @else
                    {{ translate('My Performance') }}
                @endif
            </h5>

            {{-- Period Tabs --}}
            <ul class="nav nav-tabs border-0 mb-0" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $current_period == 'today' ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}?period=today">
                        {{ translate('Today') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $current_period == 'week' ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}?period=week">
                        {{ translate('This Week') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $current_period == 'month' ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}?period=month">
                        {{ translate('This Month') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card-body">
        @if($employee_performance['is_super_admin'])
            {{-- Super Admin View: Top 5 Employees --}}
            <div class="row" id="employee-performance-content">
                @forelse($employee_performance['top_employees'] as $index => $employee)
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="card h-100 border employee-performance-card"
                         data-employee-id="{{ $employee['id'] }}"
                         style="cursor: pointer;">
                        <div class="card-body">
                            {{-- Rank Badge --}}
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge badge-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'dark') }} mr-2"
                                      style="width: 30px; height: 30px; line-height: 22px; font-size: 14px;">
                                    #{{ $index + 1 }}
                                </span>
                                <img class="rounded-circle mr-2"
                                     style="width: 50px; height: 50px; object-fit: cover;"
                                     src="{{ $employee['image'] }}"
                                     alt="">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $employee['name'] }}</h6>
                                    <small class="text-muted">{{ $employee['role'] }}</small>
                                </div>
                            </div>

                            {{-- Volume Status Bar --}}
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">
                                        {{ translate('Order Volume') }}
                                        <span class="badge badge-warning badge-pill" style="font-size: 9px;">
                                            <i class="tio-star"></i> STRETCH GOAL
                                        </span>
                                    </small>
                                    <span class="badge badge-{{ $employee['metrics']['volume_status']['color'] }}">
                                        <i class="tio-{{ $employee['metrics']['volume_status']['icon'] }}"></i>
                                        {{ $employee['metrics']['volume_status']['label'] }}
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $employee['metrics']['volume_status']['color'] }}"
                                         role="progressbar"
                                         style="width: {{ min($employee['metrics']['volume_status']['percentage'], 100) }}%">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">
                                        {{ $employee['metrics']['total_assigned'] }} /
                                        <strong>{{ $employee['metrics']['min_orders_target'] }}</strong>
                                        <span class="text-warning" style="font-size: 10px;">(+30% stretch)</span>
                                    </small>
                                    <small class="text-{{ $employee['metrics']['volume_status']['color'] }}">
                                        {{ round($employee['metrics']['volume_status']['percentage']) }}%
                                    </small>
                                </div>
                            </div>

                            {{-- Metrics Grid --}}
                            <div class="row text-center">
                                <div class="col-6 mb-2">
                                    <h4 class="mb-0 text-{{ $employee['metrics']['meets_time_target'] ? 'success' : 'warning' }}">
                                        {{ $employee['metrics']['avg_delivery_time_formatted'] }}
                                    </h4>
                                    <small class="text-muted">{{ translate('Avg Time') }}</small>
                                    <small class="d-block text-muted" style="font-size: 10px;">
                                        (Target: {{ $employee['metrics']['target_delivery_time'] }}min)
                                    </small>
                                </div>
                                <div class="col-6 mb-2">
                                    <h4 class="mb-0 text-{{ $employee['metrics']['color_class'] }}">
                                        {{ $employee['metrics']['cancellation_rate'] }}%
                                    </h4>
                                    <small class="text-muted">{{ translate('Cancel Rate') }}</small>
                                </div>
                                <div class="col-6 mb-2">
                                    <h4 class="mb-0 text-success">{{ $employee['metrics']['delivered'] }}</h4>
                                    <small class="text-muted">{{ translate('Delivered') }}</small>
                                </div>
                                <div class="col-6 mb-2">
                                    <span class="badge badge-{{ $employee['metrics']['performance_grade']['color'] }} p-2"
                                          style="font-size: 16px;">
                                        {{ $employee['metrics']['performance_score'] }}
                                    </span>
                                    <small class="d-block text-muted mt-1">
                                        {{ $employee['metrics']['performance_grade']['grade'] }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="text-center py-5">
                        <img src="{{ asset('public/assets/admin/svg/illustrations/empty-state.svg') }}"
                             alt="" style="width: 100px;">
                        <p class="text-muted mt-3">
                            {{ translate('No employee performance data available for this period') }}
                        </p>
                    </div>
                </div>
                @endforelse
            </div>

            {{-- Employee Selector (if there are more employees) --}}
            @if(count($employee_performance['employees_with_orders']) > 5)
            <div class="mt-3">
                <select id="employee-select" class="form-control">
                    <option value="">{{ translate('View Other Employees') }}</option>
                    @foreach($employee_performance['employees_with_orders'] as $emp)
                        @if(!in_array($emp->id, $employee_performance['top_employees']->pluck('id')->toArray()))
                        <option value="{{ $emp->id }}">
                            {{ $emp->f_name }} {{ $emp->l_name }} ({{ $emp->role->name ?? 'N/A' }})
                        </option>
                        @endif
                    @endforeach
                </select>
            </div>
            @endif
        @else
            {{-- Regular Employee View: Own Performance Only --}}
            @if($employee_performance['current_user_metrics']['total_assigned'] > 0)
            {{-- Volume Status --}}
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0">{{ translate('Order Volume Target') }}</h6>
                            <small class="text-warning">
                                <i class="tio-star"></i> <strong>STRETCH GOAL</strong> (30% above average)
                            </small>
                        </div>
                        <span class="badge badge-{{ $employee_performance['current_user_metrics']['volume_status']['color'] }} badge-pill">
                            <i class="tio-{{ $employee_performance['current_user_metrics']['volume_status']['icon'] }}"></i>
                            {{ $employee_performance['current_user_metrics']['volume_status']['label'] }}
                        </span>
                    </div>
                    <div class="progress mb-2" style="height: 20px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-{{ $employee_performance['current_user_metrics']['volume_status']['color'] }}"
                             role="progressbar"
                             style="width: {{ min($employee_performance['current_user_metrics']['volume_status']['percentage'], 100) }}%">
                            {{ round($employee_performance['current_user_metrics']['volume_status']['percentage']) }}%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><strong>{{ $employee_performance['current_user_metrics']['total_assigned'] }}</strong> {{ translate('orders assigned') }}</span>
                        <span class="text-muted">
                            {{ translate('Target') }}: <strong>{{ $employee_performance['current_user_metrics']['min_orders_target'] }}</strong>
                            <span class="badge badge-warning badge-sm ml-1">+30%</span>
                        </span>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="tio-info"></i> Daily: <strong>{{ $employee_performance['current_user_metrics']['daily_target'] }}</strong> orders/day |
                        Avg: {{ $employee_performance['current_user_metrics']['avg_daily_orders'] }}/day
                    </small>
                </div>
            </div>

            {{-- Metrics Row --}}
            <div class="row">
                <div class="col-md-3 text-center mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="text-primary mb-0">{{ $employee_performance['current_user_metrics']['total_assigned'] }}</h3>
                            <small class="text-muted">{{ translate('Total Assigned') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="text-success mb-0">{{ $employee_performance['current_user_metrics']['delivered'] }}</h3>
                            <small class="text-muted">{{ translate('Delivered') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="text-{{ $employee_performance['current_user_metrics']['color_class'] }} mb-0">
                                {{ $employee_performance['current_user_metrics']['cancellation_rate'] }}%
                            </h3>
                            <small class="text-muted">{{ translate('Cancel Rate') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-3">
                    <div class="card h-100 border-{{ $employee_performance['current_user_metrics']['meets_time_target'] ? 'success' : 'warning' }}">
                        <div class="card-body">
                            <h3 class="text-{{ $employee_performance['current_user_metrics']['meets_time_target'] ? 'success' : 'warning' }} mb-0">
                                {{ $employee_performance['current_user_metrics']['avg_delivery_time_formatted'] }}
                            </h3>
                            <small class="text-muted">{{ translate('Avg Delivery') }}</small>
                            <small class="d-block mt-1 text-muted" style="font-size: 11px;">
                                <i class="tio-{{ $employee_performance['current_user_metrics']['meets_time_target'] ? 'checkmark-circle' : 'time' }}"></i>
                                Target: {{ $employee_performance['current_user_metrics']['target_delivery_time'] }}min
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6 mx-auto">
                    <div class="card bg-primary text-white text-center">
                        <div class="card-body">
                            <h6 class="text-white">{{ translate('Performance Score') }}</h6>
                            <h1 class="display-4 mb-2">{{ $employee_performance['current_user_metrics']['performance_score'] }}</h1>
                            <h4>{{ $employee_performance['current_user_metrics']['performance_grade']['grade'] }} -
                                {{ translate($employee_performance['current_user_metrics']['performance_grade']['label']) }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <img src="{{ asset('public/assets/admin/svg/illustrations/empty-state.svg') }}"
                     alt="" style="width: 100px;">
                <p class="text-muted mt-3">
                    {{ translate('You have no assigned orders for this period') }}
                </p>
            </div>
            @endif
        @endif
    </div>
</div>

{{-- Performance Detail Modal (Super Admin Only) --}}
@if($employee_performance['is_super_admin'])
<div class="modal fade" id="employeePerformanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Employee Performance Detail') }}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="employee-performance-modal-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">{{ translate('Loading...') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endif

<style>
.employee-performance-card {
    transition: all 0.3s ease;
}
.employee-performance-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.nav-tabs .nav-link {
    border: 1px solid transparent;
    color: #6c757d;
}
.nav-tabs .nav-link.active {
    color: #007bff;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}
</style>

<script>
$(document).ready(function() {
    const currentPeriod = '{{ $current_period }}';

    // Employee card click - show detail modal
    $(document).on('click', '.employee-performance-card', function() {
        const employeeId = $(this).data('employee-id');
        loadEmployeeDetail(employeeId, currentPeriod);
    });

    // Employee dropdown select
    $('#employee-select').on('change', function() {
        const employeeId = $(this).val();
        if (employeeId) {
            loadEmployeeDetail(employeeId, currentPeriod);
            $(this).val(''); // Reset dropdown
        }
    });

    function loadEmployeeDetail(employeeId, period) {
        $('#employeePerformanceModal').modal('show');

        $.ajax({
            url: '{{ route("admin.dashboard.employee-performance-detail") }}',
            data: {
                employee_id: employeeId,
                period: period
            },
            beforeSend: function() {
                $('#employee-performance-modal-body').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </div>
                `);
            },
            success: function(response) {
                if (response.success) {
                    renderEmployeeDetail(response);
                }
            },
            error: function(xhr) {
                let message = '{{ translate("Failed to load employee details") }}';
                if (xhr.status === 403) {
                    message = xhr.responseJSON?.message || message;
                }
                toastr.error(message);
                $('#employeePerformanceModal').modal('hide');
            }
        });
    }

    function renderEmployeeDetail(data) {
        const metrics = data.metrics;
        const employee = data.employee;

        const html = `
            <div class="text-center mb-4">
                <img src="${employee.image}" class="rounded-circle mb-2"
                     style="width: 80px; height: 80px; object-fit: cover;">
                <h4 class="mb-0">${employee.name}</h4>
                <p class="text-muted">${employee.role}</p>
            </div>

            <!-- Volume Status -->
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">{{ translate("Order Volume Performance") }}</h6>
                        <span class="badge badge-${metrics.volume_status.color}">
                            ${metrics.volume_status.label}
                        </span>
                    </div>
                    <div class="progress mb-2" style="height: 20px;">
                        <div class="progress-bar bg-${metrics.volume_status.color}"
                             role="progressbar"
                             style="width: ${Math.min(metrics.volume_status.percentage, 100)}%">
                            ${Math.round(metrics.volume_status.percentage)}%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><strong>${metrics.total_assigned}</strong> / ${metrics.min_orders_target} orders</span>
                        <span class="text-muted">Target: ${metrics.min_orders_target} orders/day</span>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3 text-center">
                    <h3 class="text-primary mb-0">${metrics.total_assigned}</h3>
                    <small class="text-muted">{{ translate("Total Assigned") }}</small>
                </div>
                <div class="col-md-3 text-center">
                    <h3 class="text-success mb-0">${metrics.delivered}</h3>
                    <small class="text-muted">{{ translate("Delivered") }}</small>
                </div>
                <div class="col-md-3 text-center">
                    <h3 class="text-danger mb-0">${metrics.canceled}</h3>
                    <small class="text-muted">{{ translate("Canceled") }}</small>
                </div>
                <div class="col-md-3 text-center">
                    <h3 class="text-warning mb-0">${metrics.pending}</h3>
                    <small class="text-muted">{{ translate("Pending") }}</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card bg-light h-100">
                        <div class="card-body">
                            <h6>{{ translate("Performance Metrics") }}</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <strong>{{ translate("Completion Rate") }}:</strong>
                                    <span class="float-right">${metrics.completion_rate}%</span>
                                </li>
                                <li class="mb-2">
                                    <strong>{{ translate("Cancellation Rate") }}:</strong>
                                    <span class="float-right badge badge-${metrics.color_class}">
                                        ${metrics.cancellation_rate}%
                                    </span>
                                </li>
                                <li class="mb-2">
                                    <strong>{{ translate("Avg Delivery Time") }}:</strong>
                                    <span class="float-right badge badge-${metrics.meets_time_target ? 'success' : 'warning'}">
                                        ${metrics.avg_delivery_time_formatted}
                                    </span>
                                </li>
                                <li class="mb-2">
                                    <strong>{{ translate("Target") }}:</strong>
                                    <span class="float-right">${metrics.target_delivery_time} min</span>
                                </li>
                                <li class="mb-2">
                                    <strong>{{ translate("On-Time Orders") }}:</strong>
                                    <span class="float-right">${metrics.on_time_orders} / ${metrics.delivered}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card bg-light h-100">
                        <div class="card-body">
                            <h6>{{ translate("Delivery Time Range") }}</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <strong>{{ translate("Min Delivery Time") }}:</strong>
                                    <span class="float-right text-success">${metrics.min_delivery_time_formatted}</span>
                                </li>
                                <li class="mb-2">
                                    <strong>{{ translate("Avg Delivery Time") }}:</strong>
                                    <span class="float-right text-info">${metrics.avg_delivery_time_formatted}</span>
                                </li>
                                <li class="mb-2">
                                    <strong>{{ translate("Max Delivery Time") }}:</strong>
                                    <span class="float-right text-danger">${metrics.max_delivery_time_formatted}</span>
                                </li>
                            </ul>
                            <div class="mt-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center py-3">
                                        <h6 class="text-white mb-1">{{ translate("Performance Score") }}</h6>
                                        <h2 class="mb-1">${metrics.performance_score}</h2>
                                        <span class="badge badge-light">${metrics.performance_grade.grade}</span>
                                        <small class="d-block mt-1">${metrics.performance_grade.label}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#employee-performance-modal-body').html(html);
    }
});
</script>
