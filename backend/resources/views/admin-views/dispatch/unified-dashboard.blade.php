@php use App\CentralLogics\Helpers; @endphp
@extends('layouts.admin.app')

@section('title', 'Unified Dispatch Management')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .dispatch-card {
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    .dispatch-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }
    .order-item {
        border-left: 4px solid #ddd;
        padding: 15px;
        margin-bottom: 10px;
        background: white;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .order-item:hover {
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .order-item.urgent { border-left-color: #dc3545; }
    .order-item.pending { border-left-color: #ffc107; }
    .order-item.assigned { border-left-color: #17a2b8; }
    .order-item.completed { border-left-color: #28a745; }

    .module-badge {
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .module-food { background: #ffebee; color: #c62828; }
    .module-grocery { background: #e8f5e9; color: #2e7d32; }
    .module-pharmacy { background: #e3f2fd; color: #1565c0; }
    .module-ecommerce { background: #f3e5f5; color: #6a1b9a; }

    .stat-card {
        background: #2d3748;
        color: white;
        padding: 25px;
        border-radius: 16px;
        margin-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        border: 1px solid rgba(255,255,255,0.1);
    }
    .stat-card.success { background: #059669; border-color: #10b981; }
    .stat-card.warning { background: #d97706; border-color: #f59e0b; }
    .stat-card.info { background: #0284c7; border-color: #0ea5e9; }
    .stat-card.danger { background: #dc2626; border-color: #ef4444; }

    .dm-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .filter-chip {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 10px;
        background: #f1f5f9;
        margin: 5px 5px 5px 0;
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid #e2e8f0;
        font-weight: 600;
        color: #475569;
    }
    .filter-chip:hover {
        background: #e2e8f0;
        border-color: #cbd5e1;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .filter-chip.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59,130,246,0.4);
    }

    .quick-action-btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        margin: 2px;
    }

    .delivery-man-selector {
        max-height: 300px;
        overflow-y: auto;
    }

    .dm-card:hover:not(.unavailable) {
        border-color: #3b82f6 !important;
        background: #eff6ff !important;
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(59,130,246,0.15);
    }
    .dm-card.selected {
        border-color: #10b981 !important;
        background: #f0fdf4 !important;
        box-shadow: 0 4px 12px rgba(16,185,129,0.2);
    }
    .dm-card.unavailable {
        opacity: 0.4;
        cursor: not-allowed;
        filter: grayscale(1);
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .order-item:hover {
        border-color: #3b82f6 !important;
        box-shadow: 0 4px 12px rgba(59,130,246,0.15) !important;
        transform: translateY(-2px);
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Header -->
    <div class="page-header mb-4" style="background: #1e293b; padding: 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="mb-2" style="color: white; font-size: 32px; font-weight: 700;">
                    <i class="tio-dashboard"></i>
                    🚀 Unified Dispatch Management
                </h1>
                <p class="mb-0" style="color: rgba(255,255,255,0.8); font-size: 16px;">
                    Manage orders from all modules in one powerful interface
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light btn-lg" onclick="refreshDashboard()" style="border-radius: 10px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <i class="tio-refresh"></i> Refresh
                </button>
                <button class="btn btn-success btn-lg" onclick="autoAssignOrders()" style="border-radius: 10px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <i class="tio-add"></i> Auto Assign
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1" style="font-size: 42px; font-weight: 700;">{{ $stats['unassigned_orders'] ?? 0 }}</h2>
                        <p class="mb-0" style="font-size: 14px; opacity: 0.9;">Unassigned Orders</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 12px;">
                        <i class="tio-alarm" style="font-size: 40px;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1" style="font-size: 42px; font-weight: 700;">{{ $stats['active_deliveries'] ?? 0 }}</h2>
                        <p class="mb-0" style="font-size: 14px; opacity: 0.9;">Active Deliveries</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 12px;">
                        <i class="tio-checkmark-circle" style="font-size: 40px;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1" style="font-size: 42px; font-weight: 700;">{{ $stats['available_dm'] ?? 0 }}</h2>
                        <p class="mb-0" style="font-size: 14px; opacity: 0.9;">Available Delivery Men</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 12px;">
                        <i class="tio-user" style="font-size: 40px;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1" style="font-size: 42px; font-weight: 700;">{{ $stats['total_orders_today'] ?? 0 }}</h2>
                        <p class="mb-0" style="font-size: 14px; opacity: 0.9;">Orders Today</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 12px;">
                        <i class="tio-shopping-cart" style="font-size: 40px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4" style="border-radius: 16px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
        <div class="card-body" style="padding: 25px;">
            <h5 class="mb-4" style="font-weight: 700; font-size: 18px; color: #1e293b;">
                <i class="tio-filter-outlined"></i> Filters & Search
            </h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label style="font-weight: 600; color: #64748b; font-size: 13px; margin-bottom: 8px;">MODULE</label>
                    <select class="form-control" id="module-filter" onchange="applyFilters()" style="border-radius: 10px; border: 2px solid #e2e8f0; padding: 12px; font-weight: 500;">
                        <option value="all">All Modules</option>
                        @foreach($modules ?? [] as $module)
                            <option value="{{ $module->id }}">{{ $module->module_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label style="font-weight: 600; color: #64748b; font-size: 13px; margin-bottom: 8px;">STATUS</label>
                    <select class="form-control" id="status-filter" onchange="applyFilters()" style="border-radius: 10px; border: 2px solid #e2e8f0; padding: 12px; font-weight: 500;">
                        <option value="all">All Status</option>
                        <option value="pending">Unassigned</option>
                        <option value="searching_for_deliverymen">Searching for Delivery Man</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="processing">Processing</option>
                        <option value="picked_up">Out for Delivery</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label style="font-weight: 600; color: #64748b; font-size: 13px; margin-bottom: 8px;">ZONE</label>
                    <select class="form-control" id="zone-filter" onchange="applyFilters()" style="border-radius: 10px; border: 2px solid #e2e8f0; padding: 12px; font-weight: 500;">
                        <option value="all">All Zones</option>
                        @foreach($zones ?? [] as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label style="font-weight: 600; color: #64748b; font-size: 13px; margin-bottom: 8px;">SEARCH</label>
                    <input type="text" class="form-control" id="search-input" placeholder="Search orders..." onkeyup="searchOrders()" style="border-radius: 10px; border: 2px solid #e2e8f0; padding: 12px; font-weight: 500;">
                </div>
            </div>

            <!-- Quick Filter Chips -->
            <div class="mt-3">
                <label style="font-weight: 600; color: #64748b; font-size: 13px; margin-bottom: 10px;">QUICK FILTERS</label>
                <div>
                    <span class="filter-chip active" data-filter="all" onclick="quickFilter(this)" style="background: #3b82f6; color: white; font-weight: 600; border-radius: 10px; padding: 10px 20px;">
                        <i class="tio-apps"></i> All
                    </span>
                    <span class="filter-chip" data-filter="urgent" onclick="quickFilter(this)" style="border-radius: 10px; padding: 10px 20px; font-weight: 600;">
                        <i class="tio-alarm"></i> Urgent
                    </span>
                    <span class="filter-chip" data-filter="unassigned" onclick="quickFilter(this)" style="border-radius: 10px; padding: 10px 20px; font-weight: 600;">
                        <i class="tio-user-add"></i> Unassigned
                    </span>
                    <span class="filter-chip" data-filter="delayed" onclick="quickFilter(this)" style="border-radius: 10px; padding: 10px 20px; font-weight: 600;">
                        <i class="tio-time"></i> Delayed
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders and Delivery Men Section -->
    <div class="row">
        <!-- Orders List -->
        <div class="col-lg-8">
            <div class="card" style="border-radius: 16px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                <div class="card-header" style="background: #1e293b; border-radius: 16px 16px 0 0; padding: 20px; border: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color: white; font-weight: 700; font-size: 18px;">
                            <i class="tio-shopping-cart"></i> Orders
                            <span class="badge badge-light ml-2" id="order-count" style="font-size: 14px; padding: 6px 12px; border-radius: 8px;">{{ count($orders ?? []) }}</span>
                        </h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-light" onclick="changeView('list')" style="border-radius: 8px 0 0 8px;">
                                <i class="tio-list"></i>
                            </button>
                            <button class="btn btn-outline-light" onclick="changeView('grid')" style="border-radius: 0 8px 8px 0;">
                                <i class="tio-dashboard"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body" style="max-height: 800px; overflow-y: auto; padding: 15px;" id="orders-container">
                    @forelse($orders ?? [] as $order)
                    <div class="order-item {{ $order->order_status == 'pending' ? 'pending' : ($order->delivery_man_id ? 'assigned' : 'urgent') }}"
                         data-order-id="{{ $order->id }}"
                         data-module="{{ $order->module_id }}"
                         data-status="{{ $order->order_status }}"
                         style="background: white; border-radius: 12px; padding: 18px; margin-bottom: 12px; border: 2px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-3">
                                    <input type="checkbox" class="order-checkbox mr-3" value="{{ $order->id }}" style="width: 18px; height: 18px; cursor: pointer;">
                                    <h6 class="mb-0 mr-2" style="font-weight: 700; font-size: 16px;">
                                        <a href="{{ route('admin.dispatch.order.details', $order->id) }}" style="color: #1e293b; text-decoration: none;">
                                            #{{ $order->id }}
                                        </a>
                                    </h6>
                                    <span class="module-badge module-{{ strtolower($order->module->module_type ?? 'food') }}" style="font-size: 11px; padding: 5px 12px; border-radius: 6px; font-weight: 700;">
                                        {{ $order->module->module_name ?? 'N/A' }}
                                    </span>
                                    @if($order->scheduled)
                                        <span class="badge ml-2" style="background: #dbeafe; color: #1e40af; font-weight: 600; padding: 5px 10px; border-radius: 6px;">
                                            <i class="tio-time"></i> Scheduled
                                        </span>
                                    @endif
                                    @if(!$order->delivery_man_id)
                                        <span class="badge ml-2" style="background: #fee2e2; color: #991b1b; font-weight: 600; padding: 5px 10px; border-radius: 6px; animation: pulse 2s infinite;">
                                            <i class="tio-alarm"></i> UNASSIGNED
                                        </span>
                                    @endif
                                </div>

                                <div class="row" style="font-size: 13px; color: #64748b; font-weight: 500;">
                                    <div class="col-md-6 mb-2">
                                        <i class="tio-user" style="color: #3b82f6;"></i>
                                        <strong style="color: #1e293b;">{{ $order->customer->f_name ?? 'Guest' }} {{ $order->customer->l_name ?? '' }}</strong>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <i class="tio-shop" style="color: #8b5cf6;"></i>
                                        <strong style="color: #1e293b;">{{ $order->store->name ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <i class="tio-time" style="color: #f59e0b;"></i>
                                        {{ Helpers::time_date_format($order->created_at) }}
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <i class="tio-money" style="color: #10b981;"></i>
                                        <strong style="color: #059669; font-size: 14px;">{{ \App\CentralLogics\Helpers::format_currency($order->order_amount) }}</strong>
                                    </div>
                                </div>

                                @if($order->delivery_man)
                                <div class="mt-3 p-3" style="background: #f0fdf4; border-radius: 10px; border-left: 4px solid #10b981;">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $order->delivery_man->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                             class="dm-avatar mr-3" alt="DM" style="width: 40px; height: 40px;">
                                        <div>
                                            <strong style="display: block; color: #064e3b; font-size: 13px;">{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}</strong>
                                            <small style="color: #059669;">{{ $order->delivery_man->phone }}</small>
                                        </div>
                                        <span class="badge badge-success ml-auto" style="padding: 6px 12px; border-radius: 6px;">
                                            <i class="tio-checkmark-circle"></i> Assigned
                                        </span>
                                    </div>
                                </div>
                                @endif
                            </div>

                            <div class="text-right ml-3">
                                <span class="badge mb-3" style="padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 12px; background: {{ $order->order_status == 'delivered' ? '#10b981' : ($order->order_status == 'canceled' ? '#ef4444' : '#f59e0b') }}; color: white;">
                                    {{ strtoupper(str_replace('_', ' ', $order->order_status)) }}
                                </span>
                                <div class="d-flex flex-column gap-2">
                                    @if(!$order->delivery_man_id && $order->order_status != 'delivered' && $order->order_status != 'canceled')
                                    <button class="btn btn-primary btn-sm" onclick="assignDeliveryMan({{ $order->id }})" style="border-radius: 8px; font-weight: 600; padding: 8px 16px; white-space: nowrap;">
                                        <i class="tio-add"></i> Assign DM
                                    </button>
                                    @endif
                                    <button class="btn btn-outline-dark btn-sm" onclick="viewOrderDetails({{ $order->id }})" style="border-radius: 8px; font-weight: 600; padding: 8px 16px;">
                                        <i class="tio-visible"></i> View
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <img src="{{ asset('public/assets/admin/img/empty-box.png') }}" alt="empty" width="120" style="opacity: 0.5;">
                        <p class="mt-4" style="color: #94a3b8; font-weight: 600; font-size: 16px;">No orders found</p>
                        <p style="color: #cbd5e1;">Try adjusting your filters or refresh the page</p>
                    </div>
                    @endforelse

                    <!-- Bulk Actions -->
                    <div class="mt-3 p-4" id="bulk-actions" style="display: none; background: #3b82f6; border-radius: 12px; box-shadow: 0 4px 12px rgba(59,130,246,0.3);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span id="selected-count" style="color: white; font-weight: 700; font-size: 16px;">
                                <i class="tio-checkmark-circle"></i> 0 orders selected
                            </span>
                            <div>
                                <button class="btn btn-light btn-sm mr-2" onclick="bulkAssign()" style="border-radius: 8px; font-weight: 600; padding: 10px 20px;">
                                    <i class="tio-add"></i> Bulk Assign
                                </button>
                                <button class="btn btn-outline-light btn-sm" onclick="clearSelection()" style="border-radius: 8px; font-weight: 600; padding: 10px 20px;">
                                    <i class="tio-clear"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delivery Men Panel -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px; border-radius: 16px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                <div class="card-header" style="background: #059669; border-radius: 16px 16px 0 0; padding: 20px; border: none;">
                    <h5 class="mb-0" style="color: white; font-weight: 700; font-size: 18px;">
                        <i class="tio-user"></i> Available Delivery Men
                        <span class="badge badge-light ml-2" style="font-size: 14px; padding: 6px 12px; border-radius: 8px;">{{ count($delivery_men ?? []) }}</span>
                    </h5>
                </div>
                <div class="card-body delivery-man-selector" style="padding: 15px; max-height: 800px; overflow-y: auto;">
                    @forelse($delivery_men ?? [] as $dm)
                    <div class="dm-card {{ $dm->current_orders >= 3 ? 'unavailable' : '' }}"
                         onclick="selectDeliveryMan({{ $dm->id }}, this)"
                         style="padding: 15px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.3s; margin-bottom: 12px; background: white;">
                        <div class="d-flex align-items-center">
                            <img src="{{ $dm->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                 class="dm-avatar mr-3" alt="DM" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 3px solid #e2e8f0;">
                            <div class="flex-grow-1">
                                <div style="font-weight: 700; color: #1e293b; font-size: 14px;">{{ $dm->f_name }} {{ $dm->l_name }}</div>
                                <small style="color: #64748b; font-weight: 500;">{{ $dm->phone }}</small>
                                <div class="mt-1">
                                    <span class="badge" style="padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 11px; background: {{ $dm->current_orders < 1 ? '#dcfce7' : ($dm->current_orders < 3 ? '#fef3c7' : '#fee2e2') }}; color: {{ $dm->current_orders < 1 ? '#166534' : ($dm->current_orders < 3 ? '#92400e' : '#991b1b') }};">
                                        {{ $dm->current_orders }} Orders
                                    </span>
                                    @if($dm->zone)
                                    <span class="badge" style="padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 11px; background: #dbeafe; color: #1e40af;">
                                        {{ $dm->zone->name }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                @if($dm->current_orders < 3)
                                <div style="width: 40px; height: 40px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="tio-checkmark-circle" style="font-size: 24px; color: #16a34a;"></i>
                                </div>
                                @else
                                <div style="width: 40px; height: 40px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="tio-close-circle" style="font-size: 24px; color: #dc2626;"></i>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <i class="tio-user-big" style="font-size: 60px; color: #cbd5e1;"></i>
                        <p class="mt-3" style="color: #94a3b8; font-weight: 600;">No delivery men available</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Delivery Man Modal -->
<div class="modal fade" id="assignDeliveryManModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('messages.assign_delivery_man') }}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assign-order-id">
                <div class="form-group">
                    <label>{{ translate('messages.select_delivery_man') }}</label>
                    <select class="form-control" id="selected-dm-id">
                        <option value="">{{ translate('messages.select') }}</option>
                        @foreach($delivery_men ?? [] as $dm)
                            @if($dm->current_orders < 3)
                            <option value="{{ $dm->id }}">
                                {{ $dm->f_name }} {{ $dm->l_name }} ({{ $dm->current_orders }} orders)
                            </option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('messages.cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="confirmAssignment()">{{ translate('messages.assign') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script_2')
<script>
    let selectedOrders = [];
    let selectedDM = null;

    // Quick Filter
    function quickFilter(element) {
        $('.filter-chip').removeClass('active');
        $(element).addClass('active');
        let filter = $(element).data('filter');

        $('.order-item').show();
        if (filter !== 'all') {
            if (filter === 'urgent') {
                $('.order-item').not('.urgent').hide();
            } else if (filter === 'unassigned') {
                $('.order-item').not(':has(.badge:contains("unassigned"))').hide();
            } else if (filter === 'delayed') {
                // Show orders older than 30 minutes
                $('.order-item').each(function() {
                    // Add your delayed logic here
                });
            }
        }
    }

    // Apply Filters
    function applyFilters() {
        let module = $('#module-filter').val();
        let status = $('#status-filter').val();
        let zone = $('#zone-filter').val();

        $('.order-item').each(function() {
            let show = true;

            if (module !== 'all' && $(this).data('module') != module) show = false;
            if (status !== 'all' && $(this).data('status') !== status) show = false;

            if (show) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        updateOrderCount();
    }

    // Search Orders
    function searchOrders() {
        let query = $('#search-input').val().toLowerCase();

        $('.order-item').each(function() {
            let text = $(this).text().toLowerCase();
            if (text.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        updateOrderCount();
    }

    // Update Order Count
    function updateOrderCount() {
        let count = $('.order-item:visible').length;
        $('#order-count').text(count);
    }

    // Select/Deselect Orders
    $(document).on('change', '.order-checkbox', function() {
        let orderId = $(this).val();
        if ($(this).is(':checked')) {
            selectedOrders.push(orderId);
        } else {
            selectedOrders = selectedOrders.filter(id => id !== orderId);
        }

        if (selectedOrders.length > 0) {
            $('#bulk-actions').show();
            $('#selected-count').html('<i class="tio-checkmark-circle"></i> ' + selectedOrders.length + ' orders selected');
        } else {
            $('#bulk-actions').hide();
        }
    });

    // Clear Selection
    function clearSelection() {
        $('.order-checkbox').prop('checked', false);
        selectedOrders = [];
        $('#bulk-actions').hide();
    }

    // Select Delivery Man
    function selectDeliveryMan(dmId, element) {
        if ($(element).hasClass('unavailable')) {
            toastr.warning('⚠️ This delivery man is not available (already has 3+ orders)');
            return;
        }

        $('.dm-card').removeClass('selected');
        $(element).addClass('selected');
        selectedDM = dmId;

        // Visual feedback
        toastr.success('✅ Delivery man selected! Now select orders to bulk assign.');
    }

    // Assign Delivery Man to Order
    function assignDeliveryMan(orderId) {
        $('#assign-order-id').val(orderId);
        $('#assignDeliveryManModal').modal('show');
    }

    // Confirm Assignment
    function confirmAssignment() {
        let orderId = $('#assign-order-id').val();
        let dmId = $('#selected-dm-id').val();

        if (!dmId) {
            toastr.error('❌ Please select a delivery man');
            return;
        }

        // Show loading
        toastr.info('⏳ Assigning order...');

        $.ajax({
            url: '{{ route("admin.dispatch.assign-delivery-man") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                order_id: orderId,
                delivery_man_id: dmId
            },
            success: function(response) {
                toastr.success('✅ Order #' + orderId + ' assigned successfully!');
                $('#assignDeliveryManModal').modal('hide');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr) {
                let message = xhr.responseJSON?.message || 'Something went wrong';
                toastr.error('❌ ' + message);
            }
        });
    }

    // Bulk Assign
    function bulkAssign() {
        if (selectedOrders.length === 0) {
            toastr.warning('⚠️ Please select at least one order first!');
            return;
        }

        if (!selectedDM) {
            toastr.warning('⚠️ Please select a delivery man from the right panel!');
            // Highlight delivery man panel
            $('.delivery-man-selector').parent().css('box-shadow', '0 0 20px rgba(239,68,68,0.4)');
            setTimeout(function() {
                $('.delivery-man-selector').parent().css('box-shadow', '');
            }, 2000);
            return;
        }

        // Confirm bulk assign
        if (!confirm('🚀 Assign ' + selectedOrders.length + ' orders to the selected delivery man?')) {
            return;
        }

        // Show loading
        toastr.info('⏳ Bulk assigning ' + selectedOrders.length + ' orders...');

        $.ajax({
            url: '{{ route("admin.dispatch.bulk-assign") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                order_ids: selectedOrders,
                delivery_man_id: selectedDM
            },
            success: function(response) {
                toastr.success('✅ ' + selectedOrders.length + ' orders assigned successfully!');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                let message = xhr.responseJSON?.message || 'Failed to assign orders';
                toastr.error('❌ ' + message);
            }
        });
    }

    // Auto Assign Orders
    function autoAssignOrders() {
        if (!confirm('🤖 Auto-assign will automatically distribute all unassigned orders to available delivery men. Continue?')) {
            return;
        }

        // Show loading
        toastr.info('⏳ Running auto-assignment algorithm...');

        $.ajax({
            url: '{{ route("admin.dispatch.auto-assign") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success('🎉 ' + (response.message || 'Auto-assignment completed!'));
                setTimeout(function() {
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                let message = xhr.responseJSON?.message || 'Auto-assignment failed';
                toastr.error('❌ ' + message);
            }
        });
    }

    // View Order Details
    function viewOrderDetails(orderId) {
        window.location.href = '{{ route("admin.dispatch.order.details", "") }}/' + orderId;
    }

    // Refresh Dashboard
    function refreshDashboard() {
        location.reload();
    }

    // Change View
    function changeView(view) {
        // Implement grid/list view toggle
        if (view === 'grid') {
            // Add grid view classes
        } else {
            // Add list view classes
        }
    }

    // Auto-refresh every 30 seconds
    setInterval(function() {
        // You can implement partial refresh here
    }, 30000);
</script>
@endpush
