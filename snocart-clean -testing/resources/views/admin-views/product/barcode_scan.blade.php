@extends('layouts.admin.app')

@section('title', translate('Barcode Scanner'))

@push('css_or_js')
    <style>
        .barcode-scanner-container {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .barcode-input-group {
            position: relative;
        }
        
        .barcode-status {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }
        
        .scanning-indicator {
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .scanner-status {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 12px;
        }
        
        #barcodeScanner {
            font-size: 1.2rem;
            padding: 12px 16px;
        }

        /* Variation table styles */
        .variant-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .variant-table th {
            background-color: #f8f9fa;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        .variant-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .variant-table input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        /* Attribute input styles */
        .attribute-input-group {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .attribute-label {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        /* Enhanced Product Info Styles */
        .product-info-loader {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .product-info-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .product-thumbnail {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 5px;
            background: white;
        }
        
        .product-info-section {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .product-info-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .product-info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .product-info-value {
            color: #212529;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .product-specs-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .product-specs-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            justify-content: space-between;
        }
        
        .product-specs-list li:last-child {
            border-bottom: none;
        }
        
        .spec-label {
            font-weight: 500;
            color: #6c757d;
            flex: 0 0 40%;
        }
        
        .spec-value {
            color: #212529;
            flex: 1;
            text-align: right;
        }
        
        .product-features-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .product-features-list li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .product-features-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        
        .product-price-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
        }
        
        .price-original {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .price-current {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
        }
        
        .price-discount {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .auto-fill-indicator {
            color: #28a745;
            font-size: 12px;
            font-style: italic;
        }
        
        .google-search-btn {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .product-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
        
        .info-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .info-badge-primary {
            background: #e7f3ff;
            color: #0066cc;
        }
        
        .info-badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .info-badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .product-rating {
            color: #ffc107;
            font-size: 16px;
        }
        
        .multiple-results-container {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .result-option {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .result-option:hover {
            border-color: #007bff;
            background: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .result-option.selected {
            border-color: #28a745;
            background: #f0f8f0;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
        
        /* Category Suggestions Styles */
        .category-suggestions {
            background: #e7f3ff;
            border: 2px solid #0066cc;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .category-suggestions-title {
            font-weight: 600;
            color: #0066cc;
            margin-bottom: 10px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .category-suggestion-item {
            display: inline-block;
            background: white;
            border: 1px solid #0066cc;
            border-radius: 20px;
            padding: 6px 15px;
            margin: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
        }
        
        .category-suggestion-item:hover {
            background: #0066cc;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,102,204,0.3);
        }
        
        .category-suggestion-item.selected {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .category-suggestion-item .confidence-badge {
            background: rgba(0,0,0,0.1);
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 5px;
        }
        
        .subcategory-suggestions {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
            animation: slideDown 0.3s ease-out;
        }
        
        .subcategory-suggestions-title {
            font-weight: 600;
            color: #856404;
            margin-bottom: 10px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .subcategory-suggestion-item {
            display: inline-block;
            background: white;
            border: 1px solid #ffc107;
            border-radius: 20px;
            padding: 6px 15px;
            margin: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
        }
        
        .subcategory-suggestion-item:hover {
            background: #ffc107;
            color: #212529;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255,193,7,0.3);
        }
        
        .subcategory-suggestion-item.selected {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .ai-suggestion-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
            animation: pulse 2s infinite;
        }
        
        .match-score {
            display: inline-block;
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 5px;
        }
    </style>
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="btn--container align-items-center mb-0">
                <div class="mr-auto">
                    <h1 class="page-header-title">
                        <i class="tio-barcode"></i> {{translate('Barcode Scanner')}}
                    </h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Barcode Scanner Section -->
        <div class="card">
            <div class="card-body barcode-scanner-container">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="storeSelect" class="form-label">
                            <i class="tio-shop"></i> {{translate('Select Store')}} <span class="text-danger">*</span>
                        </label>
                        <select id="storeSelect" class="form-control js-select2-custom" required>
                            <option value="">{{translate('Select store to scan')}}</option>
                            @foreach($stores as $store)
                            <option value="{{$store->id}}">{{$store->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{translate('Selected Store')}}</label>
                        <div class="alert alert-info" id="selectedStoreInfo" style="display: none;">
                            <i class="tio-checkmark-circle"></i>
                            <span id="selectedStoreName"></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="barcode-input-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="tio-barcode"></i>
                                    </span>
                                </div>
                                <input type="text" id="barcodeScanner" class="form-control"
                                       placeholder="{{translate('First select store then scan')}}"
                                       autocomplete="off" disabled>
                                <div class="barcode-status" id="barcodeStatus"></div>
                            </div>
                            <small class="text-muted" id="scannerHelpText">{{translate('Please select store first')}}</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center h-100">
                            <span class="badge badge-info scanner-status" id="scannerStatus">Ready</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scan Results -->
        <div class="card mt-3" id="scanResultsCard" style="display: none;">
            <div class="card-header">
                <h5 class="card-title">{{translate('Scan Results')}}</h5>
            </div>
            <div class="card-body" id="scanResultsBody">
                <!-- Results will be displayed here -->
            </div>
        </div>
    </div>

    <!-- Quick Edit Modal -->
    <div class="modal fade" id="quickEditModal" tabindex="-1" role="dialog" aria-labelledby="quickEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickEditModalLabel">
                        <i class="tio-edit"></i> {{translate('Quick Edit Item')}}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="quickEditForm">
                    @csrf
                    <input type="hidden" name="item_id" id="editItemId">
                    <input type="hidden" name="store_id" id="editStoreId">
                    
                    <div class="modal-body">
                        <!-- Item Preview -->
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2">
                                        <img id="editItemImage" src="" class="img-fluid rounded" alt="Item">
                                    </div>
                                    <div class="col-md-10">
                                        <h5 id="editItemName" class="mb-2"></h5>
                                        <p class="mb-1"><strong>{{translate('Barcode')}}:</strong> <span id="editItemBarcode"></span></p>
                                        <p class="mb-1"><strong>{{translate('Category')}}:</strong> <span id="editItemCategory"></span></p>
                                        <p class="mb-0"><strong>{{translate('Current Status')}}:</strong> <span id="editItemStatus"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Update Section -->
                        <div class="card shadow--card-2 border-0 mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="tio-money"></i> {{translate('Update Price')}}
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label">{{translate('Current Price')}}</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">₹</span>
                                                </div>
                                                <input type="text" id="editCurrentPrice" class="form-control" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label">{{translate('New Price')}} <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">₹</span>
                                                </div>
                                                <input type="number" step="0.01" id="editNewPrice" name="price" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-info mb-0">
                                    <i class="tio-info"></i>
                                    <span id="priceDifferenceText">{{translate('Enter new price to see the difference')}}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Update Section -->
                        <div class="card shadow--card-2 border-0 mb-3">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="tio-layers"></i> {{translate('Update Stock')}}
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="input-label">{{translate('Current Stock')}}</label>
                                            <input type="text" id="editCurrentStock" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="input-label">{{translate('Add Stock')}} <span class="text-danger">*</span></label>
                                            <input type="number" id="editAddStock" name="add_stock" class="form-control" min="0" value="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="input-label">{{translate('New Total Stock')}}</label>
                                            <input type="text" id="editNewTotalStock" class="form-control" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-success mb-0">
                                    <i class="tio-checkmark-circle"></i>
                                    <span id="stockChangeText">{{translate('Enter quantity to add to existing stock')}}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Optional: Discount Update -->
                        <div class="card shadow--card-2 border-0 mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="tio-label"></i> {{translate('Update Discount')}} <small class="text-muted">({{translate('Optional')}})</small>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label">{{translate('Current Discount')}}</label>
                                            <div class="input-group">
                                                <input type="text" id="editCurrentDiscount" class="form-control" readonly>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">₹</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label">{{translate('New Discount')}}</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" id="editNewDiscount" name="discount" class="form-control" min="0" value="0">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">₹</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Toggle -->
                        <div class="card shadow--card-2 border-0">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="tio-toggle"></i> {{translate('Item Status')}}
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-0">
                                    <label class="toggle-switch toggle-switch-sm d-flex justify-content-between border rounded px-4 py-3" for="editItemStatusToggle">
                                        <span class="d-block">
                                            <span class="d-block font-weight-bold">{{translate('Active Status')}}</span>
                                            <span class="d-block text-muted">{{translate('Toggle item availability')}}</span>
                                        </span>
                                        <input type="checkbox" id="editItemStatusToggle" name="status" class="toggle-switch-input" checked>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="tio-clear"></i> {{translate('Cancel')}}
                        </button>
                        <button type="submit" class="btn btn-primary" id="quickEditSaveBtn">
                            <i class="tio-save"></i> {{translate('Save Changes')}}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add New Item Modal -->
    <div class="modal fade" id="addNewItemModal" tabindex="-1" role="dialog" aria-labelledby="addNewItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNewItemModalLabel">
                        <i class="tio-add"></i> {{translate('Add New Item')}}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addNewItemForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="barcode" id="modalBarcode">
                        <input type="hidden" name="store_id" id="modalStoreId">
                        <input type="hidden" name="lang[]" value="default">
                        
                        <!-- Enhanced Product Info from SERP API -->
                        <div id="productInfoSection" style="display: none;">
                            <div class="product-info-loader" id="productInfoLoader">
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <h6>{{translate('Fetching detailed product information...')}}</h6>
                                <p class="text-muted mb-0">{{translate('This may take a few seconds')}}</p>
                            </div>
                            
                            <!-- Multiple Results Selection -->
                            <div id="multipleResultsSection" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="tio-info"></i>
                                    {{translate('Multiple products found. Please select the most relevant one:')}}
                                </div>
                                <div class="multiple-results-container" id="multipleResultsContainer">
                                    <!-- Multiple results will be populated here -->
                                </div>
                            </div>
                            
                            <!-- Single Product Details Card -->
                            <div class="product-info-card" id="productInfoCard" style="display: none;">
                                <div class="row">
                                    <div class="col-md-2" id="productThumbnailSection" style="display: none;">
                                        <img id="productThumbnail" class="product-thumbnail" src="" alt="Product">
                                    </div>
                                    <div class="col-md-10">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="text-success mb-2">
                                                    <i class="tio-checkmark-circle"></i>
                                                    {{translate('Product Information Found')}}
                                                </h5>
                                                <div id="productBadges">
                                                    <!-- Badges will be added here -->
                                                </div>
                                            </div>
                                            <div id="productRating" style="display: none;">
                                                <!-- Rating will be shown here -->
                                            </div>
                                        </div>
                                        
                                        <!-- Basic Information -->
                                        <div class="product-info-section">
                                            <div class="product-info-label">{{translate('Product Name')}}</div>
                                            <div class="product-info-value" id="productTitle"></div>
                                        </div>
                                        
                                        <div class="product-info-section" id="productDescriptionSection" style="display: none;">
                                            <div class="product-info-label">{{translate('Description')}}</div>
                                            <div class="product-info-value" id="productSnippet"></div>
                                        </div>
                                        
                                        <!-- Price Information -->
                                        <div class="product-info-section" id="productPriceSection" style="display: none;">
                                            <div class="product-info-label">{{translate('Price Information')}}</div>
                                            <div class="product-price-info" id="productPriceInfo">
                                                <!-- Price details will be added here -->
                                            </div>
                                        </div>
                                        
                                        <!-- Brand & Category -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="product-info-section" id="productBrandSection" style="display: none;">
                                                    <div class="product-info-label">{{translate('Brand')}}</div>
                                                    <div class="product-info-value" id="productBrand"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="product-info-section" id="productCategorySection" style="display: none;">
                                                    <div class="product-info-label">{{translate('Category')}}</div>
                                                    <div class="product-info-value" id="productCategory"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Specifications -->
                                        <div class="product-info-section" id="productSpecsSection" style="display: none;">
                                            <div class="product-info-label">{{translate('Specifications')}}</div>
                                            <ul class="product-specs-list" id="productSpecs">
                                                <!-- Specs will be added here -->
                                            </ul>
                                        </div>
                                        
                                        <!-- Features -->
                                        <div class="product-info-section" id="productFeaturesSection" style="display: none;">
                                            <div class="product-info-label">{{translate('Features')}}</div>
                                            <ul class="product-features-list" id="productFeatures">
                                                <!-- Features will be added here -->
                                            </ul>
                                        </div>
                                        
                                        <!-- Availability -->
                                        <div class="product-info-section" id="productAvailabilitySection" style="display: none;">
                                            <div class="product-info-label">{{translate('Availability')}}</div>
                                            <div class="product-info-value" id="productAvailability"></div>
                                        </div>
                                        
                                        <!-- Source -->
                                        <div class="product-info-section">
                                            <div class="product-info-label">{{translate('Source')}}</div>
                                            <div class="product-info-value">
                                                <a href="#" id="productSourceLink" target="_blank" rel="noopener">
                                                    <i class="tio-open-in-new"></i> <span id="productSource"></span>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="product-actions">
                                            <button type="button" class="btn btn-primary" id="useProductInfo">
                                                <i class="tio-checkmark"></i> {{translate('Use This Information')}}
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" id="manualEntry">
                                                <i class="tio-edit"></i> {{translate('Enter Manually')}}
                                            </button>
                                            <button type="button" class="btn btn-outline-info" id="refetchProductInfo">
                                                <i class="tio-refresh"></i> {{translate('Fetch Again')}}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning" id="noProductInfo" style="display: none;">
                                <i class="tio-info"></i>
                                {{translate('No product information found. Please enter details manually.')}}
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Item Name -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="itemName">
                                        {{translate('Item Name')}} <span class="text-danger">*</span>
                                        <span class="auto-fill-indicator" id="nameAutoFill" style="display: none;">
                                            ({{translate('Auto-filled')}})
                                        </span>
                                    </label>
                                    <input type="text" class="form-control" id="itemName" name="name[default]" required>
                                </div>
                            </div>
                            
                            <!-- Price -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="itemPrice">
                                        {{translate('Price')}} <span class="text-danger">*</span>
                                        <span class="auto-fill-indicator" id="priceAutoFill" style="display: none;">
                                            ({{translate('Auto-filled')}})
                                        </span>
                                    </label>
                                    <input type="number" step="0.01" class="form-control" id="itemPrice" name="price" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Initial Stock -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="itemStock">{{translate('Initial Stock')}} <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="itemStock" name="current_stock" value="6" required>
                                </div>
                            </div>
                            
                            <!-- Category with Suggestions -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <!-- Category Suggestions (shown when AI suggests categories) -->
                                    <div id="categorySuggestions" class="category-suggestions" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="category-suggestions-title">
                                                <i class="tio-checkmark-circle"></i> {{translate('Suggested Categories')}}
                                                <span class="ai-suggestion-badge">AI</span>
                                            </div>
                                            <small class="text-muted">{{translate('Click to select')}}</small>
                                        </div>
                                        <div id="categorySuggestionsContainer">
                                            <!-- Suggestions will be added here -->
                                        </div>
                                    </div>
                                    
                                    <label class="input-label" for="itemCategory">
                                        {{translate('Category')}} <span class="text-danger">*</span>
                                        <span class="auto-fill-indicator" id="categoryAutoFill" style="display: none;">
                                            ({{translate('Auto-selected')}})
                                        </span>
                                    </label>
                                    <select class="form-control js-select2-custom" id="itemCategory" name="category_id" required>
                                        <option value="">{{translate('Select Category')}}</option>
                                        @foreach($categories as $category)
                                        <option value="{{$category->id}}" data-name="{{$category->name}}">{{$category->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Sub Category with Suggestions -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <!-- Subcategory Suggestions (shown when AI suggests subcategories) -->
                                    <div id="subcategorySuggestions" class="subcategory-suggestions" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="subcategory-suggestions-title">
                                                <i class="tio-checkmark-circle"></i> {{translate('Suggested Sub-Categories')}}
                                                <span class="ai-suggestion-badge">AI</span>
                                            </div>
                                            <small class="text-muted">{{translate('Click to select')}}</small>
                                        </div>
                                        <div id="subcategorySuggestionsContainer">
                                            <!-- Suggestions will be added here -->
                                        </div>
                                    </div>
                                    
                                    <label class="input-label" for="itemSubCategory">
                                        {{translate('Sub Category')}}
                                        <span class="auto-fill-indicator" id="subcategoryAutoFill" style="display: none;">
                                            ({{translate('Auto-suggested')}})
                                        </span>
                                    </label>
                                    <select class="form-control js-select2-custom" id="itemSubCategory" name="sub_category_id">
                                        <option value="">{{translate('Select Sub Category')}}</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Unit -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="itemUnit">{{translate('messages.unit')}}</label>
                                    <select name="unit_id" id="itemUnit" class="form-control js-select2-custom">
                                        <option value="">{{translate('Select Unit')}}</option>
                                        @foreach (\App\Models\Unit::all() as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->unit }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Storage Location Section -->
                        <div class="card shadow--card-2 border-0 mt-3">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <span class="card-header-icon"><i class="tio-home-outlined"></i></span>
                                    <span>{{ translate('Storage Location') }}</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label" for="itemRack">
                                                {{translate('Rack')}}
                                                <span class="input-label-secondary" title="{{translate('Storage rack location (e.g., A1, B2, C3)')}}">
                                                    <i class="tio-info-outined"></i>
                                                </span>
                                            </label>
                                            <input type="text" class="form-control" id="itemRack" name="rack"
                                                    placeholder="{{translate('e.g., A1, B2, R-001')}}" maxlength="50">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label" for="itemRow">
                                                {{translate('Row')}}
                                                <span class="input-label-secondary" title="{{translate('Storage row location (e.g., 1, 2, Top, Bottom)')}}">
                                                    <i class="tio-info-outined"></i>
                                                </span>
                                            </label>
                                            <input type="text" class="form-control" id="itemRow" name="row"
                                                    placeholder="{{translate('e.g., 1, 2, Top, Bottom')}}" maxlength="50">
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <i class="tio-info"></i>
                                    {{translate('Storage location helps in quick inventory management and item retrieval')}}
                                </small>
                            </div>
                        </div>
                        
                        <!-- Attributes Section -->
                        <div class="card shadow--card-2 border-0 mt-3">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <span class="card-header-icon"><i class="tio-canvas-text"></i></span>
                                    <span>{{ translate('Attributes') }}</span>
                                </h5>
                            </div>
                            <div class="card-body pb-0">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <div class="form-group mb-0">
                                            <label class="input-label" for="choice_attributes">{{ translate('Select Attributes') }}</label>
                                            <select name="attribute_id[]" id="choice_attributes" class="form-control js-select2-custom" multiple="multiple">
                                                @foreach (\App\Models\Attribute::orderBy('name')->get() as $attribute)
                                                    <option value="{{ $attribute['id'] }}">{{ $attribute['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="customer_choice_options d-flex flex-column gap-3 mt-3" id="customer_choice_options"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Variations Section -->
                        <div class="card shadow--card-2 border-0 mt-3" id="variations_section" style="display: none;">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <span class="card-header-icon"><i class="tio-label"></i></span>
                                    <span>{{ translate('Variations') }}</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="variant-table">
                                        <thead>
                                            <tr>
                                                <th>{{ translate('Variant') }}</th>
                                                <th>{{ translate('Price') }}</th>
                                                <th>{{ translate('Stock') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="variations_container"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Other form fields -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="itemDiscount">{{translate('Discount')}} (₹)</label>
                                    <input type="number" step="0.01" class="form-control" id="itemDiscount" name="discount" value="0">
                                    <input type="hidden" name="discount_type" value="amount">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="maxCartQty">{{translate('Max Cart Quantity')}}</label>
                                    <input type="number" class="form-control" id="maxCartQty" name="maximum_cart_quantity" value="10">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div class="form-group">
                            <label class="input-label" for="itemDescription">
                                {{translate('Description')}} <span class="text-danger">*</span>
                                <span class="auto-fill-indicator" id="descAutoFill" style="display: none;">
                                    ({{translate('Auto-filled')}})
                                </span>
                            </label>
                            <textarea class="form-control" id="itemDescription" name="description[default]" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="itemVeg">{{translate('messages.item_type')}} <span class="text-danger">*</span></label>
                                    <select class="form-control js-select2-custom" id="itemVeg" name="veg" required>
                                        <option value="0">{{translate('messages.non_veg')}}</option>
                                        <option value="1">{{translate('messages.veg')}}</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label" for="availableTimeStart">{{translate('messages.available_time_starts')}}</label>
                                    <input type="time" class="form-control" id="availableTimeStart" name="available_time_starts" value="00:00">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label" for="availableTimeEnd">{{translate('messages.available_time_ends')}}</label>
                                    <input type="time" class="form-control" id="availableTimeEnd" name="available_time_ends" value="23:59">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Image with Google Search Button -->
                        <div class="form-group">
                            <label class="input-label" for="itemImage">{{translate('messages.item_image')}}</label>
                            <input type="file" class="form-control-file" id="itemImage" name="image" accept="image/*">
                            <small class="text-muted">{{translate('Optional - Leave empty if no image available')}}</small>
                            
                            <button type="button" class="btn btn-outline-primary google-search-btn" id="googleSearchBtn">
                                <i class="tio-search"></i> {{translate('Search on Google')}}
                            </button>
                        </div>
                        
                        <!-- Barcode Display -->
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>{{translate('Barcode')}}:</strong> <span id="displayBarcode"></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>{{translate('Storage Location')}}:</strong>
                                    <span id="displayStorage">
                                        <span id="displayRack">-</span> / <span id="displayRow">-</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="tio-clear"></i> {{translate('Close')}}
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveNewItemBtn">
                            <i class="tio-save"></i> {{translate('Save Item')}}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script src="{{ asset('public/assets/admin/js/tags-input.min.js') }}"></script>
<script>
$(document).ready(function () {
    let scannerTimeout = null;
    let currentProductInfo = null;
    let allProductResults = [];
    let suggestedCategories = [];
    let suggestedSubcategories = [];
    
    // Category keywords mapping for intelligent matching
    const categoryKeywords = {
        'electronics': ['phone', 'mobile', 'laptop', 'computer', 'tablet', 'camera', 'headphone', 'speaker', 'electronic', 'gadget', 'device', 'charger', 'cable', 'adapter', 'television', 'tv', 'monitor', 'keyboard', 'mouse'],
        'food': ['food', 'snack', 'beverage', 'drink', 'edible', 'eat', 'taste', 'flavor', 'cuisine', 'meal', 'dish', 'recipe', 'ingredient', 'grocery'],
        'clothing': ['shirt', 'pant', 'dress', 'shoe', 'clothing', 'apparel', 'wear', 'fashion', 'garment', 'outfit', 'jacket', 'coat', 'jeans', 'trouser', 'skirt', 't-shirt', 'top', 'bottom'],
        'beauty': ['beauty', 'cosmetic', 'makeup', 'skincare', 'perfume', 'fragrance', 'lotion', 'cream', 'serum', 'lipstick', 'foundation', 'shampoo', 'conditioner'],
        'health': ['medicine', 'health', 'vitamin', 'supplement', 'pharma', 'medical', 'drug', 'tablet', 'capsule', 'syrup', 'wellness'],
        'home': ['home', 'furniture', 'decor', 'kitchen', 'appliance', 'utensil', 'bedding', 'lamp', 'chair', 'table', 'sofa', 'bed'],
        'sports': ['sport', 'fitness', 'gym', 'exercise', 'athletic', 'outdoor', 'game', 'ball', 'equipment', 'yoga', 'running', 'cycling'],
        'books': ['book', 'novel', 'magazine', 'journal', 'publication', 'reading', 'literature', 'textbook', 'guide', 'manual'],
        'toys': ['toy', 'game', 'puzzle', 'doll', 'action figure', 'lego', 'playset', 'kids', 'children'],
        'automotive': ['car', 'auto', 'vehicle', 'motor', 'automotive', 'bike', 'motorcycle', 'accessory', 'parts'],
        'grocery': ['rice', 'flour', 'oil', 'sugar', 'salt', 'spice', 'pulse', 'dal', 'grain', 'cereal', 'pasta'],
        'beverages': ['water', 'juice', 'soda', 'tea', 'coffee', 'milk', 'drink', 'beverage', 'cola', 'energy drink'],
        'bakery': ['bread', 'cake', 'biscuit', 'cookie', 'pastry', 'bun', 'muffin', 'donut', 'bakery'],
        'dairy': ['milk', 'cheese', 'butter', 'yogurt', 'cream', 'paneer', 'dairy', 'curd'],
        'snacks': ['chips', 'namkeen', 'biscuit', 'cookie', 'wafer', 'snack', 'munchies', 'cracker'],
        'personal care': ['soap', 'shampoo', 'toothpaste', 'deodorant', 'sanitizer', 'tissue', 'wipes', 'razor', 'blade'],
        'stationery': ['pen', 'pencil', 'notebook', 'paper', 'eraser', 'ruler', 'stapler', 'clip', 'file', 'folder']
    };
    
    // Initialize select2
    $('.js-select2-custom').select2();
    
    // Store selection handler
    $('#storeSelect').on('change', function() {
        let storeId = $(this).val();
        let storeName = $(this).find('option:selected').text();
        
        if(storeId) {
            $('#barcodeScanner').prop('disabled', false)
                .attr('placeholder', '{{translate("Scan or type barcode")}}');
            
            setTimeout(function() {
                $('#barcodeScanner').focus().select();
            }, 100);
            
            $('#selectedStoreName').text(storeName);
            $('#selectedStoreInfo').show();
            $('#scannerHelpText').text('{{translate("Scan barcode or type manually")}}');
            updateScannerStatus('ready', 'Ready - ' + storeName);
        } else {
            $('#barcodeScanner').prop('disabled', true)
                .attr('placeholder', '{{translate("First select store then scan")}}')
                .val('');
            $('#selectedStoreInfo').hide();
            $('#scannerHelpText').text('{{translate("Please select store first")}}');
            updateScannerStatus('ready', 'Ready');
            clearBarcodeStatus();
        }
    });
    
    // Category change handler
    $('#itemCategory').on('change', function() {
        let categoryId = $(this).val();
        let $subCategorySelect = $('#itemSubCategory');
        
        $subCategorySelect.html('<option value="">{{translate("Select Sub Category")}}</option>');
        
        if(categoryId) {
            $subCategorySelect.prop('disabled', true);
            
            $.ajax({
                url: '{{ route("admin.item.barcode_scan.process") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    action: 'get_subcategories',
                    category_id: categoryId
                },
                success: function(response) {
                    if(response.success && response.subcategories && response.subcategories.length > 0) {
                        $.each(response.subcategories, function(index, subcategory) {
                            $subCategorySelect.append($('<option>', {
                                value: subcategory.id,
                                text: subcategory.name
                            }));
                        });
                        $subCategorySelect.prop('disabled', false);
                        
                        // Suggest subcategories if we have product data
                        if(currentProductInfo && currentProductInfo.data) {
                            let data = Array.isArray(currentProductInfo.data) ?
                                       currentProductInfo.data[0] : currentProductInfo.data;
                            suggestSubcategoriesFromProduct(data, categoryId);
                        }
                    } else {
                        $subCategorySelect.prop('disabled', false);
                    }
                },
                error: function() {
                    $subCategorySelect.prop('disabled', false);
                    toastr.error('{{translate("Failed to load subcategories")}}');
                }
            });
        } else {
            $subCategorySelect.prop('disabled', true);
            // Hide subcategory suggestions when no category selected
            $('#subcategorySuggestions').hide();
            suggestedSubcategories = [];
        }
    });
    
    // Google Search Button Handler
    $('#googleSearchBtn').on('click', function() {
        let productName = $('#itemName').val().trim();
        if (!productName) {
            toastr.warning('{{translate("Please enter a product name first")}}');
            $('#itemName').focus();
            return;
        }
        window.open(`http://www.google.com/search?ie=UTF-8&tbm=isch&q=${encodeURIComponent(productName)}`, '_blank');
    });
    
    // Handle attribute selection
    $('#choice_attributes').on('change', function() {
        $('#customer_choice_options').html('');
        $('#variations_container').html('');
        
        if ($(this).val() && $(this).val().length > 0) {
            $('#variations_section').show();
            $.each($("#choice_attributes option:selected"), function() {
                add_attribute_option($(this).val(), $(this).text());
            });
        } else {
            $('#variations_section').hide();
        }
    });

    function add_attribute_option(id, name) {
        $('#customer_choice_options').append(`
            <div class="attribute-input-group" data-attribute-id="${id}">
                <div class="attribute-label">${name}</div>
                <div class="form-group">
                    <label class="input-label">{{ translate('Values') }}</label>
                    <input type="text" class="form-control attribute-values" data-attribute-id="${id}"
                           placeholder="{{ translate('Enter values separated by commas') }}" data-role="tagsinput">
                </div>
            </div>
        `);
        
        $("input[data-role=tagsinput]").tagsinput();
        $('.attribute-values').on('itemAdded itemRemoved', function() {
            generate_variations();
        });
    }

    function generate_variations() {
        let attributes = [];
        $('.attribute-input-group').each(function() {
            let attributeId = $(this).data('attribute-id');
            let values = $(this).find('.attribute-values').val() ? $(this).find('.attribute-values').val().split(',') : [];
            let prices = $(this).find('.attribute-prices').val() ? $(this).find('.attribute-prices').val().split(',') : [];
            
            if(values.length > 0) {
                let attributeValues = [];
                for(let i = 0; i < values.length; i++) {
                    attributeValues.push({
                        value: values[i].trim(),
                        price: prices[i] ? parseFloat(prices[i].trim()) : 0
                    });
                }
                
                attributes.push({
                    id: attributeId,
                    name: $(this).find('.attribute-label').text(),
                    values: attributeValues
                });
            }
        });

        if(attributes.length > 0) {
            let variations = generate_combinations(attributes);
            display_variations(variations);
        } else {
            $('#variations_container').html('');
        }
    }

    function generate_combinations(attributes) {
        let result = [];
        let current = [];
        let basePrice = parseFloat($('#itemPrice').val()) || 0;
        
        function backtrack(index) {
            if (index === attributes.length) {
                let variant = {
                    type: current.map(c => c.value).join('-'),
                    price: basePrice + current.reduce((sum, item) => sum + item.price, 0),
                    stock: parseInt($('#itemStock').val()) || 0
                };
                result.push(variant);
                return;
            }
            
            for (let value of attributes[index].values) {
                current.push(value);
                backtrack(index + 1);
                current.pop();
            }
        }
        
        backtrack(0);
        return result;
    }

    function display_variations(variations) {
        $('#variations_container').html('');
        
        variations.forEach((variation, index) => {
            $('#variations_container').append(`
                <tr>
                    <td>
                        <input type="hidden" name="variations[${index}][type]" value="${variation.type}">
                        ${variation.type}
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control"
                               name="variations[${index}][price]" value="${variation.price}" required>
                    </td>
                    <td>
                        <input type="number" class="form-control"
                               name="variations[${index}][stock]" value="${variation.stock}" required>
                    </td>
                </tr>
            `);
        });
    }
    
    // Barcode scanner input handler
    $('#barcodeScanner').on('input', function() {
        if(!$('#storeSelect').val()) {
            toastr.warning('{{translate("Please select store first")}}');
            $(this).val('');
            return;
        }
        
        let barcode = $(this).val().trim();
        clearTimeout(scannerTimeout);
        
        if(barcode.length > 0) {
            $(this).addClass('form-control-loading');
            updateScannerStatus('scanning', 'Scanning...');
            updateBarcodeStatus('info', 'Processing...');
            
            scannerTimeout = setTimeout(function() {
                processBarcodeScan(barcode);
            }, 500);
        }
    });
    
    function processBarcodeScan(barcode) {
        let storeId = $('#storeSelect').val();
        
        if(!storeId) {
            toastr.error('{{translate("Please select store first")}}');
            return;
        }
        
        $('#barcodeScanner').addClass('form-control-loading');
        updateScannerStatus('searching', 'Searching...');
        updateBarcodeStatus('info', 'Searching...');
        
        $.ajax({
            url: '{{ route("admin.item.barcode_scan.process") }}',
            type: 'POST',
            data: {
                barcode: barcode,
                store_id: storeId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success) {
                    if(response.action === 'updated') {
                        updateBarcodeStatus('success', 'Updated!');
                        updateScannerStatus('found', 'Item Updated');
                        showScanResult(response.item, true);
                        
                        setTimeout(function() {
                            $('#barcodeScanner').val('').focus();
                        }, 1000);
                    } else if(response.action === 'already_updated') {
                        updateBarcodeStatus('info', 'Already Updated');
                        updateScannerStatus('found', 'Already Updated');
                        showScanResult(response.item, false, 'already_updated');
                        
                        setTimeout(function() {
                            $('#barcodeScanner').val('').focus();
                        }, 1000);
                    } else if(response.action === 'add_new') {
                        updateBarcodeStatus('warning', 'Not Found');
                        updateScannerStatus('not_found', 'Item Not Found');
                        
                        $('#barcodeScanner').val('');
                        
                        resetModalForm();
                        $('#modalBarcode').val(barcode);
                        $('#modalStoreId').val(storeId);
                        $('#displayBarcode').text(barcode);
                        
                        if(response.product_info) {
                            currentProductInfo = response.product_info;
                            handleProductInfoDisplay(response.product_info);
                        }
                        
                        $('#addNewItemModal').modal('show');
                    }
                } else {
                    toastr.error(response.message || '{{translate("Something went wrong")}}');
                    updateBarcodeStatus('error', 'Error');
                    updateScannerStatus('error', 'Error');
                    $('#barcodeScanner').val('');
                }
            },
            error: function(xhr) {
                console.error('Barcode scan error:', xhr);
                toastr.error('{{translate("Barcode scan failed")}}');
                updateBarcodeStatus('error', 'Error');
                updateScannerStatus('error', 'Error');
                $('#barcodeScanner').val('');
            },
            complete: function() {
                $('#barcodeScanner').removeClass('form-control-loading');
                
                setTimeout(function() {
                    let storeName = $('#storeSelect option:selected').text();
                    updateScannerStatus('ready', storeName ? 'Ready - ' + storeName : 'Ready');
                    clearBarcodeStatus();
                }, 3000);
            }
        });
    }
    
    // Enhanced Product Info Display Handler
    function handleProductInfoDisplay(productInfo) {
        $('#productInfoSection').show();
        $('#productInfoLoader').show();
        $('#productInfoCard').hide();
        $('#multipleResultsSection').hide();
        $('#noProductInfo').hide();
        
        if(productInfo.success && productInfo.data) {
            setTimeout(function() {
                $('#productInfoLoader').hide();
                
                // Check if multiple results
                if(Array.isArray(productInfo.data) && productInfo.data.length > 1) {
                    allProductResults = productInfo.data;
                    displayMultipleResults(productInfo.data);
                } else {
                    // Single result or first result
                    let data = Array.isArray(productInfo.data) ? productInfo.data[0] : productInfo.data;
                    displaySingleProductInfo(data);
                }
            }, 1500);
        } else {
            setTimeout(function() {
                $('#productInfoLoader').hide();
                $('#noProductInfo').show();
            }, 1500);
        }
    }
    
    // Display Multiple Results for Selection
    function displayMultipleResults(results) {
        $('#multipleResultsSection').show();
        $('#multipleResultsContainer').html('');
        
        results.forEach((result, index) => {
            let resultHtml = `
                <div class="result-option" data-index="${index}">
                    <div class="row">
                        ${result.thumbnail ? `
                            <div class="col-md-2">
                                <img src="${result.thumbnail}" class="product-thumbnail" alt="${result.title || 'Product'}">
                            </div>
                            <div class="col-md-10">
                        ` : '<div class="col-md-12">'}
                            <h6 class="mb-2">${result.title || 'No Title'}</h6>
                            <p class="text-muted mb-2 small">${result.snippet || 'No description available'}</p>
                            ${result.price ? `<p class="mb-0"><strong>Price:</strong> ${result.price}</p>` : ''}
                            ${result.source ? `<p class="mb-0 small text-info"><i class="tio-link"></i> ${result.source}</p>` : ''}
                        </div>
                    </div>
                </div>
            `;
            $('#multipleResultsContainer').append(resultHtml);
        });
        
        // Handle result selection
        $('.result-option').on('click', function() {
            $('.result-option').removeClass('selected');
            $(this).addClass('selected');
            
            let index = $(this).data('index');
            displaySingleProductInfo(results[index]);
            
            // Scroll to product info card
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: $('#productInfoCard').offset().top - 100
                }, 500);
            }, 300);
        });
    }
    
    // Display Single Product Information
    function displaySingleProductInfo(data) {
        $('#multipleResultsSection').hide();
        $('#productInfoCard').show();
        
        // Product Title
        $('#productTitle').text(data.title || 'N/A');
        
        // Thumbnail
        if(data.thumbnail) {
            $('#productThumbnail').attr('src', data.thumbnail);
            $('#productThumbnailSection').show();
        } else {
            $('#productThumbnailSection').hide();
        }
        
        // Description
        if(data.snippet || data.description) {
            $('#productSnippet').text(data.snippet || data.description);
            $('#productDescriptionSection').show();
        } else {
            $('#productDescriptionSection').hide();
        }
        
        // Price Information
        if(data.price || data.original_price || data.currency) {
            let priceHtml = '';
            
            if(data.original_price && data.price && data.original_price !== data.price) {
                priceHtml += `<span class="price-original">${data.currency || '₹'}${data.original_price}</span>`;
                priceHtml += `<span class="price-current">${data.currency || '₹'}${data.price}</span>`;
                
                // Calculate discount percentage
                let discount = Math.round(((data.original_price - data.price) / data.original_price) * 100);
                if(discount > 0) {
                    priceHtml += `<span class="price-discount">${discount}% OFF</span>`;
                }
            } else if(data.price) {
                priceHtml += `<span class="price-current">${data.currency || '₹'}${data.price}</span>`;
            }
            
            $('#productPriceInfo').html(priceHtml);
            $('#productPriceSection').show();
        } else {
            $('#productPriceSection').hide();
        }
        
        // Brand
        if(data.brand) {
            $('#productBrand').text(data.brand);
            $('#productBrandSection').show();
        } else {
            $('#productBrandSection').hide();
        }
        
        // Category
        if(data.category) {
            $('#productCategory').text(data.category);
            $('#productCategorySection').show();
        } else {
            $('#productCategorySection').hide();
        }
        
        // Specifications
        if(data.specifications && Object.keys(data.specifications).length > 0) {
            let specsHtml = '';
            for(let key in data.specifications) {
                specsHtml += `
                    <li>
                        <span class="spec-label">${key}</span>
                        <span class="spec-value">${data.specifications[key]}</span>
                    </li>
                `;
            }
            $('#productSpecs').html(specsHtml);
            $('#productSpecsSection').show();
        } else {
            $('#productSpecsSection').hide();
        }
        
        // Features
        if(data.features && Array.isArray(data.features) && data.features.length > 0) {
            let featuresHtml = '';
            data.features.forEach(feature => {
                featuresHtml += `<li>${feature}</li>`;
            });
            $('#productFeatures').html(featuresHtml);
            $('#productFeaturesSection').show();
        } else {
            $('#productFeaturesSection').hide();
        }
        
        // Availability
        if(data.availability) {
            $('#productAvailability').html(`
                <span class="badge ${data.availability.toLowerCase().includes('in stock') || data.availability.toLowerCase().includes('available') ? 'badge-success' : 'badge-warning'}">
                    ${data.availability}
                </span>
            `);
            $('#productAvailabilitySection').show();
        } else {
            $('#productAvailabilitySection').hide();
        }
        
        // Rating
        if(data.rating) {
            let starsHtml = '';
            let fullStars = Math.floor(data.rating);
            let hasHalfStar = data.rating % 1 >= 0.5;
            
            for(let i = 0; i < fullStars; i++) {
                starsHtml += '★';
            }
            if(hasHalfStar) starsHtml += '½';
            
            let reviewCount = data.review_count ? ` (${data.review_count} reviews)` : '';
            $('#productRating').html(`
                <span class="product-rating">${starsHtml}</span>
                <span class="text-muted small">${data.rating}${reviewCount}</span>
            `).show();
        } else {
            $('#productRating').hide();
        }
        
        // Badges
        let badgesHtml = '';
        if(data.badges && Array.isArray(data.badges)) {
            data.badges.forEach(badge => {
                badgesHtml += `<span class="info-badge info-badge-primary">${badge}</span>`;
            });
        }
        if(data.availability && data.availability.toLowerCase().includes('in stock')) {
            badgesHtml += `<span class="info-badge info-badge-success">In Stock</span>`;
        }
        if(data.brand) {
            badgesHtml += `<span class="info-badge info-badge-primary">${data.brand}</span>`;
        }
        $('#productBadges').html(badgesHtml);
        
        // Source
        $('#productSource').text(data.source || 'Unknown Source');
        if(data.link) {
            $('#productSourceLink').attr('href', data.link).show();
        } else {
            $('#productSourceLink').hide();
        }
    }
    
    // Use product info button
    $('#useProductInfo').on('click', function() {
        if(currentProductInfo && currentProductInfo.success && currentProductInfo.data) {
            let data = Array.isArray(currentProductInfo.data) ?
                       currentProductInfo.data[0] : currentProductInfo.data;
            
            // Fill name field
            if(data.title) {
                $('#itemName').val(data.title);
                $('#nameAutoFill').show();
            }
            
            // Fill description field
            if(data.snippet || data.description) {
                $('#itemDescription').val(data.snippet || data.description);
                $('#descAutoFill').show();
            }
            
            // Fill price if available
            if(data.price) {
                // Remove currency symbols and extract numeric value
                let priceValue = parseFloat(data.price.toString().replace(/[^0-9.]/g, ''));
                if(!isNaN(priceValue)) {
                    $('#itemPrice').val(priceValue);
                    $('#priceAutoFill').show();
                }
            }
            
            // Intelligent category suggestion
            suggestCategoriesFromProduct(data);
            
            // Hide product info section after filling
            $('#productInfoSection').hide();
            
            toastr.success('{{translate("Form fields filled with product information. AI is suggesting categories!")}}');
            
            // Scroll to category section
            setTimeout(() => {
                $('html, body').animate({
                    scrollTop: $('#categorySuggestions').offset().top - 100
                }, 500);
            }, 300);
        } else {
            toastr.error('{{translate("No product information available")}}');
        }
    });
    
    // Match and select category based on product category
    function matchAndSelectCategory(productCategory) {
        let $categorySelect = $('#itemCategory');
        let categories = $categorySelect.find('option');
        let matched = false;
        
        // Try exact match first
        categories.each(function() {
            if($(this).text().toLowerCase() === productCategory.toLowerCase()) {
                $categorySelect.val($(this).val()).trigger('change');
                $('#categoryAutoFill').show();
                matched = true;
                return false;
            }
        });
        
        // Try partial match if exact match fails
        if(!matched) {
            categories.each(function() {
                let categoryText = $(this).text().toLowerCase();
                let searchText = productCategory.toLowerCase();
                if(categoryText.includes(searchText) || searchText.includes(categoryText)) {
                    $categorySelect.val($(this).val()).trigger('change');
                    $('#categoryAutoFill').show();
                    return false;
                }
            });
        }
    }
    
    // Intelligent category suggestion from product data
    function suggestCategoriesFromProduct(productData) {
        let $categorySelect = $('#itemCategory');
        let availableCategories = [];
        
        // Get all available categories
        $categorySelect.find('option').each(function() {
            if($(this).val()) {
                availableCategories.push({
                    id: $(this).val(),
                    name: $(this).text().toLowerCase(),
                    element: $(this)
                });
            }
        });
        
        // Collect all product information for analysis
        let productText = [
            productData.title || '',
            productData.snippet || '',
            productData.description || '',
            productData.category || '',
            productData.brand || '',
            (productData.features || []).join(' '),
            Object.keys(productData.specifications || {}).join(' '),
            Object.values(productData.specifications || {}).join(' ')
        ].join(' ').toLowerCase();
        
        let categoryScores = [];
        
        // Score each available category
        availableCategories.forEach(category => {
            let score = 0;
            let matchedKeywords = [];
            
            // Direct name match (highest priority)
            if(productText.includes(category.name)) {
                score += 100;
                matchedKeywords.push(category.name);
            }
            
            // Check if product category matches
            if(productData.category) {
                let prodCat = productData.category.toLowerCase();
                if(prodCat.includes(category.name) || category.name.includes(prodCat)) {
                    score += 80;
                    matchedKeywords.push('category match');
                }
            }
            
            // Keyword matching using our mapping
            for(let [keywordCategory, keywords] of Object.entries(categoryKeywords)) {
                if(category.name.includes(keywordCategory) || keywordCategory.includes(category.name)) {
                    keywords.forEach(keyword => {
                        if(productText.includes(keyword)) {
                            score += 5;
                            if(!matchedKeywords.includes(keyword)) {
                                matchedKeywords.push(keyword);
                            }
                        }
                    });
                }
            }
            
            // Partial word matching
            let categoryWords = category.name.split(' ');
            categoryWords.forEach(word => {
                if(word.length > 3 && productText.includes(word)) {
                    score += 10;
                    if(!matchedKeywords.includes(word)) {
                        matchedKeywords.push(word);
                    }
                }
            });
            
            if(score > 0) {
                categoryScores.push({
                    id: category.id,
                    name: category.element.text(),
                    score: score,
                    confidence: Math.min(Math.round((score / 100) * 100), 100),
                    keywords: matchedKeywords
                });
            }
        });
        
        // Sort by score
        categoryScores.sort((a, b) => b.score - a.score);
        
        // Take top 5 suggestions
        suggestedCategories = categoryScores.slice(0, 5);
        
        if(suggestedCategories.length > 0) {
            displayCategorySuggestions(suggestedCategories);
            
            // Auto-select the highest confidence match if it's above 70%
            if(suggestedCategories[0].confidence >= 70) {
                $categorySelect.val(suggestedCategories[0].id).trigger('change');
                $('#categoryAutoFill').show();
                
                // Highlight the selected suggestion
                setTimeout(() => {
                    $(`.category-suggestion-item[data-category-id="${suggestedCategories[0].id}"]`).addClass('selected');
                }, 300);
            }
        }
    }
    
    // Display category suggestions
    function displayCategorySuggestions(suggestions) {
        if(suggestions.length === 0) return;
        
        let suggestionsHtml = '';
        suggestions.forEach(suggestion => {
            let confidenceClass = suggestion.confidence >= 70 ? 'high' : suggestion.confidence >= 40 ? 'medium' : 'low';
            suggestionsHtml += `
                <span class="category-suggestion-item" data-category-id="${suggestion.id}" title="Matched keywords: ${suggestion.keywords.join(', ')}">
                    ${suggestion.name}
                    <span class="confidence-badge">${suggestion.confidence}%</span>
                </span>
            `;
        });
        
        $('#categorySuggestionsContainer').html(suggestionsHtml);
        $('#categorySuggestions').slideDown(300);
        
        // Handle suggestion click
        $('.category-suggestion-item').on('click', function() {
            let categoryId = $(this).data('category-id');
            
            $('.category-suggestion-item').removeClass('selected');
            $(this).addClass('selected');
            
            $('#itemCategory').val(categoryId).trigger('change');
            $('#categoryAutoFill').show();
            
            toastr.success('{{translate("Category selected from suggestion")}}');
        });
    }
    
    // Suggest subcategories based on selected category and product data
    function suggestSubcategoriesFromProduct(productData, categoryId) {
        // Wait for subcategories to load
        setTimeout(() => {
            let $subcategorySelect = $('#itemSubCategory');
            let availableSubcategories = [];
            
            // Get all loaded subcategories
            $subcategorySelect.find('option').each(function() {
                if($(this).val()) {
                    availableSubcategories.push({
                        id: $(this).val(),
                        name: $(this).text().toLowerCase(),
                        element: $(this)
                    });
                }
            });
            
            if(availableSubcategories.length === 0) return;
            
            // Collect product information
            let productText = [
                productData.title || '',
                productData.snippet || '',
                productData.description || '',
                productData.category || '',
                productData.brand || '',
                (productData.features || []).join(' ')
            ].join(' ').toLowerCase();
            
            let subcategoryScores = [];
            
            // Score each subcategory
            availableSubcategories.forEach(subcategory => {
                let score = 0;
                let matchedKeywords = [];
                
                // Direct name match
                if(productText.includes(subcategory.name)) {
                    score += 100;
                    matchedKeywords.push(subcategory.name);
                }
                
                // Brand match
                if(productData.brand && subcategory.name.includes(productData.brand.toLowerCase())) {
                    score += 60;
                    matchedKeywords.push('brand match');
                }
                
                // Partial word matching
                let subcategoryWords = subcategory.name.split(' ');
                subcategoryWords.forEach(word => {
                    if(word.length > 3 && productText.includes(word)) {
                        score += 15;
                        if(!matchedKeywords.includes(word)) {
                            matchedKeywords.push(word);
                        }
                    }
                });
                
                if(score > 0) {
                    subcategoryScores.push({
                        id: subcategory.id,
                        name: subcategory.element.text(),
                        score: score,
                        confidence: Math.min(Math.round((score / 100) * 100), 100),
                        keywords: matchedKeywords
                    });
                }
            });
            
            // Sort by score
            subcategoryScores.sort((a, b) => b.score - a.score);
            
            // Take top 3 suggestions
            suggestedSubcategories = subcategoryScores.slice(0, 3);
            
            if(suggestedSubcategories.length > 0) {
                displaySubcategorySuggestions(suggestedSubcategories);
                
                // Auto-select if confidence is very high
                if(suggestedSubcategories[0].confidence >= 80) {
                    $subcategorySelect.val(suggestedSubcategories[0].id).trigger('change');
                    $('#subcategoryAutoFill').show();
                    
                    setTimeout(() => {
                        $(`.subcategory-suggestion-item[data-subcategory-id="${suggestedSubcategories[0].id}"]`).addClass('selected');
                    }, 300);
                }
            }
        }, 1000); // Wait for subcategories to load via AJAX
    }
    
    // Display subcategory suggestions
    function displaySubcategorySuggestions(suggestions) {
        if(suggestions.length === 0) return;
        
        let suggestionsHtml = '';
        suggestions.forEach(suggestion => {
            suggestionsHtml += `
                <span class="subcategory-suggestion-item" data-subcategory-id="${suggestion.id}" title="Matched keywords: ${suggestion.keywords.join(', ')}">
                    ${suggestion.name}
                    <span class="confidence-badge">${suggestion.confidence}%</span>
                </span>
            `;
        });
        
        $('#subcategorySuggestionsContainer').html(suggestionsHtml);
        $('#subcategorySuggestions').slideDown(300);
        
        // Handle suggestion click
        $('.subcategory-suggestion-item').on('click', function() {
            let subcategoryId = $(this).data('subcategory-id');
            
            $('.subcategory-suggestion-item').removeClass('selected');
            $(this).addClass('selected');
            
            $('#itemSubCategory').val(subcategoryId).trigger('change');
            $('#subcategoryAutoFill').show();
            
            toastr.success('{{translate("Subcategory selected from suggestion")}}');
        });
    }
    
    // Manual entry button
    $('#manualEntry').on('click', function() {
        $('#productInfoSection').hide();
        $('#itemName').focus();
        toastr.info('{{translate("Please enter product information manually")}}');
    });
    
    // Refetch product info button
    $('#refetchProductInfo').on('click', function() {
        let barcode = $('#modalBarcode').val();
        if(!barcode) {
            toastr.warning('{{translate("No barcode available to refetch")}}');
            return;
        }
        
        $(this).prop('disabled', true).html('<i class="tio-sync spinning"></i> {{translate("Fetching...")}}');
        
        // Reset and show loader
        $('#productInfoCard').hide();
        $('#multipleResultsSection').hide();
        $('#noProductInfo').hide();
        $('#productInfoLoader').show();
        $('#productInfoSection').show();
        
        // Make AJAX call to refetch
        $.ajax({
            url: '{{ route("admin.item.barcode_scan.process") }}',
            type: 'POST',
            data: {
                barcode: barcode,
                store_id: $('#modalStoreId').val(),
                force_refetch: true,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.product_info) {
                    currentProductInfo = response.product_info;
                    handleProductInfoDisplay(response.product_info);
                    toastr.success('{{translate("Product information refreshed")}}');
                } else {
                    $('#productInfoLoader').hide();
                    $('#noProductInfo').show();
                }
            },
            error: function() {
                $('#productInfoLoader').hide();
                $('#noProductInfo').show();
                toastr.error('{{translate("Failed to fetch product information")}}');
            },
            complete: function() {
                $('#refetchProductInfo').prop('disabled', false)
                    .html('<i class="tio-refresh"></i> {{translate("Fetch Again")}}');
            }
        });
    });
    
    function resetModalForm() {
        $('#itemName').val('');
        $('#itemPrice').val('');
        $('#itemStock').val(6);
        $('#itemDescription').val('');
        $('#itemDiscount').val(0);
        $('#maxCartQty').val(10);
        $('#itemCategory').val('').trigger('change');
        $('#itemSubCategory').html('<option value="">{{translate("Select Sub Category")}}</option>').prop('disabled', true);
        $('#itemUnit').val('');
        $('#itemVeg').val(1);
        $('#availableTimeStart').val('00:00');
        $('#availableTimeEnd').val('23:59');
        $('#itemImage').val('');
        $('#itemRack').val('');
        $('#itemRow').val('');
        
        $('#choice_attributes').val(null).trigger('change');
        $('#customer_choice_options').html('');
        $('#variations_container').html('');
        $('#variations_section').hide();
        
        $('#productInfoSection').hide();
        $('#productInfoCard').hide();
        $('#multipleResultsSection').hide();
        $('#noProductInfo').hide();
        $('#productInfoLoader').hide();
        $('#nameAutoFill').hide();
        $('#descAutoFill').hide();
        $('#priceAutoFill').hide();
        $('#categoryAutoFill').hide();
        $('#subcategoryAutoFill').hide();
        
        // Hide category and subcategory suggestions
        $('#categorySuggestions').hide();
        $('#subcategorySuggestions').hide();
        
        currentProductInfo = null;
        allProductResults = [];
        suggestedCategories = [];
        suggestedSubcategories = [];
    }
    
    function showScanResult(item, updated, action = null) {
        let alertClass = 'info';
        let statusText = 'New item added successfully';
        let showEditButton = false;
        
        if (updated) {
            alertClass = 'success';
            statusText = 'Item status set to active and stock updated to 6';
            showEditButton = true;
        } else if (action === 'already_updated') {
            alertClass = 'info';
            statusText = 'Item status is already active - no changes made';
            showEditButton = true;
        }
        
        let editButtonHtml = '';
        if(showEditButton) {
            editButtonHtml = `
                <button class="btn btn-sm btn-primary mt-2" onclick="openQuickEdit(${item.id})">
                    <i class="tio-edit"></i> {{translate('Edit Price & Stock')}}
                </button>
            `;
        }
        
        let resultHtml = `
            <div class="alert alert-${alertClass}">
                <div class="row">
                    <div class="col-md-2">
                        <img src="${item.image_full_url || '{{asset('public/assets/admin/img/160x160/img2.jpg')}}'}"
                            class="img-fluid rounded"
                            onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                            alt="${item.name}">
                    </div>
                    <div class="col-md-10">
                        <h5>${item.name}</h5>
                        <p class="mb-1"><strong>Barcode:</strong> ${item.barcode || 'N/A'}</p>
                        <p class="mb-1"><strong>Store:</strong> ${item.store ? item.store.name : 'N/A'}</p>
                        <p class="mb-1"><strong>Price:</strong> ₹${item.price.toFixed(2)}</p>
                        <p class="mb-1"><strong>Stock:</strong> ${item.stock}</p>
                        <p class="mb-2"><strong>Status:</strong> <span class="badge badge-${item.status ? 'success' : 'danger'}">${item.status ? 'Active' : 'Inactive'}</span></p>
                        ${editButtonHtml}
                    </div>
                </div>
            </div>
            <p class="text-${alertClass === 'success' ? 'success' : 'info'} mb-0">
                <i class="tio-checkmark-circle"></i>
                ${statusText}
            </p>
        `;
        
        $('#scanResultsBody').html(resultHtml);
        $('#scanResultsCard').show();
        
        // Store item data for quick edit
        if(showEditButton) {
            window.currentScannedItem = item;
        }
    }
    
    // Add new item form submission
    $('#addNewItemForm').on('submit', function(e) {
        e.preventDefault();
        
        let $submitBtn = $('#saveNewItemBtn');
        let originalText = $submitBtn.html();
        
        if($submitBtn.prop('disabled')) {
            return false;
        }
        
        $submitBtn.prop('disabled', true).html('<i class="tio-sync"></i> {{translate("Saving Item...")}}');
        
        // Validate required fields
        if(!$('#itemName').val().trim()) {
            toastr.error('{{translate("Item name is required")}}');
            $submitBtn.prop('disabled', false).html(originalText);
            $('#itemName').focus();
            return false;
        }
        
        if(!$('#itemPrice').val() || parseFloat($('#itemPrice').val()) <= 0) {
            toastr.error('{{translate("Valid price is required")}}');
            $submitBtn.prop('disabled', false).html(originalText);
            $('#itemPrice').focus();
            return false;
        }
        
        if(!$('#itemCategory').val()) {
            toastr.error('{{translate("Category is required")}}');
            $submitBtn.prop('disabled', false).html(originalText);
            $('#itemCategory').focus();
            return false;
        }
        
        let formData = new FormData(this);
        
        let hasAttributes = $('#choice_attributes').val() && $('#choice_attributes').val().length > 0;
        
        if (hasAttributes) {
            let variations = [];
            
            $('#variations_container tr').each(function() {
                let type = $(this).find('input[name*="[type]"]').val();
                let price = parseFloat($(this).find('input[name*="[price]"]').val());
                let stock = parseInt($(this).find('input[name*="[stock]"]').val());
                
                variations.push({
                    type: type,
                    price: price,
                    stock: stock
                });
            });
            
            if (variations.length > 0) {
                formData.append('variations_data', JSON.stringify(variations));
            }
            
            let choice_options = [];
            $('.attribute-input-group').each(function() {
                let attributeId = $(this).data('attribute-id');
                let attributeName = $(this).find('.attribute-label').text();
                let values = $(this).find('.attribute-values').val() ? $(this).find('.attribute-values').val().split(',') : [];
                
                if(values.length > 0) {
                    let temp = {
                        'name': 'choice_' + attributeId,
                        'title': attributeName,
                        'options': values.map(v => v.trim())
                    };
                    choice_options.push(temp);
                }
            });
            
            if(choice_options.length > 0) {
                formData.append('choice_options', JSON.stringify(choice_options));
            }
        }
        
        $.ajax({
            url: '{{ route("admin.item.barcode_scan.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.success) {
                    $('#addNewItemModal').modal('hide');
                    toastr.success(response.message || '{{translate("Item added successfully")}}');
                    showScanResult(response.item, false);
                    
                    setTimeout(function() {
                        $('#barcodeScanner').val('').focus();
                    }, 500);
                } else {
                    toastr.error(response.message || '{{translate("Failed to add item")}}');
                }
            },
            error: function(xhr) {
                console.error('Add item error:', xhr);
                
                if(xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        toastr.error(Array.isArray(value) ? value[0] : value);
                    });
                } else if(xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('{{translate("Something went wrong")}}');
                }
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Modal event handlers
    $('#addNewItemModal').on('shown.bs.modal', function () {
        if(currentProductInfo) {
            $('#productInfoSection').show();
        }
        
        setTimeout(function() {
            $('#itemName').focus();
        }, 100);
    });
    
    $('#addNewItemModal').on('hidden.bs.modal', function () {
        resetModalForm();
        setTimeout(function() {
            $('#barcodeScanner').focus();
        }, 300);
    });
    
    // Helper functions
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
    
    function updateBarcodeStatus(type, text) {
        let icon = type === 'success' ? 'tio-checkmark-circle' :
                type === 'warning' ? 'tio-info-outined' :
                type === 'info' ? 'tio-time' : 'tio-clear-circle';
        let colorClass = type === 'success' ? 'text-success' :
                    type === 'warning' ? 'text-warning' :
                    type === 'info' ? 'text-info' : 'text-danger';
        
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
});

// Global function for quick edit (accessible from inline onclick)
function openQuickEdit(itemId) {
    let item = window.currentScannedItem;
    
    if(!item || item.id != itemId) {
        toastr.error('{{translate("Item data not found")}}');
        return;
    }
    
    // Populate modal with item data
    $('#editItemId').val(item.id);
    $('#editStoreId').val($('#storeSelect').val());
    $('#editItemImage').attr('src', item.image_full_url || '{{asset('public/assets/admin/img/160x160/img2.jpg')}}');
    $('#editItemName').text(item.name);
    $('#editItemBarcode').text(item.barcode || 'N/A');
    $('#editItemCategory').text(item.category ? item.category.name : 'N/A');
    $('#editItemStatus').html(`<span class="badge badge-${item.status ? 'success' : 'danger'}">${item.status ? 'Active' : 'Inactive'}</span>`);
    
    // Price section
    $('#editCurrentPrice').val(item.price.toFixed(2));
    $('#editNewPrice').val(item.price.toFixed(2));
    
    // Stock section
    $('#editCurrentStock').val(item.stock);
    $('#editAddStock').val(0);
    $('#editNewTotalStock').val(item.stock);
    
    // Discount section
    $('#editCurrentDiscount').val(item.discount ? item.discount.toFixed(2) : '0.00');
    $('#editNewDiscount').val(item.discount ? item.discount.toFixed(2) : '0');
    
    // Status toggle
    $('#editItemStatusToggle').prop('checked', item.status == 1);
    
    // Reset alerts
    $('#priceDifferenceText').text('{{translate("Enter new price to see the difference")}}');
    $('#stockChangeText').text('{{translate("Enter quantity to add to existing stock")}}');
    
    // Show modal
    $('#quickEditModal').modal('show');
}

// Quick Edit Form handlers
$(document).ready(function() {
    // Calculate price difference on input
    $('#editNewPrice').on('input', function() {
        let currentPrice = parseFloat($('#editCurrentPrice').val()) || 0;
        let newPrice = parseFloat($(this).val()) || 0;
        let difference = newPrice - currentPrice;
        
        if(difference > 0) {
            $('#priceDifferenceText').html(`
                <i class="tio-arrow-large-up"></i>
                {{translate("Price will increase by")}} <strong>₹${difference.toFixed(2)}</strong>
                <span class="text-danger">(+${((difference/currentPrice)*100).toFixed(1)}%)</span>
            `).parent().removeClass('alert-info alert-success').addClass('alert-warning');
        } else if(difference < 0) {
            $('#priceDifferenceText').html(`
                <i class="tio-arrow-large-down"></i>
                {{translate("Price will decrease by")}} <strong>₹${Math.abs(difference).toFixed(2)}</strong>
                <span class="text-success">(${((difference/currentPrice)*100).toFixed(1)}%)</span>
            `).parent().removeClass('alert-info alert-warning').addClass('alert-success');
        } else {
            $('#priceDifferenceText').text('{{translate("No change in price")}}')
                .parent().removeClass('alert-warning alert-success').addClass('alert-info');
        }
    });
    
    // Calculate new total stock on input
    $('#editAddStock').on('input', function() {
        let currentStock = parseInt($('#editCurrentStock').val()) || 0;
        let addStock = parseInt($(this).val()) || 0;
        let newTotal = currentStock + addStock;
        
        $('#editNewTotalStock').val(newTotal);
        
        if(addStock > 0) {
            $('#stockChangeText').html(`
                <i class="tio-checkmark-circle"></i>
                {{translate("Adding")}} <strong>${addStock}</strong> {{translate("units")}}.
                {{translate("New total will be")}} <strong>${newTotal}</strong> {{translate("units")}}
            `);
        } else {
            $('#stockChangeText').text('{{translate("Enter quantity to add to existing stock")}}');
        }
    });
    
    // Quick Edit Form Submission
    $('#quickEditForm').on('submit', function(e) {
        e.preventDefault();
        
        let $submitBtn = $('#quickEditSaveBtn');
        let originalText = $submitBtn.html();
        
        if($submitBtn.prop('disabled')) {
            return false;
        }
        
        // Validate
        let newPrice = parseFloat($('#editNewPrice').val());
        let addStock = parseInt($('#editAddStock').val());
        
        if(!newPrice || newPrice <= 0) {
            toastr.error('{{translate("Please enter a valid price")}}');
            $('#editNewPrice').focus();
            return false;
        }
        
        if(addStock < 0) {
            toastr.error('{{translate("Stock to add cannot be negative")}}');
            $('#editAddStock').focus();
            return false;
        }
        
        $submitBtn.prop('disabled', true).html('<i class="tio-sync spinning"></i> {{translate("Updating...")}}');
        
        let formData = {
            item_id: $('#editItemId').val(),
            store_id: $('#editStoreId').val(),
            price: newPrice,
            add_stock: addStock,
            discount: parseFloat($('#editNewDiscount').val()) || 0,
            status: $('#editItemStatusToggle').is(':checked') ? 1 : 0,
            _token: '{{ csrf_token() }}'
        };
        
        $.ajax({
            url: '{{ route("admin.item.barcode_scan.quick_update") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if(response.success) {
                    $('#quickEditModal').modal('hide');
                    toastr.success(response.message || '{{translate("Item updated successfully")}}');
                    
                    // Update the scan result display
                    if(response.item) {
                        window.currentScannedItem = response.item;
                        showScanResult(response.item, true);
                    }
                    
                    // Clear and focus scanner
                    setTimeout(function() {
                        $('#barcodeScanner').val('').focus();
                    }, 500);
                } else {
                    toastr.error(response.message || '{{translate("Failed to update item")}}');
                }
            },
            error: function(xhr) {
                console.error('Quick edit error:', xhr);
                
                if(xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        toastr.error(Array.isArray(value) ? value[0] : value);
                    });
                } else if(xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('{{translate("Something went wrong")}}');
                }
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Reset form on modal close
    $('#quickEditModal').on('hidden.bs.modal', function() {
        $('#quickEditForm')[0].reset();
        $('#priceDifferenceText').text('{{translate("Enter new price to see the difference")}}')
            .parent().removeClass('alert-warning alert-success').addClass('alert-info');
        $('#stockChangeText').text('{{translate("Enter quantity to add to existing stock")}}');
    });
});
</script>
@endpush
