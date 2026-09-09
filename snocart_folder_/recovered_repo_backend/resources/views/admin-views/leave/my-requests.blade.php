@extends('layouts.admin.app')

@section('title', 'My Leave Requests')

@push('css_or_js')
    <style>
        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: 500;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        .leave-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .delete-btn-wrapper {
            position: relative;
            display: inline-block;
        }

        .delete-btn-wrapper:hover .delete-tooltip {
            opacity: 1;
            visibility: visible;
        }

        .delete-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #dc3545;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            margin-bottom: 5px;
        }

        .delete-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid transparent;
            border-top-color: #dc3545;
        }

        .btn-delete {
            transition: all 0.2s ease;
        }

        .btn-delete:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">My Leave Requests</h1>
                <p class="page-header-text">Manage your leave requests</p>
            </div>
            <div class="col-sm-auto">
                <a class="btn btn-primary" href="{{ route('admin.leave.create') }}">
                    <i class="tio-add me-1"></i> New Leave Request
                </a>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <!-- Stats Cards -->
    <div class="row gx-2 gx-lg-3 mb-4">
        <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">Total Requests</h6>
                    <div class="row align-items-center gx-2">
                        <div class="col">
                            <span class="js-counter display-4 text-dark">{{ $leaveRequests->total() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">Pending</h6>
                    <div class="row align-items-center gx-2">
                        <div class="col">
                            <span class="js-counter display-4 text-warning">{{ $leaveRequests->where('status', 'pending')->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">Approved</h6>
                    <div class="row align-items-center gx-2">
                        <div class="col">
                            <span class="js-counter display-4 text-success">{{ $leaveRequests->where('status', 'approved')->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">Rejected</h6>
                    <div class="row align-items-center gx-2">
                        <div class="col">
                            <span class="js-counter display-4 text-danger">{{ $leaveRequests->where('status', 'rejected')->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Stats Cards -->

    <!-- Leave Requests -->
    <div class="card">
        <div class="card-header">
            <div class="row justify-content-between align-items-center flex-grow-1">
                <div class="col-md-4 mb-3 mb-md-0">
                    <form class="input-group input-group-merge">
                        <div class="input-group-prepend input-group-text">
                            <i class="tio-search"></i>
                        </div>
                        <input id="datatableSearch" type="search" class="form-control" 
                               placeholder="Search your requests" name="search" value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary" type="submit">Search</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-3">
                    <select class="form-control" onchange="filterByStatus(this.value)">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if($leaveRequests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Total Days</th>
                                <th>Status</th>
                                <th>Applied Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaveRequests as $request)
                            <tr data-status="{{ $request->status }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="ms-3">
                                            <span class="badge badge-soft-primary">{{ ucfirst($request->leave_type) }}</span>
                                            <p class="text-body font-size-sm mb-0 mt-1">{{ Str::limit($request->reason, 50) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm">{{ $request->start_date->format('M d, Y') }}</span>
                                    <span class="d-block font-size-sm text-muted">to {{ $request->end_date->format('M d, Y') }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-dark">{{ $request->total_days }} day(s)</span>
                                </td>
                                <td>
                                    @if($request->status == 'pending')
                                        <span class="status-badge status-pending">
                                            <i class="tio-time me-1"></i> Pending
                                        </span>
                                    @elseif($request->status == 'approved')
                                        <span class="status-badge status-approved">
                                            <i class="tio-checkmark-circle me-1"></i> Approved
                                        </span>
                                    @else
                                        <span class="status-badge status-rejected">
                                            <i class="tio-clear-circle me-1"></i> Rejected
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="d-block font-size-sm">{{ $request->created_at->format('M d, Y') }}</span>
                                    <span class="d-block font-size-sm text-muted">{{ $request->created_at->format('h:i A') }}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a class="btn btn-sm btn-white" href="{{ route('admin.leave.show', $request->id) }}">
                                            <i class="tio-visible-outlined"></i> View
                                        </a>
                                        @if($request->status == 'pending')
                                        <div class="delete-btn-wrapper d-inline">
                                            <button type="button" class="btn btn-sm btn-white text-danger btn-delete" 
                                                    onclick="confirmDelete({{ $request->id }}, '{{ $request->leave_type }}', '{{ $request->start_date->format('M d, Y') }}', '{{ $request->end_date->format('M d, Y') }}', {{ $request->total_days }})">
                                                <i class="tio-delete-outlined"></i>
                                            </button>
                                            <div class="delete-tooltip">Delete Request</div>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center p-4">
                    <img class="mb-3" src="{{ asset('assets/admin/svg/illustrations/sorry.svg') }}" alt="No data" style="width: 7rem;">
                    <p class="mb-0">No leave requests found</p>
                    <p class="text-body">
                        <a href="{{ route('admin.leave.create') }}">Create your first leave request</a>
                    </p>
                </div>
            @endif
        </div>

        <div class="card-footer">
            {{ $leaveRequests->links() }}
        </div>
    </div>
    <!-- End Leave Requests -->

    @if($leaveRequests->count() > 0)
    <!-- Quick Tips -->
    <div class="card mt-4">
        <div class="card-header">
            <h4 class="card-title">
                <i class="tio-info-outined"></i> Tips for Leave Requests
            </h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary"><i class="tio-checkmark-circle text-success"></i> Do's</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><small>✓ Submit requests in advance when possible</small></li>
                        <li class="mb-2"><small>✓ Provide clear reasons for your leave</small></li>
                        <li class="mb-2"><small>✓ Check for conflicting dates before submitting</small></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary"><i class="tio-clear-circle text-danger"></i> Don'ts</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><small>× Don't submit overlapping requests</small></li>
                        <li class="mb-2"><small>× Can't delete approved/rejected requests</small></li>
                        <li class="mb-2"><small>× Avoid last-minute requests for non-emergencies</small></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('script_2')
<script>
    function confirmDelete(requestId, leaveType, startDate, endDate, totalDays) {
        // Set form action
        const deleteForm = document.getElementById('deleteForm');
        deleteForm.action = `{{ url('/admin/leave') }}/${requestId}`;
        
        // Show request details in modal
        document.getElementById('deleteLeaveType').textContent = leaveType.charAt(0).toUpperCase() + leaveType.slice(1);
        document.getElementById('deleteDuration').textContent = `${totalDays} day(s)`;
        document.getElementById('deleteDateRange').textContent = `${startDate} to ${endDate}`;
        
        // Show modal
        $('#deleteModal').modal('show');
    }

    // Handle form submission with loading state
    document.getElementById('deleteForm').addEventListener('submit', function() {
        const deleteBtn = document.getElementById('confirmDeleteBtn');
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Deleting...';
    });

    function filterByStatus(status) {
        const rows = document.querySelectorAll('tbody tr[data-status]');
        rows.forEach(row => {
            if (status === '' || row.dataset.status === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Search functionality
    document.getElementById('datatableSearch').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Auto-refresh pending status every 30 seconds
    setInterval(function() {
        const pendingCount = document.querySelectorAll('.status-pending').length;
        if (pendingCount > 0) {
            // Could add AJAX call to refresh pending requests
        }
    }, 30000);
</script>
@endpush