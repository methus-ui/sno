@extends('layouts.admin.app')

@section('title',translate('messages.regular_items'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- ZXing JS for more accurate barcode scanning -->
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
    <!-- Html5-QRCode as additional option for better compatibility -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <!-- QuaggaJS for camera scanning -->
    <script src="https://unpkg.com/quagga/dist/quagga.min.js"></script>
    <style>

/* Unit Badge Styles */
.unit-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    background: #f0f0f0;
    color: #666;
    border-radius: 4px;
    text-transform: uppercase;
}

/* Status Toggle Switch */
.status-toggle {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.status-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.status-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.status-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .status-slider {
    background-color: #28a745;
}

input:checked + .status-slider:before {
    transform: translateX(26px);
}

.status-slider.loading {
    opacity: 0.6;
    cursor: not-allowed;
}

.status-label {
    font-size: 11px;
    display: block;
    text-align: center;
    margin-top: 2px;
    font-weight: 600;
}



        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .table-container {
            position: relative;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Enhanced loading states */
        .btn-loading {
            position: relative;
            pointer-events: none;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .input-loading {
            background-image: url("data:image/svg+xml,%3csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='10' cy='10' r='8' fill='none' stroke='%23007bff' stroke-width='2' stroke-linecap='round' stroke-dasharray='50.27' stroke-dashoffset='50.27'%3e%3canimateTransform attributeName='transform' type='rotate' dur='1s' values='0 10 10;360 10 10' repeatCount='indefinite'/%3e%3c/circle%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
        }

        .barcode-scanner-section {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .barcode-input-group {
            position: relative;
        }

        .barcode-input-group .input-group-text {
            background: #007bff;
            color: white;
            border: none;
        }

        .barcode-status {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }

        .stock-buttons {
            display: flex;
            gap: 5px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .stock-btn {
            min-width: 50px;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
        }

        .stock-input-wrapper {
            position: relative;
        }

        .stock-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }

        .quick-actions {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .scanning-indicator {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .barcode-scanner-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .scanner-status {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 12px;
        }

        /* Enhanced Search Styles */
        .search-form .form-label {
            font-weight: 600;
            color: #344050;
        }

        .quick-filter {
            margin: 2px;
            border-radius: 20px;
            font-size: 12px;
            padding: 6px 12px;
            transition: all 0.2s;
        }

        .quick-filter:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .quick-filter.active {
            background: #377dff !important;
            color: white !important;
            border-color: #377dff !important;
        }

        .search-result-highlight {
            background: linear-gradient(45deg, #f8f9fa, #e3f2fd);
            border-left: 4px solid #377dff;
        }

        .input-group-lg .form-control {
            font-size: 1.1rem;
            padding: 12px 16px;
        }

        .gap-2 {
            gap: 0.5rem !important;
        }

        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .search-suggestion-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
        }

        .search-suggestion-item:hover {
            background: #f8f9fa;
        }

        .search-suggestion-item:last-child {
            border-bottom: none;
        }

        /* Pagination enhancements */
        .pagination-container {
            position: relative;
        }

        .pagination-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .search-pagination {
            margin-top: 20px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }

        .search-pagination .pagination {
            justify-content: center;
            margin-bottom: 0;
        }

        .search-pagination .pagination-info {
            text-align: center;
            margin-bottom: 15px;
            color: #6c757d;
            font-size: 14px;
        }

        /* Loading states for different elements */
        .form-control-loading {
            background-image: url("data:image/svg+xml,%3csvg width='16' height='16' viewBox='0 0 16 16' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='6' fill='none' stroke='%23007bff' stroke-width='2' stroke-linecap='round' stroke-dasharray='37.7' stroke-dashoffset='37.7'%3e%3canimateTransform attributeName='transform' type='rotate' dur='1s' values='0 8 8;360 8 8' repeatCount='indefinite'/%3e%3c/circle%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
        }

        .select-loading {
            background-image: url("data:image/svg+xml,%3csvg width='16' height='16' viewBox='0 0 16 16' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='6' fill='none' stroke='%23007bff' stroke-width='2' stroke-linecap='round' stroke-dasharray='37.7' stroke-dashoffset='37.7'%3e%3canimateTransform attributeName='transform' type='rotate' dur='1s' values='0 8 8;360 8 8' repeatCount='indefinite'/%3e%3c/circle%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 25px center;
            background-size: 16px;
        }

        /* MRP Column Styles */
        .mrp-column {
            font-weight: 600;
            color: #2c7be5;
        }

        .mrp-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 4px;
        }

        /* Bigger Product Images */
        .product-image {
            width: 80px !important;
            height: 80px !important;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-image:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            cursor: pointer;
        }

        /* Adjust table cell padding for bigger images */
        #datatable td {
            vertical-align: middle;
            padding: 12px 8px;
        }

        /* Image preview modal */
        .image-preview-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            padding-top: 60px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.9);
        }

        .image-preview-content {
            margin: auto;
            display: block;
            max-width: 80%;
            max-height: 80%;
        }

        .image-preview-close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }

        .image-preview-close:hover,
        .image-preview-close:focus {
            color: #bbb;
            text-decoration: none;
        }

        /* Camera Scanner Styles - Updated for ZXing */
        #camera-scanner-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.9);
        }

        #camera-scanner-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 50px auto;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
        }

        #interactive {
            position: relative;
            width: 100%;
            height: 480px;
        }

        #interactive video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #interactive canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 2;
        }

        .scanner-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff00, transparent);
            top: 50%;
            animation: scan 2s linear infinite;
        }

        @keyframes scan {
            0% { transform: translateY(-240px); }
            100% { transform: translateY(240px); }
        }

        .scanner-corners {
            position: absolute;
            width: 80%;
            height: 60%;
            top: 20%;
            left: 10%;
            border: 2px solid transparent;
        }

        .scanner-corners::before,
        .scanner-corners::after,
        .scanner-corner-left::before,
        .scanner-corner-left::after {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            border: 3px solid #00ff00;
        }

        .scanner-corners::before {
            top: 0;
            left: 0;
            border-right: none;
            border-bottom: none;
        }

        .scanner-corners::after {
            top: 0;
            right: 0;
            border-left: none;
            border-bottom: none;
        }

        .scanner-corner-left {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .scanner-corner-left::before {
            bottom: 0;
            left: 0;
            border-right: none;
            border-top: none;
        }

        .scanner-corner-left::after {
            bottom: 0;
            right: 0;
            border-left: none;
            border-top: none;
        }

        .camera-controls {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            z-index: 3;
        }

        .camera-close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            color: #000;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            z-index: 4;
        }

        .camera-close-btn:hover {
            background: #fff;
        }

        .camera-status {
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .camera-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 0 5px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .camera-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .camera-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .camera-icon {
            animation: pulse 2s infinite;
        }

        .detected-barcode {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            z-index: 5;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        .camera-permission-msg {
            text-align: center;
            color: white;
            padding: 20px;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 8px;
            margin: 20px;
        }

        .camera-permission-msg h4 {
            color: #ffc107;
            margin-bottom: 10px;
        }

        .scan-camera-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            margin-left: 10px;
        }

        .scan-camera-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,123,255,0.3);
        }

        .scan-camera-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        /* Scanner accuracy indicator */
        .accuracy-indicator {
            position: absolute;
            top: 60px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            z-index: 5;
        }

        .scanner-type-badge {
            background: #17a2b8;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 5px;
        }

        /* Format selection */
        .format-selector {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 4;
            background: rgba(255, 255, 255, 0.9);
            padding: 5px;
            border-radius: 5px;
        }

        .format-selector select {
            border: none;
            background: transparent;
            font-size: 12px;
            cursor: pointer;
        }

        /* Detection box */
        .detection-box {
            position: absolute;
            border: 3px solid #00ff00;
            background: rgba(0, 255, 0, 0.1);
            pointer-events: none;
            z-index: 3;
            display: none;
        }
        .product-image-small {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .assign-barcode-btn {
            white-space: nowrap;
        }

        /* Performance optimization classes */
        .fast-search-indicator {
            position: absolute;
            top: 5px;
            right: 40px;
            z-index: 10;
            color: #28a745;
            font-size: 12px;
            background: rgba(40, 167, 69, 0.1);
            padding: 2px 6px;
            border-radius: 10px;
        }

        .serp-loading {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .performance-badge {
            font-size: 10px;
            background: #17a2b8;
            color: white;
            padding: 1px 5px;
            border-radius: 8px;
            margin-left: 5px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="btn--container align-items-center mb-0">
                <div class="mr-auto">
                    <h1 class="page-header-title"><i class="tio-filter-list"></i> {{translate('messages.regular_items')}}<span class="badge badge-soft-dark ml-2" id="itemCount">{{$items->total()}}</span></h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Barcode Scanner Section -->
        <div class="card mb-3">
            <div class="card-body barcode-scanner-section">
                <div class="barcode-scanner-title">
                    <h4><i class="tio-barcode"></i> {{translate('messages.barcode_scanner')}}</h4>
                    <span class="badge badge-success scanner-status" id="scannerStatus">Ready</span>
                    <span class="performance-badge">Fast Mode</span>
                </div>
                
                <!-- Store Selection First -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="scannerStoreSelect" class="form-label">
                            <i class="tio-shop"></i> {{translate('messages.select_store_first')}} <span class="text-danger">*</span>
                        </label>
                        <select id="scannerStoreSelect" class="form-control js-select2-custom" data-placeholder="{{translate('messages.select_store_to_scan')}}" required>
                            <option value="">{{translate('messages.choose_store_first')}}</option>
                            @foreach(\App\Models\Store::all() as $store)
                            <option value="{{$store->id}}">{{$store->name}}</option>
                            @endforeach
                        </select>
                        <small class="text-info">{{translate('messages.store_selection_required_for_barcode_search')}}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{translate('messages.selected_store_info')}}</label>
                        <div class="alert alert-info d-none" id="selectedStoreInfo">
                            <i class="tio-checkmark-circle"></i>
                            <span id="selectedStoreName"></span>
                            <small class="d-block">{{translate('messages.now_you_can_scan_barcodes')}}</small>
                        </div>
                    </div>
                </div>

                <!-- Barcode Scanner Input -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="barcode-input-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="tio-barcode"></i>
                                    </span>
                                </div>
                                <input type="text" id="barcodeScanner" class="form-control form-control-lg"
                                       placeholder="{{translate('messages.first_select_store_then_scan')}}"
                                       autocomplete="off" disabled>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-info scan-camera-btn" id="cameraScanBtn" disabled>
                                        <i class="tio-camera camera-icon"></i> {{translate('messages.camera')}}
                                    </button>
                                </div>
                                <div class="barcode-status" id="barcodeStatus"></div>
                                <div class="fast-search-indicator" id="fastSearchIndicator" style="display: none;">
                                    <i class="tio-zap"></i> Fast
                                </div>
                            </div>
                            <small class="text-muted" id="scannerHelpText">{{translate('messages.please_select_store_first')}}</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="quick-actions">
                            <button type="button" class="btn btn-outline-primary" id="clearBarcode" disabled>
                                <i class="tio-clear"></i> {{translate('messages.clear')}}
                            </button>
                            <button type="button" class="btn btn-primary" id="manualSearch" disabled>
                                <i class="tio-search"></i> {{translate('messages.search')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <!-- Header -->
            <div class="card-header py-2 border-0">
                <h1>{{ translate('search_data') }}</h1>
            </div>
            <div class="row mr-1 ml-2 mb-5">
                <div class="col-sm-6 col-md-4">
                    <div class="select-item">
                        <select name="category_id" id="category" data-placeholder="{{ translate('messages.select_category') }}"
                            class="js-data-example-ajax form-control set-filter"
                            data-url="{{url()->full()}}" data-filter="category_id">
                            @if($category)
                            <option value="{{$category->id}}" selected>{{$category->name}}</option>
                            @else
                            <option value="all" selected>{{translate('messages.all_category')}}</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <div class="select-item">
                        <select name="sub_category_id" class="form-control js-select2-custom set-filter"
                            data-placeholder="{{ translate('messages.select_sub_category') }}" id="sub-categories"
                            data-url="{{url()->full()}}" data-filter="sub_category_id">
                            <option value="all" selected>{{translate('messages.all_sub_category')}}</option>
                            @foreach($sub_categories as $z)
                            <option
                                value="{{$z['id']}}" {{ request()?->sub_category_id == $z['id']?'selected':''}}>
                                {{$z['name']}}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <div class="select-item">
                        <select name="store_id" id="store_filter" class="form-control js-select2-custom set-filter"
                            data-placeholder="{{ translate('messages.select_store') }}"
                            data-url="{{url()->full()}}" data-filter="store_id">
                            <option value="all" selected>{{translate('messages.all_stores')}}</option>
                            @foreach(\App\Models\Store::all() as $store)
                            <option value="{{$store->id}}" {{ request()?->store_id == $store->id?'selected':''}}>
                                {{$store->name}}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Product Search Section -->
        <div class="card mb-3">
            <div class="card-header py-3 border-0">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="mb-0"><i class="tio-search"></i> {{translate('messages.product_search')}}</h3>
                        <small class="text-muted">{{translate('messages.search_products_by_name_barcode_or_store')}}</small>
                    </div>
                    <div class="col-auto">
                        <span class="badge badge-soft-primary" id="searchResultCount">{{$items->total()}} {{translate('messages.products')}}</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="search-form" class="search-form">
                    @csrf
                    <div class="row">
                        <!-- Main Search Input -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="datatableSearch" class="form-label">
                                    <i class="tio-search"></i> {{translate('messages.search_products')}}
                                </label>
                                <div class="input-group input-group-lg">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="tio-search"></i>
                                        </span>
                                    </div>
                                    <input id="datatableSearch" type="search" name="search" class="form-control form-control-lg"
                                           placeholder="{{translate('messages.type_product_name_or_barcode')}}"
                                           aria-label="{{translate('messages.search_here')}}"
                                           autocomplete="off">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary btn-lg" id="searchSubmitBtn">
                                            <i class="tio-search"></i> {{translate('messages.search')}}
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">{{translate('messages.search_by_product_name_barcode_or_store_name')}}</small>
                            </div>
                        </div>

                        <!-- Quick Search Buttons -->
                        <div class="col-md-6">
                            <label class="form-label">{{translate('messages.quick_filters')}}</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-success quick-filter" data-filter="has_barcode">
                                    <i class="tio-checkmark-circle"></i> {{translate('messages.has_barcode')}}
                                </button>
                                <button type="button" class="btn btn-outline-warning quick-filter" data-filter="no_barcode">
                                    <i class="tio-clear-circle"></i> {{translate('messages.no_barcode')}}
                                </button>
                                <button type="button" class="btn btn-outline-info quick-filter" data-filter="low_stock">
                                    <i class="tio-archive"></i> {{translate('messages.low_stock')}}
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="clearAllFilters">
                                    <i class="tio-clear"></i> {{translate('messages.clear_all')}}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Search Results Info -->
                    <div class="row mt-3" id="searchInfo" style="display: none;">
                        <div class="col-12">
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="tio-info-outined mr-2"></i>
                                <span id="searchInfoText"></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary ml-auto" id="clearSearch">
                                    <i class="tio-clear"></i> {{translate('messages.clear_search')}}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive datatable-custom table-container">
            <!-- Loading Overlay -->
            <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                <div class="spinner"></div>
            </div>
            
            <table id="datatable" class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                data-hs-datatables-options='{
                    "columnDefs": [{
                        "targets": [],
                        "width": "5%",
                        "orderable": false
                    }],
                    "order": [],
                    "info": {
                    "totalQty": "#datatableWithPaginationInfoTotalQty"
                    },
                    "entries": "#datatableEntries",
                    "isResponsive": false,
                    "isShowPaging": false,
                        "paging":false
                }'>
                <thead class="thead-light">
                    <tr>
                        <th class="border-0">{{translate('messages.#')}}</th>
                        <th class="border-0">{{translate('messages.image')}}</th>
                        <th class="border-0">{{translate('messages.name')}}</th>
                        <th class="border-0">{{translate('messages.barcode')}}</th>
                        <th class="border-0">{{translate('messages.mrp')}}</th>
                        <th class="border-0">{{translate('messages.unit')}}</th>
                        <th class="border-0">{{translate('messages.status')}}</th>
                        <th class="border-0">{{translate('messages.store')}}</th>
                        <th class="border-0 text-center">{{translate('messages.action')}}</th>
                    </tr>
                </thead>

                <tbody id="set-rows">
                    @foreach($items as $key=>$item)
                        <tr>
                            <td>{{$key+$items->firstItem()}}</td>
                            <td>
                                <img class="product-image" src="{{ $item['image_full_url'] }}"
                                     onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                                     alt="{{$item->name}} image"
                                     onclick="showImagePreview(this.src, '{{$item->name}}')">
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{Str::limit($item['name'],20,'...')}}
                                </span>
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{$item['barcode'] ?? 'N/A'}}
                                </span>
                            </td>
                            <td>
                                <span class="mrp-badge">
                                    ₹{{number_format($item['price'], 2)}}
                                </span>
                            </td>
                         <td>
                                <span class="d-block font-size-sm text-body">
                                    {{ $item->unit->unit ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-soft-{{$item['barcode'] ? 'success' : 'danger'}}">
                                    {{$item['barcode'] ? translate('messages.has_barcode') : translate('messages.no_barcode')}}
                                </span>
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{$item->store ? $item->store->name : 'N/A'}}
                                </span>
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn btn-sm btn--primary btn-outline-primary action-btn edit-item"
                                        href="javascript:"
                                        data-id="{{$item->id}}"
                                        data-name="{{$item->name}}"
                                        data-price="{{$item->price}}"
                                        data-discount="{{$item->discount}}"
                                        data-discount-type="{{$item->discount_type}}"
                                        data-barcode="{{$item->barcode}}"
                                        data-stock="{{$item->stock}}"
                                        title="{{translate('messages.edit_item')}}">
                                        <i class="tio-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <hr>
            
            <!-- Pagination Area -->
            <div class="pagination-container" id="paginationArea">
                <div class="pagination-loading" id="paginationLoading" style="display: none;">
                    <div class="spinner"></div>
                </div>
                <table>
                    <tfoot class="border-top" id="paginationContent">
                        {!! $items->links() !!}
                    </tfoot>
                </table>
            </div>
            
            <!-- Search Pagination (Initially Hidden) -->
            <div class="search-pagination" id="searchPaginationArea" style="display: none;">
                <div class="pagination-info" id="searchPaginationInfo"></div>
                <div id="searchPaginationContent"></div>
            </div>
            
            @if(count($items) === 0)
            <div class="empty--data">
                <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                <h5>
                    {{translate('no_data_found')}}
                </h5>
            </div>
            @endif
        </div>
        <!-- End Table -->
    </div>
    <!-- End Card -->

    <!-- Image Preview Modal -->
    <div id="imagePreviewModal" class="image-preview-modal">
        <span class="image-preview-close" onclick="closeImagePreview()">&times;</span>
        <img class="image-preview-content" id="previewImage">
    </div>

    <!-- Camera Scanner Modal -->
    <div id="camera-scanner-modal">
        <div id="camera-scanner-container">
            <button class="camera-close-btn" onclick="closeCameraScanner()">
                <i class="tio-clear"></i> {{translate('messages.close')}}
            </button>
            
            <!-- Format selector for better control -->
            <div class="format-selector">
                <select id="barcode-format-selector">
                    <option value="auto">Auto-Detect All</option>
                    <option value="ean">EAN (Products)</option>
                    <option value="code128">Code 128</option>
                    <option value="code39">Code 39</option>
                    <option value="upc">UPC</option>
                </select>
            </div>
            
            <div id="detected-barcode" class="detected-barcode" style="display: none;"></div>
            <div id="accuracy-indicator" class="accuracy-indicator" style="display: none;">
                <span id="scanner-type">ZXing Scanner</span>
                <span class="scanner-type-badge">High Accuracy</span>
            </div>
            
            <div id="interactive" class="viewport">
                <video id="scanner-video" style="width: 100%; height: 100%;"></video>
                <canvas id="scanner-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></canvas>
                <div class="scanner-overlay">
                    <div class="scanner-corners">
                        <div class="scanner-corner-left"></div>
                    </div>
                    <div class="scanner-line"></div>
                </div>
                <div id="detection-box" class="detection-box"></div>
            </div>
            
            <div id="camera-permission" class="camera-permission-msg" style="display: none;">
                <h4><i class="tio-info-outined"></i> {{translate('messages.camera_permission_required')}}</h4>
                <p>{{translate('messages.please_allow_camera_access_to_scan_barcodes')}}</p>
                <button class="camera-btn" onclick="requestCameraPermission()">
                    <i class="tio-checkmark-circle"></i> {{translate('messages.allow_camera')}}
                </button>
            </div>
            
            <div class="camera-controls">
                <div class="camera-status">
                    <i class="tio-camera"></i> <span id="scanner-status">{{translate('messages.initializing_camera')}}</span>
                </div>
                <div>
                    <button class="camera-btn" id="switchCameraBtn" style="display: none;">
                        <i class="tio-refresh"></i> {{translate('messages.switch_camera')}}
                    </button>
                    <button class="camera-btn" id="toggleTorchBtn" style="display: none;">
                        <i class="tio-bulb"></i> {{translate('messages.flash')}}
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Add this modal for SERP suggestions --}}
                                        {{-- Updated SERP Suggestions Modal with Manual Search --}}
                                        <div class="modal fade" id="serpSuggestionsModal" tabindex="-1" role="dialog" aria-labelledby="serpSuggestionsModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-xl" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="serpSuggestionsModalLabel">
                                                            <i class="tio-search"></i> {{translate('messages.product_suggestions')}}
                                                            <span class="performance-badge">Fast</span>
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-info">
                                                            <i class="tio-info-outined"></i>
                                                            <span id="serpSearchInfo"></span>
                                                        </div>
                                                        
                                                        {{-- ✅ NEW: Manual Search Section --}}
                                                        <div class="card mb-3 bg-light">
                                                            <div class="card-body">
                                                                <h6 class="mb-3">
                                                                    <i class="tio-search"></i> {{translate('messages.manual_search')}}
                                                                    <small class="text-muted">- {{translate('messages.cant_find_product_search_manually')}}</small>
                                                                </h6>
                                                                <div class="row">
                                                                    <div class="col-md-8">
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text">
                                                                                    <i class="tio-search"></i>
                                                                                </span>
                                                                            </div>
                                                                            <input type="text"
                                                                                   id="manualSearchInput"
                                                                                   class="form-control"
                                                                                   placeholder="{{translate('messages.search_by_product_name')}}"
                                                                                   autocomplete="off">
                                                                            <div class="input-group-append">
                                                                                <button type="button"
                                                                                        class="btn btn-primary"
                                                                                        id="manualSearchBtn">
                                                                                    <i class="tio-search"></i> {{translate('messages.search')}}
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                        <small class="text-muted">{{translate('messages.search_products_in_selected_store')}}</small>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <button type="button"
                                                                                class="btn btn-outline-secondary btn-block"
                                                                                id="resetSuggestionsBtn">
                                                                            <i class="tio-refresh"></i> {{translate('messages.show_original_suggestions')}}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        {{-- SERP Data Preview --}}
                                                        <div class="card mb-3" id="serpDataCard" style="display: none;">
                                                            <div class="card-header">
                                                                <h6 class="mb-0">
                                                                    <i class="tio-globe"></i> {{translate('messages.search_result_from_google')}}
                                                                    <span class="performance-badge">3s timeout</span>
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="col-md-3" id="serpThumbnailContainer">
                                                                        <img id="serpThumbnail" class="img-fluid rounded" src="" alt="Product image">
                                                                    </div>
                                                                    <div class="col-md-9">
                                                                        <h5 id="serpTitle"></h5>
                                                                        <p id="serpSnippet" class="text-muted"></p>
                                                                        <a id="serpLink" href="#" target="_blank" class="small">
                                                                            <i class="tio-link"></i> {{translate('messages.view_source')}}
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        {{-- Suggested Items Table --}}
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered">
                                                                <thead class="thead-light">
                                                                    <tr>
                                                                        <th width="80">{{translate('messages.image')}}</th>
                                                                        <th>{{translate('messages.name')}}</th>
                                                                        <th width="100">{{translate('messages.mrp')}}</th>
                                                                        <th width="120">{{translate('messages.status')}}</th>
                                                                        <th width="150">{{translate('messages.store')}}</th>
                                                                        <th width="100">{{translate('messages.action')}}</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="suggestedItemsBody">
                                                                    {{-- Will be populated by JavaScript --}}
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        
                                                        <div id="noSuggestions" class="text-center py-4" style="display: none;">
                                                            <i class="tio-clear-circle-outlined text-muted" style="font-size: 3rem;"></i>
                                                            <p class="text-muted mt-2">{{translate('messages.no_matching_products_found')}}</p>
                                                        </div>
                                                        
                                                        {{-- ✅ NEW: Loading indicator for manual search --}}
                                                        <div id="manualSearchLoading" class="text-center py-4" style="display: none;">
                                                            <div class="spinner-border text-primary" role="status">
                                                                <span class="sr-only">Loading...</span>
                                                            </div>
                                                            <p class="text-muted mt-2">{{translate('messages.searching')}}</p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <span class="text-muted mr-auto" id="suggestionsCount"></span>
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                            <i class="tio-clear"></i> {{translate('messages.close')}}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="tio-edit"></i> {{translate('messages.edit_item')}}
                        <span class="badge badge-info ml-2" id="modalItemId"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editItemId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editBarcode">
                                        <i class="tio-barcode"></i> {{translate('messages.barcode')}}
                                    </label>
                                    <input type="text" class="form-control" id="editBarcode" name="barcode"
                                           placeholder="{{translate('messages.scan_or_enter_barcode')}}">
                                    <small class="text-muted">{{translate('messages.barcode_help_text')}}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editName">{{translate('messages.name')}}</label>
                                    <input type="text" class="form-control" id="editName" name="name" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editPrice">{{translate('messages.mrp')}} / {{translate('messages.price')}}</label>
                                    <input type="number" step="0.01" class="form-control" id="editPrice" name="price" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editDiscountType">{{translate('messages.discount_type')}}</label>
                                    <select class="form-control" id="editDiscountType" name="discount_type">
                                        <option value="amount">{{translate('messages.amount')}}</option>
                                        <option value="percent">{{translate('messages.percent')}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editPrice">{{translate('messages.rack')}}</label>
                                    <input type="text"  class="form-control" id="rack" name="rack" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editDiscountType">{{translate('messages.row')}}</label>
                                    <input type="text"  class="form-control" id="row" name="row" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editDiscount">{{translate('messages.discount')}}</label>
                                    <input type="number" step="0.01" class="form-control" id="editDiscount" name="discount" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group stock-input-wrapper">
                                    <label for="editStock">
                                        <i class="tio-archive"></i> {{translate('messages.stock')}}
                                    </label>
                                    <input type="number" class="form-control" id="editStock" name="stock" required>
                                    
                                    <!-- Quick Stock Buttons -->
                                    <div class="stock-controls">
                                        <small class="text-muted">{{translate('messages.quick_adjust')}}:</small>
                                        <div class="stock-buttons">
                                            <button type="button" class="btn btn-sm btn-outline-success stock-btn" data-value="5">+5</button>
                                            <button type="button" class="btn btn-sm btn-outline-success stock-btn" data-value="10">+10</button>
                                            <button type="button" class="btn btn-sm btn-outline-success stock-btn" data-value="20">+20</button>
                                            <button type="button" class="btn btn-sm btn-outline-success stock-btn" data-value="50">+50</button>
                                            <button type="button" class="btn btn-sm btn-outline-warning stock-btn" data-value="-5">-5</button>
                                            <button type="button" class="btn btn-sm btn-outline-warning stock-btn" data-value="-10">-10</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger stock-btn" data-value="0">{{translate('messages.reset')}}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="tio-clear"></i> {{translate('messages.close')}}
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveChangesBtn">
                            <i class="tio-save"></i> {{translate('messages.save_changes')}}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        "use strict";
                                        
                            let isLoading = false; // Add this line at the very top of your script section
        
        let isSearchActive = false;
        let scannerTimeout = null;
        let barcodeBuffer = '';
        let currentSearchPage = 1;
        let currentSearchData = {};
        let currentFilters = {
            category_id: $('#category').val() || 'all',
            sub_category_id: $('#sub-categories').val() || 'all',
            store_id: $('#store_filter').val() || 'all'
        };
        
        // Performance optimization variables
        let searchRequestTimeout = null;
        let fastModeEnabled = true;
        
        $(document).on('ready', function () {
            console.log('Initializing optimized barcode scanner with fast mode...');
            
            // INITIALIZATION OF DATATABLES
            let datatable = $.HSCore.components.HSDatatables.init($('#datatable'), {
                select: {
                    style: 'multi',
                    classMap: {
                        checkAll: '#datatableCheckAll',
                        counter: '#datatableCounter',
                        counterInfo: '#datatableCounterInfo'
                    }
                },
                language: {
                    zeroRecords: '<div class="text-center p-4">' +
                        '<img class="w-7rem mb-3" src="{{asset('public/assets/admin/svg/illustrations/sorry.svg')}}" alt="Image Description">' +
                        '</div>'
                }
            });

            // Clear search on mouseup if empty
            $('#datatableSearch').on('mouseup', function (e) {
                let $input = $(this),
                    oldValue = $input.val();

                if (oldValue == "") return;

                setTimeout(function(){
                    let newValue = $input.val();

                    if (newValue == ""){
                        datatable.search('').draw();
                    }
                }, 1);
            });

            // INITIALIZATION OF SELECT2
            $('.js-select2-custom').each(function () {
                let select2 = $.HSCore.components.HSSelect2.init($(this));
            });

            // Category select2
            $('#category').select2({
                ajax: {
                    url: '{{route("admin.category.get-all")}}',
                    data: function (params) {
                        return {
                            q: params.term,
                            all:true,
                            page: params.page
                        };
                    },
                    processResults: function (data) {
                        return {
                        results: data
                        };
                    },
                    __port: function (params, success, failure) {
                        let $request = $.ajax(params);
                        $request.then(success);
                        $request.fail(failure);
                        return $request;
                    }
                }
            });

            // OPTIMIZED STORE SELECTION FOR BARCODE SCANNER
            $('#scannerStoreSelect').on('change', function() {
                // Show loading state with reduced delay
                $(this).addClass('select-loading');
                
                let storeId = $(this).val();
                let storeName = $(this).find('option:selected').text();
                
                setTimeout(function() {
                    $('#scannerStoreSelect').removeClass('select-loading');
                    
                    if(storeId) {
                        // Enable barcode scanner and camera button
                        $('#barcodeScanner').prop('disabled', false)
                                           .attr('placeholder', '{{translate("messages.scan_or_type_barcode")}}')
                                           .focus();
                        $('#clearBarcode, #manualSearch, #cameraScanBtn').prop('disabled', false);
                        
                        // Show selected store info
                        $('#selectedStoreName').text(storeName);
                        $('#selectedStoreInfo').removeClass('d-none');
                        $('#scannerHelpText').text('{{translate("messages.scan_barcode_or_type_manually")}}');
                        $('#fastSearchIndicator').show(); // Show fast mode indicator
                        
                        updateScannerStatus('ready', 'Ready - ' + storeName + ' (Fast Mode)');
                    } else {
                        // Disable barcode scanner and camera button
                        $('#barcodeScanner').prop('disabled', true)
                                           .attr('placeholder', '{{translate("messages.first_select_store_then_scan")}}')
                                           .val('');
                        $('#clearBarcode, #manualSearch, #cameraScanBtn').prop('disabled', true);
                        
                        // Hide selected store info
                        $('#selectedStoreInfo').addClass('d-none');
                        $('#scannerHelpText').text('{{translate("messages.please_select_store_first")}}');
                        $('#fastSearchIndicator').hide();
                        
                        updateScannerStatus('ready', 'Ready');
                        clearBarcodeStatus();
                    }
                }, 200); // Reduced from 500ms to 200ms
            });

            // OPTIMIZED BARCODE SCANNER FUNCTIONALITY
            $('#barcodeScanner').on('input', function() {
                // Check if store is selected first
                if(!$('#scannerStoreSelect').val()) {
                    toastr.warning('{{translate("messages.please_select_store_first")}}');
                    $(this).val('');
                    return;
                }
                
                let barcode = $(this).val().trim();
                
                // Clear previous timeout for performance
                if (scannerTimeout) {
                    clearTimeout(scannerTimeout);
                }
                
                if(barcode.length > 0) {
                    $(this).addClass('form-control-loading');
                    updateScannerStatus('scanning', 'Fast Scanning...');
                    
                    // Reduced timeout for faster response
                    scannerTimeout = setTimeout(function() {
                        $('#barcodeScanner').removeClass('form-control-loading');
                        if(barcode.length >= 6) { // Reduced minimum barcode length from 8 to 6
                            searchByBarcodeOptimized(barcode);
                        }
                    }, 300); // Reduced from 500ms to 300ms
                } else {
                    $(this).removeClass('form-control-loading');
                    let storeName = $('#scannerStoreSelect option:selected').text();
                    updateScannerStatus('ready', storeName ? 'Ready - ' + storeName + ' (Fast)' : 'Ready');
                }
            });

            // Optimized Enter key handler
            $('#barcodeScanner').on('keypress', function(e) {
                if(e.which === 13 || e.keyCode === 13) {
                    e.preventDefault();
                    
                    if(!$('#scannerStoreSelect').val()) {
                        toastr.warning('{{translate("messages.please_select_store_first")}}');
                        return;
                    }
                    
                    let barcode = $(this).val().trim();
                    if(barcode.length >= 6) { // Reduced from 8
                        searchByBarcodeOptimized(barcode);
                    } else {
                        toastr.warning('{{translate("messages.barcode_too_short")}}');
                    }
                }
            });

            // Optimized manual search button
            $('#manualSearch').on('click', function() {
                if(!$('#scannerStoreSelect').val()) {
                    toastr.warning('{{translate("messages.please_select_store_first")}}');
                    return;
                }
                
                $(this).addClass('btn-loading');
                
                let barcode = $('#barcodeScanner').val().trim();
                if(barcode.length > 0) {
                    searchByBarcodeOptimized(barcode);
                } else {
                    toastr.warning('{{translate("messages.please_enter_barcode")}}');
                    $(this).removeClass('btn-loading');
                }
            });

            // Clear barcode button with faster response
            $('#clearBarcode').on('click', function() {
                $(this).addClass('btn-loading');
                
                setTimeout(function() {
                    $('#barcodeScanner').val('').focus();
                    let storeName = $('#scannerStoreSelect option:selected').text();
                    updateScannerStatus('ready', storeName ? 'Ready - ' + storeName + ' (Fast)' : 'Ready');
                    clearBarcodeStatus();
                    $('#clearBarcode').removeClass('btn-loading');
                }, 100); // Reduced from 300ms to 100ms
            });

            // OPTIMIZED BARCODE SEARCH FUNCTION WITH PERFORMANCE IMPROVEMENTS
            function searchByBarcodeOptimized(barcode) {
                let storeId = $('#scannerStoreSelect').val();
                
                if(!storeId) {
                    toastr.error('{{translate("messages.please_select_store_first")}}');
                    $('#manualSearch').removeClass('btn-loading');
                    return;
                }
                
                // Performance tracking
                let startTime = performance.now();
                
                $('#barcodeScanner').addClass('form-control-loading');
                updateScannerStatus('searching', 'Fast Search...');
                
                console.log('Fast searching for barcode:', barcode, 'in store:', storeId);
                
                let token = $('meta[name="csrf-token"]').attr('content');
                if (!token) {
                    console.error('CSRF token not found');
                    toastr.error('CSRF token missing');
                    return;
                }
                
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': token
                    }
                });
                
                // Cancel any previous request
                if (searchRequestTimeout) {
                    clearTimeout(searchRequestTimeout);
                }
                
                $.ajax({
                    url: '{{route("admin.item.regular_items.search_with_serp")}}',
                    type: 'POST',
                    timeout: 10000, // 10 second timeout instead of waiting forever
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    data: {
                        barcode: barcode,
                        store_id: storeId,
                        _token: token,
                        fast_mode: true // Indicate this is a fast mode request
                    },
                    beforeSend: function(xhr) {
                        console.log('Fast mode request initiated for barcode:', barcode);
                    },
                    success: function(response) {
                        let endTime = performance.now();
                        console.log('Fast search completed in:', (endTime - startTime).toFixed(2), 'ms');
                        
                        if(response.found) {
                            updateBarcodeStatus('success', 'Found!');
                            updateScannerStatus('found', 'Product Found (Fast)');
                            
                            openEditModal(response.item);
                            
                            setTimeout(function() {
                                $('#barcodeScanner').val('');
                            }, 1000);
                            
                        } else {
                            updateBarcodeStatus('warning', 'Not found');
                            updateScannerStatus('not_found', 'Not Found - Showing Options');
                            
                            // Show suggestions immediately without waiting
                            if(response.suggested_items && response.suggested_items.length > 0) {
                                console.log('Showing fast suggestions modal');
                                showSerpSuggestionsOptimized({
                                    search_query: barcode,
                                    message: response.message || 'Product not found, showing available options',
                                    suggested_items: response.suggested_items,
                                    serp_data: response.serp_data || null
                                });
                            } else {
                                toastr.info('Product not found with barcode "' + barcode + '" in selected store');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        let endTime = performance.now();
                        console.error('Fast search failed in:', (endTime - startTime).toFixed(2), 'ms');
                        
                        updateBarcodeStatus('error', 'Error');
                        updateScannerStatus('error', 'Search Error');
                        
                        let errorMessage = 'Fast barcode search failed';
                        
                        if(status === 'timeout') {
                            errorMessage = 'Search timed out. Trying offline suggestions...';
                            // Try to get local suggestions as fallback
                            searchSimilarProductsFast(barcode, storeId);
                        } else if(xhr.status === 419) {
                            errorMessage = 'Session expired. Please refresh the page.';
                        } else if(xhr.status === 404) {
                            errorMessage = 'Search service not available';
                        } else if(xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        $('#barcodeScanner').removeClass('form-control-loading');
                        $('#manualSearch').removeClass('btn-loading');
                        
                        setTimeout(function() {
                            let storeName = $('#scannerStoreSelect option:selected').text();
                            updateScannerStatus('ready', storeName ? 'Ready - ' + storeName + ' (Fast)' : 'Ready');
                            clearBarcodeStatus();
                        }, 2000); // Reduced from 3000ms
                    }
                });
            }

            // Fast fallback search for offline suggestions
            function searchSimilarProductsFast(barcode, storeId) {
                console.log('Fast fallback search for barcode:', barcode);
                
                $.ajax({
                    url: '{{route("admin.item.regular_items.search")}}',
                    type: 'POST',
                    timeout: 5000, // 5 second timeout
                    data: {
                        search: '',
                        store_id: storeId,
                        limit: 8, // Reduced limit for faster response
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if(response.items && response.items.length > 0) {
                            showSerpSuggestionsOptimized({
                                search_query: barcode,
                                message: 'Search timed out. Here are available products in selected store:',
                                suggested_items: response.items.slice(0, 6), // Show only 6 items
                                serp_data: null
                            });
                        } else {
                            toastr.info('No products found in the selected store.');
                        }
                    },
                    error: function() {
                        toastr.error('Unable to load product suggestions.');
                    }
                });
            }

            // Optimized SERP suggestions modal
            function showSerpSuggestionsOptimized(response) {
                console.log('Fast SERP suggestions loading...');
                
                // ✅ Store barcode properly
                   currentBarcode = response.search_query || '';
                   
                   // ✅ CRITICAL: Verify barcode is not empty
                   if (!currentBarcode) {
                       console.error('No barcode in response!', response);
                       toastr.error('Barcode information is missing');
                       return;
                   }
                   
                
                // Update modal with minimal DOM manipulation for speed
                $('#serpSearchInfo').html(`
                    <strong>Barcode: ${response.search_query}</strong><br>
                    <small>${response.message}</small>
                `);
                
                // Handle SERP data efficiently
                if(response.serp_data && response.serp_data.title) {
                    $('#serpDataCard').show();
                    $('#serpTitle').text(response.serp_data.title || '');
                    $('#serpSnippet').text(response.serp_data.snippet || '');
                    $('#serpLink').attr('href', response.serp_data.link || '#');
                    
                    if(response.serp_data.thumbnail) {
                        $('#serpThumbnail').attr('src', response.serp_data.thumbnail);
                        $('#serpThumbnailContainer').show();
                    } else {
                        $('#serpThumbnailContainer').hide();
                    }
                } else {
                    $('#serpDataCard').hide();
                }
                
                // Build suggestions HTML efficiently using array join
// Build suggestions HTML efficiently using array join
let suggestionsHtml = [];

if(response.suggested_items && response.suggested_items.length > 0) {
    response.suggested_items.forEach(function(item) {
        let imageUrl = item.image_full_url || '{{asset('public/assets/admin/img/160x160/img2.jpg')}}';
        let mrpDisplay = item.price ? `₹${parseFloat(item.price).toFixed(2)}` : 'N/A';
        let statusClass = item.barcode ? 'success' : 'danger';
        let statusText = item.barcode ? '{{translate('messages.has_barcode')}}' : '{{translate('messages.no_barcode')}}';
        let storeName = item.store && item.store.name ? item.store.name : 'N/A'; // ✅ Extract store name
        
        suggestionsHtml.push(`
        <tr>
            <td>
                <img src="${imageUrl}"
                    class="product-image-small"
                    onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                    alt="${item.name || 'Product'}" loading="lazy">
            </td>
            <td>${(item.name || 'N/A').substring(0, 30)}${item.name && item.name.length > 30 ? '...' : ''}</td>
            <td class="mrp-column">${mrpDisplay}</td>
            <td>
                <span class="badge badge-soft-${statusClass}">${statusText}</span>
            </td>
            <td>${storeName}</td>
            <td>
                <button class="btn btn-sm btn-primary assign-barcode-btn"
                        data-item-id="${item.id}"
                        data-item-name="${(item.name || 'Unknown').replace(/"/g, '&quot;')}"
                        data-barcode="${response.search_query}">
                    <i class="tio-barcode"></i> Assign
                </button>
            </td>
        </tr>
        `);
    });
    
    $('#suggestedItemsBody').html(suggestionsHtml.join(''));
    $('#noSuggestions').hide();
} else {
    $('#suggestedItemsBody').html('');
    $('#noSuggestions').show();
}
                
                // Show modal efficiently
                try {
                    $('#serpSuggestionsModal').modal('show');
                } catch(error) {
                    console.error('Modal error:', error);
                    // Fallback method
                    $('#serpSuggestionsModal').addClass('show').css('display', 'block');
                    $('body').addClass('modal-open');
                    if($('.modal-backdrop').length === 0) {
                        $('body').append('<div class="modal-backdrop fade show"></div>');
                    }
                }
            }

            // Function to update scanner status
            function updateScannerStatus(type, text) {
                let $status = $('#scannerStatus');
                $status.removeClass('badge-success badge-warning badge-danger badge-info')
                       .addClass('badge-' + getScannerBadgeClass(type))
                       .text(text);
                
                if(type === 'scanning' || type === 'searching') {
                    $status.addClass('scanning-indicator');
                } else {
                    $status.removeClass('scanning-indicator');
                }
            }

            // Function to update barcode status
            function updateBarcodeStatus(type, text) {
                let icon = type === 'success' ? 'tio-checkmark-circle' :
                          type === 'warning' ? 'tio-info-outined' : 'tio-clear-circle';
                let colorClass = type === 'success' ? 'text-success' :
                               type === 'warning' ? 'text-warning' : 'text-danger';
                
                $('#barcodeStatus').html(`<i class="${icon} ${colorClass}"></i>`);
            }

            function clearBarcodeStatus() {
                $('#barcodeStatus').html('');
            }

            function getScannerBadgeClass(type) {
                switch(type) {
                    case 'ready': return 'success';
                    case 'scanning':
                    case 'searching': return 'info';
                    case 'found': return 'success';
                    case 'not_found': return 'warning';
                    case 'error': return 'danger';
                    default: return 'secondary';
                }
            }

            // Function to open edit modal with item data
            function openEditModal(item) {
                $('#editItemId').val(item.id);
                $('#editName').val(item.name);
                $('#editPrice').val(item.price);
                $('#editDiscount').val(item.discount);
                $('#editDiscountType').val(item.discount_type);
                $('#editBarcode').val(item.barcode || '');
                $('#editStock').val(item.stock);
                $('#modalItemId').text('#' + item.id);

                $('#editModal').modal('show');
            }

            // USE EVENT DELEGATION FOR EDIT ITEMS
            $(document).on('click', '.edit-item', function() {
                var itemId = $(this).data('id');
                var itemName = $(this).data('name');
                var itemPrice = $(this).data('price');
                var itemDiscount = $(this).data('discount');
                var itemDiscountType = $(this).data('discount-type');
                var itemBarcode = $(this).data('barcode');
                var itemStock = $(this).data('stock');

                openEditModal({
                    id: itemId,
                    name: itemName,
                    price: itemPrice,
                    discount: itemDiscount,
                    discount_type: itemDiscountType,
                    barcode: itemBarcode,
                    stock: itemStock
                });
            });

            // STOCK QUICK ADJUSTMENT BUTTONS
            $(document).on('click', '.stock-btn', function() {
                $(this).addClass('btn-loading');
                
                let value = parseInt($(this).data('value'));
                let $stockInput = $('#editStock');
                let currentStock = parseInt($stockInput.val()) || 0;
                
                setTimeout(function() {
                    if(value === 0) {
                        $stockInput.val(0);
                    } else {
                        let newStock = Math.max(0, currentStock + value);
                        $stockInput.val(newStock);
                    }
                    
                    $stockInput.addClass('border-primary');
                    setTimeout(function() {
                        $stockInput.removeClass('border-primary');
                    }, 300);
                    
                    $('.stock-btn').removeClass('btn-loading');
                }, 100); // Faster response
            });

            // Set focus to barcode field after modal is shown
            $('#editModal').on('shown.bs.modal', function () {
                $('#editBarcode').focus().select();
            });

            // Auto-focus barcode scanner when modal is closed
            $('#editModal').on('hidden.bs.modal', function () {
                setTimeout(function() {
                    $('#barcodeScanner').focus();
                }, 100); // Faster focus
            });

            // Optimized form submission
            $('#editForm').on('submit', function(e) {
                e.preventDefault();
                
                let $submitBtn = $('#saveChangesBtn');
                let originalText = $submitBtn.html();
                
                $submitBtn.prop('disabled', true).addClass('btn-loading').html('<i class="tio-sync"></i> Saving...');
                
                $.ajax({
                    url: '{{ route("admin.item.regular_items.update") }}',
                    type: 'POST',
                    timeout: 10000, // 10 second timeout
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#editModal').modal('hide');
                        
                        toastr.success(response.success || '{{translate("messages.item_updated_successfully")}}');
                        
                        // Refresh efficiently
                        if(isSearchActive) {
                            performSearch(currentSearchData, currentSearchPage);
                        } else {
                            loadFilteredData();
                        }
                    },
                    error: function(xhr) {
                        console.error('Update error:', xhr);
                        
                        if(xhr.responseJSON && xhr.responseJSON.errors) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                toastr.error(Array.isArray(value) ? value[0] : value);
                            });
                        } else {
                            toastr.error(xhr.responseJSON?.message || '{{translate("messages.something_went_wrong")}}');
                        }
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).removeClass('btn-loading').html(originalText);
                    }
                });
            });

            // Optimized Filter handling
            $('.set-filter').on('change', function() {
                $(this).addClass('select-loading');
                showLoading();
                
                let filter = $(this).data('filter');
                let value = $(this).val();
                
                currentFilters[filter] = value;
                
                loadFilteredData();
                
                setTimeout(function() {
                    $('.set-filter').removeClass('select-loading');
                }, 200); // Faster loading indicator
            });

            function loadFilteredData(page = 1) {
                $.ajax({
                    url: '{{route("admin.item.regular_items.search")}}',
                    type: 'POST',
                    timeout: 8000, // 8 second timeout
                    data: {
                        category_id: currentFilters.category_id,
                        sub_category_id: currentFilters.sub_category_id,
                        store_id: currentFilters.store_id,
                        page: page,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        renderSearchResults(data);
                        renderSearchPagination(data);
                        hideLoading();
                        
                        $('#itemCount').text(data.total || 0);
                        $('#searchResultCount').text(`${data.total || 0} {{translate('messages.products')}}`);
                    },
                    error: function(xhr) {
                        console.error('Filter error:', xhr);
                        toastr.error('{{translate("messages.filter_failed")}}');
                        hideLoading();
                    }
                });
            }

            // Clear search functionality - optimized
            $('#clearSearch').on('click', function() {
                $(this).addClass('btn-loading');
                
                $('#datatableSearch').val('');
                clearSearchResults();
                
                setTimeout(function() {
                    loadFilteredData();
                    $('#clearSearch').removeClass('btn-loading');
                }, 100); // Much faster
            });

            // Enhanced search form submission - optimized
            $('#search-form').on('submit', function (e) {
                e.preventDefault();
                
                let searchTerm = $('#datatableSearch').val().trim();
                
                if (searchTerm.length === 0) {
                    toastr.warning('{{translate("messages.please_enter_search_term")}}');
                    return;
                }
                
                $('#searchSubmitBtn').addClass('btn-loading');
                $('#datatableSearch').addClass('form-control-loading');
                showLoading();
                
                let activeFilters = [];
                $('.quick-filter.active').each(function() {
                    activeFilters.push($(this).data('filter'));
                });
                
                let searchData = {
                    search: searchTerm,
                    category_id: currentFilters.category_id,
                    sub_category_id: currentFilters.sub_category_id,
                    store_id: currentFilters.store_id,
                    filters: activeFilters,
                    page: 1,
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                currentSearchData = searchData;
                currentSearchPage = 1;
                
                performSearch(searchData, 1);
            });

            // Optimized search function
            function performSearch(searchData, page = 1) {
                searchData.page = page;
                
                $.ajax({
                    url: '{{route("admin.item.regular_items.search")}}',
                    type: 'POST',
                    timeout: 8000, // 8 second timeout
                    data: searchData,
                    success: function (data) {
                        if(data.items && Array.isArray(data.items)) {
                            renderSearchResults(data);
                            showSearchInfo(data.total, searchData.filters, searchData.search);
                            renderSearchPagination(data);
                            isSearchActive = true;
                            
                            $('#paginationArea').hide();
                            $('#searchPaginationArea').show();
                            
                            if(page > 1) {
                                $('html, body').animate({
                                    scrollTop: $('.table-container').offset().top - 100
                                }, 300); // Faster scroll
                            }
                        } else {
                            toastr.error('{{translate("messages.invalid_response_format")}}');
                        }
                    },
                    error: function(xhr) {
                        console.error('Search error:', xhr);
                        toastr.error('{{translate("messages.search_failed")}}');
                    },
                    complete: function () {
                        hideLoading();
                        $('#searchSubmitBtn').removeClass('btn-loading');
                        $('#datatableSearch').removeClass('form-control-loading');
                    }
                });
            }

            // Handle search pagination clicks
            $(document).on('click', '.search-pagination .pagination a', function(e) {
                e.preventDefault();
                
                let url = $(this).attr('href');
                let urlParams = new URLSearchParams(url.split('?')[1]);
                let page = urlParams.get('page') || 1;
                
                $('#paginationLoading').show();
                
                currentSearchPage = parseInt(page);
                performSearch(currentSearchData, page);
            });

            // Optimized search pagination rendering
            function renderSearchPagination(data) {
                if(data.pagination) {
                    let paginationInfo = `Showing ${data.from} to ${data.to} of ${data.total} results`;
                    $('#searchPaginationInfo').text(paginationInfo);
                    
                    let paginationHtml = '';
                    if(data.pagination.links) {
                        paginationHtml = '<nav><ul class="pagination">';
                        
                        data.pagination.links.forEach(function(link) {
                            let activeClass = link.active ? 'active' : '';
                            let disabledClass = !link.url ? 'disabled' : '';
                            
                            paginationHtml += `
                                <li class="page-item ${activeClass} ${disabledClass}">
                                    <a class="page-link" href="${link.url || '#'}" ${!link.url ? 'tabindex="-1"' : ''}>
                                        ${link.label}
                                    </a>
                                </li>
                            `;
                        });
                        
                        paginationHtml += '</ul></nav>';
                    }
                    
                    $('#searchPaginationContent').html(paginationHtml);
                } else {
                    $('#searchPaginationArea').hide();
                }
                
                $('#paginationLoading').hide();
            }

            // OPTIMIZED QUICK FILTER FUNCTIONALITY
            $('.quick-filter').on('click', function() {
                let $this = $(this);
                
                $this.addClass('btn-loading');
                
                setTimeout(function() {
                    $this.toggleClass('active').removeClass('btn-loading');
                    
                    let activeFilters = [];
                    $('.quick-filter.active').each(function() {
                        activeFilters.push($(this).data('filter'));
                    });
                    
                    performFilteredSearch(activeFilters);
                }, 100); // Much faster response
            });

            // Clear all filters - optimized
            $('#clearAllFilters').on('click', function() {
                $(this).addClass('btn-loading');
                
                setTimeout(function() {
                    $('.quick-filter').removeClass('active');
                    $('#datatableSearch').val('');
                    clearSearchResults();
                    
                    currentFilters = {
                        category_id: 'all',
                        sub_category_id: 'all',
                        store_id: 'all'
                    };
                    
                    $('#category').val('all').trigger('change');
                    $('#sub-categories').val('all').trigger('change');
                    $('#store_filter').val('all').trigger('change');
                    
                    loadFilteredData();
                    
                    $('#clearAllFilters').removeClass('btn-loading');
                }, 100);
            });

            // Optimized search with faster debouncing
            let searchTimeout;
            $('#datatableSearch').on('input', function() {
                let value = $(this).val().trim();
                clearTimeout(searchTimeout);
                
                if (value.length > 0) {
                    $(this).addClass('form-control-loading');
                    searchTimeout = setTimeout(function() {
                        $('#datatableSearch').removeClass('form-control-loading');
                        if (value.length >= 2) {
                            performQuickSearch(value);
                        }
                    }, 200); // Reduced from 300ms to 200ms
                } else {
                    $(this).removeClass('form-control-loading');
                    clearSearchResults();
                }
            });

            function performFilteredSearch(filters) {
                showLoading();
                
                let searchData = {
                    search: $('#datatableSearch').val(),
                    category_id: currentFilters.category_id,
                    sub_category_id: currentFilters.sub_category_id,
                    store_id: currentFilters.store_id,
                    filters: filters,
                    page: 1,
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                currentSearchData = searchData;
                currentSearchPage = 1;
                
                performSearch(searchData, 1);
            }

            function performQuickSearch(searchTerm) {
                let searchData = {
                    search: searchTerm,
                    category_id: currentFilters.category_id,
                    sub_category_id: currentFilters.sub_category_id,
                    store_id: currentFilters.store_id,
                    page: 1,
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                currentSearchData = searchData;
                currentSearchPage = 1;
                
                performSearch(searchData, 1);
            }

            function showSearchInfo(total, filters, searchTerm) {
                let infoText = `Found ${total} products`;
                
                if (searchTerm) {
                    infoText += ` matching "${searchTerm}"`;
                }
                
                if (filters && filters.length > 0) {
                    let filterText = filters.map(f => {
                        switch(f) {
                            case 'has_barcode': return 'with barcode';
                            case 'no_barcode': return 'without barcode';
                            case 'low_stock': return 'low stock';
                            default: return f;
                        }
                    }).join(', ');
                    infoText += ` (filters: ${filterText})`;
                }
                
                $('#searchInfoText').text(infoText);
                $('#searchInfo').show();
                $('#searchResultCount').text(`${total} {{translate('messages.products')}}`);
            }

            function clearSearchResults() {
                $('#searchInfo').hide();
                $('#searchPaginationArea').hide();
                $('#paginationArea').show();
                $('.quick-filter').removeClass('active');
                $('#searchResultCount').text(`{{$items->total()}} {{translate('messages.products')}}`);
                isSearchActive = false;
                currentSearchData = {};
                currentSearchPage = 1;
            }

            function showLoading() {
                $('#loadingOverlay').show();
            }

            function hideLoading() {
                $('#loadingOverlay').hide();
            }

                function renderSearchResults(data) {
                    let html = '';
                    let searchTerm = $('#datatableSearch').val().trim().toLowerCase();
                    
                    if(data.items.length === 0) {
                        html = `
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="empty--data">
                                        <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="No data" style="width: 100px;">
                                        <h5 class="mt-3">{{translate('no_data_found')}}</h5>
                                        <p class="text-muted">{{translate('messages.try_different_search_term')}}</p>
                                    </div>
                                </td>
                            </tr>
                        `;
                    } else {
                        let htmlArray = [];
                        
                        data.items.forEach(function(item, key) {
                            let highlightedName = item.name;
                            let highlightedBarcode = item.barcode || 'N/A';
                            
                            if (searchTerm && searchTerm.length > 0) {
                                if (item.name.toLowerCase().includes(searchTerm)) {
                                    highlightedName = item.name.replace(new RegExp(`(${searchTerm})`, 'gi'), '<mark>$1</mark>');
                                }
                                if (item.barcode && item.barcode.toLowerCase().includes(searchTerm)) {
                                    highlightedBarcode = item.barcode.replace(new RegExp(`(${searchTerm})`, 'gi'), '<mark>$1</mark>');
                                }
                            }
                            
                            let rowClass = (searchTerm && (
                                item.name.toLowerCase().includes(searchTerm) ||
                                (item.barcode && item.barcode.toLowerCase().includes(searchTerm))
                            )) ? 'search-result-highlight' : '';
                            
                            let mrpDisplay = item.price ? `₹${parseFloat(item.price).toFixed(2)}` : 'N/A';
                            let escapedName = (item.name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                            
                            // ✅ Get unit display
                            let unitDisplay = 'N/A';
                            if (item.unit) {
                                if (typeof item.unit === 'object' && item.unit.unit) {
                                    unitDisplay = item.unit.unit;
                                } else if (typeof item.unit === 'string') {
                                    unitDisplay = item.unit;
                                }
                            }
                            
                            // ✅ Status toggle
                            let statusChecked = item.status == 1 ? 'checked' : '';
                            let statusLabel = item.status == 1 ? '{{translate("messages.active")}}' : '{{translate("messages.inactive")}}';
                            
                            htmlArray.push(`
                            <tr class="${rowClass}">
                                <td>${data.from + key}</td>
                                <td>
                                    <img class="product-image" src="${item.image_full_url || '{{asset('public/assets/admin/img/160x160/img2.jpg')}}'}"
                                        onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                                        alt="${item.name} image" loading="lazy"
                                        onclick="showImagePreview('${item.image_full_url || '{{asset('public/assets/admin/img/160x160/img2.jpg')}}'}', '${escapedName}')">
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        ${item.name && item.name.length > 20 ? highlightedName.substring(0, 20) + '...' : highlightedName || 'N/A'}
                                    </span>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        ${highlightedBarcode}
                                    </span>
                                </td>
                                <td>
                                    <span class="mrp-badge">
                                        ${mrpDisplay}
                                    </span>
                                </td>
                                <td>
                                    <span class="unit-badge">
                                        ${unitDisplay}
                                    </span>
                                </td>
<td>
    <label class="status-toggle" data-item-id="{{$item->id}}">
        <input type="checkbox"
               {{$item->status == 1 ? 'checked' : ''}}
               onchange="toggleItemStatus({{$item->id}}, this.checked)">
        <span class="status-slider"></span>
    </label>
    <span class="status-label">
        {{$item->status == 1 ? translate('messages.active') : translate('messages.inactive')}}
    </span>
</td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        ${item.store && item.store.name ? item.store.name : 'N/A'}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn btn-sm btn--primary btn-outline-primary action-btn edit-item"
                                            href="javascript:"
                                            data-id="${item.id}"
                                            data-name="${item.name || ''}"
                                            data-price="${item.price || 0}"
                                            data-discount="${item.discount || 0}"
                                            data-discount-type="${item.discount_type || 'amount'}"
                                            data-barcode="${item.barcode || ''}"
                                            data-stock="${item.stock || 0}"
                                            title="{{translate('messages.edit_item')}}">
                                            <i class="tio-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            `);
                        });
                        
                        html = htmlArray.join('');
                    }
                    
                    $('#set-rows').html(html);
                    $('#itemCount').html(data.total || 0);
                    
                    $('#set-rows tr').hide().fadeIn(200);
                }




function toggleItemStatus(itemId, isChecked) {
    console.log('Toggle status called:', {itemId: itemId, isChecked: isChecked});
    
    let status = isChecked ? 1 : 0;
    let statusText = isChecked ? '{{translate("messages.active")}}' : '{{translate("messages.inactive")}}';
    
    // Show loading state
    let $toggle = $(`.status-toggle[data-item-id="${itemId}"]`);
    let $slider = $toggle.find('.status-slider');
    let $label = $toggle.closest('td').find('.status-label');
    
    $slider.addClass('loading');
    
    // Get CSRF token
    let csrfToken = $('meta[name="csrf-token"]').attr('content');
    
    console.log('Making AJAX request to:', '{{route("admin.item.toggle-status")}}');
    console.log('Data:', {item_id: itemId, status: status, _token: csrfToken});
    
    $.ajax({
        url: '{{route("admin.item.toggle-status")}}',
        type: 'POST',
        data: {
            item_id: itemId,
            status: status,
            _token: csrfToken
        },
        success: function(response) {
            console.log('Success response:', response);
            
            if(response.success) {
                $label.text(statusText);
                toastr.success(response.message);
            } else {
                console.error('Response success is false:', response);
                // Revert checkbox on failure
                $toggle.find('input').prop('checked', !isChecked);
                toastr.error(response.message || '{{translate("messages.failed_to_update_status")}}');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            // Revert checkbox on error
            $toggle.find('input').prop('checked', !isChecked);
            
            let errorMessage = '{{translate("messages.failed_to_update_status")}}';
            if(xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                try {
                    let errorData = JSON.parse(xhr.responseText);
                    errorMessage = errorData.message || errorMessage;
                } catch(e) {
                    console.error('Could not parse error response');
                }
            }
            toastr.error(errorMessage);
        },
        complete: function() {
            $slider.removeClass('loading');
        }
    });
}
            // Image preview functions - optimized
            window.showImagePreview = function(src, name) {
                $('#previewImage').attr('src', src).attr('alt', name + ' preview');
                $('#imagePreviewModal').fadeIn(200);
            }

            window.closeImagePreview = function() {
                $('#imagePreviewModal').fadeOut(200);
            }

            $(document).on('click', '#imagePreviewModal', function(e) {
                if (e.target === this) {
                    closeImagePreview();
                }
            });

            // Optimized ESC key handler
            $(document).keydown(function(e) {
                if (e.key === "Escape") {
                    if ($('#camera-scanner-modal').is(':visible')) {
                        closeCameraScanner();
                    } else if ($('#imagePreviewModal').is(':visible')) {
                        closeImagePreview();
                    }
                }
            });


            // OPTIMIZED CAMERA BARCODE SCANNER FUNCTIONALITY
            let cameraStream = null;
            let currentCameraIndex = 0;
            let availableCameras = [];
            let scannerIsActive = false;

            $('#cameraScanBtn').on('click', function() {
                if(!$('#scannerStoreSelect').val()) {
                    toastr.warning('{{translate("messages.please_select_store_first")}}');
                    return;
                }
                openCameraScanner();
            });

            window.openCameraScanner = function() {
                $('#camera-scanner-modal').fadeIn(200);
                initQuaggaScanner();
            }

            window.closeCameraScanner = function() {
                if(scannerIsActive) {
                    Quagga.stop();
                    scannerIsActive = false;
                }
                $('#camera-scanner-modal').fadeOut(200);
                $('#detected-barcode').hide();
                $('#scanner-status').text('{{translate("messages.camera_closed")}}');
            }

            window.requestCameraPermission = function() {
                $('#camera-permission').hide();
                initQuaggaScanner();
            }

            // Optimized Quagga scanner initialization
            function initQuaggaScanner() {
                if (scannerIsActive) return;

                $('#scanner-status').text('{{translate("messages.initializing_camera")}}');

                Quagga.init({
                    inputStream: {
                        name: "Live",
                        type: "LiveStream",
                        target: document.querySelector('#interactive'),
                        constraints: {
                            width: 640,
                            height: 480,
                            facingMode: "environment"
                        }
                    },
                    locator: {
                        patchSize: "medium",
                        halfSample: true
                    },
                    numOfWorkers: Math.min(navigator.hardwareConcurrency || 2, 4), // Optimized worker count
                    frequency: 30, // Reduced from 60 for better performance
                    decoder: {
                        readers: [
                            "code_128_reader",
                            "ean_reader",
                            "ean_8_reader",
                            "code_39_reader",
                            "upc_reader",
                            "upc_e_reader"
                        ]
                    },
                    locate: true
                }, function (err) {
                    if (err) {
                        console.error('Quagga initialization error:', err);
                        $('#scanner-status').text('{{translate("messages.camera_error")}}');

                        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                            $('#camera-permission').show();
                            $('#interactive').hide();
                        } else {
                            toastr.error('{{translate("messages.camera_initialization_failed")}}');
                        }
                        return;
                    }

                    console.log("Fast camera scanner ready");
                    Quagga.start();
                    scannerIsActive = true;
                    $('#scanner-status').text('{{translate("messages.scanning_for_barcode")}}');
                    checkAvailableCameras();
                });

                // Optimized barcode detection with better confidence filtering
                let barcodeLock = false;
                Quagga.onDetected(function (result) {
                    if (barcodeLock) return;

                    if (result && result.codeResult && result.codeResult.code) {
                        let code = result.codeResult.code;

                        // Improved confidence check
                        let errors = result.codeResult.decodedCodes
                            .filter(x => x.error !== undefined)
                            .map(x => x.error);
                        let avgError = errors.length
                            ? errors.reduce((a, b) => a + b, 0) / errors.length
                            : 1;

                        if (avgError > 0.12) { // Slightly more lenient for faster scanning
                            return;
                        }

                        barcodeLock = true;

                        if (navigator.vibrate) {
                            navigator.vibrate(100); // Shorter vibration
                        }

                        $('#detected-barcode').text('Barcode: ' + code).fadeIn();
                        $('#scanner-status').text('{{translate("messages.barcode_found")}}');
                        $('#barcodeScanner').val(code);

                        Quagga.stop();
                        scannerIsActive = false;

                        setTimeout(function () {
                            closeCameraScanner();
                            searchByBarcodeOptimized(code);
                        }, 200); // Faster response
                    }
                });

                // Optimized drawing
                Quagga.onProcessed(function (result) {
                    let drawingCtx = Quagga.canvas.ctx.overlay,
                        drawingCanvas = Quagga.canvas.dom.overlay;

                    if (result && result.boxes) {
                        drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
                        
                        // Only draw the main detection box for performance
                        if (result.box) {
                            Quagga.ImageDebug.drawPath(result.box, { x: 0, y: 1 }, drawingCtx, { color: "#00F", lineWidth: 2 });
                        }

                        if (result.codeResult && result.codeResult.code) {
                            Quagga.ImageDebug.drawPath(result.line, { x: 'x', y: 'y' }, drawingCtx, { color: 'red', lineWidth: 3 });
                        }
                    }
                });
            }

            function checkAvailableCameras() {
                if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
                    navigator.mediaDevices.enumerateDevices()
                        .then(function (devices) {
                            availableCameras = devices.filter(device => device.kind === 'videoinput');
                            if (availableCameras.length > 1) {
                                $('#switchCameraBtn').show();
                            }
                        })
                        .catch(function (err) {
                            console.error('Error enumerating devices:', err);
                        });
                }
            }

            // Enhanced modal event handlers for SERP suggestions
            $('#serpSuggestionsModal').on('shown.bs.modal', function () {
                console.log('Fast SERP modal shown');
            });
            
            $('#serpSuggestionsModal').on('hidden.bs.modal', function () {
                $('#suggestedItemsBody').html('');
                $('#serpDataCard').hide();
            });
            
            // Optimized assign barcode button click handler
            $(document).on('click', '.assign-barcode-btn', function() {
                let $btn = $(this);
                let itemId = $btn.data('item-id');
                let itemName = $btn.data('item-name');
                let barcode = $btn.data('barcode');
                
                if(confirm(`Assign barcode "${barcode}" to "${itemName}"?`)) {
                    assignBarcodeOptimized(itemId, barcode, $btn);
                }
            });

function assignBarcodeOptimized(itemId, barcode, $btn) {
    let storeId = $('#scannerStoreSelect').val();
    
    if (!storeId) {
        toastr.error('{{translate("messages.please_select_store_first")}}');
        return;
    }
    
    if (isLoading) {
        console.log('Already loading, skipping...');
        return;
    }
    
    // ✅ Log the data being sent
    console.log('Sending barcode assignment request:', {
        item_id: itemId,
        store_id: storeId,
        barcode: barcode
    });
    
    let originalText = $btn.html();
    isLoading = true;
    
    $.ajax({
        url: '{{route("admin.item.regular_items.assign_barcode")}}',
        type: 'POST',
        timeout: 8000,
        data: {
            item_id: itemId,
            store_id: storeId,
            barcode: barcode,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            $btn.prop('disabled', true).html('<i class="tio-sync"></i> {{translate("messages.assigning")}}');
        },
        success: function(response) {
            console.log('Assignment success:', response);
            
            if(response.success) {
                let successMsg = response.message || '{{translate("messages.barcode_assigned_successfully")}}';
                
                if (response.item) {
                    if (response.item.status_changed) {
                        successMsg += '<br><small>✓ Item status turned ON</small>';
                    }
                    if (response.item.stock_added) {
                        successMsg += `<br><small>✓ Stock increased by ${response.item.stock_added} (New stock: ${response.item.stock})</small>`;
                    }
                }
                
                toastr.success(successMsg, '', {
                    timeOut: 4000,
                    closeButton: true,
                    progressBar: true,
                    escapeHtml: false
                });
                
                $('#serpSuggestionsModal').modal('hide');
                
                setTimeout(function() {
                    if(isSearchActive) {
                        performSearch(currentSearchData, currentSearchPage);
                    } else {
                        loadFilteredData();
                    }
                    
                    $('#barcodeScanner').val('').focus();
                }, 500);
            } else {
                toastr.error(response.message || '{{translate("messages.failed_to_assign_barcode")}}');
            }
        },
        error: function(xhr, status, error) {
            console.error('Assignment error:', {
                status: xhr.status,
                responseJSON: xhr.responseJSON,
                statusText: xhr.statusText,
                error: error
            });
            
            let errorMessage = '{{translate("messages.failed_to_assign_barcode")}}';
            
            if(xhr.responseJSON) {
                if(xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                // ✅ Show validation errors if present
                if(xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    console.error('Validation errors:', errors);
                    
                    let errorList = [];
                    Object.keys(errors).forEach(function(key) {
                        if (Array.isArray(errors[key])) {
                            errorList = errorList.concat(errors[key]);
                        } else {
                            errorList.push(errors[key]);
                        }
                    });
                    
                    errorMessage = errorList.join('<br>');
                }
            }
            
            toastr.error(errorMessage, '', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000,
                escapeHtml: false
            });
        },
        complete: function() {
            isLoading = false;
            $btn.prop('disabled', false).html(originalText);
        }
    });
}


            // Switch camera - optimized
            $('#switchCameraBtn').on('click', function () {
                if (availableCameras.length > 1) {
                    currentCameraIndex = (currentCameraIndex + 1) % availableCameras.length;

                    if (scannerIsActive) {
                        Quagga.stop();
                        scannerIsActive = false;
                    }

                    setTimeout(function () {
                        initQuaggaScanner();
                    }, 300); // Faster switching
                }
            });

            // Close camera scanner on click outside
            $('#camera-scanner-modal').on('click', function(e) {
                if (e.target === this) {
                    closeCameraScanner();
                }
            });

            // Auto-focus barcode scanner on page load
            setTimeout(function() {
                $('#barcodeScanner').focus();
            }, 200); // Faster initial focus

            console.log('Optimized barcode scanner with SERP integration loaded successfully');
        });

let originalSuggestions = [];
let currentBarcode = '';

// ✅ NEW: Manual search in SERP modal
$(document).on('click', '#manualSearchBtn', function() {
    let searchTerm = $('#manualSearchInput').val().trim();
    let storeId = $('#scannerStoreSelect').val();
    
    if (!searchTerm) {
        toastr.warning('{{translate("messages.please_enter_search_term")}}');
        return;
    }
    
    if (!storeId) {
        toastr.warning('{{translate("messages.please_select_store_first")}}');
        return;
    }
    
    performManualSearch(searchTerm, storeId, currentBarcode);
});

// ✅ NEW: Manual search on Enter key
$(document).on('keypress', '#manualSearchInput', function(e) {
    if (e.which === 13 || e.keyCode === 13) {
        e.preventDefault();
        $('#manualSearchBtn').click();
    }
});

// ✅ NEW: Reset to original suggestions
$(document).on('click', '#resetSuggestionsBtn', function() {
    if (originalSuggestions.length > 0) {
        renderSuggestions(originalSuggestions, currentBarcode);
        $('#manualSearchInput').val('');
        toastr.info('{{translate("messages.showing_original_suggestions")}}');
    }
});

// ✅ NEW: Perform manual search
function performManualSearch(searchTerm, storeId, barcode) {
    $('#manualSearchBtn').prop('disabled', true).html('<i class="tio-sync"></i> {{translate("messages.searching")}}');
    $('#manualSearchLoading').show();
    $('#suggestedItemsBody').closest('.table-responsive').hide();
    
    $.ajax({
        url: '{{route("admin.item.regular_items.search")}}',
        type: 'POST',
        timeout: 8000,
        data: {
            search: searchTerm,
            store_id: storeId,
            per_page: 20,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.items && response.items.length > 0) {
                let suggestions = response.items.map(function(item) {
                    return {
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        barcode: item.barcode,
                        stock: item.stock ?? 0,
                        image_full_url: item.image_full_url || '{{asset('public/assets/admin/img/160x160/img2.jpg')}}',
                        store: item.store ? {id: item.store.id, name: item.store.name} : null,
                        relevance_score: 0
                    };
                });
                
                renderSuggestions(suggestions, barcode);
                toastr.success(`Found ${suggestions.length} products matching "${searchTerm}"`);
            } else {
                $('#suggestedItemsBody').html('');
                $('#noSuggestions').show();
                $('#noSuggestions').html(`
                    <i class="tio-info-outlined text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2">No products found matching "${searchTerm}"</p>
                    <p class="text-muted">Try different keywords or check the spelling.</p>
                `);
                toastr.info('No products found');
            }
        },
        error: function(xhr) {
            console.error('Manual search error:', xhr);
            toastr.error('{{translate("messages.search_failed")}}');
        },
        complete: function() {
            $('#manualSearchBtn').prop('disabled', false).html('<i class="tio-search"></i> {{translate("messages.search")}}');
            $('#manualSearchLoading').hide();
            $('#suggestedItemsBody').closest('.table-responsive').show();
        }
    });
}

// ✅ NEW: Render suggestions helper function
function renderSuggestions(suggestions, barcode) {
    let suggestionsHtml = [];
    
    // ✅ Verify barcode parameter
    if (!barcode) {
        console.error('No barcode provided to renderSuggestions');
        toastr.error('Barcode is missing');
        return;
    }
    
    console.log('Rendering suggestions with barcode:', barcode);
    
    if (!suggestions || suggestions.length === 0) {
        $('#suggestedItemsBody').html('');
        $('#noSuggestions').show();
        return;
    }
    
    suggestions.forEach(function(item) {
        let imageUrl = item.image_full_url || '{{asset('public/assets/admin/img/160x160/img2.jpg')}}';
        let mrpDisplay = item.price ? `₹${parseFloat(item.price).toFixed(2)}` : 'N/A';
        let statusClass = item.barcode ? 'success' : 'danger';
        let statusText = item.barcode ? '{{translate('messages.has_barcode')}}' : '{{translate('messages.no_barcode')}}';
        let storeName = item.store && item.store.name ? item.store.name : 'N/A';
        let relevanceScore = item.relevance_score || 0;
        
        let relevanceBadge = '';
        if (relevanceScore > 0) {
            let badgeClass = relevanceScore >= 30 ? 'success' : (relevanceScore >= 15 ? 'info' : 'secondary');
            relevanceBadge = `<span class="badge badge-${badgeClass} ml-2">Match: ${relevanceScore}</span>`;
        }
        
        // ✅ Escape barcode properly for HTML attribute
        let escapedBarcode = String(barcode).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        let escapedName = String(item.name || 'Unknown').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        
        suggestionsHtml.push(`
        <tr>
            <td>
                <img src="${imageUrl}"
                    class="product-image-small"
                    onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                    alt="${item.name || 'Product'}" loading="lazy">
            </td>
            <td>
                ${(item.name || 'N/A').substring(0, 50)}${item.name && item.name.length > 50 ? '...' : ''}
                ${relevanceBadge}
            </td>
            <td class="mrp-column">${mrpDisplay}</td>
            <td>
                <span class="badge badge-soft-${statusClass}">${statusText}</span>
            </td>
            <td>${storeName}</td>
            <td>
                <button class="btn btn-sm btn-primary assign-barcode-btn"
                        data-item-id="${item.id}"
                        data-item-name="${escapedName}"
                        data-barcode="${escapedBarcode}">
                    <i class="tio-barcode"></i> {{translate('messages.assign')}}
                </button>
            </td>
        </tr>
        `);
    });
    
    $('#suggestedItemsBody').html(suggestionsHtml.join(''));
    $('#noSuggestions').hide();
    $('#suggestionsCount').text(`${suggestions.length} products found`);
}

    </script>
@endpush
