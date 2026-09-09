@extends('layouts.admin.app')

@section('title', translate('WhatsApp Customers'))

@push('css_or_js')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .customer-filters {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .customer-info {
            display: flex;
            align-items: center;
        }
        .customer-details {
            margin-left: 0.75rem;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        .status-dot.active {
            background-color: #28a745;
        }
        .status-dot.inactive {
            background-color: #dc3545;
        }
        .stats-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            background: #e7eaf3;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <i class="tio-user"></i> {{translate('WhatsApp Customers')}}
                </h1>
                <p class="text-muted">{{translate('Manage customers eligible for WhatsApp campaigns')}}</p>
            </div>
            <div class="col-sm-auto">
                <button class="btn btn-primary" id="sendBulkMessage">
                    <i class="tio-telegram"></i> {{translate('Send Message to Selected')}}
                </button>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <!-- Stats Overview -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="media-body">
                            <h6 class="card-subtitle mb-1">{{translate('Total Customers')}}</h6>
                            <h3 class="card-title">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                        <div class="ml-2">
                            <i class="tio-user text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="media-body">
                            <h6 class="card-subtitle mb-1">{{translate('Active')}}</h6>
                            <h3 class="card-title">{{ number_format($stats['active'] ?? 0) }}</h3>
                        </div>
                        <div class="ml-2">
                            <i class="tio-checkmark-circle text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="media-body">
                            <h6 class="card-subtitle mb-1">{{translate('With Orders')}}</h6>
                            <h3 class="card-title">{{ number_format($stats['with_orders'] ?? 0) }}</h3>
                        </div>
                        <div class="ml-2">
                            <i class="tio-shopping-cart text-info" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="media-body">
                            <h6 class="card-subtitle mb-1">{{translate('Total Revenue')}}</h6>
                            <h3 class="card-title">{{ \App\CentralLogics\Helpers::format_currency($stats['revenue'] ?? 0) }}</h3>
                        </div>
                        <div class="ml-2">
                            <i class="tio-money text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card customer-filters">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">{{translate('Segment')}}</label>
                <select class="form-control" id="filterSegment">
                    <option value="">{{translate('All Segments')}}</option>
                    @foreach($segments ?? [] as $segment)
                    <option value="{{ $segment['key'] }}">{{ $segment['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{translate('Zone')}}</label>
                <select class="form-control" id="filterZone">
                    <option value="">{{translate('All Zones')}}</option>
                    @foreach($zones ?? [] as $zone)
                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{translate('Status')}}</label>
                <select class="form-control" id="filterStatus">
                    <option value="">{{translate('All Status')}}</option>
                    <option value="active">{{translate('Active')}}</option>
                    <option value="inactive">{{translate('Inactive')}}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{translate('Search')}}</label>
                <input type="text" class="form-control" id="filterSearch"
                       placeholder="{{translate('Name, phone, email...')}}">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-outline-secondary w-100" id="resetFilters">
                    <i class="tio-clear"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- End Filters -->

    <!-- Customers Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-header-title">{{translate('Customer List')}}</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary" id="exportCustomers">
                    <i class="tio-download"></i> {{translate('Export')}}
                </button>
            </div>
        </div>

        <div class="table-responsive datatable-custom">
            <table id="customersTable" class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table" style="width:100%">
                <thead class="thead-light">
                    <tr>
                        <th class="text-center">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th>{{translate('ID')}}</th>
                        <th>{{translate('Customer')}}</th>
                        <th>{{translate('Phone')}}</th>
                        <th>{{translate('Zone')}}</th>
                        <th class="text-center">{{translate('Orders')}}</th>
                        <th class="text-end">{{translate('Total Spent')}}</th>
                        <th>{{translate('Last Order')}}</th>
                        <th>{{translate('Status')}}</th>
                        <th class="text-center">{{translate('Actions')}}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
    <!-- End Customers Table -->
</div>

<!-- Send Message Modal -->
<div class="modal fade" id="sendMessageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{translate('Send WhatsApp Message')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendMessageForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{translate('Recipient(s)')}}</label>
                        <input type="text" class="form-control" id="recipientPreview" readonly>
                        <input type="hidden" id="recipientIds" name="customer_ids">
                    </div>

                    <div class="mb-3">
                        <label for="messageText" class="form-label">
                            {{translate('Message')}} <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="messageText" name="message"
                                  rows="5" placeholder="{{translate('Type your message here...')}}" required
                                  maxlength="1000"></textarea>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">
                                {{translate('Variables')}}: {name}, {phone}, {zone}
                            </small>
                            <span class="character-counter">
                                <span id="msgCharCount">0</span>/1000
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{translate('Media (Optional)')}}</label>
                        <input type="file" class="form-control" id="messageMedia" name="media"
                               accept="image/*">
                        <small class="text-muted">{{translate('Supported: JPG, PNG (Max 5MB)')}}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{translate('Cancel')}}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="tio-telegram"></i> {{translate('Send Message')}}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Customer Details Modal -->
<div class="modal fade" id="customerDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{translate('Customer Details')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="customerDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">{{translate('Loading...')}}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    'use strict';

    let customersTable;
    let selectedCustomers = [];

    $(document).ready(function() {
        // Initialize DataTable
        customersTable = $('#customersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("admin.whatsapp.customers.index") }}',
                data: function(d) {
                    d.segment = $('#filterSegment').val();
                    d.zone = $('#filterZone').val();
                    d.status = $('#filterStatus').val();
                    d.search_query = $('#filterSearch').val();
                }
            },
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function(data) {
                        return `<input type="checkbox" class="customer-checkbox" value="${data}">`;
                    }
                },
                { data: 'id', name: 'id', width: '60px' },
                { data: 'customer', name: 'customer', orderable: false },
                { data: 'phone', name: 'phone' },
                { data: 'zone', name: 'zone' },
                { data: 'orders_count', name: 'orders_count', className: 'text-center' },
                { data: 'total_spent', name: 'total_spent', className: 'text-end' },
                { data: 'last_order', name: 'last_order' },
                { data: 'status', name: 'status', orderable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[1, 'desc']],
            pageLength: 50,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"></div>',
                emptyTable: '{{translate("No customers found")}}',
                zeroRecords: '{{translate("No matching customers found")}}'
            }
        });

        // Filter handlers
        $('#filterSegment, #filterZone, #filterStatus').on('change', function() {
            customersTable.ajax.reload();
        });

        $('#filterSearch').on('keyup', debounce(function() {
            customersTable.ajax.reload();
        }, 500));

        $('#resetFilters').on('click', function() {
            $('#filterSegment, #filterZone, #filterStatus, #filterSearch').val('');
            customersTable.ajax.reload();
        });

        // Select All
        $('#selectAll').on('change', function() {
            $('.customer-checkbox').prop('checked', $(this).prop('checked')).trigger('change');
        });

        // Individual checkbox
        $(document).on('change', '.customer-checkbox', function() {
            const customerId = $(this).val();
            if ($(this).is(':checked')) {
                if (!selectedCustomers.includes(customerId)) {
                    selectedCustomers.push(customerId);
                }
            } else {
                selectedCustomers = selectedCustomers.filter(id => id !== customerId);
                $('#selectAll').prop('checked', false);
            }
        });

        // Send Bulk Message
        $('#sendBulkMessage').on('click', function() {
            if (selectedCustomers.length === 0) {
                toastr.error('{{translate("Please select at least one customer")}}');
                return;
            }

            $('#recipientIds').val(selectedCustomers.join(','));
            $('#recipientPreview').val(`${selectedCustomers.length} ${selectedCustomers.length === 1 ? '{{translate("customer")}}' : '{{translate("customers")}}'} {{translate("selected")}}`);
            $('#sendMessageModal').modal('show');
        });

        // Message character counter
        $('#messageText').on('input', function() {
            $('#msgCharCount').text($(this).val().length);
        });

        // Send Message Form
        $('#sendMessageForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const $submitBtn = $(this).find('button[type="submit"]');
            const originalText = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{translate("Sending...")}}');

            $.ajax({
                url: '{{ route("admin.whatsapp.send-message") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message || '{{translate("Messages sent successfully")}}');
                    $('#sendMessageModal').modal('hide');
                    $('#sendMessageForm')[0].reset();
                    selectedCustomers = [];
                    $('.customer-checkbox').prop('checked', false);
                    $('#selectAll').prop('checked', false);
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || '{{translate("Failed to send messages")}}';
                    toastr.error(message);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        // View Customer Details
        $(document).on('click', '.view-customer', function() {
            const customerId = $(this).data('id');

            $('#customerDetailsModal').modal('show');
            $('#customerDetailsContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');

            $.ajax({
                url: '{{ route("admin.whatsapp.customers.show", ":id") }}'.replace(':id', customerId),
                method: 'GET',
                success: function(response) {
                    $('#customerDetailsContent').html(response);
                },
                error: function() {
                    $('#customerDetailsContent').html('<div class="text-center py-5"><p class="text-danger">{{translate("Failed to load customer details")}}</p></div>');
                }
            });
        });

        // Send Individual Message
        $(document).on('click', '.send-message', function() {
            const customerId = $(this).data('id');
            const customerName = $(this).data('name');

            selectedCustomers = [customerId];
            $('#recipientIds').val(customerId);
            $('#recipientPreview').val(customerName);
            $('#sendMessageModal').modal('show');
        });

        // Export Customers
        $('#exportCustomers').on('click', function() {
            const params = new URLSearchParams({
                segment: $('#filterSegment').val(),
                zone: $('#filterZone').val(),
                status: $('#filterStatus').val(),
                search_query: $('#filterSearch').val()
            });

            window.location.href = '{{ route("admin.whatsapp.customers.export") }}?' + params.toString();
            toastr.success('{{translate("Export started. Download will begin shortly.")}}');
        });
    });

    // Debounce helper
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
</script>
@endpush
