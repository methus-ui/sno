@extends('layouts.admin.app')

@section('title', 'Leave Requests Management')

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
        .leave-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">Leave Requests Management</h1>
                <p class="page-header-text">Manage all employee leave requests</p>
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
                               placeholder="Search leave requests" name="search" value="{{ request('search') }}">
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
                                <th>Employee</th>
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
                                        <div class="avatar avatar-circle avatar-xs me-2">
                                            <img class="avatar-img" src="{{ $request->admin->image ? asset('storage/admin/' . $request->admin->image) : asset('assets/admin/img/160x160/img1.jpg') }}" alt="Employee">
                                        </div>
                                        <div class="ms-3">
                                            <span class="d-block h5 text-hover-primary mb-0">
                                                {{ $request->admin->f_name }} {{ $request->admin->l_name }}
                                            </span>
                                            <span class="d-block font-size-sm text-body">{{ $request->admin->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-soft-primary">{{ ucfirst($request->leave_type) }}</span>
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
                                        <span class="status-badge status-pending">Pending</span>
                                    @elseif($request->status == 'approved')
                                        <span class="status-badge status-approved">Approved</span>
                                    @else
                                        <span class="status-badge status-rejected">Rejected</span>
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
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-white dropdown-toggle dropdown-toggle-empty" 
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <button class="dropdown-item" onclick="approveRequest({{ $request->id }})">
                                                    <i class="tio-checkmark-circle-outlined dropdown-item-icon text-success"></i> Approve
                                                </button>
                                                <button class="dropdown-item" onclick="rejectRequest({{ $request->id }})">
                                                    <i class="tio-clear-circle-outlined dropdown-item-icon text-danger"></i> Reject
                                                </button>
                                            </div>
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
                </div>
            @endif
        </div>

        <div class="card-footer">
            {{ $leaveRequests->links() }}
        </div>
    </div>
    <!-- End Leave Requests -->
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="statusModalLabel">Update Leave Request Status</h4>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="statusForm" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="statusAction" name="status" value="">
                    
                    <div class="form-group">
                        <label class="input-label">Admin Notes (Optional)</label>
                        <textarea class="form-control" name="admin_notes" rows="3" 
                                  placeholder="Add any notes about this decision..."></textarea>
                    </div>
                    
                    <div class="alert alert-soft-info">
                        <div class="media">
                            <i class="tio-info-outined tio-lg media-object mt-1"></i>
                            <div class="media-body ms-3">
                                <p class="mb-0">Are you sure you want to <span id="actionText"></span> this leave request? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="confirmBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    function approveRequest(id) {
        $('#statusForm').attr('action', '{{ url("/admin/leave") }}/' + id + '/update-status');
        $('#statusAction').val('approved');
        $('#actionText').text('approve');
        $('#statusModalLabel').text('Approve Leave Request');
        $('#confirmBtn').removeClass('btn-danger').addClass('btn-success').text('Approve');
        $('#statusModal').modal('show');
    }

    function rejectRequest(id) {
        $('#statusForm').attr('action', '{{ url("/admin/leave") }}/' + id + '/update-status');
        $('#statusAction').val('rejected');
        $('#actionText').text('reject');
        $('#statusModalLabel').text('Reject Leave Request');
        $('#confirmBtn').removeClass('btn-success').addClass('btn-danger').text('Reject');
        $('#statusModal').modal('show');
    }

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
</script>
@endpush