@extends('layouts.admin.app')

@section('title', translate('WhatsApp Campaigns'))

@push('css_or_js')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        .campaign-filters {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .status-badge {
            min-width: 80px;
            text-align: center;
        }
        .action-buttons .btn {
            margin: 0 2px;
        }
        .table-actions {
            white-space: nowrap;
        }
        .progress-cell {
            min-width: 120px;
        }
        .stats-pill {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
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
                    <i class="tio-telegram"></i> {{translate('WhatsApp Campaigns')}}
                </h1>
                <p class="text-muted">{{translate('Manage and monitor your WhatsApp broadcast campaigns')}}</p>
            </div>
            <div class="col-sm-auto">
                <a class="btn btn-primary" href="{{ route('admin.whatsapp.campaigns.create') }}">
                    <i class="tio-add"></i> {{translate('Create Campaign')}}
                </a>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <!-- Filters -->
    <div class="card campaign-filters">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">{{translate('Status')}}</label>
                <select class="form-control" id="filterStatus">
                    <option value="">{{translate('All Status')}}</option>
                    <option value="draft">{{translate('Draft')}}</option>
                    <option value="running">{{translate('Running')}}</option>
                    <option value="completed">{{translate('Completed')}}</option>
                    <option value="failed">{{translate('Failed')}}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{translate('Date Range')}}</label>
                <input type="text" class="form-control" id="filterDateRange" placeholder="{{translate('Select date range')}}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{translate('Search')}}</label>
                <input type="text" class="form-control" id="filterSearch" placeholder="{{translate('Search campaigns...')}}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-secondary w-100" id="resetFilters">
                    <i class="tio-clear"></i> {{translate('Reset')}}
                </button>
            </div>
        </div>
    </div>
    <!-- End Filters -->

    <!-- Campaigns Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-header-title">{{translate('All Campaigns')}}</h5>
        </div>

        <div class="table-responsive datatable-custom">
            <table id="campaignsTable" class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table" style="width:100%">
                <thead class="thead-light">
                    <tr>
                        <th>{{translate('ID')}}</th>
                        <th>{{translate('Campaign Name')}}</th>
                        <th>{{translate('Status')}}</th>
                        <th>{{translate('Segments')}}</th>
                        <th class="text-center">{{translate('Total')}}</th>
                        <th class="text-center">{{translate('Sent')}}</th>
                        <th class="text-center">{{translate('Delivered')}}</th>
                        <th class="text-center">{{translate('Read')}}</th>
                        <th class="text-center">{{translate('Failed')}}</th>
                        <th>{{translate('Started At')}}</th>
                        <th class="text-center">{{translate('Actions')}}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
    <!-- End Campaigns Table -->
</div>

<!-- Cancel Campaign Modal -->
<div class="modal fade" id="cancelCampaignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{translate('Cancel Campaign')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{translate('Are you sure you want to cancel this campaign? This action cannot be undone.')}}</p>
                <p class="text-muted"><small>{{translate('Messages already sent will not be affected.')}}</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{translate('Close')}}</button>
                <button type="button" class="btn btn-danger" id="confirmCancelCampaign">
                    <i class="tio-clear"></i> {{translate('Yes, Cancel Campaign')}}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Campaign Modal -->
<div class="modal fade" id="deleteCampaignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{translate('Delete Campaign')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{translate('Are you sure you want to delete this campaign? This action cannot be undone.')}}</p>
                <p class="text-danger"><small>{{translate('All campaign data and analytics will be permanently deleted.')}}</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{translate('Close')}}</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCampaign">
                    <i class="tio-delete"></i> {{translate('Yes, Delete Campaign')}}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    'use strict';

    let campaignsTable;
    let selectedCampaignId = null;

    $(document).ready(function() {
        // Initialize Date Range Picker
        $('#filterDateRange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: '{{translate("Clear")}}',
                format: 'YYYY-MM-DD'
            }
        });

        $('#filterDateRange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            campaignsTable.ajax.reload();
        });

        $('#filterDateRange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            campaignsTable.ajax.reload();
        });

        // Initialize DataTable
        campaignsTable = $('#campaignsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("admin.whatsapp.campaigns.index") }}',
                data: function(d) {
                    d.status = $('#filterStatus').val();
                    d.date_range = $('#filterDateRange').val();
                    d.search_query = $('#filterSearch').val();
                }
            },
            columns: [
                { data: 'id', name: 'id', width: '50px' },
                { data: 'name', name: 'name' },
                { data: 'status', name: 'status', orderable: false },
                { data: 'segments', name: 'segments', orderable: false },
                { data: 'total_recipients', name: 'total_recipients', className: 'text-center' },
                { data: 'sent_count', name: 'sent_count', className: 'text-center' },
                { data: 'delivered_count', name: 'delivered_count', className: 'text-center' },
                { data: 'read_count', name: 'read_count', className: 'text-center' },
                { data: 'failed_count', name: 'failed_count', className: 'text-center' },
                { data: 'started_at', name: 'started_at' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center table-actions' }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">{{translate("Loading...")}}</span></div>',
                emptyTable: '{{translate("No campaigns found")}}',
                zeroRecords: '{{translate("No matching campaigns found")}}'
            }
        });

        // Filter handlers
        $('#filterStatus').on('change', function() {
            campaignsTable.ajax.reload();
        });

        $('#filterSearch').on('keyup', debounce(function() {
            campaignsTable.ajax.reload();
        }, 500));

        $('#resetFilters').on('click', function() {
            $('#filterStatus').val('');
            $('#filterDateRange').val('');
            $('#filterSearch').val('');
            campaignsTable.ajax.reload();
        });

        // View Analytics
        $(document).on('click', '.view-analytics', function() {
            const campaignId = $(this).data('id');
            window.location.href = '{{ route("admin.whatsapp.campaigns.show", ":id") }}'.replace(':id', campaignId);
        });

        // Cancel Campaign
        $(document).on('click', '.cancel-campaign', function() {
            selectedCampaignId = $(this).data('id');
            $('#cancelCampaignModal').modal('show');
        });

        $('#confirmCancelCampaign').on('click', function() {
            if (!selectedCampaignId) return;

            const $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{translate("Cancelling...")}}');

            $.ajax({
                url: '{{ route("admin.whatsapp.campaigns.cancel", ":id") }}'.replace(':id', selectedCampaignId),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#cancelCampaignModal').modal('hide');
                    toastr.success(response.message || '{{translate("Campaign cancelled successfully")}}');
                    campaignsTable.ajax.reload();
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || '{{translate("Failed to cancel campaign")}}';
                    toastr.error(message);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="tio-clear"></i> {{translate("Yes, Cancel Campaign")}}');
                    selectedCampaignId = null;
                }
            });
        });

        // Duplicate Campaign
        $(document).on('click', '.duplicate-campaign', function() {
            const campaignId = $(this).data('id');
            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: '{{ route("admin.whatsapp.campaigns.duplicate", ":id") }}'.replace(':id', campaignId),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message || '{{translate("Campaign duplicated successfully")}}');
                    campaignsTable.ajax.reload();
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || '{{translate("Failed to duplicate campaign")}}';
                    toastr.error(message);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Delete Campaign
        $(document).on('click', '.delete-campaign', function() {
            selectedCampaignId = $(this).data('id');
            $('#deleteCampaignModal').modal('show');
        });

        $('#confirmDeleteCampaign').on('click', function() {
            if (!selectedCampaignId) return;

            const $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{translate("Deleting...")}}');

            $.ajax({
                url: '{{ route("admin.whatsapp.campaigns.destroy", ":id") }}'.replace(':id', selectedCampaignId),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#deleteCampaignModal').modal('hide');
                    toastr.success(response.message || '{{translate("Campaign deleted successfully")}}');
                    campaignsTable.ajax.reload();
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || '{{translate("Failed to delete campaign")}}';
                    toastr.error(message);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="tio-delete"></i> {{translate("Yes, Delete Campaign")}}');
                    selectedCampaignId = null;
                }
            });
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
