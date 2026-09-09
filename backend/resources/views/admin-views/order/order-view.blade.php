@extends('layouts.admin.app')

@section('title', translate('Order Details'))

@section('content')
    <style>
    /* Order Timer Styles */
    .order-timer-container {
        position: relative;
    }

    .timer-display {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e2022;
        font-family: 'Courier New', monospace;
        letter-spacing: 2px;
    }

    .timer-wrapper {
        border-left: 4px solid #377dff;
        transition: all 0.3s ease;
    }

    .timer-wrapper.warning {
        border-left-color: #ffc107;
        background-color: #fff8e1 !important;
    }

    .timer-wrapper.danger {
        border-left-color: #dc3545;
        background-color: #ffe5e8 !important;
    }
.timer-wrapper.completed {
    border-left-color: #28a745 !important;
    background-color: #e8f5e9 !important;
}

.timer-wrapper.completed .timer-display {
    color: #28a745;
}

.badge-lg {
    padding: 10px 16px;
    font-size: 0.95rem;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.timer-wrapper.warning .timer-icon i,
.timer-wrapper.danger .timer-icon i {
    animation: pulse 1.5s ease-in-out infinite;
}

/* Alert badge for 25+ minutes */
.timer-alert-badge {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #dc3545;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: bold;
    animation: pulse 2s ease-in-out infinite;
}
/* ===== Item Card - Clean Modern UI (Responsive) ===== */
.modern-item-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    transition: all .2s ease;
}

.modern-item-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    transform: translateY(-2px);
}

/* ===== Image ===== */
.item-image-wrapper {
    position: relative;
    overflow: hidden;
    background: #f8f9fa;
}

.item-image-wrapper img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    display: block;
    transition: transform .3s ease;
}

.modern-item-card:hover img {
    transform: scale(1.05);
}

@media (max-width: 992px) {
    .item-image-wrapper img {
        height: 120px;
    }
}

@media (max-width: 576px) {
    .item-image-wrapper img {
        height: 150px;
    }
}

/* ===== Badges ===== */
.quick-edit-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #3b82f6;
    color: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: .6rem;
    font-weight: 600;
    opacity: 0;
    transition: opacity .2s ease;
}

.modern-item-card:hover .quick-edit-badge {
    opacity: 1;
}

/* ===== Content ===== */
.item-info-section {
    padding: 10px;
}

@media (max-width: 768px) {
    .item-info-section {
        padding: 8px;
    }
}

.item-name {
    font-size: .8rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 6px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.1em;
}

@media (max-width: 768px) {
    .item-name {
        font-size: .75rem;
        min-height: 2em;
    }
}

/* ===== Price ===== */
.price-tag {
    display: inline-flex;
    align-items: center;
    background: #10b981;
    color: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: .75rem;
    font-weight: 600;
}

/* ===== Stock & Category ===== */
.stock-badge,
.category-badge {
    font-size: .65rem;
    padding: 3px 6px;
    border-radius: 3px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.category-badge {
    background: #ede9fe;
    color: #7c3aed;
    margin-bottom: 6px;
}

.category-badge i {
    font-size: .55rem;
}

.stock-badge.badge-soft-success {
    background: #d1fae5;
    color: #059669;
}

.stock-badge.badge-soft-warning {
    background: #fef3c7;
    color: #d97706;
}

/* ===== Button ===== */
.add-item-btn {
    width: 100%;
    background: #3b82f6;
    color: #fff;
    border: none;
    padding: 8px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.75rem;
    transition: background .2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.add-item-btn:hover {
    background: #2563eb;
}

.add-item-btn i {
    font-size: .85rem;
}

@media (max-width: 768px) {
    .add-item-btn {
        padding: 7px 8px;
        font-size: 0.7rem;
    }
    .add-item-btn i {
        font-size: .75rem;
    }
}

/* ===== Search ===== */
.enhanced-search {
    position: relative;
}

.enhanced-search input {
    width: 100%;
    padding: 10px 14px 10px 40px;
    border-radius: 22px;
    border: 1px solid #e7eaf3;
    transition: border-color .2s ease;
}

.enhanced-search input:focus {
    border-color: #4c6ef5;
    outline: none;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1rem;
    color: #9aa4b2;
}

/* ===== Filter ===== */
.filter-section {
    background: #f7f8fa;
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 16px;
}

/* ===== Grid - 4 Items Per Row (Responsive) ===== */
.items-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    padding: 8px 0;
}

@media (max-width: 1600px) {
    .items-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
    }
}

@media (max-width: 1400px) {
    .items-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
}

@media (max-width: 1200px) {
    .items-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
}

@media (max-width: 992px) {
    .items-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
}

@media (max-width: 768px) {
    .items-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .items-grid {
        grid-template-columns: 1fr;
        gap: 8px;
    }
}

/* Replacement Suggestion Styles */
.replacement-suggestion-card {
    background: linear-gradient(135deg, #fff3cd 0%, #fff8e1 100%);
    border: 2px dashed #ffc107;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0;
    animation: slideIn 0.5s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.replacement-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid rgba(255, 193, 7, 0.3);
}

.replacement-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #ff9800 0%, #ff5722 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.7);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 10px rgba(255, 152, 0, 0);
    }
}

.replacement-icon i {
    font-size: 1.5rem;
    color: white;
}

.replacement-title {
    flex: 1;
}

.replacement-title h5 {
    margin: 0;
    color: #f57c00;
    font-weight: 700;
}

.replacement-title p {
    margin: 0;
    font-size: 0.85rem;
    color: #7d6608;
}

.out-of-stock-item {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    border-left: 4px solid #dc3545;
}

.out-of-stock-badge {
    background: #dc3545;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    animation: blink 1.5s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.replacement-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.replacement-item-card {
    background: white;
    border: 2px solid #28a745;
    border-radius: 10px;
    padding: 15px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.replacement-item-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
}

.replacement-item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
    border-color: #20c997;
}

.similarity-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
}

.replacement-item-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
}

.replacement-item-details {
    text-align: center;
}

.replacement-item-name {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 8px;
    color: #1e2022;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.replacement-price {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.price-difference {
    font-size: 0.75rem;
    margin-top: 5px;
}

.price-difference.cheaper {
    color: #28a745;
}

.price-difference.expensive {
    color: #dc3545;
}

.add-replacement-btn {
    width: 100%;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    color: white;
    padding: 10px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.add-replacement-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);
    color: white;
}

.match-indicators {
    display: flex;
    gap: 5px;
    justify-content: center;
    margin: 10px 0;
    flex-wrap: wrap;
}

.match-indicator {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.match-indicator.category {
    background: #e3f2fd;
    color: #1976d2;
}

.match-indicator.price {
    background: #f3e5f5;
    color: #7b1fa2;
}

.match-indicator.brand {
    background: #e8f5e9;
    color: #388e3c;
}

.no-replacement-card {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
}

.no-replacement-card img {
    width: 120px;
    opacity: 0.5;
    margin-bottom: 15px;
}

/* ===== AJAX Search Enhancements ===== */

/* Search Input Wrapper */
.search-input-wrapper {
    position: relative;
    flex: 1;
}

.search-input-wrapper .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #8c98a4;
    pointer-events: none;
    z-index: 2;
}

.search-input-wrapper .form-control {
    padding-left: 40px;
    padding-right: 80px;
}

/* Search Loading Spinner */
.search-spinner {
    position: absolute;
    right: 45px;
    top: 50%;
    transform: translateY(-50%);
    display: none;
}

.search-spinner.active {
    display: block;
}

.search-spinner .spinner-border {
    width: 18px;
    height: 18px;
    border-width: 2px;
    color: #3498db;
}

/* Clear Button */
.search-clear-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: #e9ecef;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;
    z-index: 2;
}

.search-clear-btn.active {
    display: flex;
}

.search-clear-btn:hover {
    background: #dee2e6;
}

.search-clear-btn i {
    font-size: 14px;
    color: #6c757d;
}

/* Result Count Badge */
.result-count-badge {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-left: 10px;
    display: none;
}

.result-count-badge.active {
    display: inline-block;
}

/* Skeleton Loader - Simple (Responsive) */
.skeleton-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    padding: 8px 0;
}

@media (max-width: 1400px) {
    .skeleton-container {
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
}

@media (max-width: 992px) {
    .skeleton-container {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .skeleton-container {
        grid-template-columns: 1fr;
        gap: 8px;
    }
}

.skeleton-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.skeleton-image {
    height: 140px;
    background: #f1f5f9;
    animation: pulse 1.5s ease-in-out infinite;
}

@media (max-width: 992px) {
    .skeleton-image {
        height: 120px;
    }
}

.skeleton-content {
    padding: 10px;
}

.skeleton-line {
    height: 10px;
    background: #f1f5f9;
    animation: pulse 1.5s ease-in-out infinite;
    border-radius: 3px;
    margin-bottom: 8px;
}

.skeleton-line.short {
    width: 35%;
    height: 14px;
    border-radius: 3px;
}

.skeleton-line.medium {
    width: 65%;
}

.skeleton-line.tall {
    height: 32px;
    margin-top: 8px;
    border-radius: 6px;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

/* AJAX Result Animation */
.ajax-result-item {
    animation: fadeInUp 0.3s ease forwards;
    opacity: 0;
}

.ajax-result-item:nth-child(1) { animation-delay: 0.05s; }
.ajax-result-item:nth-child(2) { animation-delay: 0.1s; }
.ajax-result-item:nth-child(3) { animation-delay: 0.15s; }
.ajax-result-item:nth-child(4) { animation-delay: 0.2s; }
.ajax-result-item:nth-child(5) { animation-delay: 0.25s; }
.ajax-result-item:nth-child(6) { animation-delay: 0.3s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Barcode Badge */
.barcode-badge {
    display: inline-block;
    background: #f8f9fa;
    color: #6c757d;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-family: monospace;
}

.barcode-badge i {
    margin-right: 4px;
}

/* Search Shortcut Hint */
.search-shortcut-hint {
    font-size: 0.75rem;
    color: #8c98a4;
    margin-top: 5px;
}

.search-shortcut-hint kbd {
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.7rem;
}

/* No Results Container */
.no-results-container {
    grid-column: 1 / -1;
}

/* Floating Bill Comparison Panel */
.bill-floating-panel {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1050;
}
.bill-panel-backdrop {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    animation: billFadeIn 0.2s ease;
}
.bill-panel-content {
    position: absolute;
    top: 0;
    right: 0;
    width: 900px;
    max-width: 95vw;
    height: 100vh;
    background: #fff;
    box-shadow: -4px 0 20px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    animation: billSlideIn 0.25s ease;
}
.bill-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    border-bottom: 1px solid #e7eaf3;
    background: #f8f9fa;
    flex-shrink: 0;
}
.bill-panel-body {
    display: flex;
    flex: 1;
    overflow: hidden;
}
.bill-panel-left {
    width: 45%;
    padding: 16px;
    overflow-y: auto;
    border-right: 1px solid #e7eaf3;
    background: #fafbfc;
}
.bill-panel-right {
    width: 55%;
    padding: 16px;
    overflow-y: auto;
}
.bill-panel-section-title {
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    color: #71869d;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    padding-bottom: 4px;
    border-bottom: 2px solid #e7eaf3;
}
.bill-panel-img {
    width: 100%;
    max-height: 65vh;
    object-fit: contain;
    border-radius: 6px;
    border: 1px solid #e7eaf3;
    cursor: zoom-in;
    transition: transform 0.2s ease;
}
.bill-panel-img.bill-img-zoomed {
    cursor: zoom-out;
    transform: scale(1.5);
    transform-origin: top center;
    position: relative;
    z-index: 10;
}
.bill-image-slide { display: none; }
.bill-image-slide.active { display: block; }
@keyframes billSlideIn {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
@keyframes billFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@media (max-width: 768px) {
    .bill-panel-body { flex-direction: column; }
    .bill-panel-left, .bill-panel-right { width: 100%; }
    .bill-panel-left { max-height: 40vh; border-right: none; border-bottom: 1px solid #e7eaf3; }
}
/* Docked bill panel for edit mode */
.bill-floating-panel.bill-panel-docked {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: auto;
    width: 420px;
    z-index: 1040;
}
.bill-panel-docked .bill-panel-backdrop { display: none; }
.bill-panel-docked .bill-panel-content { width: 100%; position: relative; height: 100%; }
.bill-panel-docked .bill-panel-body { flex-direction: column; }
.bill-panel-docked .bill-panel-left, .bill-panel-docked .bill-panel-right { width: 100%; }
.bill-panel-docked .bill-panel-left { max-height: 40vh; border-right: none; border-bottom: 1px solid #e7eaf3; }
body.bill-docked-open .content { margin-right: 420px; transition: margin-right 0.25s ease; }
/* Old inline editing CSS removed - V2 is now the only edit interface */

/* Compact Item Info */
.item-qty-price { display: inline-flex; align-items: center; gap: 4px; font-size: 0.9rem; margin-top: 4px; }
.item-qty-price .qty { font-weight: 700; color: #377dff; }
.item-qty-price .unit-badge { font-size: 0.75rem; background: #e8f0fe; color: #377dff; padding: 1px 6px; border-radius: 3px; }

/* Bootstrap btn-xs (Bootstrap 4 compatibility) */
.btn-xs {
    padding: 0.1rem 0.4rem;
    font-size: 0.7rem;
    line-height: 1.35;
    border-radius: 0.2rem;
}

    </style>




    <?php
    $deliverman_tips = 0;
   $campaign_order = isset($order?->details[0]?->item_campaign_id )  ? true : false;
    $reasons=\App\Models\OrderCancelReason::where('status', 1)->where('user_type' ,'admin' )->get();
    $parcel_order = $order->order_type == 'parcel' ? true : false;
    $tax_included =0;
    $max_processing_time = $order->store?explode('-', $order->store['delivery_time'])[0]:0;
    ?>
    <div class="content container-fluid order-detail-wrapper" id="orderDetailWrapper">
        {{-- V2 is now the only edit interface - inline editing removed --}}
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <span class="page-header-icon">
                            <img src="{{ asset('/public/assets/admin/img/shopping-basket.png') }}" class="w--20"
                                 alt="">
                        </span>
                        <span>
                            {{ translate('order_details') }} <span
                                class="badge badge-soft-dark rounded-circle ml-1">{{ $order->details->count() }}</span>
                        </span>
                    </h1>
                </div>

                <div class="col-sm-auto">
                    {{-- NON-BREAKING: Only shows for bargaining orders, doesn't affect existing UI --}}
                    @if(isset($order->is_bargaining_order) && $order->is_bargaining_order)
                    <a href="{{ route('admin.order.view-v2', $order->id) }}" class="btn btn-sm btn-soft-purple mr-2" data-toggle="tooltip" data-placement="top" title="{{ translate('messages.view_bargaining_details') }}">
                        <i class="tio-trophy"></i> {{ translate('messages.bargaining_v2') }}
                    </a>
                    @endif
                    <a class="btn-icon btn-sm btn-soft-secondary rounded-circle mr-1"
                       href="{{ route('admin.order.details', [$order['id'] - 1]) }}" data-toggle="tooltip"
                       data-placement="top" title="{{ translate('Previous order') }}">
                        <i class="tio-chevron-left"></i>
                    </a>
                    <a class="btn-icon btn-sm btn-soft-secondary rounded-circle"
                       href="{{ route('admin.order.details', [$order['id'] + 1]) }}" data-toggle="tooltip"
                       data-placement="top" title="{{ translate('Next order') }}">
                        <i class="tio-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- Page Header -->

        @php
            $refund_amount = $order->order_amount - $order->delivery_charge - $order->dm_tips;
        @endphp


{{-- 🆕 DELIVERY TRACKING - WITH ESTIMATED DELIVERY TIME --}}
@if ($order->delivery_man && $order->dm_last_location)
@php
    // Get customer delivery coordinates
    $customer_lat = 0;
    $customer_lng = 0;
    
    // Priority 1: delivery_address (JSON field)
    if (!empty($order->delivery_address)) {
        $delivery_addr = is_string($order->delivery_address) ? json_decode($order->delivery_address, true) : $order->delivery_address;
        $customer_lat = $delivery_addr['latitude'] ?? 0;
        $customer_lng = $delivery_addr['longitude'] ?? 0;
    }
    
    // Priority 2: Check if there's a separate address variable
    if (($customer_lat == 0 || $customer_lng == 0) && isset($address)) {
        $customer_lat = $address['latitude'] ?? 0;
        $customer_lng = $address['longitude'] ?? 0;
    }
    
    // Priority 3: Customer's default location
    if (($customer_lat == 0 || $customer_lng == 0) && $order->customer) {
        $customer_lat = $order->customer->latitude ?? 0;
        $customer_lng = $order->customer->longitude ?? 0;
    }
    
    // Get store coordinates
    $store_lat = 0;
    $store_lng = 0;
    if ($order->store && !$parcel_order) {
        $store_lat = $order->store->latitude ?? 0;
        $store_lng = $order->store->longitude ?? 0;
    }
@endphp

<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- REAL-TIME DELIVERY TRACKING v2.0 - WebSocket Enabled -->
<!-- ══════════════════════════════════════════════════════════════════ -->

<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- WEBSOCKET DATA (Hidden) -->
<div id="websocketOrderData" style="display:none;"
     data-dm-id="{{ $order->delivery_man->id }}"
     data-pusher-key="{{ env('PUSHER_APP_KEY', '') }}"
     data-pusher-cluster="{{ env('PUSHER_APP_CLUSTER', 'us2') }}"
     data-customer-lat="{{ $address['latitude'] ?? 0 }}"
     data-customer-lng="{{ $address['longitude'] ?? 0 }}">
</div>

<!-- WebSocket Update Feedback -->
<div id="websocketFeedback" style="display:none; position:fixed; top:20px; right:20px; background:#10b981; color:white; padding:12px 20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.2); z-index:9999; font-weight:600;">
    📍 Live Update Received
</div>

<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- COMPACT DELIVERY TRACKING - Always Visible -->
<!-- ══════════════════════════════════════════════════════════════════ -->
<div class="compact-delivery-tracking" id="deliveryTrackingCard">
    <div class="compact-tracking-row">
        <!-- Left: DM Info with Avatar -->
        <div class="tracking-dm-info">
            <div class="dm-avatar-wrapper">
                @if($order->delivery_man->image)
                <img src="{{ $order->delivery_man->image_full_url }}" alt="DM" class="dm-avatar">
                @else
                <div class="dm-avatar-placeholder">
                    <i class="tio-user"></i>
                </div>
                @endif
                <span class="live-badge-dot" id="liveBadge" title="Live tracking active">
                    <span class="dot-pulse"></span>
                </span>
            </div>
            <div class="dm-details">
                <div class="dm-name">{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}</div>
                <div class="dm-status">
                    <i class="tio-location-pin"></i>
                    <span id="movementStatus">Calculating...</span>
                </div>
            </div>
        </div>

        <!-- Center: Distance & ETA Stats -->
        <div class="tracking-stats-inline">
            <div class="stat-box stat-distance">
                <i class="tio-poi"></i>
                <div class="stat-content">
                    <div class="stat-label">Distance</div>
                    <div class="stat-value" id="quickDistance">-</div>
                </div>
            </div>
            <div class="stat-separator"></div>
            <div class="stat-box stat-eta">
                <i class="tio-time"></i>
                <div class="stat-content">
                    <div class="stat-label">ETA</div>
                    <div class="stat-value" id="quickETA">-</div>
                </div>
            </div>
            <div class="stat-separator"></div>
            <div class="stat-box stat-speed">
                <i class="tio-directions"></i>
                <div class="stat-content">
                    <div class="stat-label">Speed</div>
                    <div class="stat-value" id="speedDM">-</div>
                </div>
            </div>
        </div>

        <!-- Right: Quick Actions & Status -->
        <div class="tracking-actions-inline">
            <span class="nearby-indicator" id="nearbyBadge" style="display:none;">
                <i class="tio-checkmark-circle"></i> NEARBY
            </span>
            <span class="last-update time-fresh" id="lastUpdateTime">Just now</span>

            <button type="button" class="btn-tracking btn-map" data-toggle="modal" data-target="#locationModal" title="View on map">
                <i class="tio-map"></i>
            </button>

            @if($order->delivery_man->phone)
            <a href="tel:{{ $order->delivery_man->phone }}" class="btn-tracking btn-call" title="Call delivery man">
                <i class="tio-call"></i>
            </a>
            @endif

            <i class="tio-refresh" id="trackingRefreshIcon" title="Refresh tracking"></i>
        </div>
    </div>

    <!-- Compact Progress Bar -->
    <div class="compact-progress-bar">
        <div class="progress-fill" id="progressFill" style="width: 0%;"></div>
    </div>

    <!-- Delivery Status Details Row -->
    <div class="delivery-status-details" id="deliveryStatusDetails">
        <div class="status-detail-row">
            <!-- Current Location Status -->
            <div class="status-badge-group">
                <div class="status-badge badge-location" id="locationStatus" style="display:none;"
                     onclick="$('#locationModal').modal('show');"
                     title="Click to view delivery man location on map">
                    <i class="tio-shop"></i>
                    <span>At Store</span>
                    <span class="badge-distance" id="locationDistance"></span>
                </div>
                <div class="status-badge badge-enroute" id="enrouteStatus" style="display:none;"
                     onclick="$('#locationModal').modal('show');"
                     title="Click to track delivery in real-time">
                    <i class="tio-directions"></i>
                    <span>En Route to Customer</span>
                    <span class="badge-distance" id="enrouteDistance"></span>
                </div>
                <div class="status-badge badge-nearby" id="nearbyStatus" style="display:none;"
                     onclick="$('#locationModal').modal('show');"
                     title="Delivery man is nearby! Click to view exact location">
                    <i class="tio-poi"></i>
                    <span>Nearby Customer</span>
                    <span class="badge-distance" id="nearbyDistance"></span>
                </div>
                <div class="status-badge badge-arrived" id="arrivedStatus" style="display:none;"
                     onclick="$('#locationModal').modal('show');"
                     title="Delivery man has arrived! Click to see location">
                    <i class="tio-checkmark-circle"></i>
                    <span>Arrived at Customer</span>
                    <span class="badge-distance" id="arrivedDistance"></span>
                </div>
            </div>

            <!-- Additional Details -->
            <div class="status-details-text">
                <div class="detail-item" id="distanceDetail">
                    <i class="tio-poi"></i>
                    <span id="distanceDetailText">-</span>
                </div>
                <div class="detail-item" id="durationDetail" style="display:none;">
                    <i class="tio-time"></i>
                    <span id="durationDetailText">-</span>
                </div>
                <div class="detail-item" id="speedDetail" style="display:none;">
                    <i class="tio-directions"></i>
                    <span id="speedDetailText">-</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delivery Tracking now handled inline (DeliveryTracking.init() removed - external module not needed) -->

<style>
/* ══════════════════════════════════════════════════════════════════ */
/* COMPACT DELIVERY TRACKING - Always Visible */
/* ══════════════════════════════════════════════════════════════════ */

.compact-delivery-tracking {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.5s ease;
}

/* ── Status-Based Background Colors ────────────────────── */

/* At Store - Warm Orange/Amber Background */
.compact-delivery-tracking.status-at-store {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border-color: #f59e0b;
    box-shadow: 0 4px 16px rgba(245, 158, 11, 0.2);
    animation: glow-orange 3s ease-in-out infinite;
}

@keyframes glow-orange {
    0%, 100% { box-shadow: 0 4px 16px rgba(245, 158, 11, 0.2); }
    50% { box-shadow: 0 6px 24px rgba(245, 158, 11, 0.3); }
}

/* ── Real-Time Update Animation ────────────────────── */
@keyframes refresh-pulse {
    0% {
        transform: scale(1);
        background-color: transparent;
    }
    25% {
        transform: scale(1.05);
        background-color: rgba(59, 130, 246, 0.1);
    }
    50% {
        transform: scale(1.08);
        background-color: rgba(16, 185, 129, 0.15);
    }
    75% {
        transform: scale(1.05);
        background-color: rgba(59, 130, 246, 0.1);
    }
    100% {
        transform: scale(1);
        background-color: transparent;
    }
}

@keyframes refresh-glow {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        color: inherit;
    }
    50% {
        box-shadow: 0 0 15px 3px rgba(16, 185, 129, 0.4);
        color: #10b981;
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        color: inherit;
    }
}

.value-updating {
    animation: refresh-pulse 0.6s ease-out, refresh-glow 0.6s ease-out;
    font-weight: 600;
    transition: all 0.3s ease;
}

.stat-card-updating {
    position: relative;
}

.stat-card-updating::after {
    content: '●';
    position: absolute;
    top: 8px;
    right: 8px;
    color: #10b981;
    font-size: 12px;
    animation: blink 1s ease-in-out infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* Moving/En Route - Cool Blue Background */
.compact-delivery-tracking.status-moving {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-color: #3b82f6;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.2);
    animation: glow-blue 3s ease-in-out infinite;
}

@keyframes glow-blue {
    0%, 100% { box-shadow: 0 4px 16px rgba(59, 130, 246, 0.2); }
    50% { box-shadow: 0 6px 24px rgba(59, 130, 246, 0.3); }
}

/* Nearby Customer - Vibrant Green Background with STRONG Pulse */
.compact-delivery-tracking.status-nearby {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-color: #10b981;
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
    animation: pulse-green-strong 1.5s ease-in-out infinite;
}

@keyframes pulse-green-strong {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        border-width: 2px;
    }
    50% {
        transform: scale(1.02);
        box-shadow: 0 10px 32px rgba(16, 185, 129, 0.5);
        border-width: 3px;
    }
}

/* Arrived - Success Green Background with Celebration Glow */
.compact-delivery-tracking.status-arrived {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-color: #22c55e;
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
    animation: success-celebration 2s ease-in-out;
}

@keyframes success-celebration {
    0% { transform: scale(1); box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4); }
    25% { transform: scale(1.03); box-shadow: 0 8px 28px rgba(34, 197, 94, 0.6); }
    50% { transform: scale(1.01); box-shadow: 0 10px 32px rgba(34, 197, 94, 0.5); }
    75% { transform: scale(1.02); box-shadow: 0 8px 28px rgba(34, 197, 94, 0.6); }
    100% { transform: scale(1); box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4); }
}

.compact-tracking-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    gap: 20px;
}

/* ── DM Info Section ────────────────────── */
.tracking-dm-info {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 200px;
}

.dm-avatar-wrapper {
    position: relative;
}

.dm-avatar, .dm-avatar-placeholder {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2px solid #e5e7eb;
}

.dm-avatar {
    object-fit: cover;
}

.dm-avatar-placeholder {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.live-badge-dot {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 16px;
    height: 16px;
    background: #10b981;
    border: 2px solid white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dot-pulse {
    width: 6px;
    height: 6px;
    background: white;
    border-radius: 50%;
    animation: pulse-dot 1.5s ease-in-out infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.8); }
}

.dm-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.dm-name {
    font-size: 14px;
    font-weight: 700;
    color: #111827;
    line-height: 1.2;
}

.dm-status {
    font-size: 12px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 4px;
}

.dm-status i {
    font-size: 13px;
    color: #3b82f6;
    animation: pulse-location 2s infinite;
}

@keyframes pulse-location {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* ── Inline Stats Section ────────────────────── */
.tracking-stats-inline {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
    justify-content: center;
}

.stat-box {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stat-box i {
    font-size: 20px;
    color: #3b82f6;
}

.stat-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.stat-label {
    font-size: 10px;
    color: #9ca3af;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 14px;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.stat-separator {
    width: 1px;
    height: 32px;
    background: #e5e7eb;
}

.nearby-indicator {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: linear-gradient(90deg, #10b981, #059669);
    color: white;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    50% { opacity: 0.9; box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
}

/* ── Compact Progress Bar ────────────────────── */
.compact-progress-bar {
    height: 4px;
    background: #e5e7eb;
    position: relative;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #10b981);
    transition: width 0.5s ease;
    position: relative;
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* ── Delivery Status Details Row ────────────────────── */
.delivery-status-details {
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    padding: 10px 18px;
}

.status-detail-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.status-badge-group {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.badge-distance {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 8px;
    padding: 2px 8px;
    font-size: 10px;
    font-weight: 700;
    margin-left: 4px;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.status-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.status-badge:hover::before {
    left: 100%;
}

.status-badge i {
    font-size: 16px;
    animation: icon-bounce 1s ease-in-out infinite;
}

@keyframes icon-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-2px); }
}

.badge-location {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
    border-color: #fbbf24;
}

.badge-location:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5);
}

.badge-location i {
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}

.badge-enroute {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    border-color: #60a5fa;
}

.badge-enroute:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
}

.badge-enroute i {
    animation: spin-slow 3s linear infinite;
}

@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.badge-nearby {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    border-color: #34d399;
    animation: pulse-nearby 1.5s ease-in-out infinite;
}

@keyframes pulse-nearby {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
    50% {
        transform: scale(1.08);
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.6);
    }
}

.badge-nearby i {
    animation: ping 1.5s ease-in-out infinite;
}

@keyframes ping {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.2); }
}

.badge-arrived {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    border-color: #34d399;
    animation: success-glow 2s ease-in-out infinite;
}

@keyframes success-glow {
    0%, 100% { box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
    50% { box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6); }
}

.badge-arrived:hover {
    transform: scale(1.05);
}

.status-details-text {
    display: flex;
    gap: 16px;
    align-items: center;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #6b7280;
}

.detail-item i {
    font-size: 14px;
    color: #9ca3af;
}

.detail-item span {
    font-weight: 600;
}

/* ── Actions Section ────────────────────── */
.tracking-actions-inline {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 240px;
    justify-content: flex-end;
}

.btn-tracking {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 16px;
    text-decoration: none;
}

.btn-tracking:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn-map {
    color: #3b82f6;
    border-color: #3b82f6;
}

.btn-map:hover {
    background: #3b82f6;
    color: white;
}

.btn-call {
    color: #10b981;
    border-color: #10b981;
}

.btn-call:hover {
    background: #10b981;
    color: white;
}

#trackingRefreshIcon {
    font-size: 18px;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.3s ease;
}

#trackingRefreshIcon:hover {
    color: #3b82f6;
    transform: rotate(180deg);
}

.last-update {
    font-size: 10px;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 6px;
}

.time-fresh {
    color: #10b981;
    background: #ecfdf5;
}

.time-recent {
    color: #f59e0b;
    background: #fffbeb;
}

.time-stale {
    color: #ef4444;
    background: #fef2f2;
}

.time-old {
    color: #9ca3af;
    background: #f3f4f6;
}

.time-very-old {
    color: #dc2626;
    background: #fef2f2;
    font-weight: 700;
    border: 1px solid #fca5a5;
}

#trackingRefreshIcon {
    font-size: 14px;
    color: #6b7280;
}

#trackingRefreshIcon.spinning {
    animation: spin 0.5s linear;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.toggle-icon {
    font-size: 16px;
    color: #6b7280;
    transition: transform 0.3s ease;
}

/* ── BODY (Expanded View - Hidden by Default) ───────────────────── */
.tracking-body {
    padding: 20px;
}

/* Stale Data Warning */
.stale-data-warning {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 2px solid #fca5a5;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 16px;
    color: #991b1b;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.stale-data-warning i {
    font-size: 18px;
    color: #dc2626;
}

/* Delivery Banner */
.delivery-banner {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.banner-nearby {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border: 2px solid #10b981;
    animation: banner-pulse 2s infinite;
}

.banner-at-store {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 2px solid #f59e0b;
}

.banner-in-transit {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border: 2px solid #3b82f6;
}

@keyframes banner-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
}

.banner-icon {
    font-size: 36px;
    line-height: 1;
    flex-shrink: 0;
}

.banner-content {
    flex: 1;
}

.banner-title {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 4px;
}

.banner-text {
    font-size: 13px;
    color: #6b7280;
}

/* Distance Cards */
.distance-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.distance-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
}

.distance-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.card-store {
    border-color: #fbbf24;
}

.card-store:hover {
    background: #fffbeb;
}

.card-transit {
    border-color: #3b82f6;
}

.card-transit:hover {
    background: #eff6ff;
}

.card-customer {
    border-color: #10b981;
}

.card-customer:hover {
    background: #ecfdf5;
}

.card-customer.nearby {
    border-color: #10b981;
    border-width: 3px;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    animation: card-pulse 2s infinite;
}

@keyframes card-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
}

.card-icon {
    font-size: 32px;
    margin-bottom: 8px;
}

.card-label {
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 600;
    color: #9ca3af;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.card-distance, .card-speed {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 8px 0;
}

.card-eta, .card-state {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 8px;
}

.card-trend {
    font-size: 18px;
}

.card-status {
    margin-top: 8px;
}

.nearby-pulse {
    display: inline-block;
    background: #10b981;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    animation: pulse-badge 2s infinite;
}

/* Journey Progress */
.journey-progress {
    margin: 24px 0;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
}

.progress-label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.progress-bar-container {
    height: 12px;
    background: #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #2563eb);
    border-radius: 6px;
    transition: width 0.5s ease;
    box-shadow: 0 0 6px rgba(59, 130, 246, 0.4);
}

.progress-percentage {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
    margin-top: 6px;
    text-align: center;
}

/* Tracking Stats */
.tracking-stats {
    margin: 24px 0;
    padding: 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.tracking-stats h6 {
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f3f4f6;
}

.stat-label {
    font-size: 12px;
    color: #6b7280;
}

.stat-value {
    font-size: 13px;
    font-weight: 600;
    color: #111827;
}

/* Action Buttons Row */
.tracking-actions-row {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .distance-cards {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .tracking-header {
        flex-wrap: wrap;
        gap: 12px;
    }

    .tracking-summary {
        order: 3;
        width: 100%;
    }
}

/* Utility Classes */
.avatar-sm {
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
.avatar-sm i { font-size: 1.25rem; }
.text-white-70 { opacity: 0.85; }

/* Live pulse dot */
.dt-live {
    display: inline-block;
    width: 8px; height: 8px;
    background: #22c55e; border-radius: 50%;
    animation: dtPulse 1.5s ease-in-out infinite;
}
@keyframes dtPulse { 0%,100%{opacity:1;transform:scale(1);} 50%{opacity:0.45;transform:scale(0.8);} }

/* Journey track */
.dt-journey {
    display: flex; align-items: center; gap: 8px;
    padding: 4px 18px 14px;
}
.dt-ep { display: flex; flex-direction: column; align-items: center; gap: 4px; flex-shrink: 0; width: 54px; text-align: center; }
.dt-ep-r { align-items: center; }
.dt-ep-dot { font-size: 20px; line-height: 1; }
.dt-ep-active-amber { filter: drop-shadow(0 0 5px #f59e0b); animation: dtEpPulse 1.6s ease-in-out infinite; }
.dt-ep-active-green { filter: drop-shadow(0 0 5px #22c55e); animation: dtEpPulse 1.6s ease-in-out infinite; }
@keyframes dtEpPulse { 0%,100%{transform:scale(1);} 50%{transform:scale(1.18);} }
.dt-ep-name {
    font-size: 10px; color: #9ca3af; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.3px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 54px;
}
.dt-road-wrap { flex: 1; }
.dt-road { position: relative; height: 8px; background: #f1f5f9; border-radius: 100px; }
.dt-road-fill {
    position: absolute; left: 0; top: 0; height: 100%;
    border-radius: 100px; transition: width 1.6s ease;
}
.dt-fill-amber  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.dt-fill-blue   { background: linear-gradient(90deg, #3b82f6, #6366f1); box-shadow: 0 0 6px rgba(99,102,241,0.3); }
.dt-fill-green  { background: linear-gradient(90deg, #22c55e, #16a34a); box-shadow: 0 0 6px rgba(34,197,94,0.3); }
.dt-rider {
    position: absolute; top: 50%;
    transform: translate(-50%, -55%);
    font-size: 18px; line-height: 1; z-index: 5;
    transition: left 1.6s ease;
    filter: drop-shadow(0 1px 3px rgba(0,0,0,0.18));
}
.dt-road-label {
    text-align: center; font-size: 10px; font-weight: 600;
    color: #9ca3af; margin-top: 5px; letter-spacing: 0.3px;
}

/* Stats row */
.dt-stats {
    display: flex; align-items: center;
    padding: 10px 18px 14px;
    border-top: 1px solid #f3f4f6;
}
.dt-stat { flex: 1; text-align: center; }
.dt-sv { font-size: 15px; font-weight: 800; color: #111827; line-height: 1; }
.dt-sk { font-size: 10px; color: #9ca3af; margin-top: 3px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px; }
.dt-sep { width: 1px; background: #e9ecef; height: 32px; flex-shrink: 0; }
.dt-ping { margin-left: auto; font-size: 11px; color: #9ca3af; white-space: nowrap; padding-left: 12px; }

/* No-location state */
.dt-no-location {
    padding: 14px 16px; border-radius: 10px;
    background: #fff8e1; color: #92400e;
    font-size: 13px; border: 1px solid #fcd34d;
}

/* Unused — kept to prevent grep noise */
.lf2-card {
/* Coloured left-border accent per state */
.lf2-store    { border-left: 3px solid #f59e0b; }
.lf2-moving   { border-left: 3px solid #6366f1; }
.lf2-delivered{ border-left: 3px solid #22c55e; }

/* ── Row 1: Status ── */
.lf2-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 16px 18px 14px;
}
.lf2-status { display: flex; align-items: flex-start; gap: 12px; }
.lf2-icon { font-size: 28px; line-height: 1; flex-shrink: 0; }
.lf2-title {
    font-size: 15px;
    font-weight: 700;
    color: #f1f5f9;
    line-height: 1.3;
}
.lf2-sub {
    font-size: 12px;
    color: #64748b;
    margin-top: 3px;
}
.lf2-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}
.lf2-big-time {
    font-size: 22px;
    font-weight: 800;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.lf2-big-time.t-amber { color: #f59e0b; }
.lf2-big-time.t-green { color: #22c55e; }
.lf2-live {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 10px;
    font-weight: 700;
    color: #4ade80;
    letter-spacing: 0.5px;
}
.lf2-dot {
    width: 6px; height: 6px;
    background: #4ade80;
    border-radius: 50%;
    animation: lf2blink 1.2s ease-in-out infinite;
}
@keyframes lf2blink { 0%,100%{opacity:1;} 50%{opacity:0.25;} }

/* ── Row 2: Track ── */
.lf2-track {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 18px 16px;
}
.lf2-ep {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    width: 50px;
    flex-shrink: 0;
    text-align: center;
}
.lf2-ep-icon {
    font-size: 22px;
    line-height: 1;
    transition: transform 0.3s;
}
.lf2-ep-icon.glow-amber { animation: lf2glowAmber 1.5s ease-in-out infinite; }
.lf2-ep-icon.glow-green { animation: lf2glowGreen 1.5s ease-in-out infinite; }
@keyframes lf2glowAmber { 0%,100%{filter:drop-shadow(0 0 0px #f59e0b);} 50%{filter:drop-shadow(0 0 6px #f59e0b);} }
@keyframes lf2glowGreen { 0%,100%{filter:drop-shadow(0 0 0px #22c55e);} 50%{filter:drop-shadow(0 0 8px #22c55e);} }
.lf2-ep-name {
    font-size: 10px;
    font-weight: 600;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 50px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.lf2-ep-r { align-items: center; }
.lf2-road-wrap { flex: 1; }
.lf2-road {
    position: relative;
    height: 7px;
    background: rgba(255,255,255,0.07);
    border-radius: 100px;
}
.lf2-fill {
    position: absolute;
    left: 0; top: 0; height: 100%;
    border-radius: 100px;
    width: 0%;
    transition: width 2s ease;
}
.lf2-fill.fc-store    { background: #f59e0b; }
.lf2-fill.fc-moving   { background: linear-gradient(90deg,#6366f1,#8b5cf6); box-shadow:0 0 8px rgba(99,102,241,0.5); }
.lf2-fill.fc-delivered{ background: #22c55e; box-shadow:0 0 8px rgba(34,197,94,0.4); }
.lf2-rider {
    position: absolute;
    top: 50%;
    left: 0%;
    transform: translate(-50%, -50%);
    font-size: 22px;
    line-height: 1;
    z-index: 5;
    transition: left 2s ease;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
}
.lf2-rider.anim-bounce { animation: lf2bounce 0.45s ease-in-out infinite alternate; }
.lf2-rider.anim-idle   { animation: lf2idle   1.8s ease-in-out infinite; }
@keyframes lf2bounce { to { transform: translate(-50%,-70%); } }
@keyframes lf2idle   { 50% { transform: translate(-50%,-50%) scale(1.12); opacity:0.7; } }

/* ── Row 3: Info bar ── */
.lf2-info {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    padding: 10px 18px 14px;
    border-top: 1px solid rgba(255,255,255,0.05);
    font-size: 12px;
    color: #94a3b8;
}
.lf2-info-item { font-weight: 600; color: #cbd5e1; }
.lf2-info-item.val-green { color: #4ade80; }
.lf2-info-item.val-amber { color: #fbbf24; }
.lf2-dot-sep {
    display: inline-block;
    width: 3px; height: 3px;
    border-radius: 50%;
    background: #334155;
    flex-shrink: 0;
}
.lf2-ping {
    margin-left: auto;
    font-size: 11px;
    color: #475569;
}
/* End lifeline styles */

/* ──────────────────────────────────────────────────────────── */
/* OLD styles below kept for reference — unused by new HTML    */
/* Header */
.lf-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px 12px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    background: rgba(255,255,255,0.02);
}
.lf-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.lf-header-icon {
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
}
.lf-header-title {
    font-size: 13px;
    font-weight: 700;
    color: #f1f5f9;
    letter-spacing: 0.3px;
}
.lf-header-sub {
    font-size: 11px;
    color: #64748b;
    margin-top: 1px;
}
.lf-header-right {
    display: flex;
    align-items: center;
    gap: 8px;
}
.lf-live-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #22c55e;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    letter-spacing: 0.8px;
}
.lf-live-dot {
    width: 6px;
    height: 6px;
    background: #22c55e;
    border-radius: 50%;
    animation: lfLivePulse 1s ease-in-out infinite;
}
@keyframes lfLivePulse {
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(34,197,94,0.4); }
    50% { opacity: 0.6; box-shadow: 0 0 0 4px rgba(34,197,94,0); }
}
.lf-map-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(59,130,246,0.12);
    border: 1px solid rgba(59,130,246,0.25);
    color: #60a5fa;
    font-size: 11px;
    font-weight: 600;
    padding: 4px 11px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
}
.lf-map-btn:hover { background: rgba(59,130,246,0.22); color: #93c5fd; }

/* DM Strip */
.lf-dm-strip {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    background: rgba(255,255,255,0.025);
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.lf-dm-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(99,102,241,0.35);
}
.lf-dm-info { flex: 1; min-width: 0; }
.lf-dm-name {
    font-size: 13px;
    font-weight: 600;
    color: #e2e8f0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.lf-dm-meta {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-top: 2px;
}
.lf-dm-rating {
    font-size: 11px;
    color: #fbbf24;
    font-weight: 600;
}
.lf-dm-status-dot {
    width: 6px;
    height: 6px;
    background: #22c55e;
    border-radius: 50%;
    display: inline-block;
    animation: lfLivePulse 1.5s ease-in-out infinite;
}
#lifelineDmStatusText {
    font-size: 11px;
    color: #94a3b8;
}
.lf-dm-actions {
    display: flex;
    gap: 7px;
    flex-shrink: 0;
}
.lf-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    padding: 5px 11px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s;
}
.lf-call-btn {
    background: rgba(34,197,94,0.12);
    border: 1px solid rgba(34,197,94,0.25);
    color: #4ade80;
}
.lf-call-btn:hover { background: rgba(34,197,94,0.22); color: #86efac; text-decoration: none; }
.lf-map-alt-btn {
    background: rgba(168,85,247,0.12);
    border: 1px solid rgba(168,85,247,0.25);
    color: #c084fc;
}
.lf-map-alt-btn:hover { background: rgba(168,85,247,0.22); color: #d8b4fe; }

/* Track section */
.lf-track-section {
    display: flex;
    align-items: center;
    padding: 20px 18px 10px;
    gap: 12px;
}
.lf-node {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    min-width: 58px;
    max-width: 70px;
    text-align: center;
    flex-shrink: 0;
}
.lf-node-bubble {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    background: rgba(255,255,255,0.05);
    border: 2px solid rgba(255,255,255,0.1);
}
.lf-bubble-dest {
    background: rgba(34,197,94,0.1);
    border-color: rgba(34,197,94,0.3);
}
.lf-node-label {
    font-size: 10px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 70px;
}
.lf-track-center {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
}
.lf-road {
    position: relative;
    height: 8px;
    background: rgba(255,255,255,0.07);
    border-radius: 100px;
    overflow: visible;
}
.lf-road-fill {
    position: absolute;
    left: 0; top: 0; height: 100%;
    background: linear-gradient(90deg, #3b82f6, #6366f1, #8b5cf6);
    border-radius: 100px;
    width: 0%;
    transition: width 2.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 0 10px rgba(99,102,241,0.5);
}
.lf-road-cp {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 3px;
    height: 12px;
    background: rgba(255,255,255,0.15);
    border-radius: 2px;
    z-index: 2;
    cursor: default;
}
.lf-cp-tip {
    position: absolute;
    bottom: 14px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 9px;
    color: #475569;
    white-space: nowrap;
    pointer-events: none;
}
.lf-rider {
    position: absolute;
    top: 50%;
    left: 0%;
    transform: translate(-50%, -50%);
    z-index: 10;
    transition: left 2.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: default;
}
.lf-rider-body {
    font-size: 22px;
    line-height: 1;
    filter: drop-shadow(0 2px 8px rgba(0,0,0,0.6));
    display: block;
}
.lf-rider-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -20%);
    width: 28px;
    height: 28px;
    border-radius: 50%;
    pointer-events: none;
}
.lf-rider-ring.ring-active {
    border: 2px solid rgba(99,102,241,0.7);
    animation: lfRiderRing 1.2s ease-out infinite;
}
@keyframes lfRiderRing {
    0% { transform: translate(-50%, -20%) scale(0.6); opacity: 1; }
    100% { transform: translate(-50%, -20%) scale(2); opacity: 0; }
}
.lf-rider.lifeline-moving .lf-rider-body {
    animation: lfRiderBounce 0.5s ease-in-out infinite alternate;
}
.lf-rider.lifeline-idle-pulse .lf-rider-body {
    animation: lfRiderIdle 1.8s ease-in-out infinite;
}
@keyframes lfRiderBounce {
    from { transform: translateY(0); }
    to { transform: translateY(-4px); }
}
@keyframes lfRiderIdle {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.12); opacity: 0.75; }
}
.lf-road-pct {
    text-align: center;
    font-size: 11px;
    font-weight: 700;
    color: #818cf8;
    letter-spacing: 0.5px;
    min-height: 16px;
}

/* Stats grid */
.lf-stats-grid {
    display: flex;
    align-items: stretch;
    padding: 0 18px 0;
    margin-top: 4px;
    border-top: 1px solid rgba(255,255,255,0.04);
}
.lf-stat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 14px 4px;
    gap: 4px;
}
.lf-stat-divider {
    width: 1px;
    background: rgba(255,255,255,0.05);
    margin: 12px 0;
    flex-shrink: 0;
}
.lf-stat-icon {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2px;
}
.lf-icon-dist { background: rgba(59,130,246,0.15); color: #60a5fa; }
.lf-icon-eta  { background: rgba(251,191,36,0.15); color: #fbbf24; }
.lf-icon-arrive { background: rgba(34,197,94,0.15); color: #4ade80; }
.lf-icon-ping { background: rgba(168,85,247,0.15); color: #c084fc; }
.lf-stat-val {
    font-size: 13px;
    font-weight: 700;
    color: #f1f5f9;
    white-space: nowrap;
}
.lf-stat-key {
    font-size: 10px;
    color: #475569;
    font-weight: 500;
    white-space: nowrap;
}

/* Footer */
.lf-footer {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 18px 14px;
    border-top: 1px solid rgba(255,255,255,0.04);
    flex-wrap: wrap;
}
.lf-state-badge, .lifeline-state-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 600;
    padding: 5px 13px;
    border-radius: 20px;
}
.lf-state-badge.state-at-store {
    background: rgba(251,191,36,0.12);
    border: 1px solid rgba(251,191,36,0.3);
    color: #fbbf24;
}
.lf-state-badge.state-moving {
    background: rgba(99,102,241,0.12);
    border: 1px solid rgba(99,102,241,0.3);
    color: #818cf8;
}
.lf-state-badge.state-at-customer {
    background: rgba(34,197,94,0.12);
    border: 1px solid rgba(34,197,94,0.3);
    color: #4ade80;
}
.lf-idle-chip, .lifeline-idle-timer {
    font-size: 11px;
    font-weight: 600;
    color: #fb923c;
    background: rgba(251,146,60,0.1);
    border: 1px solid rgba(251,146,60,0.25);
    padding: 4px 11px;
    border-radius: 20px;
}
/* ── Context Banner ───────────────────────────────────── */
.lf-context-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0 16px 0;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid transparent;
}
.lf-ctx-store {
    background: rgba(251,191,36,0.08);
    border-color: rgba(251,191,36,0.2);
}
.lf-ctx-moving {
    background: rgba(99,102,241,0.08);
    border-color: rgba(99,102,241,0.2);
}
.lf-ctx-customer {
    background: rgba(34,197,94,0.08);
    border-color: rgba(34,197,94,0.2);
}
.lf-ctx-icon {
    font-size: 26px;
    flex-shrink: 0;
    line-height: 1;
}
.lf-ctx-text { flex: 1; min-width: 0; }
.lf-ctx-title {
    font-size: 13px;
    font-weight: 700;
    color: #f1f5f9;
    margin-bottom: 2px;
}
.lf-ctx-desc {
    font-size: 11px;
    color: #94a3b8;
    line-height: 1.4;
}
.lf-ctx-right {
    flex-shrink: 0;
    text-align: right;
}
.lf-ctx-idle-big {
    font-size: 20px;
    font-weight: 800;
    color: #fb923c;
    letter-spacing: 0.5px;
    font-variant-numeric: tabular-nums;
}
.lf-ctx-idle-lbl {
    font-size: 9px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-top: 1px;
}
.lf-ctx-delivered-time {
    font-size: 20px;
    font-weight: 800;
    color: #4ade80;
    letter-spacing: 0.5px;
}
.lf-ctx-delivered-lbl {
    font-size: 9px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-top: 1px;
}

/* ── Node glow states ─────────────────────────────────── */
.lf-node-bubble.node-active-store {
    background: rgba(251,191,36,0.15) !important;
    border-color: rgba(251,191,36,0.5) !important;
    box-shadow: 0 0 0 4px rgba(251,191,36,0.15);
    animation: lfNodePulseStore 1.6s ease-in-out infinite;
}
.lf-node-bubble.node-active-customer {
    background: rgba(34,197,94,0.15) !important;
    border-color: rgba(34,197,94,0.5) !important;
    box-shadow: 0 0 0 4px rgba(34,197,94,0.15);
    animation: lfNodePulseCustomer 1.6s ease-in-out infinite;
}
@keyframes lfNodePulseStore {
    0%, 100% { box-shadow: 0 0 0 4px rgba(251,191,36,0.15); }
    50%       { box-shadow: 0 0 0 10px rgba(251,191,36,0); }
}
@keyframes lfNodePulseCustomer {
    0%, 100% { box-shadow: 0 0 0 4px rgba(34,197,94,0.15); }
    50%       { box-shadow: 0 0 0 10px rgba(34,197,94,0); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.9; }
}
.pulse-animation {
    animation: pulse 2s ease-in-out infinite;
}
</style>
@endif
{{-- 🆕 END DELIVERY TRACKING --}}
<!-- Enhanced Customer Priority Panel - Add this RIGHT AFTER the order timer section -->
@if ($order->customer && $order->is_guest == 0)
    @php
        $orderCount = $order->customer->orders_count ?? 0;
        $customerSince = $order->customer->created_at ? $order->customer->created_at->diffForHumans() : 'N/A';
        $customerDate = $order->customer->created_at ? $order->customer->created_at->format('M d, Y') : 'N/A';
        $daysSinceJoined = $order->customer->created_at ? $order->customer->created_at->diffInDays(now()) : 0;
        $avgOrdersPerMonth = $daysSinceJoined > 0 ? round(($orderCount / max(($daysSinceJoined / 30), 1)), 1) : 0;
        
        // Calculate estimated lifetime value
        $avgOrderValue = $order->order_amount ?? 500;
        $estimatedValue = \App\CentralLogics\Helpers::format_currency($orderCount * $avgOrderValue);
        
        // Last order calculation
        $lastOrderDate = $orderCount > 1 ? 'Recent' : 'First order';
        
        $priorityClass = 'secondary';
        $priorityText = 'New Customer';
        $priorityIcon = 'tio-user-add';
        $priorityBg = '#f8f9fa';
        $priorityBorder = '#dee2e6';
        $staffTip = '';
        $urgencyBadge = '';
        
        if ($orderCount >= 50) {
            $priorityClass = 'danger';
            $priorityText = 'VIP Elite';
            $priorityIcon = 'tio-premium-outlined';
            $priorityBg = '#ffe5e8';
            $priorityBorder = '#dc3545';
            $staffTip = '👑 TOP PRIORITY: Our most valuable customer. Ensure premium service, add thank you note, consider complimentary item or upgrade.';
            $urgencyBadge = 'VIP Treatment Required';
        } elseif ($orderCount >= 20) {
            $priorityClass = 'warning';
            $priorityText = 'Premium Loyal';
            $priorityIcon = 'tio-star';
            $priorityBg = '#fff8e1';
            $priorityBorder = '#ffc107';
            $staffTip = '⭐ LOYAL CUSTOMER: They trust us with 20+ orders. Double-check quality, ensure neat packaging, prioritize delivery time.';
            $urgencyBadge = 'High Priority';
        } elseif ($orderCount >= 10) {
            $priorityClass = 'success';
            $priorityText = 'Regular Customer';
            $priorityIcon = 'tio-checkmark-circle';
            $priorityBg = '#e8f5e9';
            $priorityBorder = '#28a745';
            $staffTip = '✅ REGULAR: Consistent customer. Maintain quality standards, they know what to expect from us.';
            $urgencyBadge = 'Standard Priority';
        } elseif ($orderCount >= 5) {
            $priorityClass = 'info';
            $priorityText = 'Active Customer';
            $priorityIcon = 'tio-trending-up';
            $priorityBg = '#e3f2fd';
            $priorityBorder = '#17a2b8';
            $staffTip = '📈 GROWING: Becoming regular. This is critical phase - excellent service now = loyal customer forever.';
            $urgencyBadge = 'Important';
        } elseif ($orderCount >= 1) {
            $priorityClass = 'primary';
            $priorityText = 'Returning Customer';
            $priorityIcon = 'tio-reload';
            $priorityBg = '#e7f3ff';
            $priorityBorder = '#377dff';
            $staffTip = '🔄 THEY CAME BACK: First order went well! Now exceed expectations - focus on quality, packaging & delivery.';
            $urgencyBadge = 'Make It Better';
        } else {
            $priorityClass = 'warning';
            $priorityText = 'First Order';
            $priorityIcon = 'tio-sparkle';
            $priorityBg = '#fff3cd';
            $priorityBorder = '#ff9800';
            $staffTip = '🌟 FIRST IMPRESSION: New customer trying us for the first time! Check: Item quality ✓ Packaging ✓ Accuracy ✓ Delivery time ✓';
            $urgencyBadge = 'CRITICAL - First Time';
        }
        
        $nextMilestone = $orderCount >= 20 ? 50 : ($orderCount >= 10 ? 20 : ($orderCount >= 5 ? 10 : 5));
        $remaining = $orderCount < 50 ? $nextMilestone - $orderCount : 0;
    @endphp

    <!-- Customer Intelligence Card -->
    <div class="card mb-3 border-0 shadow-sm" style="border-left: 5px solid {{ $priorityBorder }} !important; background: {{ $priorityBg }};">
        <div class="card-body p-3">
            
            <!-- Header: Customer Identity & Priority Status -->
            <div class="row align-items-center mb-3 pb-2 border-bottom">
                <div class="col-md-7">
                    <div class="media align-items-center">
                        <div class="avatar avatar-lg avatar-circle mr-3 position-relative">
                            <img class="avatar-img onerror-image"
                                 data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                 src="{{ $order->customer->image_full_url }}"
                                 alt="{{ $order->customer['f_name'] }}">
                            @if ($orderCount >= 20)
                                <span class="avatar-status avatar-lg-status avatar-status-{{ $priorityClass }}">
                                    <i class="{{ $priorityIcon }}"></i>
                                </span>
                            @endif
                        </div>
                        <div class="media-body">
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="mb-0 mr-2">
                                    <a href="{{ route('admin.users.customer.view', [$order->customer['id']]) }}"
                                       class="text-dark font-weight-bold">
                                        {{ $order->customer['f_name'] }} {{ $order->customer['l_name'] }}
                                    </a>
                                </h5>
                                <span class="badge badge-{{ $priorityClass }} px-2 py-1">
                                    <i class="{{ $priorityIcon }}"></i> {{ $priorityText }}
                                </span>
                                
                                @if ($orderCount == 0)
                                    <span class="badge badge-warning ml-1 badge-blink">
                                        <i class="tio-sparkle"></i> NEW
                                    </span>
                                @elseif ($orderCount >= 50)
                                    <span class="badge badge-danger ml-1 badge-blink">
                                        <i class="tio-premium"></i> VIP
                                    </span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center text-muted flex-wrap" style="font-size: 0.85rem; gap: 15px;">
                                <span>
                                    <i class="tio-call-talking-quiet mr-1"></i>
                                    <a href="tel:{{ $order->customer['phone'] }}" class="text-dark font-weight-semibold">
                                        {{ $order->customer['phone'] }}
                                    </a>
                                </span>
                                <span>
                                    <i class="tio-email mr-1"></i>
                                    <a href="mailto:{{ $order->customer['email'] }}" class="text-dark">
                                        {{ Str::limit($order->customer['email'], 25, '...') }}
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-5 text-right">
                    <span class="badge badge-soft-{{ $priorityClass }} px-3 py-2 mb-2" style="font-size: 0.9rem; display: block;">
                        {{ $urgencyBadge }}
                    </span>
                    <a href="{{ route('admin.users.customer.view', [$order->customer['id']]) }}"
                       class="btn btn-sm btn-{{ $priorityClass }}">
                        <i class="tio-visible"></i> Full Profile
                    </a>
                </div>
            </div>

            <!-- Customer Intelligence Stats -->
            <div class="row mb-3">
                <!-- Total Orders -->
                <div class="col-6 col-md-2 text-center mb-2 mb-md-0">
                    <div class="d-flex flex-column align-items-center">
                        <i class="tio-shopping-basket text-{{ $priorityClass }} mb-1" style="font-size: 1.5rem;"></i>
                        <h4 class="mb-0 text-{{ $priorityClass }}" style="font-weight: 700;">{{ $orderCount }}</h4>
                        <small class="text-muted text-uppercase" style="font-size: 0.7rem; font-weight: 600;">
                            {{ $orderCount == 0 ? 'First Order' : 'Total Orders' }}
                        </small>
                    </div>
                </div>

                <!-- Order Frequency -->
                <div class="col-6 col-md-2 text-center mb-2 mb-md-0 border-left">
                    <div class="d-flex flex-column align-items-center">
                        <i class="tio-chart-bar-4 text-{{ $priorityClass }} mb-1" style="font-size: 1.5rem;"></i>
                        <h5 class="mb-0 text-{{ $priorityClass }}" style="font-weight: 700;">{{ $avgOrdersPerMonth }}</h5>
                        <small class="text-muted" style="font-size: 0.7rem; font-weight: 600;">Orders/Month</small>
                    </div>
                </div>

                <!-- Customer Since -->
                <div class="col-6 col-md-2 text-center mb-2 mb-md-0 border-left">
                    <div class="d-flex flex-column align-items-center">
                        <i class="tio-date-range text-{{ $priorityClass }} mb-1" style="font-size: 1.5rem;"></i>
                        <h6 class="mb-0 text-{{ $priorityClass }}" style="font-weight: 700;">{{ $daysSinceJoined }}</h6>
                        <small class="text-muted" style="font-size: 0.7rem; font-weight: 600;">Days Active</small>
                    </div>
                </div>

                <!-- Last Order -->
                <div class="col-6 col-md-2 text-center mb-2 mb-md-0 border-left">
                    <div class="d-flex flex-column align-items-center">
                        <i class="tio-time text-{{ $priorityClass }} mb-1" style="font-size: 1.5rem;"></i>
                        <h6 class="mb-0 text-{{ $priorityClass }}" style="font-weight: 700; font-size: 0.85rem;">
                            {{ $lastOrderDate }}
                        </h6>
                        <small class="text-muted" style="font-size: 0.7rem; font-weight: 600;">Last Order</small>
                    </div>
                </div>

                <!-- Next Milestone -->
                <div class="col-6 col-md-2 text-center mb-2 mb-md-0 border-left">
                    @if ($remaining > 0)
                        <div class="d-flex flex-column align-items-center">
                            <i class="tio-trending-up text-{{ $priorityClass }} mb-1" style="font-size: 1.5rem;"></i>
                            <h5 class="mb-0 text-{{ $priorityClass }}" style="font-weight: 700;">{{ $remaining }}</h5>
                            <small class="text-muted" style="font-size: 0.7rem; font-weight: 600;">To Next Level</small>
                        </div>
                    @else
                        <div class="d-flex flex-column align-items-center">
                            <i class="tio-premium text-danger mb-1" style="font-size: 1.5rem;"></i>
                            <span class="badge badge-danger px-2 py-1">MAX</span>
                            <small class="text-muted" style="font-size: 0.7rem; font-weight: 600;">VIP Status</small>
                        </div>
                    @endif
                </div>

                <!-- Estimated Value -->
                <div class="col-6 col-md-2 text-center mb-2 mb-md-0 border-left">
                    <div class="d-flex flex-column align-items-center">
                        <i class="tio-money text-{{ $priorityClass }} mb-1" style="font-size: 1.5rem;"></i>
                        <h6 class="mb-0 text-{{ $priorityClass }}" style="font-weight: 700; font-size: 0.85rem;">{{ $estimatedValue }}</h6>
                        <small class="text-muted" style="font-size: 0.7rem; font-weight: 600;">Est. Value</small>
                    </div>
                </div>
            </div>

            <!-- Customer Journey Info -->
            <div class="row mb-2">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background: rgba(255,255,255,0.5);">
                        <div class="d-flex align-items-center flex-wrap" style="gap: 20px; font-size: 0.85rem;">
                            <span>
                                <i class="tio-calendar-month text-muted mr-1"></i>
                                <strong>Joined:</strong> {{ $customerDate }}
                            </span>
                            <span class="text-muted">|</span>
                            <span>
                                <i class="tio-shopping-basket-outlined text-muted mr-1"></i>
                                <strong>Order #{{ $order->id }}</strong> {{ $orderCount == 0 ? '(First Ever)' : '(Order #' . ($orderCount + 1) . ')' }}
                            </span>
                            <span class="text-muted">|</span>
                            <span>
                                <i class="tio-user-outlined text-muted mr-1"></i>
                                <strong>Customer ID:</strong> {{ $order->customer->id }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Instructions -->
            <div class="alert alert-{{ $priorityClass }} mb-0 py-2 px-3 d-flex align-items-start"
                 style="border-radius: 6px; border-left: 4px solid {{ $priorityBorder }};">
                <div class="mr-2">
                    <i class="tio-info-outlined" style="font-size: 1.25rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <div>
                            <strong style="font-size: 0.9rem;">
                                <i class="tio-checkmark-square-outlined"></i> Staff Action Required:
                            </strong>
                            <p class="mb-0 mt-1" style="font-size: 0.85rem; line-height: 1.5;">{{ $staffTip }}</p>
                        </div>
                        
                        <!-- Action Badges -->
                        <div class="d-flex align-items-center ml-3 mt-2 mt-md-0" style="gap: 8px;">
                            @if ($orderCount == 0)
                                <span class="badge badge-warning badge-pill px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="tio-checkmark-circle"></i> Quality
                                </span>
                                <span class="badge badge-warning badge-pill px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="tio-package"></i> Package
                                </span>
                                <span class="badge badge-warning badge-pill px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="tio-time"></i> On-Time
                                </span>
                            @elseif ($orderCount >= 50)
                                <span class="badge badge-danger badge-pill px-2 py-1 badge-blink" style="font-size: 0.7rem;">
                                    <i class="tio-premium"></i> VIP Service
                                </span>
                                <span class="badge badge-danger badge-pill px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="tio-gift"></i> Add Gift
                                </span>
                            @elseif ($orderCount >= 20)
                                <span class="badge badge-warning badge-pill px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="tio-star"></i> Priority
                                </span>
                                <span class="badge badge-warning badge-pill px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="tio-checkmark-circle"></i> Quality
                                </span>
                            @elseif ($orderCount >= 10)
                                <span class="badge badge-success badge-pill px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="tio-checkmark"></i> Standard+
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@elseif($order->is_guest)
    <!-- Guest User Card -->
    <div class="card mb-3 border-0 shadow-sm" style="border-left: 5px solid #28a745 !important; background: #e8f5e9;">
        <div class="card-body p-3">
            <div class="media align-items-center">
                <div class="avatar avatar-lg avatar-circle bg-soft-success mr-3">
                    <span class="avatar-initials">
                        <i class="tio-incognito" style="font-size: 1.5rem;"></i>
                    </span>
                </div>
                <div class="media-body">
                    <h5 class="mb-1">
                        <i class="tio-incognito"></i> Guest Order
                    </h5>
                    <p class="mb-0 text-muted small">
                        <strong>No customer account</strong> • Anonymous order • Provide excellent service to encourage account creation
                    </p>
                </div>
                <div class="ml-auto text-right">
                    <span class="badge badge-soft-success px-3 py-2 mb-1" style="display: block;">
                        <i class="tio-user-add"></i> Potential Customer
                    </span>
                    <small class="text-muted">
                        <i class="tio-info"></i> Great service = Future loyal customer
                    </small>
                </div>
            </div>
        </div>
    </div>
@endif

<style>
.avatar-lg {
    width: 3.5rem;
    height: 3.5rem;
}
.avatar-lg .avatar-img {
    width: 3.5rem;
    height: 3.5rem;
}
.avatar-lg-status {
    width: 1.125rem;
    height: 1.125rem;
    bottom: 0;
    right: 0;
}

@keyframes blink-animation {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.badge-blink {
    animation: blink-animation 2s ease-in-out infinite;
}

.border-left {
    border-left: 1px solid rgba(0, 0, 0, 0.1) !important;
}
</style>
<!-- ORDER TIMER -->
<div class="card mb-3">
    <div class="card-body">
        <div class="order-timer-container">
            @php
                $completedStatuses = ['delivered', 'canceled', 'refunded', 'failed'];
                $isCompleted = in_array($order->order_status, $completedStatuses);
                
                // Calculate final time if completed
                if ($isCompleted && isset($order->delivered_time)) {
                    $startTime = strtotime($order->created_at);
                    $endTime = strtotime($order->delivered_time);
                    $totalSeconds = $endTime - $startTime;
                } elseif ($isCompleted) {
                    $startTime = strtotime($order->created_at);
                    $endTime = strtotime($order->updated_at);
                    $totalSeconds = $endTime - $startTime;
                } else {
                    $totalSeconds = 0;
                }
                
                $finalHours = floor($totalSeconds / 3600);
                $finalMinutes = floor(($totalSeconds % 3600) / 60);
                $finalSeconds = $totalSeconds % 60;
            @endphp
            
            <div class="timer-wrapper d-flex align-items-center justify-content-between p-3 bg-light rounded {{ $isCompleted ? 'completed' : '' }}">
                <div class="timer-left d-flex align-items-center">
                    <div class="timer-icon mr-3">
                        @if($isCompleted)
                            <i class="tio-checkmark-circle-outlined text-success" style="font-size: 2rem;"></i>
                        @else
                            <i class="tio-time text-primary" style="font-size: 2rem;"></i>
                        @endif
                    </div>
                    <div class="timer-info">
                        <h6 class="mb-1">
                            @if($isCompleted)
                                {{ translate('messages.total_order_time') }}
                            @else
                                {{ translate('messages.order_time_elapsed') }}
                            @endif
                        </h6>
                        <div class="timer-display" id="orderTimer">
                            @if($isCompleted)
                                <span class="hours">{{ sprintf('%02d', $finalHours) }}</span>:<span class="minutes">{{ sprintf('%02d', $finalMinutes) }}</span>:<span class="seconds">{{ sprintf('%02d', $finalSeconds) }}</span>
                            @else
                                <span class="hours">00</span>:<span class="minutes">00</span>:<span class="seconds">00</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                @if($isCompleted)
                    <div class="timer-right text-right">
                        <span class="badge badge-{{ $order->order_status == 'delivered' ? 'success' : 'secondary' }} badge-lg">
                            {{ translate('messages.' . $order->order_status) }}
                        </span>
                    </div>
                @elseif($order->order_status == 'processing' && isset($order->processing_time))
                    <div class="timer-right text-right">
                        <h6 class="mb-1">{{ translate('messages.estimated_processing_time') }}</h6>
                        <span class="badge badge-warning">{{ $order->processing_time }} {{ translate('messages.minutes') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>


        <div class="row flex-xl-nowrap" id="printableArea">
            <div class="col-lg-8 order-print-area-left">
                <!-- Card -->
                <div class="card mb-3 mb-lg-5">
                    <!-- Header -->
                    <div class="card-header border-0 align-items-start flex-wrap">
                        <div class="order-invoice-left d-flex d-sm-block justify-content-between">
                            <div>
                                <h1 class="page-header-title d-flex align-items-center __gap-5px">
                                    {{ translate('messages.order') }} #{{ $order['id'] }}
                                    @if ($campaign_order)
                                        <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.campaign_order') }}
                                        </span>
                                    @endif
                                    @if ($order->edited)
                                        <span class="badge badge-soft-dark ml-sm-3">
                                            {{ translate('messages.edited') }}
                                        </span>
                                    @endif
                                </h1>
                                <span class="mt-2 d-block d-flex align-items-center __gap-5px">
                                    <i class="tio-date-range"></i>
                                    {{ date('d M Y ' . config('timeformat'), strtotime($order['created_at'])) }}
                                </span>
                                @if (!$parcel_order)
                                    <h6 class="mt-2 pt-1 mb-2 d-flex align-items-center __gap-5px">
                                        <i class="tio-shop"></i>
                                        <span>{{ translate('messages.store') }}</span> <span>:</span> <span
                                            class="badge badge-soft-primary">{{ Str::limit($order->store ? $order->store->name : translate('messages.store deleted!'), 25, '...') }}</span>
                                    </h6>
                                @endif
                                @if ($order->schedule_at && $order->scheduled)
                                    <h6 class="text-capitalize d-flex align-items-center __gap-5px">
                                        <span>{{ translate('messages.scheduled_at') }}</span>
                                        <span>:</span> <label
                                            class="fz--10 badge badge-soft-warning">{{ date('d M Y ' . config('timeformat'), strtotime($order['schedule_at'])) }}</label>
                                    </h6>
                                @endif
                                @if ($order->coupon)
                                    <h6 class="text-capitalize d-flex align-items-center __gap-5px"><span>{{ translate('messages.coupon') }}</span>
                                        <span>:</span> <label class="fz--10 badge badge-soft-primary">{{ $order->coupon_code }}
                                            ({{ translate('messages.' . $order->coupon->coupon_type) }})</label>
                                    </h6>
                                @endif
                                <div class="hs-unfold mt-1">
                                    <h5>
                                        <button
                                            class="btn order--details-btn-sm btn--primary btn-outline-primary btn--sm font-regular d-flex align-items-center __gap-5px"
                                            data-toggle="modal" data-target="#locationModal"><i class="tio-poi"></i>
                                            {{ translate('messages.show_locations_on_map') }}</button>
                                    </h5>
                                </div>
                                @if($order['cancellation_reason'])
                                    <h6 class="text-capitalize my-2 ml-2">
                                        <span class="text-danger">{{ translate('messages.Cancelled_By') }} :</span>
                                        {{ $order['canceled_by'] }}
                                    </h6>
                                    <h6 class=" my-2 ml-2">
                                        <span class="text-danger">{{ translate('messages.order_cancellation_reason') }} :</span>
                                        {{ $order['cancellation_reason'] }}
                                    </h6>
                                @endif
                                @if ($order['unavailable_item_note'])
                                    <h6 class="w-100 badge-soft-warning">
                                        <span class="text-dark">
                                            {{ translate('messages.order_unavailable_item_note') }} :
                                        </span>
                                        {{ $order['unavailable_item_note'] }}
                                    </h6>
                                @endif
                                @if ($order['delivery_instruction'])
                                    <h6 class="w-100 badge-soft-warning">
                                        <span class="text-dark">
                                            {{ translate('messages.order_delivery_instruction') }} :
                                        </span>
                                        {{ $order['delivery_instruction'] }}
                                    </h6>
                                @endif
                                @if ($order['order_note'])
                                    <h6>
                                        {{ translate('messages.order_note') }} :
                                        {{ $order['order_note'] }}
                                    </h6>
                                @endif
                                @if($order?->offline_payments && $order?->offline_payments->status == 'denied' && $order?->offline_payments->note )
                                    <h6 class="w-100 badge-soft-warning">
                                    <span class="text-dark">
                                        {{ translate('messages.Offline_payment_rejection_note') }} :
                                    </span>
                                        {{  $order?->offline_payments->note }}
                                    </h6>
                                @endif
                            </div>
                            <div class="d-sm-none">
                                <a class="btn btn--primary print--btn font-regular d-flex align-items-center __gap-5px"
                                   href={{ route('admin.order.generate-invoice', [$order['id']]) }}>
                                    <i class="tio-print mr-sm-1"></i> <span>{{ translate('messages.print_invoice') }}</span>
                                </a>
                            </div>
                        </div>
                        <div class="order-invoice-right mt-3 mt-sm-0">
                            <div class="btn--container ml-auto align-items-center justify-content-end">

@if (!$parcel_order &&
     in_array($order->order_status, ['pending', 'confirmed', 'processing']) &&
     isset($order->store) && !$campaign_order &&
     $order->prescription_order == 0 &&
     $order?->ref_bonus_amount == 0 && $order?->flash_admin_discount_amount == 0)
    <div class="d-flex flex-column align-items-end">
        <div class="v2-recommended-banner mb-1">
            <span class="badge" style="background: linear-gradient(90deg,#f76c2f,#ff9f00); color:#fff; font-size:11px; padding:3px 10px; border-radius:20px; letter-spacing:.4px;">
                <i class="tio-star-outlined" style="font-size:10px;"></i>
                {{ translate('messages.recommended_for_staff') }}
            </span>
        </div>
        <div class="d-flex align-items-center">
            {{-- V2 POS is now the default edit interface --}}
            <a class="btn btn-sm btn-success font-regular edit-order"
               href="{{ route('admin.order.edit-v2', $order->id) }}"
               style="font-weight:600; padding:6px 14px; border-radius:6px;">
                <i class="tio-edit mr-1"></i> {{ translate('messages.edit_order') }}
            </a>
        </div>
    </div>
@endif


                                <a class="btn btn--primary print--btn font-regular d-none d-sm-block"
                                   href={{ route('admin.order.generate-invoice', [$order['id']]) }}>
                                    <i class="tio-print mr-sm-1"></i> <span>{{ translate('messages.print_invoice') }}</span>
                                </a>
                                <button class="btn btn-sm btn-outline-secondary font-regular" type="button" id="shortcutsToggle" title="Keyboard Shortcuts (press ?)">
                                    <i class="tio-keyboard mr-1"></i> <span class="d-none d-md-inline">{{ translate('messages.shortcuts') }}</span>
                                </button>
                            </div>
                            <div class="text-right mt-3 order-invoice-right-contents text-capitalize">
                                <h6>
                                    <span>{{ translate('status') }}</span> <span>:</span>
                                    @if ($order['order_status'] == 'pending')
                                        <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.pending') }}
                                        </span>
                                    @elseif($order['order_status'] == 'confirmed')
                                        <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.confirmed') }}
                                        </span>
                                    @elseif($order['order_status'] == 'processing')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.processing') }}
                                        </span>
                                    @elseif($order['order_status'] == 'picked_up')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.out_for_delivery') }}
                                        </span>
                                    @elseif($order['order_status'] == 'delivered')
                                        <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.delivered') }}
                                        </span>
                                    @elseif($order['order_status'] == 'failed')
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.payment_failed') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                            {{ translate(str_replace('_', ' ', $order['order_status'])) }}
                                        </span>
                                    @endif
                                </h6>
                                <h6 class="text-capitalize d-flex align-items-center" style="gap:8px;">
                                    <span>{{ translate('messages.payment_method') }}</span> <span>:</span>
                                    <span>{{ translate(str_replace('_', ' ', $order['payment_method'])) }}</span>
                                    <a href="javascript:" data-toggle="modal" data-target="#editPaymentModal" class="text-primary" title="{{ translate('messages.edit') }}">
                                        <i class="tio-edit" style="font-size:14px;"></i>
                                    </a>
                                </h6>

                                <!-- offline_payment -->
                                @if($order?->offline_payments)
                                    <span>{{ translate('Payment_verification') }}</span> <span>:</span>
                                    @if ($order?->offline_payments->status == 'pending')
                                        <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.pending') }}
                                            </span>
                                    @elseif ($order?->offline_payments->status == 'verified')
                                        <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.verified') }}
                                            </span>
                                    @elseif ($order?->offline_payments->status == 'denied')
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.denied') }}
                                            </span>
                                    @endif

                                    @foreach (json_decode($order->offline_payments->payment_info) as $key=>$item)
                                        @if ($key != 'method_id')
                                            <h6 class="">
                                                <div class="d-flex justify-content-sm-end text-capitalize">
                                                    <span class="title-color">{{translate($key)}} :</span>
                                                    <strong>{{ $item }}</strong>
                                                </div>
                                            </h6>
                                        @endif
                                    @endforeach
                                @endif

                                <h6 class="d-flex align-items-center" style="gap:8px;">
                                    <span>{{ translate('messages.reference_code') }}</span> <span>:</span>
                                    @if ($order['transaction_reference'])
                                        <span>{{ $order['transaction_reference'] }}</span>
                                    @else
                                        <span class="text-muted">{{ translate('messages.N/A') }}</span>
                                    @endif
                                    <a href="javascript:" data-toggle="modal" data-target="#editPaymentModal" class="text-primary" title="{{ translate('messages.edit') }}">
                                        <i class="tio-edit" style="font-size:14px;"></i>
                                    </a>
                                </h6>

                                <h6 class="text-capitalize">
                                    <span>{{ translate('Order Type') }}</span>
                                    <span>:</span> <label
                                        class="fz--10 badge badge-soft-primary m-0">{{ translate(str_replace('_', ' ', $order['order_type'])) }}</label>
                                </h6>
                                <h6 class="d-flex align-items-center" style="gap:8px;">
                                    <span>{{ translate('payment_status') }}</span> <span>:</span>
                                    @if ($order['payment_status'] == 'paid')
                                        <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.paid') }}
                                        </span>
                                    @elseif ($order['payment_status'] == 'partially_paid')
                                        @if ($order->payments()->where('payment_status','unpaid')->exists())
                                            <strong class="text-danger">{{ translate('messages.partially_paid') }}</strong>
                                        @else
                                            <strong class="text-success">{{ translate('messages.paid') }}</strong>
                                        @endif
                                    @else
                                        <strong class="text-danger">{{ translate('messages.unpaid') }}</strong>
                                    @endif
                                    <a href="javascript:" data-toggle="modal" data-target="#editPaymentModal" class="text-primary" title="{{ translate('messages.edit') }}">
                                        <i class="tio-edit" style="font-size:14px;"></i>
                                    </a>
                                </h6>
                                @if ($order->store && $order->store->module->module_type == 'food')
                                    <h6>
                                        <span>{{ translate('cutlery') }}</span> <span>:</span>
                                        @if ($order['cutlery'] == '1')
                                            <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.yes') }}
                                        </span>
                                        @else
                                            <span class="badge badge-soft-danger ml-sm-3">
                                            {{ translate('messages.no') }}
                                        </span>
                                        @endif

                                    </h6>
                                @endif
                                @if ($order->order_attachment)
                                    @php
                                        $order_images = json_decode($order->order_attachment,true);
                                    @endphp
                                    <h5 class="text-dark">
                                        <span>{{ translate('messages.prescription') }}</span> <span>:</span>
                                    </h5>
                                    <div class="d-flex flex-wrap flex-md-row-reverse" style="gap:15px">
                                        @foreach ($order_images as $key => $item)
                                            @php($item = is_array($item)?$item:['img'=>$item,'storage'=>'public'])
                                            <div>
                                                <button class="btn w-100 px-0" data-toggle="modal"
                                                        data-target="#prescriptionimagemodal{{ $key }}"
                                                        title="{{ translate('messages.order_attachment') }}">
                                                    <div class="gallary-card ml-auto">
                                                        <img  src="{{\App\CentralLogics\Helpers::get_full_url('order', $item['img'], $item['storage']??'public') }}"
                                                              alt="{{ translate('messages.prescription') }}"
                                                              class="initial--22 object-cover">
                                                    </div>
                                                </button>
                                            </div>
                                            <div class="modal fade" id="prescriptionimagemodal{{ $key }}" tabindex="-1"
                                                 role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title" id="myModalLabel">
                                                                {{ translate('messages.prescription') }}</h4>
                                                            <button type="button" class="close"
                                                                    data-dismiss="modal"><span
                                                                    aria-hidden="true">&times;</span><span
                                                                    class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                                        </div>
                                                        <div class="modal-body scroll-bar">
                                                            <img  src="{{\App\CentralLogics\Helpers::get_full_url('order', $item['img'], $item['storage']??'public') }}"
                                                                  class="initial--22 w-100">
                                                        </div>
                                                        @php($storage = $item['storage']??'public')
                                                        @php($file = $storage == 's3'?base64_encode('order/' . $item['img']):base64_encode('public/order/' . $item['img']))
                                                        <div class="modal-footer">
                                                            <a class="btn btn-primary"
                                                               href="{{ route('admin.file-manager.download', [$file,$storage]) }}"><i
                                                                    class="tio-download"></i>
                                                                {{ translate('messages.download') }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Body -->
                    <div class="card-body px-0">
                        <!-- item cart -->


                        @if ($order->order_type == 'parcel')
                                <?php
                                $coupon = null;
                                $total_addon_price = 0;
                                $product_price = 0;
                                $store_discount_amount = 0;
                                $admin_flash_discount_amount = $order['flash_admin_discount_amount'];
                                $ref_bonus_amount = $order['ref_bonus_amount'];
                                $extra_packaging_amount = $order['extra_packaging_amount'];
                                $store_flash_discount_amount = $order['flash_store_discount_amount'];
                                $del_c = $order['delivery_charge'];
                                $additional_charge = $order['additional_charge'];
                                $total_tax_amount = 0;
                                $total_addon_price = 0;
                                $coupon_discount_amount = 0;
                                $deliverman_tips = $order['dm_tips'];
                                ?>
                            <div class="mx-3">
                                <div class="media align-items-center cart--media pb-2">
                                    <div class="avatar avatar-xl mr-3"
                                         title="{{ $order->parcel_category ? $order->parcel_category->name : translate('messages.parcel_category_not_found') }}">
                                        <img class="img-fluid onerror-image"
                                             src="{{ $order->parcel_category?->image_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}">
                                    </div>
                                    <div class="media-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <strong>
                                                    {{ Str::limit($order->parcel_category ? $order->parcel_category->name : translate('messages.parcel_category_not_found'), 25, '...') }}</strong><br>
                                                <div class="font-size-sm text-body">
                                                    <span>{{ $order->parcel_category ? $order->parcel_category->description : translate('messages.parcel_category_not_found') }}</span>
                                                </div>
                                            </div>

                                            <div class="col col-md-2 align-self-center">
                                                <h6>{{ translate('messages.distance') }}</h6>
                                                <span>{{ $order->distance }} {{ translate('km') }}</span>
                                            </div>
                                            <div class="col col-md-1 align-self-center">

                                            </div>

                                            <div class="col col-md-3 align-self-center text-right">
                                                <h6>{{ translate('messages.delivery_charge') }}</h6>
                                                <span>{{ \App\CentralLogics\Helpers::format_currency($del_c) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr class="my-2">
                            </div>
                        @else
                                <?php
                                $coupon = null;
                                $total_addon_price = 0;
                                $product_price = 0;
                                if ($order->prescription_order == 1) {
                                    $product_price = $order['order_amount'] - $order['delivery_charge'] - $order['total_tax_amount'] - $order['dm_tips'] - $order['additional_charge'] + $order['store_discount_amount'];
                                    if($order->tax_status == 'included'){
                                        $product_price += $order['total_tax_amount'];
                                    }
                                }
                                $store_discount_amount = 0;
                                $admin_flash_discount_amount = $order['flash_admin_discount_amount'];
                                $ref_bonus_amount = $order['ref_bonus_amount'];
                                $extra_packaging_amount = $order['extra_packaging_amount'];
                                $store_flash_discount_amount = $order['flash_store_discount_amount'];
                                $additional_charge = $order['additional_charge'];
                                $del_c = $order['delivery_charge'];
                                if ($editing) {
                                    $del_c = $order['original_delivery_charge'];
                                }
                                if ($order->coupon_code) {
                                    $coupon = \App\Models\Coupon::where(['code' => $order['coupon_code']])->first();
                                    if ($editing && $coupon->coupon_type == 'free_delivery') {
                                        $del_c = 0;
                                        $coupon = null;
                                    }
                                }
                                $details = $order->details;
                                if ($editing) {
                                    $details = session('order_cart');
                                } else {
                                    foreach ($details as $key => $item) {
                                        $details[$key]->status = true;
                                    }
                                }
                                ?>
                            {{-- Bill Status & Scan Summary above items table --}}
                            <?php $bill_data_top = isset($order->bill_image) ? json_decode($order->bill_image, true) : []; ?>
                            @if ($order->is_billed || ($bill_data_top != null && count($bill_data_top) > 0) || $order->bill_scan_result)
                                <div class="mb-3 p-3 rounded" style="background: {{ $order->is_billed ? '#e8f5e9' : '#fff8e1' }}; border: 1px solid {{ $order->is_billed ? '#c8e6c9' : '#ffe082' }};">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div class="d-flex align-items-center">
                                            <span style="font-size:20px;" class="mr-2">
                                                @if($order->is_billed)
                                                    <span class="text-success">&#10003;</span>
                                                @else
                                                    <span class="text-warning">&#9888;</span>
                                                @endif
                                            </span>
                                            <div>
                                                <strong>{{ translate('messages.bill_status') }}:</strong>
                                                @if($order->is_billed)
                                                    <span class="badge badge-success">{{ translate('messages.billed') }}</span>
                                                @else
                                                    <span class="badge badge-warning">{{ translate('messages.not_billed') }}</span>
                                                @endif
                                                @if($order->billed_at)
                                                    <small class="text-muted ml-2">{{ $order->billed_at->format('d M Y, h:i A') }}</small>
                                                @endif
                                            </div>
                                        </div>
                                        @if($bill_data_top && count($bill_data_top) > 0)
                                            <small class="text-muted">
                                                <i class="tio-image"></i> {{ count($bill_data_top) }} {{ translate('messages.bill_image') }}(s)
                                            </small>
                                        @endif
                                    </div>

                                    @if($order->bill_scan_result)
                                        <?php
                                            $scanSummary = $order->bill_scan_result;
                                            $summaryStatus = $scanSummary['overall_status'] ?? 'unknown';
                                            $summaryChecks = $scanSummary['checks'] ?? [];
                                        ?>
                                        <div class="mt-2 pt-2 border-top" style="border-color: rgba(0,0,0,0.1) !important;">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                                <div>
                                                    <small class="font-weight-bold">{{ translate('messages.scan_verification') }}:</small>
                                                    <span class="badge badge-{{ $summaryStatus === 'pass' ? 'success' : ($summaryStatus === 'warn' ? 'warning' : 'danger') }} ml-1">
                                                        {{ strtoupper($summaryStatus) }}
                                                    </span>
                                                    @if($scanSummary['scan_method'] ?? false)
                                                        <small class="text-muted ml-1">({{ $scanSummary['scan_method'] }})</small>
                                                    @endif
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    @if(isset($summaryChecks['total_price']))
                                                        <small class="mr-3">
                                                            <span class="text-{{ $summaryChecks['total_price']['status'] === 'pass' ? 'success' : ($summaryChecks['total_price']['status'] === 'warn' ? 'warning' : 'danger') }}">
                                                                @if($summaryChecks['total_price']['status'] === 'pass') &#10003; @elseif($summaryChecks['total_price']['status'] === 'warn') &#9888; @else &#10007; @endif
                                                            </span>
                                                            {{ translate('messages.total') }}: {{ \App\CentralLogics\Helpers::format_currency($summaryChecks['total_price']['found'] ?? 0) }}
                                                        </small>
                                                    @endif
                                                    @if(isset($summaryChecks['store_name']))
                                                        <small class="mr-3">
                                                            <span class="text-{{ $summaryChecks['store_name']['status'] === 'pass' ? 'success' : 'danger' }}">
                                                                @if($summaryChecks['store_name']['status'] === 'pass') &#10003; @else &#10007; @endif
                                                            </span>
                                                            {{ translate('messages.store') }}
                                                        </small>
                                                    @endif
                                                    @if(isset($summaryChecks['customer_name']))
                                                        <small>
                                                            <span class="text-{{ $summaryChecks['customer_name']['status'] === 'pass' ? 'success' : 'warning' }}">
                                                                @if($summaryChecks['customer_name']['status'] === 'pass') &#10003; @else &#9888; @endif
                                                            </span>
                                                            {{ translate('messages.customer') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="table-responsive" id="order-items-table-wrapper">
                                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table dataTable no-footer mb-0" id="order-items-table">
                                    <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('messages.#') }}</th>
                                        <th class="border-0">{{ translate('messages.item_details') }}</th>
                                        @if ($order->store && $order->store->module->module_type == 'food')
                                            <th class="border-0">{{ translate('messages.addons') }}</th>
                                        @endif
                                        <th class="text-right border-0">{{ translate('messages.price') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($details as $key => $detail)
                                        @if (isset($detail->item_id) && $detail->status)
                                                <?php
                                                if (!$editing) {
                                                    $detail->item = json_decode($detail->item_details, true);
                                                }
                                                $product = \App\Models\Item::where(['id' => data_get($detail->item,'id')])->first();
                                                        if(!$product){
                                                            $detail->item = json_decode($detail->item_details, true);
                                                        }
                                                ?>

                                            <tr>
                                                <td>
                                                    <!-- Static Count Number -->
                                                    <div>
                                                        {{ $key + 1 }}
                                                    </div>
                                                    <!-- Static Count Number -->
                                                </td>
                                                <td>
                                                    <div class="media media--sm">
                                                        <a class="avatar avatar-xl mr-3"
                                                        href="{{ route('admin.item.view', [$detail->item['id'],'module_id' => $order->module_id]) }}">
                                                            <img class="img-fluid rounded aspect-ratio-1 onerror-image"
                                                                src="{{ $product?->image_full_url ?? asset('public/assets/admin/img/100x100/2.png') }}"
                                                                data-onerror-image="{{ asset('public/assets/admin/img/100x100/2.png') }}"
                                                                alt="Image Description">
                                                        </a>
                                                        <div class="media-body">
<div>
    <strong class="line--limit-1">
        {{ $detail->item['name'] }}</strong>
    <div class="d-flex align-items-center mt-2">
        <div class="bg-light border rounded px-3 py-2 mr-2">
            <span class="font-weight-bold text-primary" style="font-size: 1.1rem;">{{ $detail['quantity'] }}</span>
            @if($product && $product->unit)
                <span class="text-muted mx-1">×</span>
                <span class="badge badge-soft-info">{{ is_object($product->unit) ? $product->unit->unit : $product->unit }}</span>
            @endif
        </div>
        <div class="text-muted">
            <i class="tio-atm"></i> {{ \App\CentralLogics\Helpers::format_currency($detail['price']) }}
        </div>
    </div>
    @if($detail->is_picked_up)
        <span class="badge badge-success ml-1" title="Picked up at {{ $detail->picked_up_at ? $detail->picked_up_at->format('h:i A') : '' }}">
            <i class="tio-checkmark-circle-outlined"></i> {{ translate('messages.picked_up') }}
        </span>
    @endif
    @if($detail->is_unavailable)
        <span class="badge badge-warning ml-1">{{ translate('messages.unavailable') }}</span>
        @if($detail->unavailable_note)
            <small class="d-block text-muted">{{ $detail->unavailable_note }}</small>
        @endif
    @endif
    @if($detail->is_outside_purchase)
        <small class="d-block text-info mt-1">🔸 {{ translate('messages.outside_purchase') }} | {{ translate('messages.purchase_cost') }}: {{ \App\CentralLogics\Helpers::format_currency($detail->outside_purchase_cost) }} | {{ translate('messages.profit') }}: {{ \App\CentralLogics\Helpers::format_currency(($detail->price - $detail->outside_purchase_cost) * $detail->quantity) }}@if($detail->outside_purchase_store_id && $detail->outsidePurchaseStore) | {{ $detail->outsidePurchaseStore->name }}@endif</small>
    @endif
    @if($detail->outside_purchase_status == 'pending')
        <div class="d-inline-flex align-items-center ml-1 mt-1">
            <span class="badge badge-warning">
                {{ translate('messages.outside_purchase_request') }}: {{ \App\CentralLogics\Helpers::format_currency($detail->outside_purchase_cost) }}
                ({{ translate('messages.pending_approval') }})@if($detail->outside_purchase_store_id && $detail->outsidePurchaseStore) | {{ $detail->outsidePurchaseStore->name }}@endif
            </span>
            <button type="button" class="btn btn-xs btn-success ml-1 approve-outside-purchase-btn"
                    data-detail-id="{{ $detail->id }}"
                    title="{{ translate('messages.approve_outside_purchase') }}"
                    style="padding:2px 5px;font-size:.7rem;">
                <i class="tio-checkmark-circle"></i>
            </button>
            <button type="button" class="btn btn-xs btn-danger ml-1 reject-outside-purchase-btn"
                    data-detail-id="{{ $detail->id }}"
                    title="{{ translate('messages.reject_outside_purchase') }}"
                    style="padding:2px 5px;font-size:.7rem;">
                <i class="tio-clear-circle"></i>
            </button>
        </div>
    @elseif($detail->outside_purchase_status == 'rejected')
        <div class="d-inline-flex align-items-center ml-1 mt-1">
            <span class="badge badge-danger">
                {{ translate('messages.outside_purchase_rejected') }}@if($detail->outside_purchase_rejection_reason): {{ $detail->outside_purchase_rejection_reason }}@endif
            </span>
        </div>
    @endif
    @if($detail->mrp_update_status)
        <div class="d-inline-flex align-items-center ml-1">
            <span class="badge badge-{{ $detail->mrp_update_status == 'pending' ? 'info' : ($detail->mrp_update_status == 'approved' ? 'success' : 'danger') }}">
                {{ translate('messages.mrp_request') }}: {{ \App\CentralLogics\Helpers::format_currency($detail->requested_mrp) }}
                ({{ $detail->mrp_update_status }})
            </span>
            @if($detail->mrp_update_status == 'pending')
                <button type="button" class="btn btn-xs btn-success ml-1 approve-mrp-btn" data-detail-id="{{ $detail->id }}" data-action="approved" title="Approve MRP" style="padding:2px 5px;font-size:.7rem;">
                    <i class="tio-checkmark-circle"></i>
                </button>
                <button type="button" class="btn btn-xs btn-danger ml-1 approve-mrp-btn" data-detail-id="{{ $detail->id }}" data-action="rejected" title="Reject MRP" style="padding:2px 5px;font-size:.7rem;">
                    <i class="tio-clear-circle"></i>
                </button>
            @endif
        </div>
    @endif
</div>
                                                                @if ($order->store && $order->store->module->module_type == 'food')
                                                                    @if (isset($detail['variation']) ? json_decode($detail['variation'], true) : [])
                                                                        @foreach (json_decode($detail['variation'], true) as $variation)
                                                                            @if (isset($variation['name']) && isset($variation['values']))
                                                                                <span class="d-block text-capitalize">
                                                                                        <strong>
                                                                                            {{ $variation['name'] }} -
                                                                                        </strong>
                                                                                    </span>
                                                                                @foreach ($variation['values'] as $value)
                                                                                    <span
                                                                                        class="d-block text-capitalize">
                                                                                            &nbsp; &nbsp;
                                                                                            {{ $value['label'] }} :
                                                                                            <strong>{{ \App\CentralLogics\Helpers::format_currency($value['optionPrice']) }}</strong>
                                                                                        </span>
                                                                                @endforeach
                                                                            @else
                                                                                @if (isset(json_decode($detail['variation'], true)[0]))
                                                                                    <strong><u>
                                                                                            {{ translate('messages.Variation') }}
                                                                                            : </u></strong>
                                                                                    @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                                        <div
                                                                                            class="font-size-sm text-body">
                                                                                                <span>{{ $key1 }}
                                                                                                    : </span>
                                                                                            <span
                                                                                                class="font-weight-bold">{{ $variation }}</span>
                                                                                        </div>
                                                                                    @endforeach
                                                                                @endif
                                                                                {{-- @break --}}
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @else
                                                                    @if (count(json_decode($detail['variation'], true)) > 0)
                                                                        <strong><u>{{ translate('messages.variation') }}
                                                                                :
                                                                            </u></strong>
                                                                        @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                            @if ($key1 != 'stock' || ($order->store && config('module.' . $order->store->module->module_type)['stock']))
                                                                                <div class="font-size-sm text-body">
                                                                                        <span>{{ $key1 }} :
                                                                                        </span>
                                                                                    <span
                                                                                        class="font-weight-bold">{{ Str::limit($variation, 15, '...') }}</span>
                                                                                </div>
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @endif

                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                @if ($order->store && $order->store->module->module_type == 'food')
                                                    <td>
                                                        <div>
                                                            @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                                                @if ($key2 == 0)
                                                                    <strong><u>{{ translate('messages.addons') }} :
                                                                        </u></strong>
                                                                @endif
                                                                <div class="font-size-sm text-body">
                                                                        <span>{{ Str::limit($addon['name'], 20, '...') }}
                                                                            : </span>
                                                                    <span class="font-weight-bold">
                                                                            {{ $addon['quantity'] }} x
                                                                            {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                                        </span>
                                                                </div>
                                                                @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                @endif
                                                <td class="text-right">
                                                    <div>
                                                        @php($amount = $detail['price'] * $detail['quantity'])
                                                        <h5>{{ \App\CentralLogics\Helpers::format_currency($amount) }}
                                                        </h5>
                                                    </div>
                                                </td>
                                            </tr>

                                            @php($product_price += $amount)
                                            @php($store_discount_amount += $detail['discount_on_item'] * $detail['quantity'])
                                            <!-- End Media -->
                                        @elseif(isset($detail->item_campaign_id) && $detail->status)
                                                <?php
                                                if (!$editing) {
                                                    $detail->campaign = json_decode($detail->item_details, true);
                                                }
                                                $campaign = \App\Models\ItemCampaign::where(['id' => $detail->campaign['id']])->first();
                                                ?>
                                            <tr>
                                                <td>
                                                    <!-- Static Count Number -->
                                                    <div>
                                                        {{ $key + 1 }}
                                                    </div>
                                                    <!-- Static Count Number -->
                                                </td>
                                                <td>
                                                    <div class="media media--sm">
                                                        <a class="avatar avatar-xl mr-3"
                                                            href="{{ route('admin.campaign.view', ['item', $detail->campaign['id']]) }}">
                                                            <img class="img-fluid rounded onerror-image"
                                                                src="{{ $campaign?->image_full_url ?? asset('public/assets/admin/img/900x400/img1.jpg') }}"
                                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                alt="Image Description">
                                                        </a>

                                                        <div class="media-body">
                                                            <div>
                                                                <strong
                                                                    class="line--limit-1">{{ Str::limit($detail->campaign['name'], 20, '...') }}</strong>

                                                                <h6>
                                                                    {{ $detail['quantity'] }} x
                                                                    {{ \App\CentralLogics\Helpers::format_currency($detail['price']) }}
                                                                </h6>
                                                                @if ($order->store && $order->store->module->module_type == 'food')
                                                                    @if (isset($detail['variation']) ? json_decode($detail['variation'], true) : [])
                                                                        @foreach (json_decode($detail['variation'], true) as $variation)
                                                                            @if (isset($variation['name']) && isset($variation['values']))
                                                                                <span class="d-block text-capitalize">
                                                                                        <strong>
                                                                                            {{ $variation['name'] }} -
                                                                                        </strong>
                                                                                    </span>
                                                                                @foreach ($variation['values'] as $value)
                                                                                    <span
                                                                                        class="d-block text-capitalize">
                                                                                            &nbsp; &nbsp;
                                                                                            {{ $value['label'] }} :
                                                                                            <strong>{{ \App\CentralLogics\Helpers::format_currency($value['optionPrice']) }}</strong>
                                                                                        </span>
                                                                                @endforeach
                                                                            @else
                                                                                @if (isset(json_decode($detail['variation'], true)[0]))
                                                                                    <strong><u>
                                                                                            {{ translate('messages.Variation') }}
                                                                                            : </u></strong>
                                                                                    @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                                        <div
                                                                                            class="font-size-sm text-body">
                                                                                                <span>{{ $key1 }}
                                                                                                    : </span>
                                                                                            <span
                                                                                                class="font-weight-bold">{{ $variation }}</span>
                                                                                        </div>
                                                                                    @endforeach
                                                                                @endif
                                                                                {{-- @break --}}
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @else
                                                                    @if (count(json_decode($detail['variation'], true)) > 0)
                                                                        <strong><u>{{ translate('messages.variation') }}
                                                                                :</u></strong>
                                                                        @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                            @if ($key1 != 'stock' || ($order->store && config('module.' . $order->store->module->module_type)['stock']))
                                                                                <div class="font-size-sm text-body">
                                                                                        <span>{{ $key1 }} :
                                                                                        </span>
                                                                                    <span
                                                                                        class="font-weight-bold">{{ Str::limit($variation, 15, '...') }}</span>
                                                                                </div>
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                @if ($order->store && $order->store->module->module_type == 'food')
                                                    <td>
                                                        <div>
                                                            @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                                                @if ($key2 == 0)
                                                                    <strong><u>{{ translate('messages.addons') }} :
                                                                        </u></strong>
                                                                @endif
                                                                <div class="font-size-sm text-body">
                                                                        <span>{{ Str::limit($addon['name'], 20, '...') }}
                                                                            : </span>
                                                                    <span class="font-weight-bold">
                                                                            {{ $addon['quantity'] }} x
                                                                            {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                                        </span>
                                                                </div>
                                                                @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                @endif
                                                <td class="text-right">
                                                    <div>
                                                        @php($amount = $detail['price'] * $detail['quantity'])
                                                        <h5>{{ \App\CentralLogics\Helpers::format_currency($amount) }}
                                                        </h5>
                                                    </div>
                                                </td>
                                            </tr>

                                            @php($product_price += $amount)
                                            @php($store_discount_amount += $detail['discount_on_item'] * $detail['quantity'])
                                            <!-- End Media -->
                                        @elseif(isset($detail->item_campaign_id) && $detail->status)
                                                <?php
                                                if (!$editing) {
                                                    $detail->campaign = json_decode($detail->item_details, true);
                                                }
                                                $campaign = \App\Models\ItemCampaign::where(['id' => $detail->campaign['id']])->first();
                                                ?>
                                            <tr>
                                                <td>
                                                    <!-- Static Count Number -->
                                                    <div>
                                                        {{ $key + 1 }}
                                                    </div>
                                                    <!-- Static Count Number -->
                                                </td>
                                                <td>
                                                    <div class="media media--sm">
                                                        <a class="avatar avatar-xl mr-3"
                                                            href="{{ route('admin.campaign.view', ['item', $detail->campaign['id']]) }}">
                                                            <img class="img-fluid rounded onerror-image"
                                                                src="{{ $campaign?->image_full_url ?? asset('public/assets/admin/img/900x400/img1.jpg') }}"
                                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                alt="Image Description">
                                                        </a>

                                                        <div class="media-body">
                                                            <div>
                                                                <strong
                                                                    class="line--limit-1">{{ Str::limit($detail->campaign['name'], 20, '...') }}</strong>

                                                                <h6>
                                                                    {{ $detail['quantity'] }} x
                                                                    {{ \App\CentralLogics\Helpers::format_currency($detail['price']) }}
                                                                </h6>
                                                                @if ($order->store && $order->store->module->module_type == 'food')
                                                                    @if (isset($detail['variation']) ? json_decode($detail['variation'], true) : [])
                                                                        @foreach (json_decode($detail['variation'], true) as $variation)
                                                                            @if (isset($variation['name']) && isset($variation['values']))
                                                                                <span class="d-block text-capitalize">
                                                                                        <strong>
                                                                                            {{ $variation['name'] }} -
                                                                                        </strong>
                                                                                    </span>
                                                                                @foreach ($variation['values'] as $value)
                                                                                    <span
                                                                                        class="d-block text-capitalize">
                                                                                            &nbsp; &nbsp;
                                                                                            {{ $value['label'] }} :
                                                                                            <strong>{{ \App\CentralLogics\Helpers::format_currency($value['optionPrice']) }}</strong>
                                                                                        </span>
                                                                                @endforeach
                                                                            @else
                                                                                @if (isset(json_decode($detail['variation'], true)[0]))
                                                                                    <strong><u>
                                                                                            {{ translate('messages.Variation') }}
                                                                                            : </u></strong>
                                                                                    @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                                        <div
                                                                                            class="font-size-sm text-body">
                                                                                                <span>{{ $key1 }}
                                                                                                    : </span>
                                                                                            <span
                                                                                                class="font-weight-bold">{{ $variation }}</span>
                                                                                        </div>
                                                                                    @endforeach
                                                                                @endif
                                                                                {{-- @break --}}
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @else
                                                                    @if (count(json_decode($detail['variation'], true)) > 0)
                                                                        <strong><u>{{ translate('messages.variation') }}
                                                                                :</u></strong>
                                                                        @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                            @if ($key1 != 'stock' || ($order->store && config('module.' . $order->store->module->module_type)['stock']))
                                                                                <div class="font-size-sm text-body">
                                                                                        <span>{{ $key1 }} :
                                                                                        </span>
                                                                                    <span
                                                                                        class="font-weight-bold">{{ Str::limit($variation, 15, '...') }}</span>
                                                                                </div>
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                @if ($order->store && $order->store->module->module_type == 'food')
                                                    <td>
                                                        <div>
                                                            @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                                                @if ($key2 == 0)
                                                                    <strong><u>{{ translate('messages.addons') }} :
                                                                        </u></strong>
                                                                @endif
                                                                <div class="font-size-sm text-body">
                                                                        <span>{{ Str::limit($addon['name'], 20, '...') }}
                                                                            : </span>
                                                                    <span class="font-weight-bold">
                                                                            {{ $addon['quantity'] }} x
                                                                            {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                                        </span>
                                                                </div>
                                                                @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                @endif
                                                <td class="text-right">
                                                    <div>
                                                        @php($amount = $detail['price'] * $detail['quantity'])
                                                        <h5>{{ \App\CentralLogics\Helpers::format_currency($amount) }}
                                                        </h5>
                                                    </div>
                                                </td>
                                            </tr>

                                            @php($product_price += $amount)
                                            @php($store_discount_amount += $detail['discount_on_item'] * $detail['quantity'])
                                            <!-- End Media -->
                                        @endif
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                                <?php
                                $coupon_discount_amount = $order['coupon_discount_amount'];

                                $total_price = $product_price + $total_addon_price - $store_discount_amount - $coupon_discount_amount - $admin_flash_discount_amount - $ref_bonus_amount - $store_flash_discount_amount - $extra_packaging_amount;

                                $total_tax_amount = $order['total_tax_amount'];
                                if($order->tax_status == 'included'){
                                    $total_tax_amount=0;
                                }
                                $deliverman_tips = $order['dm_tips'];

                                if ($editing) {
                                    $store_discount = \App\CentralLogics\Helpers::get_store_discount($order->store);
                                    if (isset($store_discount)) {
                                        if ($product_price + $total_addon_price < $store_discount['min_purchase']) {
                                            $store_discount_amount = 0;
                                        }

                                        if ($store_discount_amount > $store_discount['max_discount'] && $store_discount_amount > $store_discount['max_discount']) {
                                            $store_discount_amount = $store_discount['max_discount'];
                                        }
                                    }
                                    $coupon_discount_amount = $coupon ? \App\CentralLogics\CouponLogic::get_discount($coupon, $product_price + $total_addon_price - $store_discount_amount ) : $order['coupon_discount_amount'];
                                    $tax = $order->store->tax;

                                    $total_price = $product_price + $total_addon_price - $store_discount_amount - $coupon_discount_amount;

                                    $total_tax_amount = $tax > 0 ? ($total_price * $tax) / 100 : 0;

                                    $total_tax_amount = round($total_tax_amount, 2);

                                    $tax_included = \App\Models\BusinessSetting::where(['key'=>'tax_included'])->first() ?  \App\Models\BusinessSetting::where(['key'=>'tax_included'])->first()->value : 0;
                                    if ($tax_included ==  1){
                                        $total_tax_amount=0;
                                    }

                                    $store_discount_amount = round($store_discount_amount, 2);

                                    if ($order?->store?->free_delivery) {
                                        $del_c = 0;
                                    }

                                    $free_delivery_over = \App\Models\BusinessSetting::where('key', 'free_delivery_over')->first()->value;
                                    if (isset($free_delivery_over)) {
                                        if ($free_delivery_over <= $product_price + $total_addon_price - $coupon_discount_amount - $store_discount_amount) {
                                            $del_c = 0;
                                        }
                                    }
                                    if ($order->order_type == 'take_away') {
                                        $del_c = 0;
                                    }
                                } else {
                                    $store_discount_amount = $order['store_discount_amount'];
                                }

                                ?>
                        @endif
                        <div class="mx-3">
                            <hr>
                        </div>
                        <div class="row justify-content-md-end mb-3 mt-4 mx-0">
                            <div class="col-md-9 col-lg-8">
                                <dl class="row text-right">
                                    @if (!$parcel_order)
                                        <dt class="col-6">{{ translate('messages.items_price') }}:</dt>
                                        <dd class="col-6">
                                            {{ \App\CentralLogics\Helpers::format_currency($product_price) }}</dd>
                                        @if ($order->store && $order->store->module->module_type == 'food')
                                            <dt class="col-6">{{ translate('messages.addon_cost') }}:</dt>
                                            <dd class="col-6">
                                                {{ \App\CentralLogics\Helpers::format_currency($total_addon_price) }}
                                                <hr>
                                            </dd>
                                        @endif

                                        <dt class="col-6">{{ translate('messages.subtotal') }}
                                            @if ($order->tax_status == 'included' ||  $tax_included ==  1)
                                                ({{ translate('messages.TAX_Included') }})
                                            @endif
                                            :</dt>
                                        <dd class="col-6">
                                            {{ \App\CentralLogics\Helpers::format_currency($product_price + $total_addon_price) }}
                                        </dd>
                                        <dt class="col-6">{{ translate('messages.discount') }}:</dt>
                                        <dd class="col-6">
                                            - {{ \App\CentralLogics\Helpers::format_currency($store_discount_amount + $admin_flash_discount_amount  + $store_flash_discount_amount) }}
                                        </dd>



                                        <dt class="col-6">{{ translate('messages.coupon_discount') }}:</dt>
                                        <dd class="col-6">
                                            - {{ \App\CentralLogics\Helpers::format_currency($coupon_discount_amount) }}
                                        </dd>
                                        @if ($ref_bonus_amount > 0)
                                            <dt class="col-6">{{ translate('messages.Referral_Discount') }}:</dt>
                                            <dd class="col-6">
                                                - {{ \App\CentralLogics\Helpers::format_currency($ref_bonus_amount) }}
                                            </dd>
                                        @endif
                                        @if ($order->tax_status == 'excluded' || $order->tax_status == null  )
                                            {{-- @php($tax_a=0) --}}
                                            <dt class="col-6">{{ translate('messages.vat/tax') }}:</dt>
                                            <dd class="col-6 text-right">
                                                +
                                                {{ \App\CentralLogics\Helpers::format_currency($total_tax_amount) }}
                                            </dd>
                                        @endif
                                        <dt class="col-6">{{ translate('messages.delivery_fee') }}
                                            @if ($order->free_delivery_by == 'admin')
                                            <i class="tio-info-outined" data-toggle="tooltip" title="{{ translate('Delivery fee is applicable and will be covered by the admin.') }}"></i>

                                            @elseif ($order->free_delivery_by == 'vendor')
                                            <i class="tio-info-outined" data-toggle="tooltip" title="{{ translate('Delivery fee is applicable and will be covered by the Vendor.') }}"></i>
                                            @endif
                                                :</dt>
                                        <dd class="col-6">
                                            + {{ \App\CentralLogics\Helpers::format_currency($del_c) }}
                                            <hr>
                                        </dd>
                                    @endif

                                    <dt class="col-6">{{ translate('messages.delivery_man_tips') }}</dt>
                                    <dd class="col-6">
                                        + {{ \App\CentralLogics\Helpers::format_currency($deliverman_tips) }}</dd>
                                    <dt class="col-6">{{ \App\CentralLogics\Helpers::get_business_data('additional_charge_name')??\App\CentralLogics\Helpers::get_business_data('additional_charge_name')??translate('messages.additional_charge') }}</dt>

                                    <dd class="col-6">
                                        + {{ \App\CentralLogics\Helpers::format_currency($additional_charge) }}</dd>

                                    @if ($extra_packaging_amount > 0)
                                        <dt class="col-6">{{ translate('messages.Extra_Packaging_Amount') }}:</dt>
                                        <dd class="col-6">
                                            + {{ \App\CentralLogics\Helpers::format_currency($extra_packaging_amount) }}
                                        </dd>
                                    @endif

                                    <?php
                                        $allMarkedOutside = $order->details->count() > 0 && $order->details->every(function($d) { return $d->is_outside_purchase; });
                                        $orderItemTotal = 0;
                                        foreach($order->details as $d) { $orderItemTotal += $d->price * $d->quantity; }
                                    ?>
                                    @if(!$allMarkedOutside)
                                        <dt class="col-12 mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-info mark-full-order-outside-purchase"
                                                data-order-id="{{ $order->id }}"
                                                data-order-total="{{ $orderItemTotal }}">
                                                🔸 {{ translate('messages.mark_full_order_outside_purchase') }}
                                            </button>
                                        </dt>
                                    @endif

                                    @if ($order->outside_purchase_amount > 0)
                                        <dt class="col-6 text-info">🔸 {{ translate('messages.outside_purchase_cost') }}:</dt>
                                        <dd class="col-6 text-info">
                                            {{ \App\CentralLogics\Helpers::format_currency($order->outside_purchase_amount) }}
                                        </dd>
                                        <?php
                                            $op_customer_total = 0;
                                            foreach($order->details as $op_d) {
                                                if($op_d->is_outside_purchase) {
                                                    $op_customer_total += $op_d->price * $op_d->quantity;
                                                }
                                            }
                                            $op_profit = $op_customer_total - $order->outside_purchase_amount;
                                        ?>
                                        <dt class="col-6 {{ $op_profit >= 0 ? 'text-success' : 'text-danger' }}">{{ translate('messages.outside_purchase_profit') }}:</dt>
                                        <dd class="col-6 {{ $op_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ \App\CentralLogics\Helpers::format_currency($op_profit) }}
                                        </dd>
                                    @endif

                                    <dt class="col-6">{{ translate('messages.total') }}:</dt>
                                    <dd class="col-6">

                                        {{ \App\CentralLogics\Helpers::format_currency($product_price + $del_c + $total_tax_amount + $total_addon_price + $deliverman_tips + $additional_charge - $coupon_discount_amount - $store_discount_amount - $admin_flash_discount_amount - $store_flash_discount_amount - $ref_bonus_amount +$extra_packaging_amount )  }}
</dd>

{{-- AMOUNT TO COLLECT FROM CUSTOMER --}}
@if(isset($order->cod_collection_amount) && $order->cod_collection_amount > 0 && !in_array($order->order_status, ['delivered', 'canceled', 'refunded']))
<dt class="col-12 mt-4 mb-3" style="border-top: 3px dashed #ffc107; padding-top: 20px;">
    <div class="card shadow-lg border-0" style="border-left: 5px solid #ff6b6b;">
        <div class="card-body p-4" style="background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar avatar-lg avatar-circle mr-3" style="background: #ff6b6b;">
                            <i class="tio-money text-white" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 text-dark">
                                <i class="tio-info-outlined"></i> Additional Amount to Collect
                            </h5>
                            <small class="text-muted">Delivery person must collect this amount</small>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <h1 class="text-danger mb-0" style="font-size: 2.8rem; font-weight: 800; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">
                        {{ \App\CentralLogics\Helpers::format_currency($order->cod_collection_amount) }}
                    </h1>
                    <span class="badge badge-danger badge-pill px-3 py-2 mt-2" style="font-size: 0.9rem;">
                        <i class="tio-wallet"></i> COLLECT ON DELIVERY
                    </span>
                </div>
            </div>
            
            <div class="alert alert-warning mt-3 mb-0" style="border-left: 4px solid #ffc107;">
                <div class="d-flex align-items-center">
                    <i class="tio-checkmark-circle mr-2" style="font-size: 1.5rem;"></i>
                    <small>
                        <strong>Note:</strong> This is extra amount added after order was edited.
                        Original order amount: <strong>{{ \App\CentralLogics\Helpers::format_currency($order->order_amount - $order->cod_collection_amount) }}</strong>
                    </small>
                </div>
            </div>
        </div>
    </div>
</dt>
@endif






                                    @if ($order?->payments)
                                        @foreach ($order?->payments as $payment)
                                            @if ($payment->payment_status == 'paid')
                                                @if ($payment->payment_method == 'cash_on_delivery')
                                                    <dt class="col-5 d-flex align-items-center">{{ translate('messages.Paid_with_Cash') }} ({{ translate('COD') }}) :</dt>
                                                @else
                                                    <dt class="col-5 d-flex align-items-center">{{ translate('messages.Paid_by') }} {{ translate($payment->payment_method) }} :</dt>
                                                @endif
                                            @else
                                                <dt class="col-5 d-flex align-items-center">{{ translate('Due_Amount') }} ({{ $payment->payment_method == 'cash_on_delivery' ? translate('messages.COD') : translate($payment->payment_method) }}) :</dt>
                                            @endif
                                            <dd class="col-7 text-right d-flex align-items-center justify-content-end" style="gap:8px;">
                                                {{ \App\CentralLogics\Helpers::format_currency($payment->amount) }}
                                                <a href="javascript:"
                                                   class="text-primary edit-order-payment-btn"
                                                   title="{{ translate('messages.edit') }}"
                                                   data-id="{{ $payment->id }}"
                                                   data-method="{{ $payment->payment_method }}"
                                                   data-status="{{ $payment->payment_status }}"
                                                   data-amount="{{ $payment->amount }}"
                                                   data-ref="{{ $payment->transaction_ref }}"
                                                   data-toggle="modal"
                                                   data-target="#editOrderPaymentModal">
                                                    <i class="tio-edit" style="font-size:13px;"></i>
                                                </a>
                                            </dd>
                                        @endforeach
                                    @endif
                                </dl>
                                <!-- End Row -->
                            </div>
                        </div>
                        <!-- End Row -->
                    </div>
                    <!-- End Body -->
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-4 order-print-area-right">
                @if ($order->order_status == 'canceled')

                    <div class="card mb-3">


                        <div class="card-body pt-2">

                            <ul class="delivery--information-single mt-3">
                                <li>
                                    <span class=" badge badge-soft-danger "> {{ translate('messages.Cancel_Reason') }} :</span>
                                    <span class="info">  {{ $order->cancellation_reason }} </span>
                                </li>
                                <hr class="w-100">
                                <li>
                                    <span class="name">{{ translate('Cancel_Note') }} </span>
                                    <span class="info">  {{ $order->cancellation_note ?? translate('messages.N/A')}} </span>
                                </li>
                                <li>
                                    <span class="name">{{ translate('Canceled_By') }} </span>
                                    <span class="info">  {{ translate($order->canceled_by) }} </span>
                                </li>
                                @if ($order->payment_status == 'paid' || $order->payment_status == 'partially_paid' )
                                    @if ( $order?->payments)
                                        @php( $pay_infos =$order->payments()->where('payment_status','paid')->get())
                                        @foreach ($pay_infos as $pay_info)
                                            <li>
                                                <span class="name">{{ translate('Amount_paid_by') }} {{ translate($pay_info->payment_method) }} </span>
                                                <span class="info">  {{ \App\CentralLogics\Helpers::format_currency($pay_info->amount)  }} </span>
                                            </li>
                                        @endforeach
                                    @else
                                        <li>
                                            <span class="name">{{ translate('Amount_paid_by') }} {{ translate($order->payment_method) }} </span>
                                            <span class="info ">  {{ \App\CentralLogics\Helpers::format_currency($order->order_amount)  }} </span>
                                        </li>
                                    @endif
                                @endif

                                @if ($order->payment_status == 'paid' || $order->payment_status == 'partially_paid')
                                    @if ( $order?->payments)
                                        @php( $amount =$order->payments()->where('payment_status','paid')->sum('amount'))
                                        <li>
                                            <span class="name">{{ translate('Amount_Returned_To_Wallet') }} </span>
                                            <span class="info">  {{ \App\CentralLogics\Helpers::format_currency($amount)  }} </span>
                                        </li>
                                    @else
                                        <li>
                                            <span class="name">{{ translate('Amount_Returned_To_Wallet') }} </span>
                                            <span class="info">  {{ \App\CentralLogics\Helpers::format_currency($order->order_amount)  }} </span>
                                        </li>
                                    @endif
                                @endif


                            </ul>
                        </div>
                    </div>

                @endif
                @php($refund = \App\Models\BusinessSetting::where(['key' => 'refund_active_status'])->first())

                @if (!empty($order->refund))
                    @if (
                        $order->order_status == 'refund_requested' ||
                            $order->order_status == 'refunded' ||
                            $order->order_status == 'refund_request_canceled')
                        <div class="card mb-2">
                            <div class="card-header border-0 d-block text-center pb-0">
                                <h4 class="m-0">{{ translate('messages.Refund Request') }} </h4>
                                <span>
                                    {{ date('d M Y ' . config('timeformat'), strtotime($order->refund->created_at)) }}
                                </span>

                                @if ($order->order_status == 'refund_requested')
                                    <span
                                        class="badge __badge badge-primary __badge-abs">{{ translate('messages.pending') }}</span>
                                @elseif($order->order_status == 'refunded')
                                    <span
                                        class="badge __badge badge-info __badge-abs">{{ translate('messages.refunded') }}</span>
                                @elseif($order->refund->order_status == 'refund_request_canceled')
                                    <span
                                        class="badge __badge-pill badge-danger __badge-abs">{{ translate('messages.rejected') }}</span>
                                @endif

                            </div>
                            <div class="card-body pt-2">
                                <label class="input-label"
                                       for="exampleFormControlInput1">{{ translate('messages.image') }} : </label>
                                <div class="row g-3">
                                    @php($data = isset($order->refund->image) ? json_decode($order->refund->image, true) : 0)
                                    @if ($data)
                                        @foreach ($data as $key => $img)
                                            @php($img = is_array($img)?$img:['img'=>$img,'storage'=>'public'])
                                            <div class="col-3">
                                                <img class="img__aspect-1 rounded border w-100 onerror-image" data-toggle="modal"
                                                     data-target="#imagemodal{{ $key }}"
                                                     data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                     src="{{ \App\CentralLogics\Helpers::get_full_url('refund',$img['img'],$img['storage']) }}">
                                            </div>
                                            <div class="modal fade" id="imagemodal{{ $key }}" tabindex="-1"
                                                 role="dialog" aria-labelledby="myModalLabel{{ $key }}"
                                                 aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title"
                                                                id="myModalLabel{{ $key }}">
                                                                {{ translate('Refund Image') }}</h4>
                                                            <button type="button" class="close"
                                                                    data-dismiss="modal"><span
                                                                    aria-hidden="true">&times;</span><span
                                                                    class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <img
                                                                src="{{ \App\CentralLogics\Helpers::get_full_url('refund',$img['img'],$img['storage']) }}"

                                                                class="initial--22 w-100">
                                                        </div>
                                                        @php($storage = $img['storage']??'public')
                                                        @php($file = $storage == 's3'?base64_encode('refund/' . $img['img']):base64_encode('public/refund/' . $img['img']))
                                                        <div class="modal-footer">
                                                            <a class="btn btn-primary"
                                                               href="{{ route('admin.file-manager.download', [$file,$storage]) }}"><i
                                                                    class="tio-download"></i>
                                                                {{ translate('messages.download') }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="col-3">
                                            <img class="img__aspect-1 rounded border w-100 onerror-image"
                                                 data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                 src="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}">
                                        </div>
                                    @endif
                                </div>
                                <hr>


                                <ul class="delivery--information-single mt-3">
                                    <li>
                                        <span class="name">{{ translate('Reason') }} </span>
                                        <span class="info"> {{ $order->refund->customer_reason }} </span>
                                    </li>
                                    <li>
                                        <span class="name">{{ translate('amount') }} </span>
                                        <span class="info"> {{ $order->refund->refund_amount }}</span>
                                    </li>
                                    <li>
                                        <span class="name">{{ translate('Method') }} </span>
                                        <span class="info"> {{ $order->refund->refund_method }}</span>
                                    </li>
                                    <li>
                                        <span class="name"> {{ translate('Status') }} </span>
                                        <span class="info"> {{ $order->refund->refund_status }}</span>
                                    </li>
                                    <li>
                                        <span class="name"> {{ translate('Admin Note') }} </span>
                                        <span class="info"> {{ $order->refund->admin_note ?? 'No Note' }}</span>
                                    </li>
                                    <li>
                                        <span class="name"> {{ translate('Customer Note') }} </span>
                                        <span class="info"> {{ $order->refund->customer_note ?? 'No Note' }}</span>
                                    </li>
                                    <hr class="w-100">
                                </ul>
                                @if ($order->store)
                                    <div class="btn--container refund--btn">
                                        @if (
                                            (($refund && $refund->value == true) || $order->order_status == 'refund_requested') &&
                                                $order->payment_status == 'paid' &&
                                                $order->order_status != 'refunded')
                                            <button class="btn btn--primary btn--sm route-alert"
                                                    data-url="{{ route('admin.order.status', ['id' => $order['id'],'order_status' => 'refunded',
                                            ]) }}" data-message="{{ translate('messages.you_want_to_refund_this_order', ['amount' => $refund_amount . ' ' . \App\CentralLogics\Helpers::currency_code()]) }}" data-title="{{ translate('messages.are_you_sure_want_to_refund') }}"
                                            ><i
                                                    class="tio-money"></i> <span
                                                    class="ml-1">{{ translate('messages.Refund') }}</span> </button>
                                        @endif
                                        @if ($order->order_status == 'refund_requested' )
                                            <button type="button" class="btn btn--danger btn-outline-danger"
                                                    data-toggle="modal" data-target="#refund_cancelation_note">
                                                <i class="tio-money"></i> <span
                                                    class="ml-1">{{ translate('messages.Cancel Refund') }}</span> </button>
                                        @endif
                                    </div>

                                @endif
                            </div>
                        </div>
                    @endif
                @endif
                @if ( !in_array($order->order_status, ['refund_requested', 'refunded', 'refund_request_canceled', 'delivered','canceled']) )
                    <div class="card">
                        <div class="card-header justify-content-center">
                            <h5 class="card-title">{{ translate('order_setup') }}</h5>
                        </div>
                        <div class="card-body">



                            @if($order?->offline_payments  && !in_array($order->order_status, ['canceled']) )
                                <div class="card border-info text-center mb-2">
                                    <div class="card-body">
                                        <h2>
                                            {{ $order?->offline_payments->status == 'verified'?translate('Payment_Verified'):translate('Payment_Verification') }}
                                        </h2>
                                        @if ($order?->offline_payments->status == 'pending')
                                            <p class="text-danger"> {{ translate('Please_Verify_the_payment_before_confirm_order.') }}</p>
                                            <div class="btn--container justify-content-center">
                                                <button  type="button" class="btn btn--primary btn-sm" data-toggle="modal" data-target="#verifyViewModal" >{{ translate('messages.Verify_Payment') }}</button>
                                            </div>

                                        @elseif($order?->offline_payments->status == 'verified')
                                            <div class="btn--container justify-content-center">
                                                <button  type="button" class="btn btn--primary btn-sm" data-toggle="modal" data-target="#verifyViewModal" >{{ translate('messages.Payment_Details') }}</button>
                                            </div>
                                        @elseif($order?->offline_payments->status == 'denied')
                                            <div class="btn--container justify-content-center">
                                                <button  type="button" class="btn btn--primary btn-sm" data-toggle="modal" data-target="#verifyViewModal" >{{ translate('messages.Recheck_Verification') }}</button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                             @if ($order->offline_payments == null  || ($order?->offline_payments && $order?->offline_payments->status == 'verified'))
                                @if ( !in_array($order->order_status, [ 'refunded', 'refund_request_canceled']))
                                    <div class="hs-unfold w-100">
                                        <div class="dropdown">
                                            <button
                                                class="form-control h--45px dropdown-toggle d-flex justify-content-between align-items-center w-100"
                                                type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                aria-haspopup="true" aria-expanded="false">
                                                    <?php
                                                    $message= match($order['order_status']){
                                                        'pending' => translate('messages.pending'),
                                                        'confirmed' => translate('messages.confirmed'),
                                                        'accepted' => translate('messages.accepted'),
                                                        'processing' => translate('messages.processing'),
                                                        'handover' => translate('messages.handover'),
                                                        'picked_up' => translate('messages.out_for_delivery'),
                                                        'delivered' => translate('messages.delivered'),
                                                        'canceled' => translate('messages.canceled'),
                                                        default => translate('messages.status') ,
                                                    };
                                                    ?>
                                                {{ $message }}
                                            </button>
                                            @php($order_delivery_verification = (bool) \App\Models\BusinessSetting::where(['key' => 'order_delivery_verification'])->first()->value)
                                            <div class="dropdown-menu text-capitalize" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item {{ $order['order_status'] == 'pending' ? 'active' : '' }} route-alert"
                                                   data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'pending']) }}" data-message="{{ translate('Change status to pending ?') }}"
                                                   href="javascript:">{{ translate('messages.pending') }}</a>
                                                <a class="dropdown-item {{ $order['order_status'] == 'confirmed' ? 'active' : '' }} route-alert"
                                                   data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'confirmed']) }}" data-message="{{ translate('Change status to confirmed ?') }}"
                                                   href="javascript:">{{ translate('messages.confirmed') }}</a>
                                                @if ($order->order_type != 'parcel')
                                                    @if ($order->store && $order->store->module->module_type == 'food')
                                                        <a class="dropdown-item {{ $order['order_status'] == 'processing' ? 'active' : '' }} order_status_change_alert" data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'processing']) }}" data-message="{{ translate('Change status to cooking ?') }}" data-processing={{ $max_processing_time }}
                                                        href="javascript:">{{ translate('messages.processing') }}</a>
                                                    @else
                                                        <a class="dropdown-item {{ $order['order_status'] == 'processing' ? 'active' : '' }} route-alert"
                                                           data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'processing']) }}" data-message="{{ translate('Change status to processing ?') }}"
                                                           href="javascript:">{{ translate('messages.processing') }}</a>
                                                    @endif
                                                    <a class="dropdown-item {{ $order['order_status'] == 'handover' ? 'active' : '' }} route-alert"
                                                       data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'handover']) }}" data-message="{{ translate('Change status to handover ?') }}"
                                                       href="javascript:">{{ translate('messages.handover') }}</a>
                                                @endif
                                                <a class="dropdown-item {{ $order['order_status'] == 'picked_up' ? 'active' : '' }} route-alert"
                                                   data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'picked_up']) }}" data-message="{{ translate('Change status to out for delivery ?') }}"
                                                   href="javascript:">{{ translate('messages.out_for_delivery') }}</a>
                                                <a class="dropdown-item {{ $order['order_status'] == 'delivered' ? 'active' : '' }} route-alert"
                                                   data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'delivered']) }}" data-message="{{ translate('Change status to delivered (payment status will be paid if not)?') }}"
                                                   href="javascript:">{{ translate('messages.delivered') }}</a>
                                                <a class="dropdown-item {{ $order['order_status'] == 'canceled' ? 'active' : '' }} canceled-status">{{ translate('messages.canceled') }}</a>
                                            </div>

                                        </div>
                                    </div>
                                @endif
                                @if (!in_array($order->order_status, [ 'refunded','delivered', 'canceled']) &&  ( !$order->delivery_man && $order['order_type'] != 'take_away' && (($order->store && !$order?->store?->sub_self_delivery) || $parcel_order)))
                                    <div class="w-100 text-center mt-3">
                                        <button type="button" class="btn btn--primary w-100" data-toggle="modal"
                                                data-target="#myModal" data-lat='21.03' data-lng='105.85'>
                                            {{ translate('messages.assign_delivery_man_manually') }}
                                        </button>
                                    </div>
                                @endif
                            @endif
                        
               
                                                
                            <!-- Assigned Admin Details Section -->
                            @if($order->assignedAdmin)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title d-flex align-items-center mb-3">
                                        <i class="tio-user mr-2"></i>
                                        {{ translate('messages.assigned_admin') }}
                                    </h5>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-lg avatar-circle mr-3">
                                            @if($order->assignedAdmin->image)
                                                <img class="avatar-img"
                                                    src="{{ asset('storage/app/public/admin/' . $order->assignedAdmin->image) }}"
                                                    alt="{{ $order->assignedAdmin->f_name }} {{ $order->assignedAdmin->l_name }}"
                                                    onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'">
                                            @else
                                                <img class="avatar-img"
                                                    src="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                    alt="{{ $order->assignedAdmin->f_name }} {{ $order->assignedAdmin->l_name }}">
                                            @endif
                                        </div>
                                        <div class="media-body">
                                            <h6 class="mb-1">
                                                {{ $order->assignedAdmin->f_name }} {{ $order->assignedAdmin->l_name }}
                                            </h6>
                                            <p class="mb-1 text-muted">
                                                <i class="tio-email mr-1"></i>
                                                {{ $order->assignedAdmin->email }}
                                            </p>
                                            @if($order->assignedAdmin->phone)
                                            <p class="mb-1 text-muted">
                                                <i class="tio-phone mr-1"></i>
                                                <a href="tel:{{ $order->assignedAdmin->phone }}">
                                                    {{ $order->assignedAdmin->phone }}
                                                </a>
                                            </p>
                                            @endif
                                            @if($order->assignedAdmin->role)
                                            <span class="badge badge-soft-primary">
                                                <i class="tio-verified mr-1"></i>
                                                {{ $order->assignedAdmin->role->name ?? translate('messages.admin') }}
                                            </span>
                                            @endif
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="tio-time mr-1"></i>
                                                    {{ translate('messages.assigned_on') }}:
                                                    {{ $order->updated_at ? $order->updated_at->format('M d, Y \a\t h:i A') : translate('messages.not_available') }}
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Reassign Button - Only show for role_id 1 and 2 -->
                                        @if(in_array(auth('admin')->user()->role_id, [1, 2]))
                                        <div class="ml-auto">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle"
                                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    {{ translate('messages.reassign') }}
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    @php($admins = \App\Models\Admin::where('role_id', '!=', 1)->where('id', '!=', $order->assigned_to)->select('id', 'f_name', 'l_name')->get())
                                                    @foreach($admins as $admin)
                                                        <a class="dropdown-item assign-order-detail"
                                                        href="javascript:void(0)"
                                                        data-order-id="{{ $order->id }}"
                                                        data-admin-id="{{ $admin->id }}"
                                                        data-admin-name="{{ $admin->f_name }} {{ $admin->l_name }}">
                                                            {{ $admin->f_name }} {{ $admin->l_name }}
                                                        </a>
                                                    @endforeach

                                                    <div class="dropdown-divider"></div>

                                                    <!-- Unassign Option -->
                                                    <a class="dropdown-item text-danger assign-order-detail"
                                                    href="javascript:void(0)"
                                                    data-order-id="{{ $order->id }}"
                                                    data-admin-id=""
                                                    data-admin-name="{{ translate('messages.unassigned') }}">
                                                        <i class="tio-clear mr-1"></i>
                                                        {{ translate('messages.unassign') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @else
                            
                                
                            <!-- No Admin Assigned Card -->
                            <div class="card mb-3 border-dashed">
                                <div class="card-body text-center py-4">
                                    <div class="mb-3">
                                        <i class="tio-user-big text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                    <h6 class="text-muted mb-2">{{ translate('messages.no_admin_assigned') }}</h6>
                                    <p class="text-muted mb-3">{{ translate('messages.assign_admin_to_manage_order') }}</p>
                                    
                                    <!-- Any role can assign when there is no assigned admin -->
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="tio-user-add mr-1"></i>
                                            {{ translate('messages.assign_admin') }}
                                        </button>
                                        <div class="dropdown-menu">
                                            @php($admins = \App\Models\Admin::where('role_id', '!=', 1)->select('id', 'f_name', 'l_name')->get())
                                            @foreach($admins as $admin)
                                                <a class="dropdown-item assign-order-detail"
                                                href="javascript:void(0)"
                                                data-order-id="{{ $order->id }}"
                                                data-admin-id="{{ $admin->id }}"
                                                data-admin-name="{{ $admin->f_name }} {{ $admin->l_name }}">
                                                    {{ $admin->f_name }} {{ $admin->l_name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>
                @endif
                @if ($parcel_order || ($order['order_type'] != 'take_away' && $order->store ))
                    @if ($order->delivery_man)
                        <div class="card mt-2">
                            <div class="card-body">
                                <h5 class="card-title mb-3 d-flex flex-wrap align-items-center">
                                    <span class="card-header-icon">
                                        <i class="tio-user"></i>
                                    </span>
                                    <span>{{ translate('messages.deliveryman') }}</span>


                                    @if ($order?->store?->sub_self_delivery)
                                        &nbsp; ({{ translate('messages.store') }})
                                    @endif

                                    @if (!isset($order->delivered) && !$order?->store?->sub_self_delivery)
                                        <a type="button" href="#myModal" class="text--base cursor-pointer ml-auto"
                                           data-toggle="modal" data-target="#myModal">
                                            {{ translate('messages.change') }}
                                        </a>
                                    @endif
                                </h5>
                                <a class="media align-items-center deco-none customer--information-single"
                                   href="{{ !$order?->store?->sub_self_delivery ?  route('admin.users.delivery-man.preview', [$order->delivery_man['id']]) : '#' }}">
                                    <div class="avatar avatar-circle">
                                        <img class="avatar-img onerror-image"
                                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                             src="{{ $order->delivery_man?->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                             alt="Image Description">
                                    </div>
                                    <div class="media-body">
                                        <span
                                            class="text-body d-block text-hover-primary mb-1">{{ $order->delivery_man['f_name'] . ' ' . $order->delivery_man['l_name'] }}</span>

                                        <span class="text--title font-semibold d-flex align-items-center">
                                            <i class="tio-shopping-basket-outlined mr-2"></i>
                                            {{ $order->delivery_man->orders_count }}
                                            {{ translate('messages.orders_delivered') }}
                                        </span>

                                        <span class="text--title font-semibold d-flex align-items-center">
                                            <i class="tio-call-talking-quiet mr-2"></i>
                                            {{ $order->delivery_man['phone'] }}
                                        </span>

                                        <span class="text--title font-semibold d-flex align-items-center">
                                            <i class="tio-email-outlined mr-2"></i>
                                            {{ $order->delivery_man['email'] }}
                                        </span>

                                    </div>
                                </a>
                                <hr>
                                @php($address = $order->dm_last_location)
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5>{{ translate('messages.last_location') }}</h5>
                                </div>
                                @if (isset($address))
                                    <span class="d-block">
                                        <a target="_blank"
                                           href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $address['latitude'] }}+{{ $address['longitude'] }}">
                                            <i class="tio-map"></i> {{ $address['location'] }}<br>
                                        </a>
                                    </span>
                                @else
                                    <span class="d-block text-lowercase qcont">
                                        {{ translate('messages.location_not_found') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif


                <div class="card mt-2">
                    <div class="card-body pt-3">
                        @if ($order->customer && $order->is_guest == 0)
                            <h5 class="card-title mb-3">
                                <span class="card-header-icon">
                                    <i class="tio-user"></i>
                                </span>
                                <span>{{ translate('customer_information') }}</span>
                            </h5>

                                                
                                                
                            <a class="media align-items-center deco-none customer--information-single"
                               href="{{ route('admin.users.customer.view', [$order->customer['id']]) }}">
                                <div class="avatar avatar-circle">
                                    <img class="avatar-img onerror-image"
                                         data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                         src="{{ $order->customer->image_full_url }}"
                                         alt="Image Description">
                                </div>
                                <div class="media-body">
                                    <span class="fz--14px text--title font-semibold text-hover-primary d-block">
                                        {{ $order->customer['f_name'] . ' ' . $order->customer['l_name'] }}
                                    </span>
                                    <span>{{ $order->customer->orders_count }} {{ translate('messages.orders') }}</span>
                                    <span class="text--title font-semibold d-flex align-items-center">
                                        <i class="tio-call-talking-quiet mr-2"></i> <span>{{ $order->customer['phone'] }}</span>
                                    </span>
                                    <span class="text--title d-flex align-items-center">
                                        <i class="tio-email mr-2"></i> <span>{{ $order->customer['email'] }}</span>
                                    </span>
                                </div>
                            </a>


                        @elseif($order->is_guest)
                            <span class="badge badge-soft-success py-2 d-block qcont">
                                {{ translate('Guest_user') }}
                            </span>

                        @else
                            <span class="badge badge-soft-danger py-2 d-block qcont">
                                {{ translate('Customer Not found!') }}
                            </span>
                        @endif
                        @if ($order->receiver_details)
                            @php($receiver_details = $order->receiver_details)
                            <h5 class="card-title mt-3">
                                    <span class="card-header-icon">
                                        <i class="tio-user"></i>
                                    </span>
                                <span>{{ translate('messages.receiver_info') }}</span>
                            </h5>
                            @if (isset($receiver_details))
                                <span class="delivery--information-single mt-3">
                                        <span class="name">{{ translate('messages.name') }}</span>
                                        <span class="info">{{ $receiver_details['contact_person_name'] }}</span>
                                        <span class="name">{{ translate('messages.contact') }}</span>
                                        <a class="deco-none info d-flex"
                                           href="tel:{{ $receiver_details['contact_person_number'] }}">
                                            {{ $receiver_details['contact_person_number'] }}</a>
                                            @if (data_get($receiver_details,'floor') != '')
                                                <span class="name">{{ translate('Floor') }}</span> <span
                                                class="info">{{ data_get($receiver_details,'floor', translate('messages.N/A'))  }}</span>
                                            @endif
                                            @if ( data_get($receiver_details,'house') != '')
                                                    <span class="name">{{ translate('House') }}</span> <span
                                                    class="info">{{data_get($receiver_details,'house', translate('messages.N/A')) }}</span>
                                            @endif

                                            @if ( data_get($receiver_details,'road') != '')
                                                    <span class="name">{{ translate('Road') }}</span> <span
                                                    class="info">{{ data_get($receiver_details,'road', translate('messages.N/A')) }}</span>
                                            @endif

                                        <hr class="w-100">

                                        @if (isset($receiver_details['address']))
                                        @if (isset($receiver_details['latitude']) && isset($receiver_details['longitude']))
                                            <a class="mt-2 d-flex" target="_blank"
                                               href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $receiver_details['latitude'] }}+{{ $receiver_details['longitude'] }}">
                                                    <i class="tio-poi"></i>{{ $receiver_details['address'] }}
                                                </a>
                                        @else
                                            <i class="tio-poi"></i>{{ $receiver_details['address'] }}
                                        @endif
                                    @endif
                                    </span>
                            @endif
                        @endif

                        @if ($order->delivery_address)
                            @php($address = json_decode($order->delivery_address, true))
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title">
                                    <span class="card-header-icon">
                                        <i class="tio-user"></i>
                                    </span>
                                    <span>{{ translate($parcel_order ? 'messages.sender' : 'messages.delivery_info') }}</span>
                                </h5>
                                @if ($order->order_status != 'delivered' && $order['partially_paid_amount'] == 0)
                                    @if (isset($address) && !$parcel_order)
                                        <a class="link d-flex" data-toggle="modal" data-target="#shipping-address-modal"
                                           href="javascript:"><i class="tio-edit"></i></a>
                                    @endif
                                @endif
                            </div>
                            @if (isset($address))
                                <div class="delivery--information-single mt-3">
                                    <span class="name">{{ translate('messages.name') }}</span>
                                    <span class="info">{{ data_get($address,'contact_person_name', translate('messages.N/A')) }}</span>
                                    <span class="name">{{ translate('messages.contact') }}</span>
                                    <a class="deco-none info" href="tel:{{ data_get($address,'contact_person_number', translate('messages.N/A'))  }}">
                                        {{ data_get($address,'contact_person_number', translate('messages.N/A')) }}</a>
                                            @if ( data_get($address,'house') != '')
                                                <span class="name">{{ translate('House') }}</span> <span
                                                class="info">{{data_get($address,'house', translate('messages.N/A')) }}</span>
                                            @endif
                                            @if (data_get($address,'floor') != '')
                                                <span class="name">{{ translate('Floor') }}</span> <span
                                                class="info">{{ data_get($address,'floor', translate('messages.N/A'))  }}</span>
                                            @endif

                                            @if ( data_get($address,'road') != '')
                                                <span class="name">{{ translate('Road') }}</span> <span
                                                class="info">{{ data_get($address,'road', translate('messages.N/A')) }}</span>
                                            @endif

                                    <hr class="w-100">
                                    <div>
                                        @if (isset($address['address']))
                                            @if ( data_get($address,'latitude', null) &&  data_get($address,'longitude', null))
                                                <a target="_blank" class="d-flex align-items-center"
                                                   href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $address['latitude'] }}+{{ $address['longitude'] }}">
                                                    <i class="tio-poi"></i>{{ $address['address'] }}
                                                </a>
                                            @else
                                                <i class="tio-poi"></i>{{ $address['address'] }}
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
                <!-- Customer Card -->
                @php($data = isset($order->order_proof) ? json_decode($order->order_proof, true) : [])
                @if ( in_array($order->order_status, [ 'handover', 'delivered', 'picked_up']) || ($data != null && count($data) > 0) )

                    <!-- order proof -->
                    <div class="card mb-2 mt-2">
                        <div class="card-header border-0 text-center pb-0">
                            <h4 class="m-0">{{ translate('messages.delivery_proof') }} </h4>
                            @if ( in_array($order->order_status, [ 'handover', 'delivered', 'picked_up']) )
                                <button class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target=".order-proof-modal">  {{ translate('messages.add') }}  </button>
                            @endif
                        </div>
                        <div class="card-body pt-2">
                            @if ($data)
                                <label class="input-label"
                                       for="order_proof">{{ translate('messages.image') }} : </label>
                                <div class="row g-3">
                                    @foreach ($data as $key => $img)
                                        @php($img = is_array($img)?$img:['img'=>$img,'storage'=>'public'])
                                        <div class="col-3">
                                            <img class="img__aspect-1 rounded border w-100 onerror-image" data-toggle="modal"
                                                 data-target="#imagemodal{{ $key }}"
                                                 data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                 src="{{\App\CentralLogics\Helpers::get_full_url('order',$img['img'],$img['storage']) }}">
                                        </div>
                                        <div class="modal fade" id="imagemodal{{ $key }}" tabindex="-1"
                                             role="dialog" aria-labelledby="order_proof_{{ $key }}"
                                             aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title"
                                                            id="order_proof_{{ $key }}">
                                                            {{ translate('order_proof_image') }}</h4>
                                                        <button type="button" class="close"
                                                                data-dismiss="modal"><span
                                                                aria-hidden="true">&times;</span><span
                                                                class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <img src="{{\App\CentralLogics\Helpers::get_full_url('order',$img['img'],$img['storage']) }}"
                                                             class="initial--22 w-100">
                                                    </div>
                                                    @php($storage = $img['storage'] ?? 'public')
                                                    @php($file = $storage == 's3'?base64_encode('order/' . $img['img']):base64_encode('public/order/' . $img['img']))
                                                    <div class="modal-footer">
                                                        <a class="btn btn-primary"
                                                           href="{{ route('admin.file-manager.download', [$file,$storage]) }}"><i
                                                                class="tio-download"></i>
                                                            {{ translate('messages.download') }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Bill Image Section - Floating Panel Trigger --}}
                <?php $bill_data = isset($order->bill_image) ? json_decode($order->bill_image, true) : []; ?>
                @if ($order->is_billed || ($bill_data != null && count($bill_data) > 0))
                    <div class="card mb-2 mt-2">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <h5 class="m-0 mr-2">{{ translate('messages.bill_image') }}</h5>
                                @if($order->is_billed)
                                    <span class="badge badge-success">{{ translate('messages.billed') }}</span>
                                @endif
                                @if($bill_data && count($bill_data) > 0)
                                    <span class="badge badge-soft-info ml-2">{{ count($bill_data) }} {{ translate('messages.image') }}(s)</span>
                                @endif
                                @if($order->bill_scan_result)
                                    <?php $panelStatus = $order->bill_scan_result['overall_status'] ?? 'unknown'; ?>
                                    <span class="badge badge-{{ $panelStatus === 'pass' ? 'success' : ($panelStatus === 'warn' ? 'warning' : 'danger') }} ml-2">
                                        {{ translate('messages.scan') }}: {{ strtoupper($panelStatus) }}
                                    </span>
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="toggleBillPanel()">
                                <i class="tio-receipt mr-1"></i> {{ translate('messages.view_bill') }}
                            </button>
                        </div>
                    </div>
                @endif

                @if ($order->store)
                    <!-- Restaurant Card -->
                    <div class="card mt-2">
                        <!-- Body -->
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <span class="card-header-icon">
                                    <i class="tio-user"></i>
                                </span>
                                <span>{{ translate('messages.store_information') }}</span>
                            </h5>
                            <a class="media align-items-center deco-none resturant--information-single"
                               href="{{ route('admin.store.view', [$order->store['id'],'module_id' => $order->module_id]) }}">
                                <div class="avatar avatar-circle">
                                    <img class="avatar-img w-75px onerror-image"
                                         data-onerror-image="{{ asset('public/assets/admin/img/100x100/1.png') }}"
                                         src="{{$order?->store?->logo_full_url ?? asset('public/assets/admin/img/100x100/1.png')  }}"
                                         alt="Image Description">
                                </div>
                                <div class="media-body">
                                    <span class="fz--14px text--title font-semibold text-hover-primary d-block">
                                        {{ $order->store['name'] }}
                                    </span>
                                    <span>{{ $order->store->orders_count }} {{ translate('messages.orders') }}</span>
                                    <span class="text--title font-semibold d-flex align-items-center">
                                        <i class="tio-call-talking-quiet mr-2"></i>{{ $order->store['phone'] }}
                                    </span>
                                    <span class="text--title d-flex align-items-center">
                                        <i class="tio-email mr-2"></i>{{ $order->store['email'] }}
                                    </span>
                                </div>
                            </a>
                            <hr>
                            <span class="d-block">
                                <a target="_blank" class="d-flex align-items-center __gap-5px" href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $order->store['latitude'] }}+{{ $order->store['longitude'] }}">
                                    <i class="tio-poi"></i> <span>{{ $order->store['address'] }}</span><br>
                                </a>
                            </span>
                        </div>
                        <!-- End Body -->
                    </div>
                    <!-- End Card -->
                @endif
            </div>
        </div>
        <!-- End Row -->
    </div>

    <!-- Modal -->
    <div class="modal fade" id="refund_cancelation_note" tabindex="-1" role="dialog"
         aria-labelledby="refund_cancelation_note_l" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="refund_cancelation_note_l">{{ translate('messages.add_Order Rejection_Note') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.refund.order_refund_rejection') }}" method="post">
                        @method('PUT')
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <input type="text" class="form-control" name="admin_note" value="{{ old('admin_note') }}"
                               placeholder="Fake Order">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{  translate('close') }}</button>
                    <button type="submit" class="btn btn-danger">{{ translate('messages.Confirm_Order Rejection') }} </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Edit Individual Order Payment Modal -->
    <div class="modal fade" id="editOrderPaymentModal" tabindex="-1" role="dialog" aria-labelledby="editOrderPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOrderPaymentModalLabel">
                        <i class="tio-edit mr-2"></i>{{ translate('messages.edit_payment') }}
                    </h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal" aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>
                <form id="editOrderPaymentForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">

                        <div class="form-group">
                            <label class="input-label">{{ translate('messages.payment_method') }}</label>
                            <select name="payment_method" id="opm_method" class="form-control">
                                <option value="cash_on_delivery">{{ translate('messages.cash_on_delivery') }}</option>
                                <option value="wallet">{{ translate('messages.wallet') }}</option>
                                <option value="digital_payment">{{ translate('messages.digital_payment') }}</option>
                                <option value="razor_pay">Razorpay</option>
                                <option value="stripe">Stripe</option>
                                <option value="paypal">PayPal</option>
                                <option value="bkash">bKash</option>
                                <option value="flutterwave">Flutterwave</option>
                                <option value="ssl_commerz">SSLCommerz</option>
                                <option value="paystack">Paystack</option>
                                <option value="mercado_pago">Mercado Pago</option>
                                <option value="paytm">Paytm</option>
                                <option value="liqpay">LiqPay</option>
                                <option value="iyzico">Iyzico</option>
                                <option value="senang_pay">SenangPay</option>
                                <option value="offline_payment">{{ translate('messages.offline_payment') }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('payment_status') }}</label>
                            <select name="payment_status" id="opm_status" class="form-control">
                                <option value="paid">{{ translate('messages.paid') }}</option>
                                <option value="unpaid">{{ translate('messages.unpaid') }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('messages.amount') }}</label>
                            <input type="number" name="amount" id="opm_amount" class="form-control" step="0.01" min="0">
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('messages.reference_code') }} <small class="text-muted">({{ translate('messages.optional') }})</small></label>
                            <input type="text" name="transaction_ref" id="opm_ref" class="form-control" maxlength="100" placeholder="{{ translate('messages.Ex:') }} TXN123456">
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ translate('messages.cancel') }}</button>
                        <button type="submit" class="btn btn--primary btn-sm">
                            <i class="tio-save mr-1"></i>{{ translate('messages.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.edit-order-payment-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id     = this.dataset.id;
                    var method = this.dataset.method;
                    var status = this.dataset.status;
                    var amount = this.dataset.amount;
                    var ref    = this.dataset.ref;

                    document.getElementById('editOrderPaymentForm').action =
                        '{{ rtrim(url("/"), "/") }}/admin/order/update-order-payment/' + id;

                    document.getElementById('opm_method').value = method;
                    document.getElementById('opm_status').value = status;
                    document.getElementById('opm_amount').value = amount;
                    document.getElementById('opm_ref').value    = (ref && ref !== 'null') ? ref : '';
                });
            });
        });
    </script>
    <!-- End Edit Individual Order Payment Modal -->

    <!-- Edit Payment Info Modal -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPaymentModalLabel">
                        <i class="tio-edit mr-2"></i>{{ translate('messages.edit_payment_info') }}
                    </h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal" aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>
                <form action="{{ route('admin.order.update-payment-info', $order->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">

                        <?php /* Show validation errors inside the modal */ ?>
                        @if($errors->hasAny(['payment_method','payment_status','transaction_reference']))
                        <div class="alert alert-danger py-2 px-3 mb-3">
                            <ul class="mb-0 pl-3">
                                @foreach($errors->only(['payment_method','payment_status','transaction_reference']) as $error)
                                    <li style="font-size:13px;">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <!-- Payment Method -->
                        <?php $pm = old('payment_method', $order->payment_method); ?>
                        <div class="form-group">
                            <label class="input-label">{{ translate('messages.payment_method') }}</label>
                            <select name="payment_method" class="form-control">
                                <option value="cash_on_delivery"   {{ $pm=='cash_on_delivery'  ?'selected':'' }}>{{ translate('messages.cash_on_delivery') }}</option>
                                <option value="wallet"             {{ $pm=='wallet'             ?'selected':'' }}>{{ translate('messages.wallet') }}</option>
                                <option value="digital_payment"    {{ $pm=='digital_payment'   ?'selected':'' }}>{{ translate('messages.digital_payment') }}</option>
                                <option value="razor_pay"          {{ $pm=='razor_pay'          ?'selected':'' }}>Razorpay</option>
                                <option value="stripe"             {{ $pm=='stripe'             ?'selected':'' }}>Stripe</option>
                                <option value="paypal"             {{ $pm=='paypal'             ?'selected':'' }}>PayPal</option>
                                <option value="bkash"              {{ $pm=='bkash'              ?'selected':'' }}>bKash</option>
                                <option value="flutterwave"        {{ $pm=='flutterwave'        ?'selected':'' }}>Flutterwave</option>
                                <option value="ssl_commerz"        {{ $pm=='ssl_commerz'        ?'selected':'' }}>SSLCommerz</option>
                                <option value="paystack"           {{ $pm=='paystack'           ?'selected':'' }}>Paystack</option>
                                <option value="mercado_pago"       {{ $pm=='mercado_pago'       ?'selected':'' }}>Mercado Pago</option>
                                <option value="paytm"              {{ $pm=='paytm'              ?'selected':'' }}>Paytm</option>
                                <option value="liqpay"             {{ $pm=='liqpay'             ?'selected':'' }}>LiqPay</option>
                                <option value="iyzico"             {{ $pm=='iyzico'             ?'selected':'' }}>Iyzico</option>
                                <option value="senang_pay"         {{ $pm=='senang_pay'         ?'selected':'' }}>SenangPay</option>
                                <option value="offline_payment"    {{ $pm=='offline_payment'    ?'selected':'' }}>{{ translate('messages.offline_payment') }}</option>
                                <option value="partial_payment"    {{ $pm=='partial_payment'    ?'selected':'' }}>{{ translate('messages.partial_payment') }}</option>
                            </select>
                        </div>

                        <!-- Payment Status -->
                        <?php $ps = old('payment_status', $order->payment_status); ?>
                        <div class="form-group">
                            <label class="input-label">{{ translate('payment_status') }}</label>
                            <select name="payment_status" class="form-control">
                                <option value="paid"    {{ $ps=='paid'   ?'selected':'' }}>{{ translate('messages.paid') }}</option>
                                <option value="unpaid"  {{ $ps=='unpaid' ?'selected':'' }}>{{ translate('messages.unpaid') }}</option>
                            </select>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="form-group">
                            <label class="input-label">{{ translate('messages.reference_code') }} <small class="text-muted">({{ translate('messages.optional') }})</small></label>
                            <input type="text" name="transaction_reference" class="form-control"
                                   value="{{ old('transaction_reference', $order->transaction_reference) }}"
                                   maxlength="100"
                                   placeholder="{{ translate('messages.Ex:') }} TXN123456">
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ translate('messages.cancel') }}</button>
                        <button type="submit" class="btn btn--primary btn-sm">
                            <i class="tio-save mr-1"></i>{{ translate('messages.save_changes') }}
                        </button>
                    </div>
                </form>

                <?php /* Auto-open modal if validation failed for payment fields */ ?>
                @if($errors->hasAny(['payment_method','payment_status','transaction_reference']))
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        $('#editPaymentModal').modal('show');
                    });
                </script>
                @endif
            </div>
        </div>
    </div>
    <!-- End Edit Payment Info Modal -->

    <!-- Modal -->
    <div class="modal fade bd-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h4" id="mySmallModalLabel">{{ translate('messages.reference_code_add') }}</h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal"
                            aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>

                <form action="{{ route('admin.order.add-payment-ref-code', [$order['id']]) }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <!-- Input Group -->
                        <div class="form-group">
                            <input type="text" name="transaction_reference" class="form-control"
                                   placeholder="{{ translate('messages.Ex:') }} Code123" required>
                        </div>
                        <!-- End Input Group -->
                        <div class="text-right">
                            <button class="btn btn--primary">{{ translate('messages.submit') }}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- End Modal -->
    <!-- Modal -->
    <div class="modal fade order-proof-modal" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h4" id="mySmallModalLabel">{{ translate('messages.add_delivery_proof') }}</h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal"
                            aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>

                <form action="{{ route('admin.order.add-order-proof', [$order['id']]) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="flex-grow-1 mx-auto">
                            <div class="d-flex flex-wrap __gap-12px __new-coba" id="coba">
                                @php($proof = isset($order->order_proof) ? json_decode($order->order_proof, true) : 0)
                                @if ($proof)

                                    @foreach ($proof as $key => $photo)
                                        @php($photo = is_array($photo)?$photo:['img'=>$photo,'storage'=>'public'])
                                        <div class="spartan_item_wrapper min-w-176px max-w-176px">
                                            <img class="img--square"
                                                 src="{{\App\CentralLogics\Helpers::get_full_url('order',$photo['img'],$photo['storage']) }}"
                                                 alt="order image">
                                            <div class="pen spartan_remove_row"><i class="tio-edit"></i></div>
                                            <a href="{{ route('admin.order.remove-proof-image', ['id' => $order['id'], 'name' => $photo['img']]) }}"
                                               class="spartan_remove_row"><i class="tio-add-to-trash"></i></a>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="text-right mt-2">
                            <button class="btn btn--primary">{{ translate('messages.submit') }}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!-- Modal -->
    <div id="shipping-address-modal" class="modal fade" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalTopCoverTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-top-cover bg-dark text-center">
                    <figure class="position-absolute right-0 bottom-0 left-0 mb--1">
                        <svg preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
                             viewBox="0 0 1920 100.1">
                            <path fill="#fff" d="M0,0c0,0,934.4,93.4,1920,0v100.1H0L0,0z" />
                        </svg>
                    </figure>

                    <div class="modal-close">
                        <button type="button" class="btn btn-icon btn-sm btn-ghost-light" data-dismiss="modal"
                                aria-label="Close">
                            <svg width="16" height="16" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor"
                                      d="M11.5,9.5l5-5c0.2-0.2,0.2-0.6-0.1-0.9l-1-1c-0.3-0.3-0.7-0.3-0.9-0.1l-5,5l-5-5C4.3,2.3,3.9,2.4,3.6,2.6l-1,1 C2.4,3.9,2.3,4.3,2.5,4.5l5,5l-5,5c-0.2,0.2-0.2,0.6,0.1,0.9l1,1c0.3,0.3,0.7,0.3,0.9,0.1l5-5l5,5c0.2,0.2,0.6,0.2,0.9-0.1l1-1 c0.3-0.3,0.3-0.7,0.1-0.9L11.5,9.5z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- End Header -->

                <div class="modal-top-cover-icon">
                    <span class="icon icon-lg icon-light icon-circle icon-centered shadow-soft">
                        <i class="tio-location-search"></i>
                    </span>
                </div>

                @if (isset($address))
                    <form action="{{ route('admin.order.update-shipping', [$order['id']]) }}" method="post">
                        @csrf
                        <div class="modal-body">
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.type') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="address_type"
                                           value="{{ $address['address_type'] }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.contact') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="contact_person_number"
                                           value="{{ $address['contact_person_number'] }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.name') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="contact_person_name"
                                           value="{{ $address['contact_person_name'] }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('House') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="house"
                                           value="{{ isset($address['house']) ? $address['house'] : '' }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('Floor') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="floor"
                                           value="{{ isset($address['floor']) ? $address['floor'] : '' }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('Road') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="road"
                                           value="{{ isset($address['road']) ? $address['road'] : '' }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.address') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="address"
                                           value="{{ $address['address'] }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.latitude') }}
                                </label>
                                <div class="col-md-4 js-form-message">
                                    <input type="text" class="form-control" name="latitude" id="latitude"
                                           value="{{ $address['latitude'] }}">
                                </div>
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.longitude') }}
                                </label>
                                <div class="col-md-4 js-form-message">
                                    <input type="text" class="form-control" name="longitude" id="longitude"
                                           value="{{ $address['longitude'] }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <input id="pac-input" class="controls rounded initial-8"
                                       title="{{ translate('messages.search_your_location_here') }}" type="text"
                                       placeholder="{{ translate('messages.search_here') }}" />
                                <div class="mb-2 h-200px" id="map"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn--reset"
                                    data-dismiss="modal">{{ translate('messages.close') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('messages.save_changes') }}</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!--Dm assign Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">{{ translate('messages.assign_deliveryman') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 my-2">
                            <div class="mb-3">
                                <input type="text" class="form-control" id="dm-search-input" placeholder="{{ translate('messages.search_deliveryman') }}...">
                            </div>
                            <ul class="list-group overflow-auto" id="dm-list-container" style="max-height: 450px;">
                                @foreach ($deliveryMen as $dm)
                                    <li class="list-group-item dm-list-item p-3 mb-2 border rounded">
                                        <div class="d-flex align-items-start">
                                            <span class="dm_list flex-grow-1" role='button' data-id="{{ $dm['id'] }}">
                                                <div class="d-flex">
                                                    <div class="mr-3">
                                                        <img class="avatar avatar-lg avatar-circle onerror-image"
                                                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                             src="{{ $dm['image_full_url'] }}"
                                                             alt="{{ $dm['name'] }}"
                                                             style="width: 60px; height: 60px; object-fit: cover;">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="mb-1 dm-name">{{ $dm['name'] }}</h5>
                                                        @if($dm['phone'])
                                                        <small class="text-muted d-block">
                                                            <i class="tio-call-talking mr-1"></i>{{ $dm['phone'] }}
                                                        </small>
                                                        @endif
                                                        @if($dm['vehicle_type'])
                                                        <small class="text-muted d-block">
                                                            <i class="tio-car mr-1"></i>{{ $dm['vehicle_type'] }}
                                                        </small>
                                                        @endif

                                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                                            <!-- Distance from store -->
                                                            <span class="badge badge-{{ $dm['distance_color'] ?? 'secondary' }} px-2 py-1" title="{{ translate('messages.distance_from_store') }}">
                                                                <i class="tio-map-arrow-with-circle mr-1"></i>{{ $dm['distance_text'] ?? 'N/A' }}
                                                            </span>

                                                            <!-- Rating -->
                                                            <span class="badge badge-soft-warning px-2 py-1" title="{{ translate('messages.rating') }}">
                                                                <i class="tio-star"></i>
                                                                {{ $dm['avg_rating'] > 0 ? $dm['avg_rating'] : '-' }}
                                                                @if($dm['rating_count'] > 0)
                                                                    <small>({{ $dm['rating_count'] }})</small>
                                                                @endif
                                                            </span>

                                                            <!-- Current Orders -->
                                                            <span class="badge badge-soft-info px-2 py-1" title="{{ translate('messages.current_orders') }}">
                                                                <i class="tio-shopping-basket"></i>
                                                                {{ $dm['current_orders'] }} {{ translate('messages.active') }}
                                                            </span>

                                                            <!-- Total Delivered -->
                                                            <span class="badge badge-soft-success px-2 py-1" title="{{ translate('messages.total_delivered') }}">
                                                                <i class="tio-checkmark-circle"></i>
                                                                {{ $dm['total_delivered_orders'] }} {{ translate('messages.delivered') }}
                                                            </span>

                                                            <!-- Cash In Hand -->
                                                            @if($dm['cash_in_hand'] > 0)
                                                            <span class="badge badge-soft-danger px-2 py-1" title="{{ translate('messages.cash_in_hand') }}">
                                                                <i class="tio-money"></i>
                                                                {{ \App\CentralLogics\Helpers::format_currency($dm['cash_in_hand']) }}
                                                            </span>
                                                            @endif
                                                        </div>

                                                        @if($dm['location'])
                                                        <small class="text-muted d-block mt-1">
                                                            <i class="tio-poi mr-1"></i>{{ Str::limit($dm['location'], 40) }}
                                                        </small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </span>
                                            <div class="ml-2">
                                                <a class="btn btn-primary btn-sm add-delivery-man" data-id="{{ $dm['id'] }}">
                                                    {{ translate('messages.assign') }}
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                                @if(count($deliveryMen) == 0)
                                    <li class="list-group-item text-center py-4">
                                        <img src="{{ asset('public/assets/admin/img/empty-box.png') }}" alt="" style="width: 60px;">
                                        <p class="text-muted mt-2 mb-0">{{ translate('messages.no_delivery_man_available') }}</p>
                                    </li>
                                @endif

                                <?php /* ---- Out-of-zone delivery men section ---- */ ?>
                                @if(isset($outOfZoneDeliveryMen) && count($outOfZoneDeliveryMen) > 0)
                                    <li class="list-group-item p-0 border-0">
                                        <div class="d-flex align-items-center my-2" style="gap:8px;">
                                            <hr style="flex:1;margin:0;">
                                            <span class="badge badge-warning px-3 py-1" style="font-size:11px;white-space:nowrap;">
                                                <i class="tio-world mr-1"></i>{{ translate('messages.other_zones') }} ({{ count($outOfZoneDeliveryMen) }})
                                            </span>
                                            <hr style="flex:1;margin:0;">
                                        </div>
                                    </li>
                                    @foreach ($outOfZoneDeliveryMen as $dm)
                                        <li class="list-group-item dm-list-item p-3 mb-2 border rounded" style="border-left: 3px solid #f5a623 !important; background:#fffdf5;">
                                            <div class="d-flex align-items-start">
                                                <span class="dm_list flex-grow-1" role='button' data-id="{{ $dm['id'] }}">
                                                    <div class="d-flex">
                                                        <div class="mr-3">
                                                            <img class="avatar avatar-lg avatar-circle onerror-image"
                                                                 data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                 src="{{ $dm['image_full_url'] }}"
                                                                 alt="{{ $dm['name'] }}"
                                                                 style="width: 60px; height: 60px; object-fit: cover;">
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h5 class="mb-1 dm-name">{{ $dm['name'] }}</h5>
                                                            @if(!empty($dm['zone_name']))
                                                            <small class="text-warning d-block">
                                                                <i class="tio-world mr-1"></i>{{ $dm['zone_name'] }}
                                                            </small>
                                                            @endif
                                                            @if($dm['phone'])
                                                            <small class="text-muted d-block">
                                                                <i class="tio-call-talking mr-1"></i>{{ $dm['phone'] }}
                                                            </small>
                                                            @endif
                                                            @if($dm['vehicle_type'])
                                                            <small class="text-muted d-block">
                                                                <i class="tio-car mr-1"></i>{{ $dm['vehicle_type'] }}
                                                            </small>
                                                            @endif
                                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                                <!-- Distance from store -->
                                                                <span class="badge badge-{{ $dm['distance_color'] ?? 'secondary' }} px-2 py-1" title="{{ translate('messages.distance_from_store') }}">
                                                                    <i class="tio-map-arrow-with-circle mr-1"></i>{{ $dm['distance_text'] ?? 'N/A' }}
                                                                </span>
                                                                <span class="badge badge-soft-warning px-2 py-1" title="{{ translate('messages.rating') }}">
                                                                    <i class="tio-star"></i>
                                                                    {{ $dm['avg_rating'] > 0 ? $dm['avg_rating'] : '-' }}
                                                                    @if($dm['rating_count'] > 0)
                                                                        <small>({{ $dm['rating_count'] }})</small>
                                                                    @endif
                                                                </span>
                                                                <span class="badge badge-soft-info px-2 py-1" title="{{ translate('messages.current_orders') }}">
                                                                    <i class="tio-shopping-basket"></i>
                                                                    {{ $dm['current_orders'] }} {{ translate('messages.active') }}
                                                                </span>
                                                                <span class="badge badge-soft-success px-2 py-1" title="{{ translate('messages.total_delivered') }}">
                                                                    <i class="tio-checkmark-circle"></i>
                                                                    {{ $dm['total_delivered_orders'] }} {{ translate('messages.delivered') }}
                                                                </span>
                                                                @if($dm['cash_in_hand'] > 0)
                                                                <span class="badge badge-soft-danger px-2 py-1" title="{{ translate('messages.cash_in_hand') }}">
                                                                    <i class="tio-money"></i>
                                                                    {{ \App\CentralLogics\Helpers::format_currency($dm['cash_in_hand']) }}
                                                                </span>
                                                                @endif
                                                            </div>
                                                            @if($dm['location'])
                                                            <small class="text-muted d-block mt-1">
                                                                <i class="tio-poi mr-1"></i>{{ Str::limit($dm['location'], 40) }}
                                                            </small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </span>
                                                <div class="ml-2">
                                                    <a class="btn btn-warning btn-sm add-delivery-man" data-id="{{ $dm['id'] }}" style="color:#fff;">
                                                        {{ translate('messages.assign') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                        <div class="col-md-6 modal_body_map">
                            <!-- Map Legend -->
                            <div class="card mb-2">
                                <div class="card-body p-2">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2" style="font-size: 12px;">
                                        <span class="d-flex align-items-center">
                                            <img src="{{ asset('public/assets/admin/img/restaurant_map.png') }}" alt="" style="width: 20px; height: 20px;" class="mr-1">
                                            {{ translate('messages.store') }}
                                        </span>
                                        <span class="d-flex align-items-center">
                                            <img src="{{ asset('public/assets/admin/img/customer_location.png') }}" alt="" style="width: 20px; height: 20px;" class="mr-1">
                                            {{ translate('messages.customer') }}
                                        </span>
                                        <span class="d-flex align-items-center">
                                            <img src="{{ asset('public/assets/admin/img/delivery_boy_map.png') }}" alt="" style="width: 20px; height: 20px;" class="mr-1">
                                            {{ translate('messages.delivery_man') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Map Container -->
                            <div class="card">
                                <div class="card-body p-0">
                                    <div class="location-map" id="dmassign-map" style="border-radius: 8px; overflow: hidden;">
                                        <div id="map_canvas" style="height: 400px; width: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- Location Info -->
                            @if(isset($order->store) || isset($address))
                            <div class="card mt-2">
                                <div class="card-body p-2">
                                    <div class="row" style="font-size: 12px;">
                                        @if(isset($order->store) && !$parcel_order)
                                        <div class="col-6">
                                            <div class="d-flex align-items-start">
                                                <i class="tio-store text-primary mr-1 mt-1"></i>
                                                <div>
                                                    <strong>{{ translate('messages.store') }}</strong>
                                                    <p class="mb-0 text-muted text-truncate" style="max-width: 150px;" title="{{ $order->store->address }}">
                                                        {{ Str::limit($order->store->address, 30) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                        @if(isset($address))
                                        <div class="col-6">
                                            <div class="d-flex align-items-start">
                                                <i class="tio-poi text-danger mr-1 mt-1"></i>
                                                <div>
                                                    <strong>{{ translate('messages.delivery_address') }}</strong>
                                                    <p class="mb-0 text-muted text-truncate" style="max-width: 150px;" title="{{ $address['address'] ?? '' }}">
                                                        {{ Str::limit($address['address'] ?? 'N/A', 30) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Search functionality for delivery man list
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('dm-search-input');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const dmItems = document.querySelectorAll('.dm-list-item');

                    dmItems.forEach(function(item) {
                        const dmName = item.querySelector('.dm-name');
                        if (dmName) {
                            const name = dmName.textContent.toLowerCase();
                            if (name.includes(searchTerm)) {
                                item.style.display = '';
                            } else {
                                item.style.display = 'none';
                            }
                        }
                    });
                });
            }
        });
    </script>
    <!-- End Modal -->

                                                <!--Show locations on map Modal - WITHOUT FOOTER -->
                                                <div class="modal fade" id="locationModal" tabindex="-1" role="dialog" aria-labelledby="locationModalLabel">
                                                    <div class="modal-dialog modal-xl" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-primary text-white">
                                                                <div class="w-100">
                                                                    <h4 class="modal-title mb-1" id="locationModalLabel">
                                                                        <i class="tio-poi mr-2"></i>
                                                                        {{ translate('messages.live_tracking') }}
                                                                        <span class="badge badge-light ml-2" id="modalLiveIndicator">
                                                                            <i class="tio-circle text-success pulse-dot"></i> {{ translate('messages.live') }}
                                                                        </span>
                                                                    </h4>
                                                                    <small class="text-white-70" id="modalLastUpdate">
                                                                        {{ translate('messages.last_update') }}: <span id="modalTimeAgo">{{ translate('messages.just_now') }}</span>
                                                                    </small>
                                                                </div>
                                                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body p-0">
                                                                <!-- Hidden data for delivery tracking -->
                                                                @if ($order->delivery_man && $order->dm_last_location)
                                                                <div id="deliveryTrackingData"
                                                                     data-dm-lat="{{ $order->dm_last_location['latitude'] }}"
                                                                     data-dm-lng="{{ $order->dm_last_location['longitude'] }}"
                                                                     data-dm-updated="{{ $order->dm_last_location['updated_at'] ?? $order->dm_last_location['time'] ?? $order->dm_last_location['created_at'] ?? $order->updated_at }}"
                                                                     @if($order->customer && isset($address))
                                                                     data-customer-lat="{{ $address['latitude'] }}"
                                                                     data-customer-lng="{{ $address['longitude'] }}"
                                                                     @endif
                                                                     @if($order->store && !$parcel_order)
                                                                     data-store-lat="{{ $order->store->latitude }}"
                                                                     data-store-lng="{{ $order->store->longitude }}"
                                                                     data-store-name="{{ $order->store->name }}"
                                                                     @endif
                                                                     data-dm-name="{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}"
                                                                     data-dm-phone="{{ $order->delivery_man->phone }}"
                                                                     style="display:none;">
                                                                </div>
                                                                @endif

                                                                <!-- ETA Banner -->
                                                                <div class="eta-banner" id="etaBanner" style="display:none;">
                                                                    <div class="container-fluid py-4">
                                                                        <div class="row align-items-center">
                                                                            <div class="col-md-8">
                                                                                <h5 class="mb-1">
                                                                                    <i class="tio-time mr-2"></i>
                                                                                    <span id="etaBannerText">{{ translate('messages.calculating_eta') }}...</span>
                                                                                </h5>
                                                                                <small class="text-white-50" id="etaBannerSubtext"></small>
                                                                            </div>
                                                                            <div class="col-md-4 text-right">
                                                                                <div class="eta-countdown" id="etaCountdown">
                                                                                    <span class="eta-minutes" id="etaMinutes">--</span>
                                                                                    <span class="eta-label">{{ translate('messages.mins') }}</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Distance Summary Cards -->
                                                                <div class="p-4 bg-light">
                                                                    <div class="row" id="distanceCardsContainer">
                                                                        <div class="col-12 text-center py-3">
                                                                            <div class="spinner-border text-primary" role="status">
                                                                                <span class="sr-only">Loading...</span>
                                                                            </div>
                                                                            <p class="text-muted mt-2">{{ translate('messages.calculating_distances') }}...</p>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Location Info Cards -->
                                                                <div class="p-4 pt-0">
                                                                    <div class="row">
                                                                        <!-- Store -->
                                                                        @if ($order->store && !$parcel_order)
                                                                        <div class="col-md-4 mb-3">
                                                                            <div class="card h-100 border-primary">
                                                                                <div class="card-body p-3">
                                                                                    <div class="text-center">
                                                                                        <div class="avatar avatar-lg avatar-circle mx-auto mb-3">
                                                                                            <img class="avatar-img onerror-image"
                                                                                                 data-onerror-image="{{ asset('public/assets/admin/img/100x100/1.png') }}"
                                                                                                 src="{{ $order->store->logo_full_url }}"
                                                                                                 alt="Store">
                                                                                        </div>
                                                                                        <span class="badge badge-soft-primary mb-3">
                                                                                            <i class="tio-shop"></i> {{ translate('messages.store') }}
                                                                                        </span>
                                                                                        <h6 class="mb-2">{{ Str::limit($order->store->name, 25, '...') }}</h6>
                                                                                        <div class="btn-group btn-group-sm w-100">
                                                                                            <a href="tel:{{ $order->store->phone }}" class="btn btn-soft-primary">
                                                                                                <i class="tio-call"></i>
                                                                                            </a>
                                                                                            <a href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $order->store->latitude }}+{{ $order->store->longitude }}"
                                                                                               target="_blank" class="btn btn-soft-secondary">
                                                                                                <i class="tio-map"></i>
                                                                                            </a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        @endif

                                                                        <!-- Customer -->
                                                                        @if ($order->customer && isset($address))
                                                                        <div class="col-md-4 mb-3">
                                                                            <div class="card h-100 border-success">
                                                                                <div class="card-body p-3">
                                                                                    <div class="text-center">
                                                                                        <div class="avatar avatar-lg avatar-circle mx-auto mb-3">
                                                                                            <img class="avatar-img onerror-image"
                                                                                                 data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                                                 src="{{ $order->customer->image_full_url }}"
                                                                                                 alt="Customer">
                                                                                        </div>
                                                                                        <span class="badge badge-soft-success mb-3">
                                                                                            <i class="tio-user"></i> {{ translate($parcel_order ? 'messages.sender' : 'messages.customer') }}
                                                                                        </span>
                                                                                        <h6 class="mb-2">{{ $order->customer->f_name }} {{ $order->customer->l_name }}</h6>
                                                                                        <div class="btn-group btn-group-sm w-100">
                                                                                            <a href="tel:{{ $order->customer->phone }}" class="btn btn-soft-success">
                                                                                                <i class="tio-call"></i>
                                                                                            </a>
                                                                                            <a href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $address['latitude'] }}+{{ $address['longitude'] }}"
                                                                                               target="_blank" class="btn btn-soft-secondary">
                                                                                                <i class="tio-map"></i>
                                                                                            </a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        @endif

                                                                        <!-- Delivery Man - Enhanced -->
                                                                        @if ($order->delivery_man && $order->dm_last_location)
                                                                        <div class="col-md-4 mb-3">
                                                                            <div class="card h-100 border-warning shadow-sm">
                                                                                <div class="card-body p-3">
                                                                                    <div class="text-center">
                                                                                        <div class="avatar avatar-lg avatar-circle mx-auto mb-3 position-relative">
                                                                                            <img class="avatar-img onerror-image"
                                                                                                 data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                                                 src="{{ $order->delivery_man->image_full_url }}"
                                                                                                 alt="Delivery">
                                                                                            <span class="avatar-status avatar-lg-status avatar-status-success" id="dmStatusBadge">
                                                                                                <i class="tio-checkmark-circle-outlined"></i>
                                                                                            </span>
                                                                                        </div>
                                                                                        <span class="badge badge-soft-warning mb-2">
                                                                                            <i class="tio-motorbike"></i> {{ translate('messages.delivery_man') }}
                                                                                        </span>

                                                                                        <h5 class="mb-1">{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}</h5>

                                                                                        <!-- Rating -->
                                                                                        @if($order->delivery_man->rating && count($order->delivery_man->rating) > 0)
                                                                                        <div class="mb-2">
                                                                                            <span class="badge badge-warning">
                                                                                                <i class="tio-star"></i>
                                                                                                {{ number_format($order->delivery_man->rating[0]->average ?? 0, 1) }}
                                                                                            </span>
                                                                                            <small class="text-muted">({{ $order->delivery_man->rating[0]->rating_count ?? 0 }} {{ translate('messages.reviews') }})</small>
                                                                                        </div>
                                                                                        @endif

                                                                                        <!-- Delivery Status Badge -->
                                                                                        <div id="deliveryStatusBadge" class="mb-2"></div>

                                                                                        <!-- Real-time Stats -->
                                                                                        <div class="dm-stats mb-2">
                                                                                            <div class="row text-center small">
                                                                                                <div class="col-6 border-right">
                                                                                                    <div class="text-muted">{{ translate('messages.speed') }}</div>
                                                                                                    <strong id="dmSpeed">-- km/h</strong>
                                                                                                </div>
                                                                                                <div class="col-6">
                                                                                                    <div class="text-muted">{{ translate('messages.distance') }}</div>
                                                                                                    <strong id="dmDistanceToCustomer">-- km</strong>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>

                                                                                        <!-- Last Update Time -->
                                                                                        <small class="text-muted d-block mb-2" id="lastUpdateTime">
                                                                                            <i class="tio-time"></i>
                                                                                            {{ translate('messages.updated') }}
                                                                                            <span id="timeAgo">{{ translate('messages.calculating') }}...</span>
                                                                                        </small>

                                                                                        <div class="btn-group btn-group-sm w-100">
                                                                                            <a href="tel:{{ $order->delivery_man->phone }}" class="btn btn-warning">
                                                                                                <i class="tio-call"></i> {{ translate('messages.call') }}
                                                                                            </a>
                                                                                            <a href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $order->dm_last_location['latitude'] }}+{{ $order->dm_last_location['longitude'] }}"
                                                                                               target="_blank" class="btn btn-soft-secondary">
                                                                                                <i class="tio-open-in-new"></i> {{ translate('messages.navigate') }}
                                                                                            </a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        @endif

                                                                        <!-- Receiver (Parcel) -->
                                                                        @if ($parcel_order && isset($receiver_details))
                                                                        <div class="col-md-4 mb-3">
                                                                            <div class="card h-100 border-info">
                                                                                <div class="card-body p-3">
                                                                                    <div class="text-center">
                                                                                        <div class="avatar avatar-lg avatar-circle mx-auto mb-2">
                                                                                            <span class="avatar-initials bg-soft-info text-info">
                                                                                                <i class="tio-user"></i>
                                                                                            </span>
                                                                                        </div>
                                                                                        <span class="badge badge-soft-info mb-2">
                                                                                            <i class="tio-user"></i> {{ translate('messages.receiver') }}
                                                                                        </span>
                                                                                        <h6 class="mb-2">{{ $receiver_details['contact_person_name'] }}</h6>
                                                                                        <div class="btn-group btn-group-sm w-100">
                                                                                            <a href="tel:{{ $receiver_details['contact_person_number'] }}" class="btn btn-soft-info">
                                                                                                <i class="tio-call"></i>
                                                                                            </a>
                                                                                            <a href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $receiver_details['latitude'] }}+{{ $receiver_details['longitude'] }}"
                                                                                               target="_blank" class="btn btn-soft-secondary">
                                                                                                <i class="tio-map"></i>
                                                                                            </a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                <!-- Enhanced Map with Controls -->
                                                                <div class="position-relative">
                                                                    <div class="modal_body_map">
                                                                        <div class="location-map" id="location-map">
                                                                            <div style="height: 600px; width: 100%;" id="location_map_canvas"></div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Map Controls Overlay -->
                                                                    <div class="map-controls-overlay">
                                                                        <!-- Refresh Button -->
                                                                        <button type="button" class="btn btn-primary btn-sm map-control-btn" id="refreshLocationMap" title="{{ translate('messages.refresh_location') }}">
                                                                            <i class="tio-refresh"></i>
                                                                        </button>

                                                                        <!-- Center on DM Button -->
                                                                        <button type="button" class="btn btn-warning btn-sm map-control-btn" id="centerOnDM" title="{{ translate('messages.center_on_delivery_man') }}">
                                                                            <i class="tio-user-big"></i>
                                                                        </button>

                                                                        <!-- Show Route Toggle -->
                                                                        <button type="button" class="btn btn-success btn-sm map-control-btn active" id="toggleRoute" title="{{ translate('messages.toggle_route') }}">
                                                                            <i class="tio-route"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- NO FOOTER -->
                                                        </div>
                                                    </div>
                                                </div>

                                                <style>
                                                /* Modal Header Enhancements */
                                                .text-white-70 {
                                                    color: rgba(255, 255, 255, 0.7);
                                                }

                                                .pulse-dot {
                                                    animation: pulse-dot 2s ease-in-out infinite;
                                                }

                                                @keyframes pulse-dot {
                                                    0%, 100% { opacity: 1; }
                                                    50% { opacity: 0.4; }
                                                }

                                                /* ETA Banner */
                                                .eta-banner {
                                                    background: #10b981;
                                                    color: white;
                                                    border-bottom: 3px solid #047857;
                                                }

                                                .eta-banner.warning {
                                                    background: #f59e0b;
                                                    border-bottom-color: #b45309;
                                                }

                                                .eta-banner.danger {
                                                    background: #ef4444;
                                                    border-bottom-color: #b91c1c;
                                                }

                                                .eta-countdown {
                                                    display: flex;
                                                    align-items: baseline;
                                                    justify-content: flex-end;
                                                }

                                                .eta-minutes {
                                                    font-size: 2.5rem;
                                                    font-weight: 700;
                                                    line-height: 1;
                                                    margin-right: 0.5rem;
                                                }

                                                .eta-label {
                                                    font-size: 1rem;
                                                    opacity: 0.8;
                                                }

                                                /* Map Controls Overlay */
                                                .map-controls-overlay {
                                                    position: absolute;
                                                    bottom: 20px;
                                                    right: 20px;
                                                    z-index: 1000;
                                                    display: flex;
                                                    flex-direction: column;
                                                    gap: 10px;
                                                }

                                                .map-control-btn {
                                                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                                                    border-radius: 50%;
                                                    width: 45px;
                                                    height: 45px;
                                                    padding: 0;
                                                    display: flex;
                                                    align-items: center;
                                                    justify-content: center;
                                                    transition: all 0.3s ease;
                                                }

                                                .map-control-btn:hover {
                                                    transform: scale(1.1);
                                                    box-shadow: 0 6px 16px rgba(0,0,0,0.3);
                                                }

                                                .map-control-btn.active {
                                                    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.5);
                                                }

                                                .map-control-btn i {
                                                    font-size: 1.25rem;
                                                }

                                                /* Delivery Man Stats */
                                                .dm-stats .border-right {
                                                    border-right: 1px solid #e5e7eb !important;
                                                }

                                                /* Pulse animation for "Reached" badge */
                                                @keyframes pulse {
                                                    0%, 100% {
                                                        transform: scale(1);
                                                        opacity: 1;
                                                    }
                                                    50% {
                                                        transform: scale(1.05);
                                                        opacity: 0.9;
                                                    }
                                                }

                                                .pulse-animation {
                                                    animation: pulse 2s ease-in-out infinite;
                                                }

                                                /* Blink animation for "Stopped" status */
                                                @keyframes blink {
                                                    0%, 100% {
                                                        opacity: 1;
                                                    }
                                                    50% {
                                                        opacity: 0.5;
                                                    }
                                                }

                                                .blink-animation {
                                                    animation: blink 1.5s ease-in-out infinite;
                                                }

                                                /* Avatar status colors */
                                                .avatar-status {
                                                    position: absolute;
                                                    bottom: 0;
                                                    right: 0;
                                                    width: 1rem;
                                                    height: 1rem;
                                                    border-radius: 50%;
                                                    border: 2px solid #fff;
                                                    display: flex;
                                                    align-items: center;
                                                    justify-content: center;
                                                    font-size: 0.5rem;
                                                }

                                                .avatar-lg-status {
                                                    width: 1.25rem;
                                                    height: 1.25rem;
                                                    font-size: 0.625rem;
                                                }

                                                .avatar-status-success {
                                                    background-color: #00c9a7;
                                                    color: white;
                                                }

                                                .avatar-status-warning {
                                                    background-color: #ffc107;
                                                    color: white;
                                                }

                                                .avatar-status-danger {
                                                    background-color: #dc3545;
                                                    color: white;
                                                }

                                                .text-white-70 {
                                                    opacity: 0.7;
                                                }

                                                /* Distance card hover effect */
                                                .card {
                                                    transition: all 0.3s ease;
                                                }

                                                .card:hover {
                                                    transform: translateY(-3px);
                                                }

                                                /* Spinner */
                                                .spinner-border-sm {
                                                    width: 1rem;
                                                    height: 1rem;
                                                    border-width: 0.15em;
                                                }
                                                </style>
    <!-- End Modal -->

    <div class="modal fade" id="quick-view" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" id="quick-view-modal">

            </div>
        </div>
    </div>



    @if ($order?->offline_payments)
        <div class="modal fade" id="verifyViewModal" tabindex="-1" aria-labelledby="verifyViewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header d-flex justify-content-end  border-0">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true" class="tio-clear"></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex align-items-center flex-column gap-3 text-center">
                            <h2>{{translate('Payment_Verification')}}
                                @if ($order?->offline_payments->status == 'verified')
                                    <span class="badge badge-soft-success mt-3 mb-3">{{ translate('messages.verified') }}</span>
                                @endif
                            </h2>
                            <p class="text-danger mb-2 mt-2">{{ translate('Please_Check_&_Verify_the_payment_information_weather_it_is_correct_or_not_before_confirm_the_order.') }}</p>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h4 class="mb-3">{{ translate('messages.customer_information') }}</h4>
                                <div class="d-flex flex-column gap-2">
                                    @if($order->is_guest)
                                        @php($customer_details = json_decode($order['delivery_address'],true))

                                        <div class="d-flex align-items-center gap-2">
                                            <span>{{translate('Name')}}</span>:
                                            <span class="text-dark"> {{$customer_details['contact_person_name']}}</span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <span>{{translate('Phone')}}</span>:
                                            <span class="text-dark">  {{$customer_details['contact_person_number']}}</span>
                                        </div>

                                    @elseif($order->customer)
                                        <div class="d-flex align-items-center gap-2">
                                            <span>{{translate('Name')}}</span>:
                                            <span class="text-dark"> <a class="text-body text-capitalize" href="{{route('admin.customer.view',[$order['user_id']])}}"> {{$order->customer['f_name'].' '.$order->customer['l_name']}}  </a>  </span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <span>{{translate('Phone')}}</span>:
                                            <span class="text-dark">{{$order->customer['phone']}}  </span>
                                        </div>

                                    @else
                                        <label class="badge badge-danger">{{translate('messages.invalid_customer_data')}}</label>
                                    @endif

                                </div>

                                <div class="mt-5">
                                    <h4 class="mb-3">{{ translate('messages.Payment_Information') }}</h4>
                                    <div class="row g-3">
                                        @foreach (json_decode($order->offline_payments->payment_info) as $key=>$item)
                                            @if ($key != 'method_id')
                                                <div class="col-sm-6  col-lg-5">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="w-sm-25"> {{translate($key)}}</span>:
                                                        <span class="text-dark text-break">{{ $item }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>

                                    <div class="d-flex flex-column gap-2 mt-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span>{{translate('Customer_Note')}}</span>:
                                            <span class="text-dark text-break">{{$order->offline_payments?->customer_note ?? translate('messages.N/A')}} </span>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        @if ($order?->offline_payments->status != 'verified')
                            <div class="btn--container justify-content-end mt-3">
                                @if ($order?->offline_payments->status != 'denied')
                                    <button type="button" class="btn btn--danger btn-outline-danger offline_payment_cancelation_note" data-toggle="modal" data-target="#offline_payment_cancelation_note" data-id="{{ $order['id'] }}" class="btn btn--reset">{{translate('Payment_Didn’t_Recerive')}}</button>
                                @elseif ($order?->offline_payments->status == 'denied')
                                    <button type="button" data-url="{{ route('admin.order.offline_payment', [ 'id' => $order['id'], 'verify' => 'switched_to_cod', ]) }}" data-message="{{ translate('messages.Make_the_payment_verified_for_this_order') }}" class="btn btn-info mb-2 route-alert">{{translate('Switched_to_COD')}}</button>
                                @endif

                                <button type="button" data-url="{{ route('admin.order.offline_payment', [ 'id' => $order['id'], 'verify' => 'yes', ]) }}" data-message="{{ translate('messages.Make_the_payment_verified_for_this_order') }}" class="btn btn--primary mb-2 route-alert">{{translate('Yes,_Payment_Received')}}</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="offline_payment_cancelation_note" tabindex="-1" role="dialog"
             aria-labelledby="offline_payment_cancelation_note_l" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="offline_payment_cancelation_note_l">{{ translate('messages.Add_Offline_Payment_Rejection_Note') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.order.offline_payment') }}" method="get">
                            <input type="hidden" name="id" value="{{ $order->id }}">
                            <input type="text" required class="form-control" name="note" value="{{ old('note') }}"
                                   placeholder="{{ translate('transaction_id_mismatched') }}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{  translate('close') }}</button>
                        <button type="submit" class="btn btn--danger btn-outline-danger">{{ translate('messages.Confirm_Rejection') }} </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- End Modal -->

    {{-- Floating Bill Comparison Panel --}}
    <?php $bill_data_panel = isset($order->bill_image) ? json_decode($order->bill_image, true) : []; ?>
    @if ($order->is_billed || ($bill_data_panel != null && count($bill_data_panel) > 0))
    <div id="billFloatingPanel" class="bill-floating-panel" style="display:none;">
        {{-- Backdrop --}}
        <div class="bill-panel-backdrop" onclick="toggleBillPanel()"></div>
        {{-- Panel --}}
        <div class="bill-panel-content">
            {{-- Panel Header --}}
            <div class="bill-panel-header">
                <h5 class="m-0">
                    <i class="tio-receipt mr-1"></i> {{ translate('messages.bill_comparison') }}
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-info mr-1" id="scanBillBtn" onclick="scanBillWithAI({{ $order->id }})">
                        <i class="tio-search mr-1"></i> {{ translate('messages.scan_bill') }}
                    </button>
                    <span id="scanBillSpinner" class="d-none mr-2">
                        <span class="spinner-border spinner-border-sm text-info" role="status"></span>
                    </span>
                    <button type="button" class="btn btn-sm btn-light" onclick="toggleBillPanel()">
                        <i class="tio-clear"></i>
                    </button>
                </div>
            </div>

            {{-- Panel Body: Two columns --}}
            <div class="bill-panel-body">
                {{-- Left: Bill Image --}}
                <div class="bill-panel-left">
                    <div class="bill-panel-section-title">{{ translate('messages.bill_image') }}</div>
                    @if($bill_data_panel)
                        <div class="bill-image-carousel">
                            @foreach ($bill_data_panel as $bkey => $bimg)
                                <?php $bimg = is_array($bimg)?$bimg:['img'=>$bimg,'storage'=>'public']; ?>
                                <div class="bill-image-slide {{ $bkey == 0 ? 'active' : '' }}" data-index="{{ $bkey }}">
                                    <img src="{{ \App\CentralLogics\Helpers::get_full_url('order',$bimg['img'],$bimg['storage']) }}"
                                         class="bill-panel-img"
                                         data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                         onclick="this.classList.toggle('bill-img-zoomed')">
                                </div>
                            @endforeach
                        </div>
                        @if(count($bill_data_panel) > 1)
                            <div class="text-center mt-2">
                                @foreach ($bill_data_panel as $bkey => $bimg)
                                    <button class="btn btn-xs {{ $bkey == 0 ? 'btn-primary' : 'btn-outline-secondary' }} bill-img-dot mx-1"
                                            onclick="showBillSlide({{ $bkey }})">{{ $bkey + 1 }}</button>
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-2 text-center">
                            <?php
                                $firstBimg = is_array($bill_data_panel[0]) ? $bill_data_panel[0] : ['img'=>$bill_data_panel[0],'storage'=>'public'];
                                $dlStorage = $firstBimg['storage'] ?? 'public';
                                $dlFile = $dlStorage == 's3' ? base64_encode('order/' . $firstBimg['img']) : base64_encode('public/order/' . $firstBimg['img']);
                            ?>
                            <a class="btn btn-sm btn-outline-primary"
                               href="{{ route('admin.file-manager.download', [$dlFile, $dlStorage]) }}">
                                <i class="tio-download mr-1"></i> {{ translate('messages.download') }}
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Right: Order Data + Scan Results --}}
                <div class="bill-panel-right">
                    {{-- Order Summary --}}
                    <div class="bill-panel-section-title">{{ translate('messages.order_summary') }} #{{ $order->id }}</div>
                    <table class="table table-sm table-borderless mb-2" style="font-size:13px;">
                        <tbody>
                            <tr>
                                <td class="text-muted py-1">{{ translate('messages.store') }}</td>
                                <td class="text-right py-1 font-weight-bold">{{ $order->store?->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted py-1">{{ translate('messages.date') }}</td>
                                <td class="text-right py-1">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}</td>
                            </tr>
                            <tr><td colspan="2"><hr class="my-1"></td></tr>
                            <tr>
                                <td class="text-muted py-1">{{ translate('messages.subtotal') }}</td>
                                <td class="text-right py-1">{{ \App\CentralLogics\Helpers::format_currency($order->order_amount - $order->delivery_charge - $order->total_tax_amount - $order->dm_tips - $order->additional_charge + $order->store_discount_amount + $order->coupon_discount_amount) }}</td>
                            </tr>
                            @if($order->store_discount_amount + $order->coupon_discount_amount > 0)
                            <tr>
                                <td class="text-muted py-1">{{ translate('messages.discount') }}</td>
                                <td class="text-right py-1 text-danger">- {{ \App\CentralLogics\Helpers::format_currency($order->store_discount_amount + $order->coupon_discount_amount) }}</td>
                            </tr>
                            @endif
                            @if($order->total_tax_amount > 0)
                            <tr>
                                <td class="text-muted py-1">{{ translate('messages.tax') }}</td>
                                <td class="text-right py-1">+ {{ \App\CentralLogics\Helpers::format_currency($order->total_tax_amount) }}</td>
                            </tr>
                            @endif
                            <tr style="border-top:2px solid #333;">
                                <td class="font-weight-bold py-1" style="font-size:14px;">{{ translate('messages.total') }}</td>
                                <td class="text-right font-weight-bold py-1" style="font-size:14px;">{{ \App\CentralLogics\Helpers::format_currency($order->order_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <?php
                        $txEdited = isset($transaction) && $transaction ? ($transaction->is_edited ?? false) : false;
                        $txAdj    = isset($transaction) && $transaction ? (float)($transaction->total_adjustment ?? 0) : 0;
                    ?>
                    {{-- Transaction Summary + Edit History Audit --}}
                    @if(isset($transaction) && $transaction)
                    <div class="mt-4 mb-2 d-flex align-items-center justify-content-between" style="border-bottom:2px solid #e7eaf3; padding-bottom:6px;">
                        <strong style="font-size:13px;">
                            <i class="tio-receipt"></i> {{ translate('messages.transaction_summary') ?? 'Transaction Summary' }}
                        </strong>
                        @if($txEdited)
                            <span class="badge badge-warning" style="font-size:11px;">
                                <i class="tio-edit"></i> Edited {{ $transaction->edit_count }}×
                            </span>
                        @else
                            <span class="badge badge-soft-success" style="font-size:11px;">Original</span>
                        @endif
                    </div>

                    <div class="card border-0 shadow-sm mb-3" style="border-left: 4px solid {{ $txEdited ? '#f5a623' : '#28a745' }} !important;">
                        <div class="card-body p-3">
                            <table class="table table-sm table-borderless mb-0" style="font-size:12px;">
                                <tr>
                                    <td class="text-muted py-1" style="width:55%;">
                                        {{ translate('messages.order_amount') ?? 'Order Amount' }}
                                        @if($txEdited && $transaction->original_order_amount)
                                            <br><small class="text-danger" style="font-size:10px;">Was: {{ \App\CentralLogics\Helpers::format_currency($transaction->original_order_amount) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-right py-1 font-weight-bold">
                                        {{ \App\CentralLogics\Helpers::format_currency($transaction->order_amount) }}
                                        @if($txEdited && $txAdj != 0)
                                            <br><span class="badge badge-{{ $txAdj > 0 ? 'success' : 'danger' }}" style="font-size:10px;">{{ $txAdj > 0 ? '+' : '' }}{{ \App\CentralLogics\Helpers::format_currency($txAdj) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">
                                        {{ translate('messages.store_amount') ?? 'Store Amount' }}
                                        @if($txEdited && $transaction->original_store_amount)
                                            <br><small class="text-muted" style="font-size:10px;">Was: {{ \App\CentralLogics\Helpers::format_currency($transaction->original_store_amount) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-right py-1">{{ \App\CentralLogics\Helpers::format_currency($transaction->store_amount) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">
                                        {{ translate('messages.admin_commission') ?? 'Admin Commission' }}
                                        @if($txEdited && $transaction->original_admin_commission)
                                            <br><small class="text-muted" style="font-size:10px;">Was: {{ \App\CentralLogics\Helpers::format_currency($transaction->original_admin_commission) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-right py-1">{{ \App\CentralLogics\Helpers::format_currency($transaction->admin_commission) }}</td>
                                </tr>
                                @if($transaction->delivery_charge > 0)
                                <tr>
                                    <td class="text-muted py-1">{{ translate('messages.delivery_charge') ?? 'Delivery Charge' }}</td>
                                    <td class="text-right py-1">{{ \App\CentralLogics\Helpers::format_currency($transaction->delivery_charge) }}</td>
                                </tr>
                                @endif
                                @if($txEdited && $transaction->last_edited_at)
                                <tr><td colspan="2"><hr class="my-1"></td></tr>
                                <tr>
                                    <td class="text-muted py-1">Last edited</td>
                                    <td class="text-right py-1 small">{{ \Carbon\Carbon::parse($transaction->last_edited_at)->format('d M Y, h:i A') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if($txEdited && !empty($editHistory))
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-gradient-primary text-white">
                            <h6 class="mb-0">
                                <i class="tio-time"></i> {{ translate('messages.complete_edit_history') }}
                                <span class="badge badge-light text-primary ml-2">{{ count($editHistory) }} {{ count($editHistory) == 1 ? translate('messages.edit') : translate('messages.edits') }}</span>
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="timeline-container p-3" style="max-height: 600px; overflow-y: auto;">
                                @foreach($editHistory as $index => $edit)
                                <div class="timeline-item mb-4 pb-3" style="border-left: 3px solid {{ $edit['adjustment'] >= 0 ? '#28a745' : '#dc3545' }}; padding-left: 20px; position: relative; background: #f8f9fa; border-radius: 0 8px 8px 0; padding: 15px 15px 15px 20px;">
                                    <div style="position: absolute; left: -8px; top: 20px; width: 14px; height: 14px; border-radius: 50%; background: {{ $edit['adjustment'] >= 0 ? '#28a745' : '#dc3545' }}; border: 3px solid white; box-shadow: 0 0 0 3px {{ $edit['adjustment'] >= 0 ? '#28a745' : '#dc3545' }};"></div>
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <span class="badge badge-{{ $edit['edited_by'] == 'admin' ? 'primary' : 'info' }}" style="font-size: 11px;">
                                                <i class="tio-user"></i> {{ ucfirst($edit['edited_by']) }} #{{ $edit['user_id'] }}
                                            </span>
                                            <div class="text-muted mt-1" style="font-size: 11px;">
                                                <i class="tio-calendar"></i> {{ \Carbon\Carbon::parse($edit['edited_at'])->format('d M Y, h:i A') }}
                                            </div>
                                        </div>
                                        <span class="badge badge-{{ $edit['adjustment'] >= 0 ? 'success' : 'danger' }} font-weight-bold" style="font-size: 13px; padding: 8px 12px;">
                                            {{ $edit['adjustment'] >= 0 ? '+' : '' }}{{ \App\CentralLogics\Helpers::format_currency($edit['adjustment']) }}
                                        </span>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 text-center py-2" style="border-right: 1px solid #dee2e6;">
                                            <div class="text-muted mb-1" style="font-size: 10px; text-transform: uppercase; font-weight: 600;">{{ translate('messages.before') }}</div>
                                            <div class="text-danger font-weight-bold" style="font-size: 16px;">{{ \App\CentralLogics\Helpers::format_currency($edit['old_order_amount']) }}</div>
                                        </div>
                                        <div class="col-6 text-center py-2">
                                            <div class="text-muted mb-1" style="font-size: 10px; text-transform: uppercase; font-weight: 600;">{{ translate('messages.after') }}</div>
                                            <div class="text-success font-weight-bold" style="font-size: 16px;">{{ \App\CentralLogics\Helpers::format_currency($edit['new_order_amount']) }}</div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-soft-info mt-2 mb-2 p-2" style="font-size: 11px; border-radius:6px; background:#e8f4fd; border-left:3px solid #17a2b8;">
                        <i class="tio-checkmark-circle"></i> This order has not been edited. Transaction amounts are original.
                    </div>
                    @endif
                    @endif

                    {{-- Order Items --}}
                    <div class="bill-panel-section-title mt-3">{{ translate('messages.order_items') }}</div>
                    <table class="table table-sm table-bordered mb-3" style="font-size:12px;">
                        <thead class="thead-light">
                            <tr><th>{{ translate('messages.item') }}</th><th class="text-center">{{ translate('messages.qty') }}</th><th class="text-right">{{ translate('messages.price') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach($order->details as $d)
                                @if($d->status)
                                <?php $dItem = json_decode($d->item_details, true); ?>
                                <tr>
                                    <td>{{ $dItem['name'] ?? 'Unknown' }}</td>
                                    <td class="text-center">{{ $d->quantity }}</td>
                                    <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($d->price) }}</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Scan Results --}}
                    <div class="bill-panel-section-title mt-3">{{ translate('messages.scan_results') }}</div>
                    <div id="billScanResultsContainer">
                        @if($order->bill_scan_result)
                            @include('admin-views.order.partials._bill-scan-results', ['scanResult' => $order->bill_scan_result])
                        @else
                            <p class="text-muted small text-center py-3">{{ translate('messages.click_scan_bill_to_verify') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif


@endsection

@push('css')
<style>
.v2-pos-btn { transition: transform .15s, box-shadow .15s; }
.v2-pos-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(40,167,69,.55) !important; color:#fff; }
.v2-recommended-banner { animation: v2-pulse 2.5s ease-in-out infinite; }
@keyframes v2-pulse {
    0%,100% { opacity:1; }
    50%      { opacity:.7; }
}
</style>
@endpush

@push('script_2')
                                                <script>
                                                // ===== AJAX Search System =====
                                                let searchTimeout = null;
                                                const DEBOUNCE_DELAY = 300;
                                                const ORDER_ID = '{{ $order->id }}';
                                                const SEARCH_URL = '{{ route("admin.order.search-items-for-order") }}';

                                                // Initialize search functionality
                                                function initAjaxSearch() {
                                                    const searchInput = $('#datatableSearch');
                                                    const clearBtn = $('.search-clear-btn');
                                                    const spinner = $('.search-spinner');
                                                    const resultBadge = $('#resultCount');
                                                    const itemsGrid = $('#items');
                                                    const skeletonLoader = $('#skeletonLoader');
                                                    const paginationContainer = $('#paginationContainer');

                                                    // Update clear button visibility
                                                    function updateClearButton() {
                                                        if (searchInput.val().length > 0) {
                                                            clearBtn.addClass('active');
                                                        } else {
                                                            clearBtn.removeClass('active');
                                                        }
                                                    }

                                                    // Show loading state
                                                    function showLoading() {
                                                        spinner.addClass('active');
                                                        itemsGrid.hide();
                                                        skeletonLoader.show();
                                                        paginationContainer.hide();
                                                    }

                                                    // Hide loading state
                                                    function hideLoading() {
                                                        spinner.removeClass('active');
                                                        skeletonLoader.hide();
                                                        itemsGrid.show();
                                                    }

                                                    // Perform AJAX search
                                                    function performSearch() {
                                                        const keyword = searchInput.val().trim();
                                                        const categoryId = $('#category').val();

                                                        showLoading();

                                                        $.ajax({
                                                            url: SEARCH_URL,
                                                            type: 'GET',
                                                            data: {
                                                                order_id: ORDER_ID,
                                                                keyword: keyword,
                                                                category_id: categoryId
                                                            },
                                                            success: function(response) {
                                                                if (response.success) {
                                                                    itemsGrid.html(response.html);

                                                                    // Update result count
                                                                    resultBadge.text(response.count + ' result' + (response.count !== 1 ? 's' : ''));
                                                                    resultBadge.addClass('active');

                                                                    // Hide pagination for AJAX results
                                                                    paginationContainer.hide();

                                                                    // Re-bind quick-view handlers
                                                                    bindQuickViewHandlers();
                                                                }
                                                            },
                                                            error: function(xhr, status, error) {
                                                                console.error('Search error:', xhr.responseText || error);
                                                                let errorMsg = '{{ translate("messages.search_failed") }}';
                                                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                                                    errorMsg = xhr.responseJSON.message;
                                                                }
                                                                toastr.error(errorMsg);
                                                            },
                                                            complete: function() {
                                                                hideLoading();
                                                            }
                                                        });
                                                    }

                                                    // Re-bind quick-view handlers after AJAX load
                                                    function bindQuickViewHandlers() {
                                                        $('#items').find('.quick-view').off('click').on('click', function() {
                                                            let product_id = $(this).data('product-id');
                                                            quickView(product_id);
                                                        });
                                                    }

                                                    // Search input handler with debounce
                                                    searchInput.on('input', function() {
                                                        updateClearButton();

                                                        // Clear previous timeout
                                                        if (searchTimeout) {
                                                            clearTimeout(searchTimeout);
                                                        }

                                                        // Set new timeout for debounce
                                                        searchTimeout = setTimeout(function() {
                                                            performSearch();
                                                        }, DEBOUNCE_DELAY);
                                                    });

                                                    // Clear button handler
                                                    clearBtn.on('click', function() {
                                                        searchInput.val('');
                                                        updateClearButton();
                                                        resultBadge.removeClass('active');
                                                        performSearch();
                                                        searchInput.focus();
                                                    });

                                                    // Form submit handler (prevent page reload)
                                                    $('#search-form').on('submit', function(e) {
                                                        e.preventDefault();
                                                        if (searchTimeout) {
                                                            clearTimeout(searchTimeout);
                                                        }
                                                        performSearch();
                                                    });

                                                    // Category filter change handler (AJAX)
                                                    $('#category').on('change', function() {
                                                        performSearch();
                                                    });

                                                    // Keyboard shortcuts
                                                    $(document).on('keydown', function(e) {
                                                        // Ctrl+K to focus search
                                                        if (e.ctrlKey && e.key === 'k') {
                                                            e.preventDefault();
                                                            searchInput.focus().select();
                                                        }
                                                        // Escape to clear search (when input is focused)
                                                        if (e.key === 'Escape' && searchInput.is(':focus')) {
                                                            searchInput.val('');
                                                            updateClearButton();
                                                            resultBadge.removeClass('active');
                                                            performSearch();
                                                        }
                                                    });

                                                    // Initialize clear button state
                                                    updateClearButton();
                                                }

                                                // Initialize on document ready
                                                $(document).ready(function() {
                                                });

                                                $('.addon_quantity_input_toggle').on('change', function(event) {
                                                    addon_quantity_input_toggle(event);
                                                })

                                                function addon_quantity_input_toggle(e) {
                                                    var cb = $(e.target);
                                                    if (cb.is(":checked")) {
                                                        cb.siblings('.addon-quantity-input').css({
                                                            'visibility': 'visible'
                                                        });
                                                    } else {
                                                        cb.siblings('.addon-quantity-input').css({
                                                            'visibility': 'hidden'
                                                        });
                                                    }
                                                }

                                                // ✅ IMPROVED: Better error handling and validation
$(document).on('click', '.add-replacement-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    let button = $(this);
    let itemId = button.data('product-id');
    let oldItemId = $('.replacement-suggestion-card').data('old-item-id');
    
    // Disable button to prevent double clicks
    button.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Adding...');
    
    // Add the replacement item to the order
    addReplacementToOrder(itemId, oldItemId, button);
});
function addReplacementToOrder(newItemId, oldItemId, button) {
    Swal.fire({
        title: '{{ translate("messages.add_replacement") }}',
        text: '{{ translate("messages.add_this_item_as_replacement") }}',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        cancelButtonText: '{{ translate("messages.cancel") }}',
        confirmButtonText: '{{ translate("messages.yes_add_it") }}',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed || result.value) {
            $.ajax({
                url: "{{ route('admin.order.replace-item') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    order_id: "{{ $order->id }}",
                    old_item_id: oldItemId,
                    new_item_id: newItemId,
                    quantity: 1 // Default quantity
                },
                timeout: 30000,
                beforeSend: function() {
                    Swal.fire({
                        title: '{{ translate("messages.processing") }}',
                        text: '{{ translate("messages.adding_item_to_order") }}',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function(response) {
                    if (response && response.success) {
                        Swal.fire({
                            title: '{{ translate("messages.success") }}',
                            text: response.message || '{{ translate("messages.replacement_added_successfully") }}',
                            icon: 'success',
                            confirmButtonText: '{{ translate("messages.ok") }}'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        button.prop('disabled', false).html('<i class="tio-add-circle-outlined mr-1"></i> Add to Order');
                        Swal.fire({
                            title: '{{ translate("messages.error") }}',
                            text: response?.message || '{{ translate("messages.something_went_wrong") }}',
                            icon: 'error',
                            confirmButtonText: '{{ translate("messages.ok") }}'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    button.prop('disabled', false).html('<i class="tio-add-circle-outlined mr-1"></i> Add to Order');
                    console.error("Add replacement error:", {xhr, status, error});
                    
                    let errorMsg = '{{ translate("messages.something_went_wrong") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (status === 'timeout') {
                        errorMsg = '{{ translate("messages.request_timeout") }}';
                    }
                    
                    Swal.fire({
                        title: '{{ translate("messages.error") }}',
                        text: errorMsg,
                        icon: 'error',
                        confirmButtonText: '{{ translate("messages.ok") }}'
                    });
                }
            });
        } else {
            // User cancelled
            button.prop('disabled', false).html('<i class="tio-add-circle-outlined mr-1"></i> Add to Order');
        }
    });
}

                                                    let key = $(this).data('key');
                                                    let campaignId = $(this).data('campaign-id');
                                                    let mrpInput = $(this).closest('td').find('.mrp-input');
                                                    let mrpValue = mrpInput.val();

                                                    // Enhanced validation
                                                    if (!mrpValue || isNaN(mrpValue) || parseFloat(mrpValue) <= 0) {
                                                        toastr.error('{{ translate("messages.please_enter_valid_mrp") }}', {
                                                            CloseButton: true,
                                                            ProgressBar: true
                                                        });
                                                        mrpInput.focus();
                                                        return;
                                                    }

                                                    updateCampaignMRP(campaignId, parseFloat(mrpValue), key);
                                                });

                                                // ✅ IMPROVED: Better error handling with loading state
                                                function updateItemMRP(itemId, mrpValue, key) {
                                                    console.log('updateItemMRP called:', {itemId, mrpValue, key, order_detail_id: key});
                                                    $.ajax({
                                                        url: '{{ route("admin.order.update-item-mrp") }}',
                                                        type: 'POST',
                                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                                        data: {
                                                            item_id: itemId,
                                                            mrp: mrpValue,
                                                            order_id: '{{ $order->id }}',
                                                            order_detail_id: key,
                                                            _token: '{{ csrf_token() }}'
                                                        },
                                                        timeout: 30000, // 30 second timeout
                                                        beforeSend: function() {
                                                            $('#loading').fadeIn(200);
                                                        },
                                                        success: function(response) {
                                                            if (response && response.success) {
                                                                toastr.success(response.message, {
                                                                    CloseButton: true,
                                                                    ProgressBar: true
                                                                });

                                                                if (response.new_total) {
                                                                    $('#order-total').text(response.new_total);
                                                                }
                                                                
                                                                // Optional: Reload after short delay
                                                                setTimeout(() => location.reload(), 1500);
                                                            } else {
                                                                toastr.error(response?.message || '{{ translate("messages.something_went_wrong") }}', {
                                                                    CloseButton: true,
                                                                    ProgressBar: true
                                                                });
                                                            }
                                                        },
                                                        error: function(xhr, status, error) {
                                                            console.error('MRP Update Error:', {xhr, status, error});
                                                            let errorMsg = '{{ translate("messages.something_went_wrong") }}';
                                                            
                                                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                                                errorMsg = xhr.responseJSON.message;
                                                            } else if (status === 'timeout') {
                                                                errorMsg = '{{ translate("messages.request_timeout") }}';
                                                            }
                                                            
                                                            toastr.error(errorMsg, {
                                                                CloseButton: true,
                                                                ProgressBar: true
                                                            });
                                                        },
                                                        complete: function() {
                                                            $('#loading').fadeOut(200);
                                                        }
                                                    });
                                                }

                                                function updateCampaignMRP(campaignId, mrpValue, key) {
                                                    $.ajax({
                                                        type: 'POST',
                                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                                        data: {
                                                            campaign_id: campaignId,
                                                            mrp: mrpValue,
                                                            order_id: '{{ $order->id }}',
                                                            order_detail_id: key,
                                                            _token: '{{ csrf_token() }}'
                                                        },
                                                        timeout: 30000,
                                                        beforeSend: function() {
                                                            $('#loading').fadeIn(200);
                                                        },
                                                        success: function(response) {
                                                            if (response && response.success) {
                                                                toastr.success(response.message, {
                                                                    CloseButton: true,
                                                                    ProgressBar: true
                                                                });

                                                                if (response.new_total) {
                                                                    $('#order-total').text(response.new_total);
                                                                }
                                                                
                                                                setTimeout(() => location.reload(), 1500);
                                                            } else {
                                                                toastr.error(response?.message || '{{ translate("messages.something_went_wrong") }}', {
                                                                    CloseButton: true,
                                                                    ProgressBar: true
                                                                });
                                                            }
                                                        },
                                                        error: function(xhr, status, error) {
                                                            console.error('Campaign MRP Update Error:', {xhr, status, error});
                                                            let errorMsg = '{{ translate("messages.something_went_wrong") }}';
                                                            
                                                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                                                errorMsg = xhr.responseJSON.message;
                                                            } else if (status === 'timeout') {
                                                                errorMsg = '{{ translate("messages.request_timeout") }}';
                                                            }
                                                            
                                                            toastr.error(errorMsg, {
                                                                CloseButton: true,
                                                                ProgressBar: true
                                                            });
                                                        },
                                                        complete: function() {
                                                            $('#loading').fadeOut(200);
                                                        }
                                                    });
                                                }

// ============================================
// COMPLETE REPLACEMENT SYSTEM - FIXED VERSION
// ============================================

// Mark Item Out of Stock with Replacement Suggestions
$(document).on('click', '.mark-out-of-stock', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    let button = $(this);
    let key = button.data('key');
    let itemId = button.data('item-id');
    let orderDetailId = button.data('order-detail-id');
    
    if (!orderDetailId || !itemId) {
        Swal.fire({
            title: '{{ translate("messages.error") }}',
            text: '{{ translate("messages.missing_required_data") }}',
            icon: 'error',
            confirmButtonText: '{{ translate("messages.ok") }}'
        });
        return false;
    }
    
    button.prop('disabled', true);
    markItemOutOfStock(itemId, orderDetailId, key, button);
});

// Mark Campaign Out of Stock
$(document).on('click', '.mark-campaign-out-of-stock', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    let button = $(this);
    let key = button.data('key');
    let campaignId = button.data('campaign-id');
    let orderDetailId = button.data('order-detail-id');
    
    if (!orderDetailId || !campaignId) {
        Swal.fire({
            title: '{{ translate("messages.error") }}',
            text: '{{ translate("messages.missing_required_data") }}',
            icon: 'error',
            confirmButtonText: '{{ translate("messages.ok") }}'
        });
        return false;
    }
    
    button.prop('disabled', true);
    markCampaignOutOfStock(campaignId, orderDetailId, key, button);
});

function markItemOutOfStock(itemId, orderDetailId, key, button) {
    Swal.fire({
        title: '{{ translate("messages.are_you_sure") }}',
        html: '<p>{{ translate("messages.item_will_be_marked_out_of_stock") }}</p><p class="text-danger font-weight-bold">⚠️ Stock will be set to 0</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FC6A57',
        cancelButtonColor: '#3085d6',
        cancelButtonText: '{{ translate("messages.no") }}',
        confirmButtonText: '{{ translate("messages.yes_mark_it") }}',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed || result.value) {
            $.ajax({
                url: "{{ route('admin.order.mark-item-out-of-stock') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    item_id: itemId,
                    order_detail_id: orderDetailId,
                    order_id: "{{ $order->id }}",
                    key: key
                },
                timeout: 30000,
                beforeSend: function() {
                    button.html('<i class="spinner-border spinner-border-sm"></i> Processing...');
                    $('#loading').fadeIn(200);
                },
                success: function(response) {
                    $('#loading').fadeOut(200);
                    
                    if (response && response.success) {
                        // Remove row with animation
                        let row = button.closest('tr');
                        row.fadeOut(400, function() {
                            $(this).remove();
                            
                            // Check if table is empty
                            if ($('.table-responsive table tbody tr:visible').length === 0) {
                                $('.table-responsive table tbody').html(`
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="tio-clear text-muted" style="font-size: 3rem;"></i>
                                            <p class="text-muted mt-3">{{ translate("messages.all_items_removed") }}</p>
                                        </td>
                                    </tr>
                                `);
                            }
                        });
                        
                        // Show success message
                        toastr.success('✓ ' + response.message, {
                            CloseButton: true,
                            ProgressBar: true,
                            timeOut: 3000
                        });
                        
                        // Now find replacements
                        findReplacementItems(itemId, orderDetailId);
                        
                    } else {
                        button.prop('disabled', false).html('<i class="tio-delete-outlined"></i> Mark as Out of Stock');
                        Swal.fire({
                            title: '{{ translate("messages.error") }}',
                            text: response?.message || '{{ translate("messages.something_went_wrong") }}',
                            icon: 'error',
                            confirmButtonText: '{{ translate("messages.ok") }}'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    $('#loading').fadeOut(200);
                    button.prop('disabled', false).html('<i class="tio-delete-outlined"></i> Mark as Out of Stock');
                    console.error("Out of stock error:", {xhr, status, error});
                    
                    let errorMsg = '{{ translate("messages.something_went_wrong") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        title: '{{ translate("messages.error") }}',
                        text: errorMsg,
                        icon: 'error',
                        confirmButtonText: '{{ translate("messages.ok") }}'
                    });
                }
            });
        } else {
            button.prop('disabled', false);
        }
    });
}

function markCampaignOutOfStock(campaignId, orderDetailId, key, button) {
    Swal.fire({
        title: '{{ translate("messages.are_you_sure") }}',
        html: '<p>{{ translate("messages.campaign_will_be_marked_out_of_stock") }}</p><p class="text-danger font-weight-bold">⚠️ Stock will be set to 0</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FC6A57',
        cancelButtonColor: '#3085d6',
        cancelButtonText: '{{ translate("messages.no") }}',
        confirmButtonText: '{{ translate("messages.yes_mark_it") }}',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed || result.value) {
            $.ajax({
                url: "{{ route('admin.order.mark-campaign-out-of-stock') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    campaign_id: campaignId,
                    order_detail_id: orderDetailId,
                    order_id: "{{ $order->id }}",
                    key: key
                },
                timeout: 30000,
                beforeSend: function() {
                    button.html('<i class="spinner-border spinner-border-sm"></i> Processing...');
                    $('#loading').fadeIn(200);
                },
                success: function(response) {
                    $('#loading').fadeOut(200);
                    
                    if (response && response.success) {
                        // Remove row with animation
                        let row = button.closest('tr');
                        row.fadeOut(400, function() {
                            $(this).remove();
                            
                            // Check if table is empty
                            if ($('.table-responsive table tbody tr:visible').length === 0) {
                                $('.table-responsive table tbody').html(`
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="tio-clear text-muted" style="font-size: 3rem;"></i>
                                            <p class="text-muted mt-3">{{ translate("messages.all_items_removed") }}</p>
                                        </td>
                                    </tr>
                                `);
                            }
                        });
                        
                        // Show success message
                        toastr.success('✓ ' + response.message, {
                            CloseButton: true,
                            ProgressBar: true,
                            timeOut: 3000
                        });
                        
                        // Reload after short delay to update totals
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                        
                    } else {
                        button.prop('disabled', false).html('<i class="tio-delete-outlined"></i> {{ translate("messages.out_of_stock") }}');
                        Swal.fire({
                            title: '{{ translate("messages.error") }}',
                            text: response?.message || '{{ translate("messages.something_went_wrong") }}',
                            icon: 'error',
                            confirmButtonText: '{{ translate("messages.ok") }}'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    $('#loading').fadeOut(200);
                    button.prop('disabled', false).html('<i class="tio-delete-outlined"></i> {{ translate("messages.out_of_stock") }}');
                    
                    let errorMsg = '{{ translate("messages.something_went_wrong") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        title: '{{ translate("messages.error") }}',
                        text: errorMsg,
                        icon: 'error',
                        confirmButtonText: '{{ translate("messages.ok") }}'
                        });
                }
            });
        } else {
            button.prop('disabled', false);
        }
    });
}

function findReplacementItems(itemId, orderDetailId) {
    $.ajax({
        url: "{{ route('admin.order.find-replacements') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            item_id: itemId,
            order_id: "{{ $order->id }}"
        },
        timeout: 30000,
        success: function(data) {
            if (data && data.success && data.replacements && data.replacements.length > 0) {
                showReplacementSuggestions(data.out_of_stock_item, data.replacements, orderDetailId);
            } else {
                console.log('No replacements found');
            }
        },
        error: function(xhr) {
            console.error('Find replacements error:', xhr);
        }
    });
}

// Update MRP for Items
    let button = $(this);
    let key = button.data('key');
    let itemId = button.data('item-id');
    let mrpInput = button.closest('td').find('.mrp-input');
    let mrpValue = parseFloat(mrpInput.val());

    if (!mrpValue || isNaN(mrpValue) || mrpValue < 0) {
        toastr.error('{{ translate("messages.please_enter_valid_mrp") }}', {
            CloseButton: true,
            ProgressBar: true
        });
        mrpInput.focus();
        return;
    }

    button.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');
    
    $.ajax({
        url: '{{ route("admin.order.update-item-mrp") }}',
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        data: {
            item_id: itemId,
            mrp: mrpValue,
            order_id: '{{ $order->id }}',
            order_detail_id: key,
            _token: '{{ csrf_token() }}'
        },
        timeout: 30000,
        beforeSend: function() {
            $('#loading').fadeIn(200);
        },
        success: function(response) {
            $('#loading').fadeOut(200);
            button.prop('disabled', false).html('{{ translate("messages.update") }}');
            
            if (response && response.success) {
                // Update the display price
                button.closest('tr').find('h5').text(response.new_price);
                
                toastr.success('✓ ' + response.message, {
                    CloseButton: true,
                    ProgressBar: true
                });
                
                // Reload to update totals
                setTimeout(() => location.reload(), 1500);
            } else {
                toastr.error(response?.message || '{{ translate("messages.something_went_wrong") }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
            }
        },
        error: function(xhr, status, error) {
            $('#loading').fadeOut(200);
            button.prop('disabled', false).html('{{ translate("messages.update") }}');
            console.error('MRP Update Error:', {xhr, status, error});
            
            let errorMsg = '{{ translate("messages.something_went_wrong") }}';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            
            toastr.error(errorMsg, {
                CloseButton: true,
                ProgressBar: true
            });
        }
    });
});

// Update MRP for Campaigns
    let button = $(this);
    let key = button.data('key');
    let campaignId = button.data('campaign-id');
    let mrpInput = button.closest('td').find('.mrp-input');
    let mrpValue = parseFloat(mrpInput.val());

    if (!mrpValue || isNaN(mrpValue) || mrpValue < 0) {
        toastr.error('{{ translate("messages.please_enter_valid_mrp") }}', {
            CloseButton: true,
            ProgressBar: true
        });
        mrpInput.focus();
        return;
    }

    button.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');
    
    $.ajax({
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        data: {
            campaign_id: campaignId,
            mrp: mrpValue,
            order_id: '{{ $order->id }}',
            order_detail_id: key,
            _token: '{{ csrf_token() }}'
        },
        timeout: 30000,
        beforeSend: function() {
            $('#loading').fadeIn(200);
        },
        success: function(response) {
            $('#loading').fadeOut(200);
            button.prop('disabled', false).html('{{ translate("messages.update") }}');
            
            if (response && response.success) {
                // Update the display price
                button.closest('tr').find('h5').text(response.new_price);
                
                toastr.success('✓ ' + response.message, {
                    CloseButton: true,
                    ProgressBar: true
                });
                
                // Reload to update totals
                setTimeout(() => location.reload(), 1500);
            } else {
                toastr.error(response?.message || '{{ translate("messages.something_went_wrong") }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
            }
        },
        error: function(xhr, status, error) {
            $('#loading').fadeOut(200);
            button.prop('disabled', false).html('{{ translate("messages.update") }}');
            console.error('Campaign MRP Update Error:', {xhr, status, error});
            
            let errorMsg = '{{ translate("messages.something_went_wrong") }}';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            
            toastr.error(errorMsg, {
                CloseButton: true,
                ProgressBar: true
            });
        }
    });
});
// ============================================
// UPDATED: Show Replacement Suggestions with "Keep This Item" option
// ============================================

function showReplacementSuggestions(outOfStockItem, replacements, orderDetailId) {
    console.log('🎨 Displaying replacement suggestions');
    
    // Remove any existing replacement cards first
    $('.replacement-suggestion-card').remove();
    
    let replacementHTML = `
        <div class="replacement-suggestion-card" data-old-item-id="${outOfStockItem.id}" data-order-detail-id="${orderDetailId}">
            <div class="replacement-header">
                <div class="replacement-icon">
                    <i class="tio-atm"></i>
                </div>
                <div class="replacement-title">
                    <h5>🔄 Smart Replacement Suggestions</h5>
                    <p>Found ${replacements.length} similar alternative${replacements.length > 1 ? 's' : ''}</p>
                </div>
            </div>
            
            <div class="out-of-stock-item">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <img src="${outOfStockItem.image_full_url}"
                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; margin-right: 15px;"
                             onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'">
                        <div>
                            <h6 class="mb-1">${outOfStockItem.name}</h6>
                            <small class="text-muted">
                                {{ \App\CentralLogics\Helpers::currency_symbol() }}${outOfStockItem.price}
                                ${outOfStockItem.unit ? ' / ' + (typeof outOfStockItem.unit === 'object' ? outOfStockItem.unit.unit : outOfStockItem.unit) : ''}
                            </small>
                        </div>
                    </div>
                    <span class="badge badge-info badge-pill px-3 py-2">
                        <i class="tio-info"></i> Current Item
                    </span>
                </div>
            </div>
    `;
    
    if (replacements && replacements.length > 0) {
        replacementHTML += `
            <h6 class="mt-3 mb-3 text-center">
                <i class="tio-checkmark-circle text-success"></i>
                Found ${replacements.length} Similar Item${replacements.length > 1 ? 's' : ''}
            </h6>
            <div class="replacement-items-grid">
        `;
        
        replacements.forEach(function(item) {
            let priceDiffHTML = '';
            if (item.price_difference !== 0) {
                let diffAmount = Math.abs(item.price_difference);
                let diffText = item.price_difference < 0 ?
                    `{{ \App\CentralLogics\Helpers::currency_symbol() }}${diffAmount.toFixed(2)} cheaper` :
                    `{{ \App\CentralLogics\Helpers::currency_symbol() }}${diffAmount.toFixed(2)} more`;
                let diffClass = item.price_difference < 0 ? 'cheaper' : 'expensive';
                
                priceDiffHTML = `<div class="price-difference ${diffClass}">
                    <i class="tio-${item.price_difference < 0 ? 'trending-down' : 'trending-up'}"></i>
                    ${diffText}
                </div>`;
            }
            
            let matchIndicators = '';
            if (item.category_match) {
                matchIndicators += '<span class="match-indicator category"><i class="tio-checkmark"></i> Same Category</span>';
            }
            if (Math.abs(item.price_difference) <= outOfStockItem.price * 0.1) {
                matchIndicators += '<span class="match-indicator price"><i class="tio-checkmark"></i> Similar Price</span>';
            }
            
            replacementHTML += `
                <div class="replacement-item-card">
                    <span class="similarity-badge">
                        ${item.similarity_score}% Match
                    </span>
                    <img src="${item.image_full_url}"
                         class="replacement-item-image"
                         onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'">
                    <div class="replacement-item-details">
                        <div class="replacement-item-name" title="${item.name}">
                            ${item.name}
                        </div>
                        ${matchIndicators ? `<div class="match-indicators">${matchIndicators}</div>` : ''}
                        <div class="replacement-price">
                            <i class="tio-money mr-1"></i>
                            {{ \App\CentralLogics\Helpers::currency_symbol() }}${item.price}
                            ${item.unit ? '<span style="font-size: 0.8rem; opacity: 0.9;"> / ' + (typeof item.unit === 'object' ? item.unit.unit : item.unit) + '</span>' : ''}
                        </div>
                        ${priceDiffHTML}
                        ${item.stock ? `<small class="text-success"><i class="tio-checkmark-circle"></i> ${item.stock} in stock</small>` : ''}
                        <button type="button"
                                class="add-replacement-btn mt-2"
                                data-product-id="${item.id}">
                            <i class="tio-add-circle-outlined mr-1"></i>
                            Add to Order
                        </button>
                    </div>
                </div>
            `;
        });
        
        replacementHTML += `</div>`;
    }
    
    replacementHTML += `
            <div class="text-center mt-3">
                <button type="button" class="btn btn-secondary mr-2" onclick="$('.replacement-suggestion-card').fadeOut(function() { $(this).remove(); })">
                    <i class="tio-close"></i> {{ translate('messages.close') }}
                </button>
                <button type="button" class="btn btn-success" onclick="$('.replacement-suggestion-card').fadeOut(function() { $(this).remove(); })">
                    <i class="tio-checkmark"></i> {{ translate('messages.keep_current_item') }}
                </button>
            </div>
        </div>
    `;
    
    // Insert at the top of the order items section
    $('.table-responsive').before(replacementHTML);
    
    // Smooth scroll to the replacement section
    $('html, body').animate({
        scrollTop: $('.replacement-suggestion-card').offset().top - 100
    }, 500);
    
    console.log('✅ Replacement suggestions displayed');
}

// ============================================
// END REPLACEMENT SYSTEM
// ============================================
        // Rest of existing JavaScript code...
        $('.quick-view-cart-item').on('click',function (){
            let key = $(this).data('key');
            $.get({
                url: '{{ route('admin.order.quick-view-cart-item') }}',
                dataType: 'json',
                data: {
                    key: key,
                    order_id: '{{ $order->id }}',
                },
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#quick-view').modal('show');
                    $('#quick-view-modal').empty().html(data.view);
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        })

        $('.quick-view').on('click',function (){
            let product_id = $(this).data('product-id');
            quickView(product_id);
        })

        function quickView(product_id) {
            $.get({
                url: '{{ route('admin.order.quick-view') }}',
                dataType: 'json',
                data: {
                    product_id: product_id,
                    order_id: '{{ $order->id }}',
                },
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    console.log("success...")
                    $('#quick-view').modal('show');
                    $('#quick-view-modal').empty().html(data.view);
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        }

        function cartQuantityInitialize() {
            $('.btn-number').click(function(e) {
                e.preventDefault();

                var fieldName = $(this).attr('data-field');
                var type = $(this).attr('data-type');
                var input = $("input[name='" + fieldName + "']");
                var currentVal = parseInt(input.val());

                if (!isNaN(currentVal)) {
                    if (type == 'minus') {

                        if (currentVal > input.attr('min')) {
                            input.val(currentVal - 1).change();
                        }
                        if (parseInt(input.val()) == input.attr('min')) {
                            $(this).attr('disabled', true);
                        }

                    } else if (type == 'plus') {

                        if (currentVal < input.attr('max')) {
                            input.val(currentVal + 1).change();
                        }
                        if (parseInt(input.val()) == input.attr('max')) {
                            $(this).attr('disabled', true);
                        }

                    }
                } else {
                    input.val(0);
                }
            });

            $('.input-number').focusin(function() {
                $(this).data('oldValue', $(this).val());
            });

            $('.input-number').change(function() {

                minValue = parseInt($(this).attr('min'));
                maxValue = parseInt($(this).attr('max'));
                valueCurrent = parseInt($(this).val());

                var name = $(this).attr('name');
                if (valueCurrent >= minValue) {
                    $(".btn-number[data-type='minus'][data-field='" + name + "']").removeAttr('disabled')
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cart',
                        text: 'Sorry, the minimum value was reached'
                    });
                    $(this).val($(this).data('oldValue'));
                }
                if (valueCurrent <= maxValue) {
                    $(".btn-number[data-type='plus'][data-field='" + name + "']").removeAttr('disabled')
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cart',
                        text: 'Sorry, stock limit exceeded.'
                    });
                    $(this).val($(this).data('oldValue'));
                }
            });
            $(".input-number").keydown(function(e) {
                // Allow: backspace, delete, tab, escape, enter and .
                if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
                    // Allow: Ctrl+A
                    (e.keyCode == 65 && e.ctrlKey === true) ||
                    // Allow: home, end, left, right
                    (e.keyCode >= 35 && e.keyCode <= 39)) {
                    // let it happen, don't do anything
                    return;
                }
                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
        }

        function getVariantPrice() {
            if ($('#add-to-cart-form input[name=quantity]').val() > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                });
                $.ajax({
                    type: "POST",
                    url: '{{ route('admin.item.variant-price') }}',
                    data: $('#add-to-cart-form').serializeArray(),
                    success: function(data) {
                        $('#add-to-cart-form #chosen_price_div').removeClass('d-none');
                        $('#add-to-cart-form #chosen_price_div #chosen_price').html(data.price);
                    }
                });
            }
        }

        $(document).on('click', '.update_order_item', function () {
            update_order_item();
        })

        function update_order_item(form_id = 'add-to-cart-form') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.order.add-to-cart') }}',
                data: $('#' + form_id).serializeArray(),
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    if (data.data == 1) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Cart',
                            text: "{{ translate('messages.product_already_added_in_cart') }}"
                        });
                        return false;
                    } else if (data.data == 0) {
                        toastr.success('{{ translate('messages.product_has_been_added_in_cart') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        location.reload();
                        return false;
                    } else if (data.data == 'variation_error') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cart',
                            text: data.message
                        });
                        return false;
                    }
                    $('.call-when-done').click();

                    toastr.success('{{ translate('messages.order_updated_successfully') }}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                    location.reload();
                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        }

        $(document).on('click', '.removeFromCart', function () {
            let key = $(this).data('key');
            removeFromCart(key);
        })

        function removeFromCart(key) {
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_remove_this_order_item') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.post('{{ route('admin.order.remove-from-cart') }}', {
                        _token: '{{ csrf_token() }}',
                        key: key,
                        order_id: '{{ $order->id }}'
                    }, function(data) {
                        if (data.errors) {
                            for (var i = 0; i < data.errors.length; i++) {
                                toastr.error(data.errors[i].message, {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            }
                        } else {
                            toastr.success(
                                '{{ translate('messages.item_has_been_removed_from_cart') }}', {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            location.reload();
                        }

                    });
                }
            })
        }

        $('.edit-order').on('click',function (){
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_edit_this_order') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = '{{ route('admin.order.edit-v2', $order->id) }}';
                }
            })
        })

        // REMOVED ORPHANED SWAL BLOCKS - These were causing JavaScript errors
 </script>

    <script>
    $(document).ready(function() {
    // Use 'assign-order-detail' class to handle dropdown item click
    $(document).on('click', '.assign-order-detail', function() {
        var orderId = $(this).data('order-id');
        var adminId = $(this).data('admin-id');
        var adminName = $(this).data('admin-name');

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            url: '{{ route('admin.order.assign') }}',
            method: 'POST',
            data: {
                order_id: orderId,
                admin_id: adminId
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);

                    // Optional: Refresh section or reload
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 403) {
                    var response = JSON.parse(xhr.responseText);
                    toastr.error(response.message);
                } else {
                    toastr.error('{{ translate("messages.something_went_wrong") }}');
                }
            }
        });
    });
    });
    </script>

    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&v=3.45.8">
    </script>
    <script>
        // INITIALIZATION OF SELECT2
        // =======================================================
        $('.js-select2-custom').each(function () {
            var select2 = $.HSCore.components.HSSelect2.init($(this));
        });

        $('.add-delivery-man').on('click',function (){
            id = $(this).data('id');
            $.ajax({
                type: "GET",
                url: '{{ url('/') }}/admin/order/add-delivery-man/{{ $order['id'] }}/' + id,
                success: function(data) {
                    location.reload();
                    console.log(data)
                    toastr.success('Successfully added', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                error: function(response) {
                    console.log(response);
                    toastr.error(response.responseJSON.message, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        })

        $('.order_status_change_alert').on('click', function (){
            let route = $(this).data('url');
            let message = $(this).data('message');
            let processing = $(this).data('processing');
            order_status_change_alert(route, message, processing);
        })

        function order_status_change_alert(route, message, processing = false) {
            if (processing) {
                Swal.fire({
                    //text: message,
                    title: '{{ translate('messages.Are you sure ?') }}',
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    cancelButtonText: '{{ translate('messages.Cancel') }}',
                    confirmButtonText: '{{ translate('messages.submit') }}',
                    inputPlaceholder: "{{ translate('Enter processing time') }}",
                    input: 'text',
                    html: message + '<br/>'+'<label>{{ translate('Enter Processing time in minutes') }}</label>',
                    inputValue: processing,
                    preConfirm: (processing_time) => {
                        location.href = route + '&processing_time=' + processing_time;
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                })
            } else {
                Swal.fire({
                    title: '{{ translate('messages.Are you sure ?') }}',
                    text: message,
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    cancelButtonText: '{{ translate('messages.No') }}',
                    confirmButtonText: '{{ translate('messages.Yes') }}',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        location.href = route;
                    }
                })
            }
        }


        function last_location_view() {
            toastr.warning('Only available when order is out for delivery!', {
                CloseButton: true,
                ProgressBar: true
            });
        }
        $(document).ready(function () {
            // Event handler for 'canceled-status' click
            $('.canceled-status').on('click', function () {
                // Assuming $reasons is properly populated and contains reasons

                // Create a select dropdown with options using map()
                var selectOptions = '';
                @foreach ($reasons as $r)
                    selectOptions += `<option value="{{ $r->reason }}">{{ $r->reason }}</option>`;
                @endforeach

                // Generate the Swal modal with the select dropdown
                Swal.fire({
                    title: '{{ translate('messages.are_you_sure') }}',
                    text: '{{ translate('messages.Change status to canceled ?') }}',
                    type: 'warning',
                    html: `<select class="form-control js-select2-custom mx-1" name="reason" id="reason">${selectOptions}</select>`,
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    cancelButtonText: '{{ translate('messages.no') }}',
                    confirmButtonText: '{{ translate('messages.yes') }}',
                    reverseButtons: true,
                    onOpen: function () {
                        // Initialize select2 after the modal is opened
                        $('.js-select2-custom').select2({
                            minimumResultsForSearch: 5,
                            width: '100%',
                            placeholder: "Select Reason",
                            language: "en",
                        });
                    }
                }).then((result) => {
                    if (result.value) {
                        // On confirmation, get the selected reason and redirect
                        var reason = $('#reason').val();
                        var orderID = '{{ $order['id'] }}';
                        var statusRoute = '{{ route('admin.order.status') }}';

                        // Redirect with order ID, status, and reason
                        var redirectURL = `${statusRoute}?id=${orderID}&order_status=canceled&reason=${reason}`;

                        // Redirect the user to the generated URL
                        window.location.href = redirectURL;
                    }
                });
            });
        });
    </script>
    <script>
        var deliveryMan = <?php echo json_encode($deliveryMen); ?>;
        var map = null;
        @if ($order->order_type == 'parcel')
        var myLatlng = new google.maps.LatLng({{ $address['latitude'] }}, {{ $address['longitude'] }});
        @else
        @php($default_location = App\CentralLogics\Helpers::get_business_settings('default_location'))
        var myLatlng = new google.maps.LatLng(
            {{ isset($order->store) ? $order->store->latitude : (isset($default_location) ? $default_location['lat'] : 0) }},
            {{ isset($order->store) ? $order->store->longitude : (isset($default_location['lng']) ? $default_location['lng'] : 0) }}
        );
        @endif
        var dmbounds = new google.maps.LatLngBounds(null);
        var locationbounds = new google.maps.LatLngBounds(null);
        var dmMarkers = [];
        dmbounds.extend(myLatlng);
        locationbounds.extend(myLatlng);
        var myOptions = {
            center: myLatlng,
            zoom: 13,
            mapTypeId: google.maps.MapTypeId.ROADMAP,

            panControl: true,
            mapTypeControl: false,
            panControlOptions: {
                position: google.maps.ControlPosition.RIGHT_CENTER
            },
            zoomControl: true,
            zoomControlOptions: {
                style: google.maps.ZoomControlStyle.LARGE,
                position: google.maps.ControlPosition.RIGHT_CENTER
            },
            scaleControl: false,
            streetViewControl: false,
            streetViewControlOptions: {
                position: google.maps.ControlPosition.RIGHT_CENTER
            }
        };

        function initializeGMap() {

            map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

            var infowindow = new google.maps.InfoWindow();
            @if ($order->store)
            var Restaurantmarker = new google.maps.Marker({
                @if ($parcel_order)
                position: new google.maps.LatLng({{ $address['latitude'] }},
                    {{ $address['longitude'] }}),
                title: "{{ Str::limit($order->customer->f_name . ' ' . $order->customer->l_name, 15, '...') }}",
                // icon: "{{ asset('public/assets/admin/img/restaurant_map.png') }}"
                @else
                position: new google.maps.LatLng({{ $order->store->latitude }},
                    {{ $order->store->longitude }}),
                title: "{{ Str::limit($order?->store?->name, 15, '...') }}",
                icon: "{{ asset('public/assets/admin/img/restaurant_map.png') }}",
                @endif
                map: map,

            });

            google.maps.event.addListener(Restaurantmarker, 'click', (function(Restaurantmarker) {
                return function() {
                    @if ($parcel_order)
                    infowindow.setContent(
                        "<div style='float:left'><img style='max-height:40px;wide:auto;' src='{{ $order?->customer?->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}'></div><div style='float:right; padding: 10px;'><b>{{ $order->customer->f_name }}{{ $order->customer->l_name }}</b><br />{{ $address['address'] }}</div>"
                    );
                    @else
                    infowindow.setContent(
                        "<div style='float:left'><img style='max-height:40px;wide:auto;' src='{{ $order?->store?->logo_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}'></div><div class='text-break' style='float:right; padding: 10px;'><b>{{ Str::limit($order?->store?->name, 15, '...') }}</b><br /> {{ $order->store->address }}</div>"
                    );
                    @endif
                    infowindow.open(map, Restaurantmarker);
                }
            })(Restaurantmarker));
            @endif

            @if(isset($address) && isset($address['latitude']) && isset($address['longitude']) && !$parcel_order)
            // Customer delivery location marker
            var customerLatLng = new google.maps.LatLng({{ $address['latitude'] }}, {{ $address['longitude'] }});
            dmbounds.extend(customerLatLng);

            var customerMarker = new google.maps.Marker({
                position: customerLatLng,
                map: map,
                title: "{{ translate('messages.delivery_location') }}",
                icon: "{{ asset('public/assets/admin/img/customer_location.png') }}"
            });

            google.maps.event.addListener(customerMarker, 'click', (function(customerMarker) {
                return function() {
                    infowindow.setContent(
                        "<div style='padding: 10px;'><b><i class='tio-poi text-danger mr-1'></i>{{ translate('messages.delivery_location') }}</b><br/><small>{{ $address['address'] ?? '' }}</small></div>"
                    );
                    infowindow.open(map, customerMarker);
                }
            })(customerMarker));
            @endif

            map.fitBounds(dmbounds);
            for (var i = 0; i < deliveryMan.length; i++) {
                if (deliveryMan[i].lat) {
                    // var contentString = "<div style='float:left'><img style='max-height:40px;wide:auto;' src='{{ asset('storage/app/public/delivery-man') }}/"+deliveryMan[i].image+"'></div><div style='float:right; padding: 10px;'><b>"+deliveryMan[i].name+"</b><br/> "+deliveryMan[i].location+"</div>";
                    var point = new google.maps.LatLng(deliveryMan[i].lat, deliveryMan[i].lng);
                    dmbounds.extend(point);
                    map.fitBounds(dmbounds);
                    var marker = new google.maps.Marker({
                        position: point,
                        map: map,
                        title: deliveryMan[i].location,
                        icon: "{{ asset('public/assets/admin/img/delivery_boy_map.png') }}"
                    });
                    dmMarkers[deliveryMan[i].id] = marker;
                    google.maps.event.addListener(marker, 'click', (function(marker, i) {
                        return function() {
                            var ratingHtml = deliveryMan[i].avg_rating > 0 ? '<span style="color: #ffc107;">★ ' + deliveryMan[i].avg_rating + '</span>' : '';
                            var ordersHtml = '<small style="color: #17a2b8;">{{ translate('messages.active') }}: ' + deliveryMan[i].current_orders + '</small>';
                            var deliveredHtml = '<small style="color: #28a745;"> | {{ translate('messages.delivered') }}: ' + deliveryMan[i].total_delivered_orders + '</small>';
                            infowindow.setContent(
                                "<div style='display:flex; align-items:flex-start; min-width: 200px;'>" +
                                "<img style='width:50px; height:50px; border-radius:50%; object-fit:cover; margin-right:10px;' src='"+ deliveryMan[i].image_link +"'>" +
                                "<div style='flex:1;'>" +
                                "<b>" + deliveryMan[i].name + "</b> " + ratingHtml + "<br/>" +
                                (deliveryMan[i].phone ? "<small style='color:#666;'><i class='tio-call-talking'></i> " + deliveryMan[i].phone + "</small><br/>" : "") +
                                ordersHtml + deliveredHtml + "<br/>" +
                                (deliveryMan[i].vehicle_type ? "<small style='color:#666;'><i class='tio-car'></i> " + deliveryMan[i].vehicle_type + "</small><br/>" : "") +
                                "<small style='color:#999;'>" + (deliveryMan[i].location || '{{ translate('messages.location_not_available') }}') + "</small>" +
                                "</div></div>");
                            infowindow.open(map, marker);
                        }
                    })(marker, i));
                }

            };
        }

        function initMap() {
            let map = new google.maps.Map(document.getElementById("map"), {
                zoom: 13,
                center: {
                    lat: {{ isset($order->store) ? $order->store->latitude : '23.757989' }},
                    lng: {{ isset($order->store) ? $order->store->longitude : '90.360587' }}
                }
            });

            let zonePolygon = null;

            //get current location block
            let infoWindow = new google.maps.InfoWindow();
            // Try HTML5 geolocation.
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        myLatlng = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };
                        infoWindow.setPosition(myLatlng);
                        infoWindow.setContent("Location found.");
                        infoWindow.open(map);
                        map.setCenter(myLatlng);
                    },
                    () => {
                        handleLocationError(true, infoWindow, map.getCenter());
                    }
                );
            } else {
                // Browser doesn't support Geolocation
                handleLocationError(false, infoWindow, map.getCenter());
            }
            //-----end block------
            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
            let markers = [];
            const bounds = new google.maps.LatLngBounds();
            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();

                if (places.length == 0) {
                    return;
                }
                // Clear out the old markers.
                markers.forEach((marker) => {
                    marker.setMap(null);
                });
                markers = [];
                // For each place, get the icon, name and location.
                places.forEach((place) => {
                    if (!place.geometry || !place.geometry.location) {
                        console.log("Returned place contains no geometry");
                        return;
                    }
                    console.log(place.geometry.location);
                    if (!google.maps.geometry.poly.containsLocation(
                        place.geometry.location,
                        zonePolygon
                    )) {
                        toastr.error('{{ translate('messages.out_of_coverage') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        return false;
                    }

                    document.getElementById('latitude').value = place.geometry.location.lat();
                    document.getElementById('longitude').value = place.geometry.location.lng();

                    const icon = {
                        url: place.icon,
                        size: new google.maps.Size(71, 71),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25),
                    };
                    // Create a marker for each place.
                    markers.push(
                        new google.maps.Marker({
                            map,
                            icon,
                            title: place.name,
                            position: place.geometry.location,
                        })
                    );

                    if (place.geometry.viewport) {
                        // Only geocodes have viewport.
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });
            @if ($order->store)
            $.get({
                url: '{{ url('/') }}/admin/zone/get-coordinates/{{ $order->store->zone_id }}',
                dataType: 'json',
                success: function(data) {
                    zonePolygon = new google.maps.Polygon({
                        paths: data.coordinates,
                        strokeColor: "#FF0000",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: 'white',
                        fillOpacity: 0,
                    });
                    zonePolygon.setMap(map);
                    zonePolygon.getPaths().forEach(function(path) {
                        path.forEach(function(latlng) {
                            bounds.extend(latlng);
                            map.fitBounds(bounds);
                        });
                    });
                    map.setCenter(data.center);
                    google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                        infoWindow.close();
                        // Create a new InfoWindow.
                        infoWindow = new google.maps.InfoWindow({
                            position: mapsMouseEvent.latLng,
                            content: JSON.stringify(mapsMouseEvent.latLng.toJSON(), null,
                                2),
                        });
                        var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                        var coordinates = JSON.parse(coordinates);

                        document.getElementById('latitude').value = coordinates['lat'];
                        document.getElementById('longitude').value = coordinates['lng'];
                        infoWindow.open(map);
                    });
                },
            });
            @endif

        }

        $(document).ready(function() {

            // Re-init map before show modal
            $('#myModal').on('shown.bs.modal', function(event) {
                initMap();
                var button = $(event.relatedTarget);
                $("#dmassign-map").css("width", "100%");
                $("#map_canvas").css("width", "100%");
            });

            // Trigger map resize event after modal shown
            $('#myModal').on('shown.bs.modal', function() {
                initializeGMap();
                google.maps.event.trigger(map, "resize");
                map.setCenter(myLatlng);
            });

            // Address change modal modal shown
            $('#shipping-address-modal').on('shown.bs.modal', function() {
                initMap();
                // google.maps.event.trigger(map, "resize");
                // map.setCenter(myLatlng);
            });


function initializegLocationMap() {
    // Reset bounds
    locationbounds = new google.maps.LatLngBounds(null);
    
    // Initialize map
    map = new google.maps.Map(document.getElementById("location_map_canvas"), myOptions);

    var infowindow = new google.maps.InfoWindow();
    
    // Store coordinates for distance calculation
    var locations = {
        customer: null,
        deliveryMan: null,
        store: null,
        receiver: null
    };

    @if ($order->customer && isset($address))
    var customerLatLng = new google.maps.LatLng({{ $address['latitude'] }}, {{ $address['longitude'] }});
    locations.customer = customerLatLng;
    
    var marker = new google.maps.Marker({
        position: customerLatLng,
        map: map,
        title: "{{ $order->customer->f_name }} {{ $order->customer->l_name }}",
        icon: "http://maps.google.com/mapfiles/ms/icons/green-dot.png"
    });

    google.maps.event.addListener(marker, 'click', (function(marker) {
        return function() {
            infowindow.setContent(
                "<div style='float:left'><img style='max-height:40px;width:auto;' src='{{ $order?->customer?->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}'></div><div style='float:right; padding: 10px;'><b>{{ $order->customer->f_name }} {{ $order->customer->l_name }}</b><br />{{ $address['address'] }}</div>"
            );
            infowindow.open(map, marker);
        }
    })(marker));
    locationbounds.extend(marker.getPosition());
    @endif

    @if ($order->delivery_man && $order->dm_last_location)
    var dmLatLng = new google.maps.LatLng({{ $order->dm_last_location['latitude'] }}, {{ $order->dm_last_location['longitude'] }});
    locations.deliveryMan = dmLatLng;

    // Make marker global so it can be updated via WebSocket
    window.dmMarkerOnMap = new google.maps.Marker({
        position: dmLatLng,
        map: map,
        title: "{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}",
        icon: "http://maps.google.com/mapfiles/ms/icons/yellow-dot.png"
    });
    var dmmarker = window.dmMarkerOnMap;

    google.maps.event.addListener(dmmarker, 'click', (function(dmmarker) {
        return function() {
            infowindow.setContent(
                "<div style='float:left'><img style='max-height:40px;width:auto;' src='{{ $order?->delivery_man?->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}'></div> <div style='float:right; padding: 10px;'><b>{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}</b><br /> {{ $order->dm_last_location['location'] }}</div>"
            );
            infowindow.open(map, dmmarker);
        }
    })(dmmarker));
    locationbounds.extend(dmmarker.getPosition());
    @endif

    @if ($order->store && !$parcel_order)
    var storeLatLng = new google.maps.LatLng({{ $order->store->latitude }}, {{ $order->store->longitude }});
    locations.store = storeLatLng;
    
    var Retaurantmarker = new google.maps.Marker({
        position: storeLatLng,
        map: map,
        title: "{{ Str::limit($order?->store?->name, 15, '...') }}",
        icon: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
    });

    google.maps.event.addListener(Retaurantmarker, 'click', (function(Retaurantmarker) {
        return function() {
            infowindow.setContent(
                "<div style='float:left'><img style='max-height:40px;width:auto;' src='{{ $order?->store?->logo_full_url ?? asset('public/assets/admin/img/100x100/1.png') }}'></div> <div style='float:right; padding: 10px;'><b>{{ Str::limit($order?->store?->name, 15, '...') }}</b><br /> {{ $order->store->address }}</div>"
            );
            infowindow.open(map, Retaurantmarker);
        }
    })(Retaurantmarker));
    locationbounds.extend(Retaurantmarker.getPosition());
    @endif

    @if ($parcel_order && isset($receiver_details))
    var receiverLatLng = new google.maps.LatLng({{ $receiver_details['latitude'] }}, {{ $receiver_details['longitude'] }});
    locations.receiver = receiverLatLng;
    
    var Receivermarker = new google.maps.Marker({
        position: receiverLatLng,
        map: map,
        title: "{{ Str::limit($receiver_details['contact_person_name'], 15, '...') }}",
        icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
    });

    google.maps.event.addListener(Receivermarker, 'click', (function(Receivermarker) {
        return function() {
            infowindow.setContent(
                "<div style='float:right; padding: 10px;'><b>{{ Str::limit($receiver_details['contact_person_name'], 15, '...') }}</b><br />{{ $receiver_details['address'] }}</div>"
            );
            infowindow.open(map, Receivermarker);
        }
    })(Receivermarker));
    locationbounds.extend(Receivermarker.getPosition());
    @endif

    // Fit map to bounds and calculate distances
    map.fitBounds(locationbounds);
    
    // Add a small delay to ensure map is rendered
    setTimeout(function() {
        calculateAndDisplayDistances(locations);
    }, 500);
}

// Haversine formula to calculate distance
function calculateDistance(lat1, lng1, lat2, lng2) {
    var R = 6371; // Radius of Earth in kilometers
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLng = (lng2 - lng1) * Math.PI / 180;
    var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLng/2) * Math.sin(dLng/2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    var distance = R * c;
    return distance.toFixed(2);
}

// Calculate and display all distances with proximity detection
function calculateAndDisplayDistances(locations) {
    console.log('📊 Calculating distances...', locations);
    
    var distancesHTML = '';
    var totalDistance = 0;
    
    // Check if we have valid locations
    if (!locations.customer && !locations.deliveryMan && !locations.store && !locations.receiver) {
        $('#distanceCardsContainer').html(`
            <div class="col-12 text-center py-3">
                <p class="text-muted">{{ translate('messages.no_location_data') }}</p>
            </div>
        `);
        return;
    }
    
    // Check if delivery man location exists
    var trackingData = $('#deliveryTrackingData');
    var dmUpdated = trackingData.data('dm-updated');
    
    // Calculate time since last update
    if (dmUpdated && locations.deliveryMan) {
        checkDeliveryManStatus(dmUpdated);
    }
    
    // Customer to Store
    if (locations.customer && locations.store) {
        var custToStore = calculateDistance(
            locations.customer.lat(), locations.customer.lng(),
            locations.store.lat(), locations.store.lng()
        );
        totalDistance += parseFloat(custToStore);
        distancesHTML += `
            <div class="col-md-3 mb-2">
                <div class="card bg-soft-primary">
                    <div class="card-body text-center p-3">
                        <i class="tio-arrow-large-forward text-primary mb-2" style="font-size: 2rem;"></i>
                        <h4 class="text-primary mb-1">${custToStore} km</h4>
                        <small class="text-muted">{{ translate('messages.customer') }} ↔ {{ translate('messages.store') }}</small>
                        <div class="mt-2">
                            <small class="badge badge-soft-primary">~${Math.ceil(custToStore * 3)} min</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Delivery Man to Customer - WITH PROXIMITY DETECTION
    if (locations.deliveryMan && locations.customer) {
        var dmToCust = calculateDistance(
            locations.deliveryMan.lat(), locations.deliveryMan.lng(),
            locations.customer.lat(), locations.customer.lng()
        );
        totalDistance += parseFloat(dmToCust);
        
        // Convert km to meters
        var distanceInMeters = dmToCust * 1000;
        
        // Check if delivery man is within 500m of customer
        var proximityBadge = '';
        var cardClass = 'bg-soft-success';
        var iconClass = 'tio-fastfood';
        
        if (distanceInMeters <= 500) {
            proximityBadge = `
                <div class="badge badge-success badge-pill px-3 py-2 mb-2 pulse-animation">
                    <i class="tio-checkmark-circle"></i> Reached!
                </div>
            `;
            cardClass = 'bg-success text-white';
            iconClass = 'tio-done-vs';
            
            // Update delivery status badge
            $('#deliveryStatusBadge').html(`
                <span class="badge badge-success badge-pill px-3 py-1 pulse-animation">
                    <i class="tio-poi"></i> Nearby (${distanceInMeters.toFixed(0)}m)
                </span>
            `);
            
            // Show notification
            showProximityNotification();
        } else {
            $('#deliveryStatusBadge').html(`
                <span class="badge badge-soft-info badge-pill px-3 py-1">
                    <i class="tio-directions"></i> ${dmToCust} km away
                </span>
            `);
        }
        
        distancesHTML += `
            <div class="col-md-3 mb-2">
                <div class="card ${cardClass}">
                    <div class="card-body text-center p-3">
                        ${proximityBadge}
                        <i class="${iconClass} ${cardClass.includes('text-white') ? 'text-white' : 'text-success'} mb-2" style="font-size: 2rem;"></i>
                        <h4 class="${cardClass.includes('text-white') ? 'text-white' : 'text-success'} mb-1">${dmToCust} km</h4>
                        <small class="${cardClass.includes('text-white') ? 'text-white-70' : 'text-muted'}">
                            Delivery ↔ Customer
                        </small>
                        <div class="mt-2">
                            <small class="badge ${cardClass.includes('text-white') ? 'badge-light' : 'badge-soft-success'}">
                                ${distanceInMeters <= 500 ? `${distanceInMeters.toFixed(0)}m` : `~${Math.ceil(dmToCust * 3)} min`}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Delivery Man to Store
    if (locations.deliveryMan && locations.store) {
        var dmToStore = calculateDistance(
            locations.deliveryMan.lat(), locations.deliveryMan.lng(),
            locations.store.lat(), locations.store.lng()
        );
        distancesHTML += `
            <div class="col-md-3 mb-2">
                <div class="card bg-soft-warning">
                    <div class="card-body text-center p-3">
                        <i class="tio-directions text-warning mb-2" style="font-size: 2rem;"></i>
                        <h4 class="text-warning mb-1">${dmToStore} km</h4>
                        <small class="text-muted">Delivery ↔ Store</small>
                        <div class="mt-2">
                            <small class="badge badge-soft-warning">~${Math.ceil(dmToStore * 3)} min</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Total Distance
    if (totalDistance > 0) {
        distancesHTML += `
            <div class="col-md-3 mb-2">
                <div class="card bg-soft-dark">
                    <div class="card-body text-center p-3">
                        <i class="tio-route text-dark mb-2" style="font-size: 2rem;"></i>
                        <h4 class="text-dark mb-1">${totalDistance.toFixed(2)} km</h4>
                        <small class="text-muted">Total Distance</small>
                        <div class="mt-2">
                            <small class="badge badge-soft-dark">~${Math.ceil(totalDistance * 3)} min</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Insert distances
    console.log('✅ Distances calculated and displayed');
    $('#distanceCardsContainer').html(distancesHTML);
}

// Check if delivery man is idle/stopped
function checkDeliveryManStatus(lastUpdateTime) {
    if (!lastUpdateTime) return;
    
    var lastUpdate = new Date(lastUpdateTime);
    var now = new Date();
    var diffMinutes = Math.floor((now - lastUpdate) / 1000 / 60);
    
    var timeAgoText = '';
    if (diffMinutes < 1) {
        timeAgoText = 'Just now';
    } else if (diffMinutes < 60) {
        timeAgoText = diffMinutes + ' min ago';
    } else {
        var hours = Math.floor(diffMinutes / 60);
        timeAgoText = hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
    }
    $('#timeAgo').text(timeAgoText);
    
    if (diffMinutes >= 5) {
        if (diffMinutes >= 10) {
            $('#dmStatusBadge').removeClass('avatar-status-success avatar-status-warning')
                              .addClass('avatar-status-danger')
                              .html('<i class="tio-clear"></i>');
            
            $('#deliveryStatusBadge').prepend(
                '<span class="badge badge-danger badge-pill px-3 py-1 mb-2 blink-animation">' +
                    '<i class="tio-time"></i> Stopped' +
                '</span>'
            );
            
            $('#lastUpdateTime').addClass('text-danger font-weight-bold');
            
        } else {
            $('#dmStatusBadge').removeClass('avatar-status-success avatar-status-danger')
                              .addClass('avatar-status-warning')
                              .html('<i class="tio-time"></i>');
            
            $('#deliveryStatusBadge').prepend(
                '<span class="badge badge-warning badge-pill px-3 py-1 mb-2">' +
                    '<i class="tio-pause"></i> Idle' +
                '</span>'
            );
            
            $('#lastUpdateTime').addClass('text-warning font-weight-bold');
        }
    } else {
        $('#dmStatusBadge').removeClass('avatar-status-warning avatar-status-danger')
                          .addClass('avatar-status-success')
                          .html('<i class="tio-checkmark"></i>');
        
        $('#lastUpdateTime').removeClass('text-danger text-warning font-weight-bold');
    }
}

// Show proximity notification
var proximityNotificationShown = false;
function showProximityNotification() {
    if (proximityNotificationShown) return;
    
    proximityNotificationShown = true;
    
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('🎯 Delivery Reached!', {
            body: 'Delivery man is nearby customer location',
            icon: '{{ asset("public/assets/admin/img/delivery_boy_map.png") }}',
            tag: 'delivery-reached-{{ $order->id }}',
            requireInteraction: true
        });
    }
    
    toastr.success('Delivery man has reached customer location!', 'Delivery Reached', {
        CloseButton: true,
        ProgressBar: true,
        timeOut: 10000,
        positionClass: 'toast-top-right'
    });
    
    playDeliveryReachedSound();
}

function playDeliveryReachedSound() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const audioContext = new AudioContext();
        
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        gainNode.gain.value = 0.3;
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.2);
        
        setTimeout(() => {
            const osc2 = audioContext.createOscillator();
            const gain2 = audioContext.createGain();
            osc2.connect(gain2);
            gain2.connect(audioContext.destination);
            osc2.frequency.value = 1000;
            gain2.gain.value = 0.3;
            osc2.start(audioContext.currentTime);
            osc2.stop(audioContext.currentTime + 0.2);
        }, 250);
        
    } catch (error) {
        console.log('Audio not supported');
    }
}

// Global variables for route and enhanced map features
var routePolyline = null;
var routeVisible = true;

// Draw route polyline on map
function drawRoutePolyline() {
    if (!map || !routeVisible) return;

    // Remove existing polyline
    if (routePolyline) {
        routePolyline.setMap(null);
    }

    var trackingData = $('#deliveryTrackingData');
    if (!trackingData.length) return;

    var path = [];

    // Store → DM → Customer route
    var storeLat = parseFloat(trackingData.data('store-lat'));
    var storeLng = parseFloat(trackingData.data('store-lng'));
    var dmLat = parseFloat(trackingData.data('dm-lat'));
    var dmLng = parseFloat(trackingData.data('dm-lng'));
    var custLat = parseFloat(trackingData.data('customer-lat'));
    var custLng = parseFloat(trackingData.data('customer-lng'));

    if (storeLat && storeLng) path.push({lat: storeLat, lng: storeLng});
    if (dmLat && dmLng) path.push({lat: dmLat, lng: dmLng});
    if (custLat && custLng) path.push({lat: custLat, lng: custLng});

    if (path.length >= 2) {
        routePolyline = new google.maps.Polyline({
            path: path,
            geodesic: true,
            strokeColor: '#3b82f6',
            strokeOpacity: 0.8,
            strokeWeight: 3,
            map: map
        });
    }
}

// Update ETA banner
function updateETABanner() {
    var trackingData = $('#deliveryTrackingData');
    if (!trackingData.length) return;

    var dmLat = parseFloat(trackingData.data('dm-lat'));
    var dmLng = parseFloat(trackingData.data('dm-lng'));
    var custLat = parseFloat(trackingData.data('customer-lat'));
    var custLng = parseFloat(trackingData.data('customer-lng'));

    if (!dmLat || !custLat) return;

    var distance = calculateDistance(dmLat, dmLng, custLat, custLng);
    var distanceKm = parseFloat(distance);
    var distanceM = distanceKm * 1000;

    // Calculate ETA (assuming 20 km/h average speed)
    var etaMinutes = Math.ceil((distanceKm / 20) * 60);

    var banner = $('#etaBanner');
    var text = $('#etaBannerText');
    var subtext = $('#etaBannerSubtext');
    var minutes = $('#etaMinutes');

    banner.show();
    minutes.text(etaMinutes);

    // Update banner color based on proximity
    banner.removeClass('warning danger');
    if (distanceM <= 500) {
        banner.addClass('danger');
        text.html('<i class="tio-checkmark-circle"></i> Delivery Man Nearby!');
        subtext.text('Expected arrival within 1-2 minutes');
    } else if (distanceM <= 2000) {
        banner.addClass('warning');
        text.html('<i class="tio-time"></i> Approaching Delivery Location');
        subtext.text('Distance: ' + distanceM.toFixed(0) + 'm');
    } else {
        text.html('<i class="tio-poi"></i> En Route to Customer');
        subtext.text('Distance: ' + distanceKm.toFixed(1) + ' km');
    }
}

// Update DM stats in the modal
function updateModalDMStats() {
    var trackingData = $('#deliveryTrackingData');
    if (!trackingData.length) return;

    var dmLat = parseFloat(trackingData.data('dm-lat'));
    var dmLng = parseFloat(trackingData.data('dm-lng'));
    var custLat = parseFloat(trackingData.data('customer-lat'));
    var custLng = parseFloat(trackingData.data('customer-lng'));

    if (dmLat && custLat) {
        var distance = calculateDistance(dmLat, dmLng, custLat, custLng);
        $('#dmDistanceToCustomer').text(distance + ' km');
    }

    // Speed would come from WebSocket updates
    $('#dmSpeed').text('-- km/h');
}

// Center map on delivery man
$(document).on('click', '#centerOnDM', function() {
    if (window.dmMarkerOnMap && map) {
        map.panTo(window.dmMarkerOnMap.getPosition());
        map.setZoom(16);
        window.dmMarkerOnMap.setAnimation(google.maps.Animation.BOUNCE);
        setTimeout(() => window.dmMarkerOnMap.setAnimation(null), 1500);
    }
});

// Toggle route visibility
$(document).on('click', '#toggleRoute', function() {
    routeVisible = !routeVisible;
    $(this).toggleClass('active', routeVisible);

    if (routeVisible) {
        drawRoutePolyline();
    } else if (routePolyline) {
        routePolyline.setMap(null);
    }
});

// Update modal last seen time
function updateModalTime() {
    var trackingData = $('#deliveryTrackingData');
    if (!trackingData.length) return;

    var updatedAt = trackingData.data('dm-updated');
    if (!updatedAt) return;

    var now = new Date();
    var updated = new Date(updatedAt);
    var diffSec = Math.floor((now - updated) / 1000);

    var timeStr = '';
    if (diffSec < 60) {
        timeStr = '{{ translate("messages.just_now") }}';
    } else if (diffSec < 3600) {
        var mins = Math.floor(diffSec / 60);
        timeStr = mins + ' min' + (mins > 1 ? 's' : '') + ' ago';
    } else if (diffSec < 86400) {
        var hrs = Math.floor(diffSec / 3600);
        timeStr = hrs + ' hour' + (hrs > 1 ? 's' : '') + ' ago';
    } else {
        var days = Math.floor(diffSec / 86400);
        timeStr = days + ' day' + (days > 1 ? 's' : '') + ' ago';
    }

    $('#modalTimeAgo').text(timeStr);
    $('#timeAgo').text(timeStr);
}

// Refresh button
$(document).on('click', '#refreshLocationMap', function() {
    var btn = $(this);
    btn.prop('disabled', true);
    btn.html('<i class="tio-refresh spinner-border spinner-border-sm"></i>');

    proximityNotificationShown = false;

    $('#distanceCardsContainer').html(`
        <div class="col-12 text-center py-3">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2">Refreshing...</p>
        </div>
    `);

    initializegLocationMap();
    drawRoutePolyline();
    updateETABanner();
    updateModalDMStats();
    updateModalTime();

    setTimeout(() => {
        btn.prop('disabled', false);
        btn.html('<i class="tio-refresh"></i>');
        toastr.success('Location updated');
    }, 1500);
});

// Auto-refresh
var locationRefreshInterval;

$('#locationModal').on('shown.bs.modal', function() {
    console.log('🗺️ Enhanced location modal opened');
    initializegLocationMap();

    // Add route visualization and enhancements
    setTimeout(() => {
        drawRoutePolyline();
        updateETABanner();
        updateModalDMStats();
        updateModalTime();
    }, 1000);

    // Update time display every second
    setInterval(updateModalTime, 1000);

    // Auto-refresh location every 30 seconds
    locationRefreshInterval = setInterval(function() {
        console.log('🔄 Auto-refreshing location...');
        proximityNotificationShown = false;
        initializegLocationMap();

        setTimeout(() => {
            drawRoutePolyline();
            updateETABanner();
            updateModalDMStats();
        }, 500);
    }, 30000);
});

$('#locationModal').on('hidden.bs.modal', function() {
    if (locationRefreshInterval) {
        clearInterval(locationRefreshInterval);
    }
});
            // Haversine formula to calculate distance
            function calculateDistance(lat1, lng1, lat2, lng2) {
                var R = 6371; // Radius of Earth in kilometers
                var dLat = (lat2 - lat1) * Math.PI / 180;
                var dLng = (lng2 - lng1) * Math.PI / 180;
                var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                        Math.sin(dLng/2) * Math.sin(dLng/2);
                var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                var distance = R * c;
                return distance.toFixed(2);
            }

            // Calculate and display all distances with proximity detection
// Calculate and display all distances with proximity detection
function calculateAndDisplayDistances(locations) {
    console.log('📊 Calculating distances...', locations);
    
    var distancesHTML = '';
    var totalDistance = 0;
    
    // Check if we have valid locations
    if (!locations.customer && !locations.deliveryMan && !locations.store && !locations.receiver) {
        $('#distanceCardsContainer').html(`
            <div class="col-12 text-center py-3">
                <p class="text-muted">{{ translate('messages.no_location_data') }}</p>
            </div>
        `);
        return;
    }
    
    // Check if delivery man location exists
    var trackingData = $('#deliveryTrackingData');
    var dmUpdated = trackingData.data('dm-updated');
    
    // Calculate time since last update
    if (dmUpdated && locations.deliveryMan) {
        checkDeliveryManStatus(dmUpdated);
    }
    
    // Variables to track proximity status
    var isNearStore = false;
    var isNearCustomer = false;
    var storeDistanceInMeters = 0;
    var customerDistanceInMeters = 0;
    
    // Check Delivery Man to Store distance
    var dmToStore = 0;
    if (locations.deliveryMan && locations.store) {
        dmToStore = calculateDistance(
            locations.deliveryMan.lat(), locations.deliveryMan.lng(),
            locations.store.lat(), locations.store.lng()
        );
        storeDistanceInMeters = dmToStore * 1000;
        
        // Check if within 400m of store
        if (storeDistanceInMeters <= 400) {
            isNearStore = true;
        }
    }
    
    // Check Delivery Man to Customer distance
    var dmToCust = 0;
    if (locations.deliveryMan && locations.customer) {
        dmToCust = calculateDistance(
            locations.deliveryMan.lat(), locations.deliveryMan.lng(),
            locations.customer.lat(), locations.customer.lng()
        );
        customerDistanceInMeters = dmToCust * 1000;
        
        // Check if within 500m of customer
        if (customerDistanceInMeters <= 500) {
            isNearCustomer = true;
        }
    }
    
    // Update Delivery Man Status Badge based on proximity
    updateDeliveryManStatusBadge(isNearStore, isNearCustomer, storeDistanceInMeters, customerDistanceInMeters);
    
    // Customer to Store
    if (locations.customer && locations.store) {
        var custToStore = calculateDistance(
            locations.customer.lat(), locations.customer.lng(),
            locations.store.lat(), locations.store.lng()
        );
        totalDistance += parseFloat(custToStore);
        distancesHTML += `
            <div class="col-md-3 mb-2">
                <div class="card bg-soft-primary">
                    <div class="card-body text-center p-3">
                        <i class="tio-arrow-large-forward text-primary mb-2" style="font-size: 2rem;"></i>
                        <h4 class="text-primary mb-1">${custToStore} km</h4>
                        <small class="text-muted">Customer ↔ Store</small>
                        <div class="mt-2">
                            <small class="badge badge-soft-primary">~${Math.ceil(custToStore * 3)} min</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Delivery Man to Customer - WITH PROXIMITY DETECTION
    if (locations.deliveryMan && locations.customer) {
        totalDistance += parseFloat(dmToCust);
        
        var proximityBadge = '';
        var cardClass = 'bg-soft-success';
        var iconClass = 'tio-fastfood';
        
        if (isNearCustomer) {
            proximityBadge = `
                <div class="badge badge-success badge-pill px-3 py-2 mb-2 pulse-animation">
                    <i class="tio-checkmark-circle"></i> At Customer!
                </div>
            `;
            cardClass = 'bg-success text-white';
            iconClass = 'tio-done-vs';
            
            // Show notification for customer proximity
            showProximityNotification('customer');
        }
        
        distancesHTML += `
            <div class="col-md-3 mb-2">
                <div class="card ${cardClass}">
                    <div class="card-body text-center p-3">
                        ${proximityBadge}
                        <i class="${iconClass} ${cardClass.includes('text-white') ? 'text-white' : 'text-success'} mb-2" style="font-size: 2rem;"></i>
                        <h4 class="${cardClass.includes('text-white') ? 'text-white' : 'text-success'} mb-1">${dmToCust} km</h4>
                        <small class="${cardClass.includes('text-white') ? 'text-white-70' : 'text-muted'}">
                            Delivery ↔ Customer
                        </small>
                        <div class="mt-2">
                            <small class="badge ${cardClass.includes('text-white') ? 'badge-light' : 'badge-soft-success'}">
                                ${isNearCustomer ? `${customerDistanceInMeters.toFixed(0)}m` : `~${Math.ceil(dmToCust * 3)} min`}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Delivery Man to Store - WITH PROXIMITY DETECTION
    if (locations.deliveryMan && locations.store) {
        var storeProximityBadge = '';
        var storeCardClass = 'bg-soft-warning';
        var storeIconClass = 'tio-directions';
        
        if (isNearStore) {
            storeProximityBadge = `
                <div class="badge badge-warning badge-pill px-3 py-2 mb-2 pulse-animation">
                    <i class="tio-checkmark-circle"></i> At Store!
                </div>
            `;
            storeCardClass = 'bg-warning text-white';
            storeIconClass = 'tio-done-vs';
            
            // Show notification for store proximity
            showProximityNotification('store');
        }
        
        distancesHTML += `
            <div class="col-md-3 mb-2">
                <div class="card ${storeCardClass}">
                    <div class="card-body text-center p-3">
                        ${storeProximityBadge}
                        <i class="${storeIconClass} ${storeCardClass.includes('text-white') ? 'text-white' : 'text-warning'} mb-2" style="font-size: 2rem;"></i>
                        <h4 class="${storeCardClass.includes('text-white') ? 'text-white' : 'text-warning'} mb-1">${dmToStore} km</h4>
                        <small class="${storeCardClass.includes('text-white') ? 'text-white-70' : 'text-muted'}">
                            Delivery ↔ Store
                        </small>
                        <div class="mt-2">
                            <small class="badge ${storeCardClass.includes('text-white') ? 'badge-light' : 'badge-soft-warning'}">
                                ${isNearStore ? `${storeDistanceInMeters.toFixed(0)}m` : `~${Math.ceil(dmToStore * 3)} min`}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Total Distance
    if (totalDistance > 0) {
        distancesHTML += `
            <div class="col-md-3 mb-2">
                <div class="card bg-soft-dark">
                    <div class="card-body text-center p-3">
                        <i class="tio-route text-dark mb-2" style="font-size: 2rem;"></i>
                        <h4 class="text-dark mb-1">${totalDistance.toFixed(2)} km</h4>
                        <small class="text-muted">Total Distance</small>
                        <div class="mt-2">
                            <small class="badge badge-soft-dark">~${Math.ceil(totalDistance * 3)} min</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Insert distances
    console.log('✅ Distances calculated and displayed');
    $('#distanceCardsContainer').html(distancesHTML);
}

// NEW FUNCTION: Update Delivery Man Status Badge
function updateDeliveryManStatusBadge(isNearStore, isNearCustomer, storeDistanceInMeters, customerDistanceInMeters) {
    var statusBadgeHTML = '';
    
    if (isNearCustomer) {
        // Priority 1: At Customer (within 500m)
        statusBadgeHTML = `
            <span class="badge badge-success badge-pill px-3 py-1 pulse-animation">
                <i class="tio-user"></i> At Customer (${customerDistanceInMeters.toFixed(0)}m)
            </span>
        `;
    } else if (isNearStore) {
        // Priority 2: At Store (within 400m)
        statusBadgeHTML = `
            <span class="badge badge-warning badge-pill px-3 py-1 pulse-animation">
                <i class="tio-shop"></i> At Store (${storeDistanceInMeters.toFixed(0)}m)
            </span>
        `;
    } else {
        // Default: Show distance to customer
        if (customerDistanceInMeters > 0) {
            statusBadgeHTML = `
                <span class="badge badge-soft-info badge-pill px-3 py-1">
                    <i class="tio-directions"></i> ${(customerDistanceInMeters / 1000).toFixed(2)} km away
                </span>
            `;
        }
    }
    
    $('#deliveryStatusBadge').html(statusBadgeHTML);
}

// Updated proximity notification with location type
var proximityNotificationShown = {
    store: false,
    customer: false
};

function showProximityNotification(locationType) {
    if (proximityNotificationShown[locationType]) return;
    
    proximityNotificationShown[locationType] = true;
    
    var title = '';
    var body = '';
    var icon = '';
    
    if (locationType === 'customer') {
        title = '🎯 At Customer Location!';
        body = 'Delivery man has reached customer location';
        icon = '{{ asset("public/assets/admin/img/customer_location.png") }}';
    } else if (locationType === 'store') {
        title = '🏪 At Store Location!';
        body = 'Delivery man has reached store for pickup';
        icon = '{{ asset("public/assets/admin/img/restaurant_map.png") }}';
    }
    
    // Browser notification
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, {
            body: body,
            icon: icon,
            badge: icon,
            tag: 'delivery-' + locationType + '-{{ $order->id }}',
            requireInteraction: true
        });
    }
    
    // Toast notification
    toastr.success(body, title, {
        CloseButton: true,
        ProgressBar: true,
        timeOut: 10000,
        positionClass: 'toast-top-right'
    });
    
    // Play sound
    playDeliveryReachedSound();
}

function playDeliveryReachedSound() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const audioContext = new AudioContext();
        
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        gainNode.gain.value = 0.3;
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.2);
        
        setTimeout(() => {
            const osc2 = audioContext.createOscillator();
            const gain2 = audioContext.createGain();
            osc2.connect(gain2);
            gain2.connect(audioContext.destination);
            osc2.frequency.value = 1000;
            gain2.gain.value = 0.3;
            osc2.start(audioContext.currentTime);
            osc2.stop(audioContext.currentTime + 0.2);
        }, 250);
        
    } catch (error) {
        console.log('Audio not supported');
    }
}
            // Check if delivery man is idle/stopped (not moved in 5 minutes)
            function checkDeliveryManStatus(lastUpdateTime) {
                if (!lastUpdateTime) return;
                
                // Parse the update time
                var lastUpdate = new Date(lastUpdateTime);
                var now = new Date();
                var diffMinutes = Math.floor((now - lastUpdate) / 1000 / 60);
                
                // Update time ago display
                var timeAgoText = '';
                if (diffMinutes < 1) {
                    timeAgoText = '{{ translate("messages.just_now") }}';
                } else if (diffMinutes < 60) {
                    timeAgoText = diffMinutes + ' {{ translate("messages.min") }} {{ translate("messages.ago") }}';
                } else {
                    var hours = Math.floor(diffMinutes / 60);
                    timeAgoText = hours + ' {{ translate("messages.hour") }}' + (hours > 1 ? 's' : '') + ' {{ translate("messages.ago") }}';
                }
                $('#timeAgo').text(timeAgoText);
                
                // Check if stopped/idle (no update for 5+ minutes)
                if (diffMinutes >= 5) {
                    // Change status badge to warning/danger
                    if (diffMinutes >= 10) {
                        // Red - Stopped (10+ minutes)
                        $('#dmStatusBadge').removeClass('avatar-status-success avatar-status-warning')
                                          .addClass('avatar-status-danger')
                                          .html('<i class="tio-clear"></i>');
                        
                        $('#deliveryStatusBadge').prepend(
                            '<span class="badge badge-danger badge-pill px-3 py-1 mb-2 blink-animation">' +
                                '<i class="tio-time"></i> {{ translate('messages.stopped') }}' +
                            '</span>'
                        );
                        
                        $('#lastUpdateTime').addClass('text-danger font-weight-bold');
                        
                    } else {
                        // Yellow - Idle (5-10 minutes)
                        $('#dmStatusBadge').removeClass('avatar-status-success avatar-status-danger')
                                          .addClass('avatar-status-warning')
                                          .html('<i class="tio-time"></i>');
                        
                        $('#deliveryStatusBadge').prepend(
                            '<span class="badge badge-warning badge-pill px-3 py-1 mb-2">' +
                                '<i class="tio-pause"></i> {{ translate('messages.idle') }}' +
                            '</span>'
                        );
                        
                        $('#lastUpdateTime').addClass('text-warning font-weight-bold');
                    }
                } else {
                    // Green - Active (updated within 5 minutes)
                    $('#dmStatusBadge').removeClass('avatar-status-warning avatar-status-danger')
                                      .addClass('avatar-status-success')
                                      .html('<i class="tio-checkmark"></i>');
                    
                    $('#lastUpdateTime').removeClass('text-danger text-warning font-weight-bold');
                }
            }

            // Show proximity notification
            var proximityNotificationShown = false;
            function showProximityNotification() {
                if (proximityNotificationShown) return;
                
                proximityNotificationShown = true;
                
                // Browser notification
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('🎯 {{ translate("messages.delivery_reached") }}!', {
                        body: '{{ translate("messages.delivery_man_is_nearby") }}',
                        icon: '{{ asset("public/assets/admin/img/delivery_boy_map.png") }}',
                        badge: '{{ asset("public/assets/admin/img/delivery_boy_map.png") }}',
                        tag: 'delivery-reached-{{ $order->id }}',
                        requireInteraction: true
                    });
                }
                
                // Toast notification
                toastr.success('{{ translate("messages.delivery_man_has_reached_customer_location") }}!',
                    '{{ translate("messages.delivery_reached") }}', {
                    CloseButton: true,
                    ProgressBar: true,
                    timeOut: 10000,
                    positionClass: 'toast-top-right'
                });
                
                // Play sound
                playDeliveryReachedSound();
            }

            // Play sound when delivery man reaches
            function playDeliveryReachedSound() {
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    const audioContext = new AudioContext();
                    
                    // Play a pleasant notification sound
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    // Pleasant two-tone notification
                    oscillator.frequency.value = 800;
                    gainNode.gain.value = 0.3;
                    
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.2);
                    
                    setTimeout(() => {
                        const osc2 = audioContext.createOscillator();
                        const gain2 = audioContext.createGain();
                        osc2.connect(gain2);
                        gain2.connect(audioContext.destination);
                        osc2.frequency.value = 1000;
                        gain2.gain.value = 0.3;
                        osc2.start(audioContext.currentTime);
                        osc2.stop(audioContext.currentTime + 0.2);
                    }, 250);
                    
                } catch (error) {
                    console.log('Audio not supported');
                }
            }

            // Refresh only map
            $(document).on('click', '#refreshLocationMap', function() {
                var btn = $(this);
                btn.prop('disabled', true);
                btn.html('<i class="tio-refresh spinner-border spinner-border-sm"></i> {{ translate("messages.refreshing") }}...');
                
                // Reset proximity notification flag
                proximityNotificationShown = false;
                
                // Show loading in distance cards
                $('#distanceCardsContainer').html(`
                    <div class="col-12 text-center py-3">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2">{{ translate("messages.refreshing") }}...</p>
                    </div>
                `);
                
                // Reinitialize map
                initializegLocationMap();
                
                setTimeout(() => {
                    btn.prop('disabled', false);
                    btn.html('<i class="tio-refresh"></i> {{ translate("messages.refresh") }}');
                    toastr.success('{{ translate("messages.location_updated") }}');
                }, 1500);
            });

            // Auto-refresh every 30 seconds when modal is open
            var locationRefreshInterval;

            $('#locationModal').on('shown.bs.modal', function() {
                // Initial load
                initializegLocationMap();
                
                // Set up auto-refresh
                locationRefreshInterval = setInterval(function() {
                    console.log('🔄 Auto-refreshing location map...');
                    proximityNotificationShown = false; // Allow notification on each refresh
                    initializegLocationMap();
                }, 30000); // 30 seconds
            });

            $('#locationModal').on('hidden.bs.modal', function() {
                // Clear interval when modal is closed
                if (locationRefreshInterval) {
                    clearInterval(locationRefreshInterval);
                }
            });

            // Re-init map before show modal
            $('#locationModal').on('shown.bs.modal', function(event) {
                initializegLocationMap();
            });


            $('.dm_list').on('click', function() {
                var id = $(this).data('id');
                map.panTo(dmMarkers[id].getPosition());
                map.setZoom(13);
                dmMarkers[id].setAnimation(google.maps.Animation.BOUNCE);
                window.setTimeout(() => {
                    dmMarkers[id].setAnimation(null);
                }, 3);
            });
        })
    </script>

    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script type="text/javascript">
        $(function() {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'order_proof[]',
                maxCount: 6-{{ ($order->order_proof && is_array($order->order_proof))?count(json_decode($order->order_proof)):0 }},
                rowHeight: '176px !important',
                groupClassName: 'spartan_item_wrapper min-w-176px max-w-176px',
                maxFileSize: '',
                placeholderImage: {
                    image: "{{ asset('public/assets/admin/img/upload-img.png') }}",
                    width: '176px'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function(index, file) {

                },
                onRenderedPreview: function(index) {

                },
                onRemoveRow: function(index) {

                },
                onExtensionErr: function(index, file) {
                    toastr.error(
                        "{{ translate('messages.please_only_input_png_or_jpg_type_file') }}", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                },
                onSizeErr: function(index, file) {
                    toastr.error("{{ translate('messages.file_size_too_big') }}", {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        });

@if(!isset($jquery_loaded))
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@endif

<script>
// 🔥 VERSION CHECK - If you see old version, clear browser cache!
console.log('%c🔥 ORDER VIEW VERSION: 2026-03-27-17:10 (WEBSOCKET PROTECTED)', 'background: #10b981; color: white; font-size: 16px; padding: 10px; font-weight: bold;');

(function() {
    'use strict';

    // Create AudioContext for beep sound
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    const audioContext = new AudioContext();
    
    // Beep sound function using Web Audio API
    function playBeep(duration, frequency, volume) {
        return new Promise((resolve) => {
            duration = duration || 200;
            frequency = frequency || 800;
            volume = volume || 50;
            
            try {
                let oscillatorNode = audioContext.createOscillator();
                let gainNode = audioContext.createGain();
                
                oscillatorNode.connect(gainNode);
                oscillatorNode.frequency.value = frequency;
                oscillatorNode.type = "sine"; // sine wave for pleasant sound
                
                gainNode.connect(audioContext.destination);
                gainNode.gain.value = volume * 0.01;
                
                oscillatorNode.start(audioContext.currentTime);
                oscillatorNode.stop(audioContext.currentTime + duration * 0.001);
                
                oscillatorNode.onended = () => {
                    resolve();
                };
            } catch (error) {
                console.error('Audio error:', error);
                resolve();
            }
        });
    }
    
    // Play notification buzzer (3 short beeps)
    async function playNotificationBuzzer() {
        await playBeep(150, 800, 60);  // First beep
        await new Promise(resolve => setTimeout(resolve, 100));
        await playBeep(150, 800, 60);  // Second beep
        await new Promise(resolve => setTimeout(resolve, 100));
        await playBeep(150, 800, 60);  // Third beep
    }
    
    // Check order status
    const orderStatus = "{{ $order->order_status }}";
    const completedStatuses = ['delivered', 'canceled', 'refunded', 'failed'];
    
    if (completedStatuses.includes(orderStatus)) {
        console.log('⏹️ Timer not started - Order status:', orderStatus);
        return;
    }
    
    // Initialize timer
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTimer);
    } else {
        initTimer();
    }
    
    function initTimer() {
        console.log('🕐 Starting timer with buzzer alert...');
        
        const hoursEl = document.querySelector('.timer-display .hours');
        const minutesEl = document.querySelector('.timer-display .minutes');
        const secondsEl = document.querySelector('.timer-display .seconds');
        const timerWrapper = document.querySelector('.timer-wrapper');
        
        if (!hoursEl || !minutesEl || !secondsEl) {
            console.error('❌ Timer elements not found!');
            return;
        }
        
        const orderDateStr = "{{ $order->created_at }}".replace(' ', 'T');
        const orderCreatedAt = new Date(orderDateStr);
        const maxProcessingTime = parseInt({{ $max_processing_time ?? 30 }});
        
        if (isNaN(orderCreatedAt.getTime())) {
            console.error('❌ Invalid date:', orderDateStr);
            return;
        }
        
        // Track if buzzer has been played
        let buzzerPlayed = sessionStorage.getItem('buzzer_25min_{{ $order->id }}') === 'true';
        
        console.log('✅ Timer initialized - Buzzer alert at 25 minutes');
        
        function updateTimer() {
            const now = new Date();
            const diffMs = now - orderCreatedAt;
            const diffSeconds = Math.floor(diffMs / 1000);
            
            const hours = Math.floor(diffSeconds / 3600);
            const minutes = Math.floor((diffSeconds % 3600) / 60);
            const seconds = diffSeconds % 60;
            const totalMinutes = hours * 60 + minutes;
            
            // Update display
            hoursEl.textContent = String(hours).padStart(2, '0');
            minutesEl.textContent = String(minutes).padStart(2, '0');
            secondsEl.textContent = String(seconds).padStart(2, '0');
            
            // Play buzzer at exactly 25 minutes
            if (totalMinutes >= 25 && !buzzerPlayed) {
                console.log('🔔 25 MINUTES REACHED - Playing buzzer!');
                playNotificationBuzzer();
                buzzerPlayed = true;
                sessionStorage.setItem('buzzer_25min_{{ $order->id }}', 'true');
                
                // Show browser notification
                showBrowserNotification();
            }
            
            // Color coding
            if (timerWrapper) {
                timerWrapper.classList.remove('warning', 'danger');
                
                if (totalMinutes >= maxProcessingTime * 1.5) {
                    timerWrapper.classList.add('danger');
                } else if (totalMinutes >= maxProcessingTime) {
                    timerWrapper.classList.add('warning');
                } else if (totalMinutes >= 25) {
                    timerWrapper.classList.add('warning');
                }
            }
        }
        
        // Browser notification
        function showBrowserNotification() {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('⚠️ Order Alert', {
                    body: 'Order #{{ $order->id }} has reached 25 minutes!',
                    icon: '{{ asset("/public/assets/admin/img/shopping-basket.png") }}',
                    badge: '{{ asset("/public/assets/admin/img/shopping-basket.png") }}',
                    tag: 'order-{{ $order->id }}',
                    requireInteraction: true
                });
            }
        }
        
        // Request notification permission on first load
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        
        updateTimer();
        setInterval(updateTimer, 1000);

        // Auto-refresh every 30 seconds
//        setInterval(function() {
//            location.reload();
//        }, 30000);
    }
})();
</script>

{{-- 🔴 REAL-TIME DELIVERY TRACKING VIA WEBSOCKET --}}
@if ($order->delivery_man)
{{-- Load Pusher JS library --}}
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function() {
    'use strict';

    var deliveryManId = {{ $order->delivery_man->id }};
    var orderId = {{ $order->id }};
    var pusherKey = "{{ env('PUSHER_APP_KEY', '') }}";
    var pusherCluster = "{{ env('PUSHER_APP_CLUSTER', 'mt1') }}";

    // Store for tracking data updates
    window.liveTrackingData = {
        lat: {{ $order->dm_last_location['latitude'] ?? 0 }},
        lng: {{ $order->dm_last_location['longitude'] ?? 0 }},
        speed: {{ $order->dm_last_location['speed'] ?? 0 }},
        lastUpdate: new Date()
    };

    console.log('🚴 Real-time tracking initialized for DM #' + deliveryManId);

    if (!pusherKey) {
        console.warn('⚠️ Pusher not configured, real-time tracking disabled');
        return;
    }

    try {
        // Initialize Pusher for delivery tracking
        // Let Pusher use its own servers - DO NOT override wsHost
        var trackingPusher = new Pusher(pusherKey, {
            cluster: pusherCluster,
            forceTLS: true,
            enabledTransports: ['ws', 'wss'],
            disableStats: true
        });

        // Subscribe to delivery man's location channel
        var channel = trackingPusher.subscribe('delivery-man.' + deliveryManId);

        channel.bind('pusher:subscription_succeeded', function() {
            console.log('✅ Subscribed to real-time location updates for DM #' + deliveryManId);
            showRealTimeIndicator(true);
        });

        channel.bind('pusher:subscription_error', function(error) {
            console.error('❌ Failed to subscribe to location channel:', error);
            showRealTimeIndicator(false);
        });

        // Listen for location updates
        channel.bind('location.updated', function(data) {
            try {
                console.log('📍 Real-time location update received:', data);

                if (data.location && data.location.latitude && data.location.longitude) {
                    // Ensure function exists before calling
                    if (typeof updateDeliveryManLocation === 'function') {
                        updateDeliveryManLocation(data.location);
                    } else {
                        console.error('❌ updateDeliveryManLocation function not available');
                        // Fallback: reload page to show new data
                        setTimeout(function() { location.reload(); }, 2000);
                    }
                }
            } catch (error) {
                console.error('❌ Error processing location update:', error);
            }
        });

        // Connection state changes
        trackingPusher.connection.bind('connected', function() {
            console.log('🔌 WebSocket connected for tracking');
            showRealTimeIndicator(true);
        });

        trackingPusher.connection.bind('disconnected', function() {
            console.log('🔌 WebSocket disconnected');
            showRealTimeIndicator(false);
        });

    } catch (e) {
        console.error('❌ Failed to initialize real-time tracking:', e);
    }

    // Update delivery man location on map and UI
    function updateDeliveryManLocation(location) {
        var newLat = parseFloat(location.latitude);
        var newLng = parseFloat(location.longitude);

        // Store new location
        window.liveTrackingData.lat = newLat;
        window.liveTrackingData.lng = newLng;
        window.liveTrackingData.lastUpdate = new Date();

        // Update map marker if visible
        if (window.dmMarkerOnMap && typeof google !== 'undefined') {
            var newPosition = new google.maps.LatLng(newLat, newLng);
            window.dmMarkerOnMap.setPosition(newPosition);
            console.log('🗺️ Map marker updated to:', newLat, newLng);
        }

        // Update tracking cards in the UI
        updateTrackingUI(newLat, newLng, location);



        // Show toast notification
        toastr.info('Delivery location updated', 'Live Tracking', {
            timeOut: 3000,
            positionClass: 'toast-bottom-right'
        });

        // Flash the tracking card
        flashTrackingCard();
    }

    // Update tracking UI cards with new location
    function updateTrackingUI(newLat, newLng, location) {
        // Update the tracking data element
        var trackingDataEl = document.getElementById('deliveryTrackingData');
        if (trackingDataEl) {
            trackingDataEl.setAttribute('data-dm-lat', newLat);
            trackingDataEl.setAttribute('data-dm-lng', newLng);
            trackingDataEl.setAttribute('data-dm-updated', new Date().toISOString());
        }

        // Get customer and store coordinates
        var customerLat = parseFloat('{{ $address["latitude"] ?? 0 }}');
        var customerLng = parseFloat('{{ $address["longitude"] ?? 0 }}');
        var storeLat = parseFloat('{{ $order->store->latitude ?? 0 }}');
        var storeLng = parseFloat('{{ $order->store->longitude ?? 0 }}');

        // Calculate distances
        var distToCustomer = 0, distToStore = 0, distInMeters = 0, timeToCustomer = 0;
        var speed = location.speed || 0;

        if (customerLat && customerLng) {
            distToCustomer = haversineDistance(newLat, newLng, customerLat, customerLng);
            distInMeters = distToCustomer * 1000;
            timeToCustomer = speed > 0 ? Math.ceil((distToCustomer / speed) * 60) : Math.ceil(distToCustomer * 3);
        }

        if (storeLat && storeLng) {
            distToStore = haversineDistance(newLat, newLng, storeLat, storeLng);
        }

        // Update compact tracking display
        var quickDistanceEl = document.getElementById('quickDistance');
        var quickETAEl = document.getElementById('quickETA');
        var speedDMEl = document.getElementById('speedDM');
        var movementStatusEl = document.getElementById('movementStatus');

        if (quickDistanceEl) {
            var newDistanceText = distToCustomer < 1 ?
                distInMeters.toFixed(0) + 'm' :
                distToCustomer.toFixed(2) + ' km';

            if (quickDistanceEl.textContent !== newDistanceText) {
                quickDistanceEl.textContent = newDistanceText;
                triggerUpdateAnimation(quickDistanceEl);
            }
        }

        if (quickETAEl) {
            var newETAText = '~' + timeToCustomer + ' mins';
            if (quickETAEl.textContent !== newETAText) {
                quickETAEl.textContent = newETAText;
                triggerUpdateAnimation(quickETAEl);
            }
        }

        if (speedDMEl) {
            var newSpeedText = speed > 0 ?
                speed.toFixed(1) + ' km/h' :
                '0 km/h';

            if (speedDMEl.textContent !== newSpeedText) {
                speedDMEl.textContent = newSpeedText;
                if (speed === 0) {
                    speedDMEl.title = 'Delivery person is stationary';
                } else {
                    speedDMEl.title = '';
                }
                triggerUpdateAnimation(speedDMEl);
            }
        }

        // Update movement status
        var movingStatus = speed > 2 ? 'Moving' : 'Stationary';
        if (movementStatusEl) {
            movementStatusEl.textContent = movingStatus + ' • ' + distToCustomer.toFixed(2) + ' km to customer';
        }

        // Hide all status badges first
        ['locationStatus', 'enrouteStatus', 'nearbyStatus', 'arrivedStatus'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });

        // Get tracking card container
        var trackingCard = document.getElementById('deliveryTrackingCard');

        // Remove all status classes first
        if (trackingCard) {
            trackingCard.classList.remove('status-at-store', 'status-moving', 'status-nearby', 'status-arrived');
        }

        // Show appropriate status badge based on location
        var storeDistMeters = distToStore * 1000;

        if (storeDistMeters <= 100) {
            // At store (within 100m)
            var locationStatusEl = document.getElementById('locationStatus');
            var locationDistanceEl = document.getElementById('locationDistance');
            if (locationStatusEl) locationStatusEl.style.display = 'inline-flex';
            if (locationDistanceEl) locationDistanceEl.textContent = storeDistMeters.toFixed(0) + 'm';

            // Change entire section background to orange/amber
            if (trackingCard) trackingCard.classList.add('status-at-store');

        } else if (distInMeters <= 100) {
            // Arrived at customer (within 100m)
            var arrivedStatusEl = document.getElementById('arrivedStatus');
            var arrivedDistanceEl = document.getElementById('arrivedDistance');
            if (arrivedStatusEl) arrivedStatusEl.style.display = 'inline-flex';
            if (arrivedDistanceEl) arrivedDistanceEl.textContent = distInMeters.toFixed(0) + 'm away';

            // Change entire section background to success green
            if (trackingCard) trackingCard.classList.add('status-arrived');

        } else if (distInMeters <= 500) {
            // Nearby customer (within 500m)
            var nearbyStatusEl = document.getElementById('nearbyStatus');
            var nearbyDistanceEl = document.getElementById('nearbyDistance');
            if (nearbyStatusEl) nearbyStatusEl.style.display = 'inline-flex';
            if (nearbyDistanceEl) nearbyDistanceEl.textContent = distInMeters.toFixed(0) + 'm away';

            // Also show nearby badge in header
            var nearbyBadgeEl = document.getElementById('nearbyBadge');
            if (nearbyBadgeEl) nearbyBadgeEl.style.display = 'inline-flex';

            // Change entire section background to vibrant green with STRONG pulse
            if (trackingCard) trackingCard.classList.add('status-nearby');

        } else {
            // En route
            var enrouteStatusEl = document.getElementById('enrouteStatus');
            var enrouteDistanceEl = document.getElementById('enrouteDistance');
            if (enrouteStatusEl) enrouteStatusEl.style.display = 'inline-flex';
            if (enrouteDistanceEl) {
                if (distToCustomer < 1) {
                    enrouteDistanceEl.textContent = distInMeters.toFixed(0) + 'm away';
                } else {
                    enrouteDistanceEl.textContent = distToCustomer.toFixed(1) + 'km away';
                }
            }

            // Hide nearby badge in header
            var nearbyBadgeEl = document.getElementById('nearbyBadge');
            if (nearbyBadgeEl) nearbyBadgeEl.style.display = 'none';

            // Change entire section background to blue
            if (trackingCard) trackingCard.classList.add('status-moving');
        }

        // Update detail texts
        var distanceDetailTextEl = document.getElementById('distanceDetailText');
        var speedDetailTextEl = document.getElementById('speedDetailText');
        var speedDetailEl = document.getElementById('speedDetail');

        if (distanceDetailTextEl) {
            distanceDetailTextEl.textContent = distToCustomer.toFixed(2) + ' km to customer • ' + distToStore.toFixed(2) + ' km from store';
        }

        if (speed > 0) {
            if (speedDetailEl) speedDetailEl.style.display = 'flex';
            if (speedDetailTextEl) {
                var movementType = speed > 15 ? 'Fast' : speed > 5 ? 'Normal' : 'Slow';
                speedDetailTextEl.textContent = movementType + ' movement at ' + speed.toFixed(1) + ' km/h';
            }
        } else {
            if (speedDetailEl) speedDetailEl.style.display = 'none';
        }

        // Update progress bar
        var progressFillEl = document.getElementById('progressFill');
        if (progressFillEl && storeLat && storeLng) {
            var totalDistance = haversineDistance(storeLat, storeLng, customerLat, customerLng);
            var progress = totalDistance > 0 ? Math.min(100, Math.max(0, ((totalDistance - distToCustomer) / totalDistance) * 100)) : 0;
            progressFillEl.style.width = progress.toFixed(1) + '%';
        }

        // Update time ago display
        var timeAgoEl = document.getElementById('timeAgo');
        if (timeAgoEl) {
            timeAgoEl.textContent = 'Just now';
        }

        // Update last update time
        var lastUpdateTimeEl = document.getElementById('lastUpdateTime');
        if (lastUpdateTimeEl) {
            lastUpdateTimeEl.textContent = 'Just now';
            lastUpdateTimeEl.className = 'last-update time-fresh';
        }

        console.log('📍 Tracking UI updated - Distance:', distToCustomer.toFixed(2), 'km, ETA:', timeToCustomer, 'mins, Speed:', speed.toFixed(1), 'km/h');
    }

    // Trigger visual update animation on element
    function triggerUpdateAnimation(element) {
        if (!element) return;

        // Remove existing animation class first
        element.classList.remove('value-updating');

        // Add parent card animation
        var parentCard = element.closest('.stat-item') || element.closest('.tracking-stat');
        if (parentCard) {
            parentCard.classList.remove('stat-card-updating');
        }

        // Force reflow to restart animation
        void element.offsetWidth;

        // Add animation class
        element.classList.add('value-updating');
        if (parentCard) {
            parentCard.classList.add('stat-card-updating');
        }

        // Remove animation class after it completes
        setTimeout(function() {
            element.classList.remove('value-updating');
            if (parentCard) {
                parentCard.classList.remove('stat-card-updating');
            }
        }, 600); // Match animation duration
    }

    // Haversine formula for distance calculation
    function haversineDistance(lat1, lng1, lat2, lng2) {
        var R = 6371; // Earth radius in km
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLng = (lng2 - lng1) * Math.PI / 180;
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    // Show/hide real-time connection indicator
    function showRealTimeIndicator(isConnected) {
        var indicator = document.getElementById('realTimeIndicator');
        if (!indicator) {
            // Create indicator if it doesn't exist
            var container = document.querySelector('.card-body h5');
            if (container) {
                var badge = document.createElement('span');
                badge.id = 'realTimeIndicator';
                badge.className = 'badge badge-sm ml-2 ' + (isConnected ? 'badge-success' : 'badge-secondary');
                badge.innerHTML = '<i class="tio-' + (isConnected ? 'checkmark-circle' : 'remove-circle') + '"></i> ' + (isConnected ? 'LIVE' : 'OFFLINE');
                container.appendChild(badge);
            }
        } else {
            indicator.className = 'badge badge-sm ml-2 ' + (isConnected ? 'badge-success' : 'badge-secondary');
            indicator.innerHTML = '<i class="tio-' + (isConnected ? 'checkmark-circle' : 'remove-circle') + '"></i> ' + (isConnected ? 'LIVE' : 'OFFLINE');
        }
    }


    // Flash animation for tracking card on update
    function flashTrackingCard() {
        var card = document.querySelector('.card.border-warning');
        if (card) {
            card.classList.add('flash-update');
            setTimeout(function() {
                card.classList.remove('flash-update');
            }, 1000);
        }
    }

    // Initialize tracking UI with current data on page load
    if (window.liveTrackingData) {
        updateTrackingUI(
            window.liveTrackingData.lat,
            window.liveTrackingData.lng,
            { speed: window.liveTrackingData.speed || 0 }
        );
        console.log('📍 Initial tracking UI loaded with speed:', window.liveTrackingData.speed || 0, 'km/h');
    }
})();
</script>

<style>
/* Real-time tracking styles */
@keyframes flashUpdate {
    0%, 100% { box-shadow: 0 0 0 rgba(255, 193, 7, 0); }
    50% { box-shadow: 0 0 20px rgba(255, 193, 7, 0.5); }
}
.flash-update {
    animation: flashUpdate 0.5s ease-in-out 2;
}
#realTimeIndicator {
    font-size: 10px;
    vertical-align: middle;
}
#realTimeIndicator i {
    font-size: 10px;
}
</style>
@endif
{{-- END REAL-TIME DELIVERY TRACKING --}}

{{-- Bill Panel & Scan Functions --}}
<script>
function toggleBillPanel() {
    var panel = document.getElementById('billFloatingPanel');
    if (!panel) return;
    var isDocked = panel.classList.contains('bill-panel-docked');
    if (panel.style.display === 'none' || panel.style.display === '') {
        panel.style.display = 'block';
        if (isDocked) {
            document.body.classList.add('bill-docked-open');
        } else {
            document.body.style.overflow = 'hidden';
        }
    } else {
        panel.style.display = 'none';
        if (isDocked) {
            document.body.classList.remove('bill-docked-open');
        } else {
            document.body.style.overflow = '';
        }
    }
}

function showBillSlide(index) {
    document.querySelectorAll('.bill-image-slide').forEach(function(s) { s.classList.remove('active'); });
    document.querySelectorAll('.bill-img-dot').forEach(function(b) { b.classList.remove('btn-primary'); b.classList.add('btn-outline-secondary'); });
    var slide = document.querySelector('.bill-image-slide[data-index="' + index + '"]');
    if (slide) slide.classList.add('active');
    var dot = document.querySelectorAll('.bill-img-dot')[index];
    if (dot) { dot.classList.remove('btn-outline-secondary'); dot.classList.add('btn-primary'); }
}

// Close panel on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var panel = document.getElementById('billFloatingPanel');
        if (panel && panel.style.display === 'block') toggleBillPanel();
    }
});

// Edit mode bill panel docking removed - V2 has its own interface

function scanBillWithAI(orderId) {
    var btn = $('#scanBillBtn');
    var spinner = $('#scanBillSpinner');
    var resultsContainer = $('#billScanResultsContainer');

    btn.prop('disabled', true);
    spinner.removeClass('d-none');

    $.ajax({
        url: "{{ url('admin/order/scan-bill') }}/" + orderId,
        type: "POST",
        data: { _token: "{{ csrf_token() }}" },
        timeout: 120000,
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Bill scanned successfully');
                renderBillScanResults(response.data, resultsContainer);
            } else {
                toastr.error(response.message || 'Bill scan failed');
            }
        },
        error: function(xhr, status) {
            var errorMsg = 'Bill scan failed';
            if (xhr.responseJSON && xhr.responseJSON.message) errorMsg = xhr.responseJSON.message;
            else if (status === 'timeout') errorMsg = 'Request timeout - please try again';
            toastr.error(errorMsg);
        },
        complete: function() {
            btn.prop('disabled', false);
            spinner.addClass('d-none');
        }
    });
}

function renderBillScanResults(data, container) {
    if (!data) return;
    var s = data.overall_status || 'unknown';
    var checks = data.checks || {};
    var cls = s === 'pass' ? 'success' : (s === 'warn' ? 'warning' : 'danger');
    var bg = s === 'pass' ? '#e8f5e9' : (s === 'warn' ? '#fff8e1' : '#ffebee');

    var h = '<div class="p-3 border rounded border-' + cls + ' mt-2" style="background:' + bg + ';">';
    h += '<div class="d-flex justify-content-between align-items-center mb-2">';
    h += '<strong>' + getStatusIcon(s) + ' Scan Verification</strong>';
    h += '<span class="badge badge-' + cls + '">' + s.toUpperCase() + '</span></div>';
    if (data.scanned_at) h += '<small class="text-muted d-block mb-2">Scanned: ' + fmtDt(data.scanned_at) + ' | Method: ' + (data.scan_method||'unknown') + '</small>';

    if (checks.store_name) h += chkRow('Store', checks.store_name.expected, checks.store_name.found, checks.store_name.status);
    if (checks.total_price) {
        var diff = checks.total_price.difference ? ' (Diff: ' + fmtC(checks.total_price.difference) + ')' : '';
        h += chkRow('Total', fmtC(checks.total_price.expected), fmtC(checks.total_price.found) + diff, checks.total_price.status);
    }
    if (checks.customer_name) h += chkRow('Customer', checks.customer_name.expected, checks.customer_name.found, checks.customer_name.status);
    if (checks.date) h += chkRow('Date', checks.date.expected, checks.date.found, checks.date.status);
    if (checks.discount) h += chkRow('Discount', fmtC(checks.discount.expected), fmtC(checks.discount.found), checks.discount.status);

    if (checks.items && checks.items.length > 0) {
        h += '<table class="table table-sm table-bordered mt-2 mb-0" style="font-size:12px;"><thead class="thead-light"><tr><th>Item</th><th>Expected</th><th>Found</th><th>Status</th></tr></thead><tbody>';
        for (var i = 0; i < checks.items.length; i++) {
            var it = checks.items[i];
            h += '<tr><td>' + esc(it.name) + '</td><td>' + fmtC(it.expected_price) + '</td><td>' + fmtC(it.found_price) + '</td><td class="text-center">' + getStatusIcon(it.status) + '</td></tr>';
        }
        h += '</tbody></table>';
    }
    if (checks.extra_items && checks.extra_items.length > 0) {
        h += '<div class="mt-2 p-2" style="background:#fff3cd;border-radius:4px;"><strong class="text-warning">Extra Items:</strong>';
        h += '<table class="table table-sm table-bordered mt-1 mb-0" style="font-size:12px;"><tbody>';
        for (var j = 0; j < checks.extra_items.length; j++) {
            var ex = checks.extra_items[j];
            h += '<tr><td>' + esc(ex.name) + '</td><td>' + (ex.quantity||1) + '</td><td>' + fmtC(ex.price) + '</td></tr>';
        }
        h += '</tbody></table></div>';
    }
    h += '</div>';
    container.html(h);
}

function chkRow(label, expected, found, status) {
    return '<div class="d-flex align-items-center mb-1 px-2 py-1" style="background:#f8f9fa;border-radius:3px;font-size:13px;">' +
        '<span class="mr-2">' + getStatusIcon(status) + '</span>' +
        '<strong class="mr-2" style="min-width:70px;">' + label + '</strong>' +
        '<span class="text-muted mr-1">Exp:</span>' + esc(expected) +
        '<span class="text-muted mx-1">|</span><span class="text-muted mr-1">Found:</span>' + esc(found) + '</div>';
}

function getStatusIcon(status) {
    if (status === 'pass') return '<span class="text-success">&#10003;</span>';
    if (status === 'warn') return '<span class="text-warning">&#9888;</span>';
    return '<span class="text-danger">&#10007;</span>';
}

function esc(text) { if (!text) return 'N/A'; var d = document.createElement('div'); d.textContent = text; return d.innerHTML; }
function fmtDt(iso) { try { var d = new Date(iso); return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})+', '+d.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:true}); } catch(e) { return iso; } }
function fmtC(amt) { return '{{ \App\CentralLogics\Helpers::currency_symbol() }}' + parseFloat(amt||0).toFixed(2); }
</script>
{{-- END Bill Panel & Scan Functions --}}

{{-- ===== KEYBOARD SHORTCUTS FOR FASTER ORDER TRANSITIONS ===== --}}
<script>
$(document).ready(function() {
    // Shortcuts panel HTML
    var shortcutsPanelHtml = `
<div id="shortcutsPanel" style="display:none;position:fixed;bottom:20px;right:20px;z-index:9999;background:#fff;border-radius:10px;box-shadow:0 4px 25px rgba(0,0,0,.15);padding:20px;min-width:320px;max-width:400px;font-size:13px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0"><i class="tio-keyboard mr-1"></i> {{ translate('messages.keyboard_shortcuts') }}</h6>
        <button type="button" class="close" id="closeShortcuts" style="font-size:18px;">&times;</button>
    </div>
    <table class="table table-sm table-borderless mb-0">
        <tbody>
            <tr><td><kbd>E</kbd></td><td>{{ translate('messages.edit_order') }}</td></tr>
            <tr><td><kbd>S</kbd></td><td>{{ translate('messages.submit_edit_changes') }}</td></tr>
            <tr><td><kbd>Esc</kbd></td><td>{{ translate('messages.cancel_edit') }}</td></tr>
            <tr><td><kbd>P</kbd></td><td>{{ translate('messages.print_invoice') }}</td></tr>
            <tr><td colspan="2" class="pt-2"><strong>{{ translate('messages.order_status_changes') }}</strong></td></tr>
            <tr><td><kbd>1</kbd></td><td>{{ translate('messages.set_pending') }}</td></tr>
            <tr><td><kbd>2</kbd></td><td>{{ translate('messages.set_confirmed') }}</td></tr>
            <tr><td><kbd>3</kbd></td><td>{{ translate('messages.set_processing') }}</td></tr>
            <tr><td><kbd>4</kbd></td><td>{{ translate('messages.set_handover') }}</td></tr>
            <tr><td><kbd>5</kbd></td><td>{{ translate('messages.set_out_for_delivery') }}</td></tr>
            <tr><td><kbd>6</kbd></td><td>{{ translate('messages.set_delivered') }}</td></tr>
            <tr><td><kbd>7</kbd></td><td>{{ translate('messages.set_canceled') }}</td></tr>
            <tr><td colspan="2" class="pt-2"><strong>{{ translate('messages.confirmation_dialog') }}</strong></td></tr>
            <tr><td><kbd>Y</kbd></td><td>{{ translate('messages.confirm_yes') }}</td></tr>
            <tr><td><kbd>N</kbd></td><td>{{ translate('messages.cancel_no') }}</td></tr>
            <tr><td colspan="2" class="pt-2"><strong>{{ translate('messages.other') }}</strong></td></tr>
            <tr><td><kbd>Ctrl+K</kbd></td><td>{{ translate('messages.focus_search') }}</td></tr>
            <tr><td><kbd>D</kbd></td><td>{{ translate('messages.assign_delivery_man') }}</td></tr>
            <tr><td><kbd>?</kbd></td><td>{{ translate('messages.toggle_this_panel') }}</td></tr>
        </tbody>
    </table>
</div>`;
$('body').append(shortcutsPanelHtml);

$('#shortcutsToggle, #closeShortcuts').on('click', function() {
    $('#shortcutsPanel').toggle();
});

// Edit order handler
$('.edit-order').on('click', function(e) {
    e.preventDefault();
    Swal.fire({
        title: '{{ translate('messages.are_you_sure') }}',
        text: '{{ translate('messages.you_want_to_edit_this_order') }}',
        type: 'warning',
        showCancelButton: true,
        cancelButtonColor: 'default',
        confirmButtonColor: '#FC6A57',
        cancelButtonText: '{{ translate('messages.no') }}',
        confirmButtonText: '{{ translate('messages.yes') }}',
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            location.href = '{{ route('admin.order.edit-v2', $order->id) }}';
        }
    });
});

// Main keyboard shortcut handler
$(document).on('keydown', function(e) {
    var tag = e.target.tagName.toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select' || $(e.target).attr('contenteditable')) {
        return;
    }

    var swalVisible = $('.swal2-container').length > 0;
    if (swalVisible) {
        if (e.key === 'y' || e.key === 'Y') {
            e.preventDefault();
            var confirmBtn = $('.swal2-confirm');
            if (confirmBtn.length) confirmBtn.trigger('click');
            return;
        }
        if (e.key === 'n' || e.key === 'N' || e.key === 'Escape') {
            e.preventDefault();
            var cancelBtn = $('.swal2-cancel');
            if (cancelBtn.length) cancelBtn.trigger('click');
            return;
        }
        return;
    }

    var modalOpen = $('.modal.show').length > 0;

    switch(e.key) {
        case '?':
            e.preventDefault();
            $('#shortcutsPanel').toggle();
            break;
        case 'e':
        case 'E':
            console.log('E key pressed, modalOpen:', modalOpen);
            if (modalOpen) return;
            e.preventDefault();
            var editBtn = $('.edit-order');
            console.log('Edit button found:', editBtn.length);
            if (editBtn.length) {
                console.log('Triggering click on edit button');
                editBtn.trigger('click');
            }
            break;
        case 's':
        case 'S':
            if (modalOpen) return;
            e.preventDefault();
            var submitBtn = $('#submit-edit-btn, .submit-edit-order');
            if (submitBtn.length) submitBtn.trigger('click');
            break;
        case 'p':
        case 'P':
            if (modalOpen) return;
            e.preventDefault();
            var printBtn = $('.print--btn.d-none.d-sm-block');
            if (printBtn.length) window.location.href = printBtn.attr('href');
            break;
        case 'd':
        case 'D':
            if (modalOpen) return;
            e.preventDefault();
            var dmBtn = $('[data-target="#myModal"]');
            if (dmBtn.length) dmBtn.trigger('click');
            break;
        case 'Escape':
            if ($('#shortcutsPanel').is(':visible')) {
                $('#shortcutsPanel').hide();
                return;
            }
            if (modalOpen) return;
            var cancelEditBtn = $('#cancel-edit-btn, .cancel-edit-order');
            if (cancelEditBtn.length) cancelEditBtn.trigger('click');
            break;
        case '1': case '2': case '3': case '4': case '5': case '6': case '7':
            if (modalOpen) return;
            e.preventDefault();
            var statusMap = {'1':'pending','2':'confirmed','3':'processing','4':'handover','5':'picked_up','6':'delivered','7':'canceled'};
            var targetStatus = statusMap[e.key];
            if (!targetStatus) return;
            if (targetStatus === 'canceled') {
                var cancelItem = $('.canceled-status');
                if (cancelItem.length) cancelItem.trigger('click');
            } else {
                var statusLinks = $('.dropdown-menu[aria-labelledby="dropdownMenuButton"] .dropdown-item');
                statusLinks.each(function() {
                    var url = $(this).data('url') || '';
                    if (url && url.indexOf('order_status=' + targetStatus) !== -1) {
                        $(this).trigger('click');
                        return false;
                    }
                });
            }
            break;
    }
});
}); // End document.ready for shortcuts
</script>

<script>
// Outside Purchase marking
$(document).on('click', '.mark-outside-purchase', function(e) {
    e.preventDefault();
    let button = $(this);
    let orderDetailId = button.data('order-detail-id');
    let itemPrice = button.data('item-price');

    Swal.fire({
        title: '{{ translate("messages.outside_purchase") }}',
        html: '<label>{{ translate("messages.purchase_cost") }}</label>' +
              '<input type="number" id="op-cost" class="swal2-input" step="0.01" min="0" value="' + itemPrice + '" placeholder="{{ translate("messages.enter_purchase_cost") }}">' +
              '<label class="mt-2">{{ translate("messages.purchased_from_store") }}</label>' +
              '<select id="op-store" class="swal2-input" style="width: 100%;"><option value="">{{ translate("messages.select_store_or_leave_empty") }}</option></select>' +
              '<small class="text-muted">{{ translate("messages.search_by_store_name") }}</small>',
        showCancelButton: true,
        confirmButtonText: '{{ translate("messages.confirm") }}',
        cancelButtonText: '{{ translate("messages.cancel") }}',
        didOpen: () => {
            $.ajax({
                url: '{{ route("admin.order.get-stores-for-outside-purchase") }}',
                type: 'GET',
                dataType: 'json',
                data: { search: '', limit: 200 },
                success: function(stores) {
                    var storeOptions = [{id: '', text: '{{ translate("messages.select_store_or_leave_empty") }}'}];
                    storeOptions = storeOptions.concat(stores);
                    $('#op-store').select2({
                        dropdownParent: $('.swal2-container'),
                        placeholder: '{{ translate("messages.select_store_or_leave_empty") }}',
                        allowClear: true,
                        width: '100%',
                        data: storeOptions
                    });
                }
            });
        },
        preConfirm: () => {
            let cost = document.getElementById('op-cost').value;
            if (!cost || cost <= 0) {
                Swal.showValidationMessage('{{ translate("messages.please_enter_valid_cost") }}');
            }
            return { cost: cost, store_id: $('#op-store').val() || null };
        }
    }).then((result) => {
        if (result.value) {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.post({
                url: '{{ route("admin.order.mark-outside-purchase") }}',
                data: { order_detail_id: orderDetailId, outside_purchase_cost: result.value.cost, outside_purchase_store_id: result.value.store_id },
                success: function() {
                    toastr.success('{{ translate("messages.outside_purchase_marked_successfully") }}');
                    setTimeout(() => location.reload(), 1000);
                },
                error: function() { toastr.error('{{ translate("messages.something_went_wrong") }}'); }
            });
        }
    });
});

// Full Order Outside Purchase marking
$(document).on('click', '.mark-full-order-outside-purchase', function(e) {
    e.preventDefault();
    let button = $(this);
    let orderId = button.data('order-id');
    let orderTotal = button.data('order-total');

    Swal.fire({
        title: '{{ translate("messages.mark_full_order_outside_purchase") }}',
        html: '<label>{{ translate("messages.total_purchase_cost") }}</label>' +
              '<input type="number" id="fop-cost" class="swal2-input" step="0.01" min="0" value="' + orderTotal + '" placeholder="{{ translate("messages.enter_purchase_cost") }}">' +
              '<label class="mt-2">{{ translate("messages.purchased_from_store") }}</label>' +
              '<select id="fop-store" class="swal2-input"><option value="">{{ translate("messages.not_from_a_listed_store") }}</option></select>',
        showCancelButton: true,
        confirmButtonText: '{{ translate("messages.confirm") }}',
        cancelButtonText: '{{ translate("messages.cancel") }}',
        preConfirm: () => {
            let cost = document.getElementById('fop-cost').value;
            if (!cost || cost <= 0) {
                Swal.showValidationMessage('{{ translate("messages.please_enter_valid_cost") }}');
            }
            return { cost: cost, store_id: document.getElementById('fop-store').value };
        }
    }).then((result) => {
        if (result.value) {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.post({
                url: '{{ route("admin.order.mark-full-order-outside-purchase") }}',
                data: { order_id: orderId, outside_purchase_cost: result.value.cost, outside_purchase_store_id: result.value.store_id || null },
                success: function() {
                    toastr.success('{{ translate("messages.full_order_outside_purchase_marked_successfully") }}');
                    setTimeout(() => location.reload(), 1000);
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || '{{ translate("messages.something_went_wrong") }}');
                }
            });
        }
    });
});

// Approve Outside Purchase Request
$(document).on('click', '.approve-outside-purchase-btn', function() {
    let button = $(this);
    let detailId = button.data('detail-id');

    Swal.fire({
        title: '{{ translate("messages.are_you_sure") }}',
        text: '{{ translate("messages.approve_outside_purchase_confirm") }}',
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: 'default',
        cancelButtonText: '{{ translate("messages.no") }}',
        confirmButtonText: '{{ translate("messages.yes") }}',
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            button.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');
            $.ajax({
                url: '{{ route("admin.order.approve-outside-purchase") }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                data: { order_detail_id: detailId, _token: '{{ csrf_token() }}' },
                success: function(response) {
                    toastr.success('{{ translate("messages.outside_purchase_approved_successfully") }}');
                    setTimeout(() => location.reload(), 1000);
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || '{{ translate("messages.something_went_wrong") }}');
                    button.prop('disabled', false).html('<i class="tio-checkmark-circle"></i>');
                }
            });
        }
    });
});

// Reject Outside Purchase Request
$(document).on('click', '.reject-outside-purchase-btn', function() {
    let button = $(this);
    let detailId = button.data('detail-id');

    Swal.fire({
        title: '{{ translate("messages.reject_outside_purchase") }}',
        text: '{{ translate("messages.rejection_reason_optional") }}',
        input: 'textarea',
        inputPlaceholder: '{{ translate("messages.enter_rejection_reason") }}',
        showCancelButton: true,
        confirmButtonColor: '#FC6A57',
        cancelButtonColor: 'default',
        cancelButtonText: '{{ translate("messages.cancel") }}',
        confirmButtonText: '{{ translate("messages.reject") }}',
        reverseButtons: true
    }).then((result) => {
        if (result.value !== undefined) {
            button.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');
            $.ajax({
                url: '{{ route("admin.order.reject-outside-purchase") }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                data: { order_detail_id: detailId, rejection_reason: result.value || '', _token: '{{ csrf_token() }}' },
                success: function(response) {
                    toastr.success('{{ translate("messages.outside_purchase_rejected_successfully") }}');
                    setTimeout(() => location.reload(), 1000);
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || '{{ translate("messages.something_went_wrong") }}');
                    button.prop('disabled', false).html('<i class="tio-clear-circle"></i>');
                }
            });
        }
    });
});

// Auto-save after cart modifications
$(document).on('DOMSubtreeModified', '#order-items-table-wrapper', _.debounce(function() {
    if (typeof autoSaveEditProgress === 'function') autoSaveEditProgress();
}, 2000));

// Warn before leaving page with unsaved edits
window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = '{{ translate("messages.unsaved_order_edits_warning") }}';
        return e.returnValue;
    }
});

// Remove warning when properly saving/cancelling
    window.onbeforeunload = null;
});
</script>

{{-- Inline editing removed - V2 is now the only edit interface --}}

    </script>

{{-- STANDALONE WEBSOCKET - Loads independently to avoid JS errors --}}
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="{{ asset('public/assets/admin/js/order-websocket.js') }}?v={{ time() }}"></script>

@endpush
