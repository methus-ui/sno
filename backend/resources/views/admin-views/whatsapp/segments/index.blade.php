@extends('layouts.admin.app')

@section('title', translate('Customer Segments'))

@push('css_or_js')
    <style>
        .segment-card {
            border: 2px solid #e7eaf3;
            border-radius: 0.5rem;
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
        }
        .segment-card:hover {
            border-color: #377dff;
            box-shadow: 0 4px 12px rgba(55, 125, 255, 0.15);
            transform: translateY(-3px);
        }
        .segment-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .segment-count {
            font-size: 2rem;
            font-weight: bold;
            color: #377dff;
        }
        .segment-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .segment-description {
            font-size: 0.875rem;
            color: #8c98a4;
        }
        .refresh-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
        .custom-segment-table td {
            vertical-align: middle;
        }
        .filter-row {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
            border: 1px solid #e7eaf3;
        }
        .modal-preview {
            max-height: 400px;
            overflow-y: auto;
        }
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .bg-gradient-success {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .bg-gradient-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .bg-gradient-warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .bg-gradient-danger {
            background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%);
        }
        .bg-gradient-secondary {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
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
                    <i class="tio-filter-list"></i> {{translate('Customer Segments')}}
                </h1>
                <p class="text-muted">{{translate('Organize customers into targeted groups for campaigns')}}</p>
            </div>
            <div class="col-sm-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSegmentModal">
                    <i class="tio-add"></i> {{translate('Create Custom Segment')}}
                </button>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <!-- Predefined Segments -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-header-title">{{translate('Predefined Segments')}}</h5>
            <button class="btn btn-sm btn-outline-primary" id="refreshAllSegments">
                <i class="tio-refresh"></i> {{translate('Refresh All')}}
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                @php
                    $gradients = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
                    $icons = ['tio-star', 'tio-chart-bar-4', 'tio-time', 'tio-shopping-cart', 'tio-heart', 'tio-user', 'tio-money', 'tio-archive'];
                @endphp

                @foreach($predefined_segments ?? [] as $index => $segment)
                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                    <div class="card segment-card" data-segment="{{ $segment['key'] }}">
                        <div class="card-body text-center position-relative">
                            <button class="btn btn-sm btn-white refresh-btn refresh-segment" data-segment="{{ $segment['key'] }}" title="{{translate('Refresh')}}">
                                <i class="tio-refresh"></i>
                            </button>

                            <div class="segment-icon bg-gradient-{{ $gradients[$index % count($gradients)] }} mx-auto text-white">
                                <i class="{{ $icons[$index % count($icons)] }}"></i>
                            </div>

                            <div class="segment-count" id="count_{{ $segment['key'] }}">
                                {{ number_format($segment['count']) }}
                            </div>

                            <h6 class="segment-name">{{ $segment['name'] }}</h6>
                            <p class="segment-description">{{ $segment['description'] }}</p>

                            <small class="text-muted d-block mb-2" id="updated_{{ $segment['key'] }}">
                                {{translate('Updated')}}: {{ $segment['updated_at'] ?? translate('Never') }}
                            </small>

                            <button class="btn btn-sm btn-outline-primary w-100 preview-segment"
                                    data-segment="{{ $segment['key'] }}"
                                    data-name="{{ $segment['name'] }}">
                                <i class="tio-visible"></i> {{translate('Preview')}}
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <!-- End Predefined Segments -->

    <!-- Custom Segments -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-header-title">{{translate('Custom Segments')}}</h5>
        </div>

        @if(isset($custom_segments) && count($custom_segments) > 0)
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table custom-segment-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{translate('Segment Name')}}</th>
                        <th>{{translate('Description')}}</th>
                        <th class="text-center">{{translate('Customers')}}</th>
                        <th>{{translate('Created')}}</th>
                        <th>{{translate('Last Updated')}}</th>
                        <th class="text-center">{{translate('Actions')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($custom_segments as $segment)
                    <tr>
                        <td><strong>{{ $segment->name }}</strong></td>
                        <td>{{ $segment->description ?? translate('No description') }}</td>
                        <td class="text-center">
                            <span class="badge badge-soft-primary rounded-pill">
                                {{ number_format($segment->customer_count) }}
                            </span>
                        </td>
                        <td>{{ $segment->created_at->format('M d, Y') }}</td>
                        <td>{{ $segment->updated_at->diffForHumans() }}</td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-white preview-custom-segment"
                                        data-id="{{ $segment->id }}"
                                        data-name="{{ $segment->name }}">
                                    <i class="tio-visible"></i>
                                </button>
                                <button class="btn btn-sm btn-white refresh-custom-segment"
                                        data-id="{{ $segment->id }}">
                                    <i class="tio-refresh"></i>
                                </button>
                                <button class="btn btn-sm btn-white edit-custom-segment"
                                        data-id="{{ $segment->id }}">
                                    <i class="tio-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-white text-danger delete-custom-segment"
                                        data-id="{{ $segment->id }}"
                                        data-name="{{ $segment->name }}">
                                    <i class="tio-delete"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="card-body text-center py-5">
            <i class="tio-filter-list" style="font-size: 4rem; opacity: 0.2;"></i>
            <p class="text-muted mt-3">{{translate('No custom segments created yet')}}</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSegmentModal">
                <i class="tio-add"></i> {{translate('Create Your First Segment')}}
            </button>
        </div>
        @endif
    </div>
    <!-- End Custom Segments -->
</div>

<!-- Create Custom Segment Modal -->
<div class="modal fade" id="createSegmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{translate('Create Custom Segment')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createSegmentForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="segmentName" class="form-label">
                            {{translate('Segment Name')}} <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="segmentName" name="name"
                               placeholder="{{translate('Enter segment name')}}" required>
                    </div>

                    <div class="mb-3">
                        <label for="segmentDescription" class="form-label">{{translate('Description')}}</label>
                        <textarea class="form-control" id="segmentDescription" name="description"
                                  rows="2" placeholder="{{translate('Optional description')}}"></textarea>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">{{translate('Filters')}}</h6>
                        <button type="button" class="btn btn-sm btn-primary" id="addFilterRow">
                            <i class="tio-add"></i> {{translate('Add Filter')}}
                        </button>
                    </div>

                    <div id="filtersContainer">
                        <!-- Filter rows will be added here -->
                    </div>

                    <div class="alert alert-info mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="tio-info"></i> {{translate('Estimated customers matching filters')}}:</span>
                            <strong id="estimatedCount">--</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{translate('Cancel')}}</button>
                    <button type="button" class="btn btn-outline-primary" id="previewSegment">
                        <i class="tio-visible"></i> {{translate('Preview')}}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="tio-save"></i> {{translate('Create Segment')}}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Segment Modal -->
<div class="modal fade" id="previewSegmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span id="previewSegmentName">{{translate('Segment Preview')}}</span>
                    <span class="badge badge-soft-primary ms-2" id="previewTotalCount">0</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="modal-preview" id="previewCustomersContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteSegmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{translate('Delete Segment')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{translate('Are you sure you want to delete')}} "<span id="deleteSegmentName"></span>"?</p>
                <p class="text-danger"><small>{{translate('This action cannot be undone.')}}</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{translate('Cancel')}}</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteSegment">
                    <i class="tio-delete"></i> {{translate('Delete')}}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    'use strict';

    let filterRowCount = 0;
    let segmentToDelete = null;

    $(document).ready(function() {
        // Add initial filter row
        addFilterRow();

        // Add Filter Row
        $('#addFilterRow').on('click', function() {
            addFilterRow();
        });

        function addFilterRow() {
            const filterHtml = `
                <div class="filter-row" data-row="${filterRowCount}">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2">
                            <label class="form-label">{{translate('Field')}}</label>
                            <select class="form-control filter-field" name="filters[${filterRowCount}][field]" required>
                                <option value="">{{translate('Select field')}}</option>
                                <option value="order_count">{{translate('Order Count')}}</option>
                                <option value="total_spent">{{translate('Total Spent')}}</option>
                                <option value="last_order_days">{{translate('Last Order (days ago)')}}</option>
                                <option value="zone_id">{{translate('Zone')}}</option>
                                <option value="status">{{translate('Status')}}</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">{{translate('Operator')}}</label>
                            <select class="form-control filter-operator" name="filters[${filterRowCount}][operator]" required>
                                <option value="=">=</option>
                                <option value=">">></option>
                                <option value="<"><</option>
                                <option value=">=">=</option>
                                <option value="<="><=</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">{{translate('Value')}}</label>
                            <input type="text" class="form-control filter-value" name="filters[${filterRowCount}][value]" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="button" class="btn btn-sm btn-danger w-100 remove-filter" data-row="${filterRowCount}">
                                <i class="tio-delete"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('#filtersContainer').append(filterHtml);
            filterRowCount++;
        }

        // Remove Filter Row
        $(document).on('click', '.remove-filter', function() {
            const row = $(this).data('row');
            $(`.filter-row[data-row="${row}"]`).remove();
            updateEstimatedCount();
        });

        // Update estimated count on filter change
        $(document).on('change', '.filter-field, .filter-operator, .filter-value', debounce(function() {
            updateEstimatedCount();
        }, 500));

        function updateEstimatedCount() {
            const formData = $('#createSegmentForm').serialize();

            $.ajax({
                url: '{{ route("admin.whatsapp.segments.estimate") }}',
                method: 'POST',
                data: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    $('#estimatedCount').text(response.count.toLocaleString());
                },
                error: function() {
                    $('#estimatedCount').text('--');
                }
            });
        }

        // Preview Segment
        $('#previewSegment').on('click', function() {
            const formData = $('#createSegmentForm').serialize();

            $('#previewSegmentModal').modal('show');
            $('#previewCustomersContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');

            $.ajax({
                url: '{{ route("admin.whatsapp.segments.preview") }}',
                method: 'POST',
                data: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    $('#previewSegmentName').text($('#segmentName').val() || '{{translate("Segment Preview")}}');
                    $('#previewTotalCount').text(response.total.toLocaleString());
                    $('#previewCustomersContent').html(response.html);
                },
                error: function() {
                    $('#previewCustomersContent').html('<p class="text-danger text-center">{{translate("Failed to load preview")}}</p>');
                }
            });
        });

        // Create Segment Form
        $('#createSegmentForm').on('submit', function(e) {
            e.preventDefault();

            const $submitBtn = $(this).find('button[type="submit"]');
            const originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{translate("Creating...")}}');

            $.ajax({
                url: '{{ route("admin.whatsapp.segments.store") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    toastr.success(response.message || '{{translate("Segment created successfully")}}');
                    setTimeout(() => location.reload(), 1000);
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || '{{translate("Failed to create segment")}}');
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Refresh Segment
        $(document).on('click', '.refresh-segment', function(e) {
            e.stopPropagation();
            const segment = $(this).data('segment');
            refreshSegmentCount(segment);
        });

        $(document).on('click', '.refresh-custom-segment', function() {
            const segmentId = $(this).data('id');
            refreshCustomSegment(segmentId);
        });

        // Refresh All Segments
        $('#refreshAllSegments').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: '{{ route("admin.whatsapp.segments.refresh-all") }}',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    toastr.success('{{translate("All segments refreshed successfully")}}');
                    location.reload();
                },
                error: function() {
                    toastr.error('{{translate("Failed to refresh segments")}}');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Preview Predefined Segment
        $(document).on('click', '.preview-segment', function(e) {
            e.stopPropagation();
            const segment = $(this).data('segment');
            const name = $(this).data('name');
            previewSegment(segment, name);
        });

        $(document).on('click', '.preview-custom-segment', function() {
            const segmentId = $(this).data('id');
            const name = $(this).data('name');
            previewCustomSegment(segmentId, name);
        });

        // Delete Custom Segment
        $(document).on('click', '.delete-custom-segment', function() {
            segmentToDelete = $(this).data('id');
            $('#deleteSegmentName').text($(this).data('name'));
            $('#deleteSegmentModal').modal('show');
        });

        $('#confirmDeleteSegment').on('click', function() {
            if (!segmentToDelete) return;

            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: '{{ route("admin.whatsapp.segments.destroy", ":id") }}'.replace(':id', segmentToDelete),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    toastr.success(response.message || '{{translate("Segment deleted successfully")}}');
                    setTimeout(() => location.reload(), 1000);
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || '{{translate("Failed to delete segment")}}');
                    $btn.prop('disabled', false);
                }
            });
        });

        function refreshSegmentCount(segment) {
            $(`#count_${segment}`).html('<div class="spinner-border spinner-border-sm"></div>');

            $.ajax({
                url: '{{ route("admin.whatsapp.segments.refresh") }}',
                method: 'POST',
                data: { segment: segment },
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    $(`#count_${segment}`).text(response.count.toLocaleString());
                    $(`#updated_${segment}`).text('{{translate("Updated")}}: ' + response.updated_at);
                    toastr.success('{{translate("Segment refreshed")}}');
                },
                error: function() {
                    $(`#count_${segment}`).text('--');
                    toastr.error('{{translate("Failed to refresh segment")}}');
                }
            });
        }

        function refreshCustomSegment(segmentId) {
            $.ajax({
                url: '{{ route("admin.whatsapp.segments.refresh-custom", ":id") }}'.replace(':id', segmentId),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    toastr.success('{{translate("Segment refreshed")}}');
                    location.reload();
                },
                error: function() {
                    toastr.error('{{translate("Failed to refresh segment")}}');
                }
            });
        }

        function previewSegment(segment, name) {
            $('#previewSegmentModal').modal('show');
            $('#previewSegmentName').text(name);
            $('#previewCustomersContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');

            $.ajax({
                url: '{{ route("admin.whatsapp.segments.preview-predefined") }}',
                method: 'POST',
                data: { segment: segment },
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    $('#previewTotalCount').text(response.total.toLocaleString());
                    $('#previewCustomersContent').html(response.html);
                },
                error: function() {
                    $('#previewCustomersContent').html('<p class="text-danger text-center">{{translate("Failed to load preview")}}</p>');
                }
            });
        }

        function previewCustomSegment(segmentId, name) {
            $('#previewSegmentModal').modal('show');
            $('#previewSegmentName').text(name);
            $('#previewCustomersContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');

            $.ajax({
                url: '{{ route("admin.whatsapp.segments.preview-custom", ":id") }}'.replace(':id', segmentId),
                method: 'GET',
                success: function(response) {
                    $('#previewTotalCount').text(response.total.toLocaleString());
                    $('#previewCustomersContent').html(response.html);
                },
                error: function() {
                    $('#previewCustomersContent').html('<p class="text-danger text-center">{{translate("Failed to load preview")}}</p>');
                }
            });
        }
    });

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
