@extends('layouts.admin.app')

@section('title', 'Leave Request Details')

@push('css_or_js')
    <style>
        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        .detail-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 1.5rem;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 50%;
            background-color: #6b7280;
        }
        .timeline-item.active:before {
            background-color: #10b981;
        }
        .timeline-item.rejected:before {
            background-color: #ef4444;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-no-gutter">
                        <li class="breadcrumb-item">
                            @if(auth('admin')->user()->role_id == 1)
                                <a class="breadcrumb-link" href="{{ route('admin.leave.index') }}">Leave Requests</a>
                            @else
                                <a class="breadcrumb-link" href="{{ route('admin.leave.index') }}">My Leave Requests</a>
                            @endif
                        </li>
                        <li class="breadcrumb-item active">Request Details</li>
                    </ol>
                </nav>

                <h1 class="page-header-title">Leave Request #{{ $leaveRequest->id }}</h1>
                <div class="d-flex align-items-center mt-2">
                    @if($leaveRequest->status == 'pending')
                        <span class="status-badge status-pending">
                            <i class="tio-time me-1"></i> Pending Review
                        </span>
                    @elseif($leaveRequest->status == 'approved')
                        <span class="status-badge status-approved">
                            <i class="tio-checkmark-circle me-1"></i> Approved
                        </span>
                    @else
                        <span class="status-badge status-rejected">
                            <i class="tio-clear-circle me-1"></i> Rejected
                        </span>
                    @endif
                </div>
            </div>

            @if(auth('admin')->user()->role_id == 1 && $leaveRequest->status == 'pending')
            <div class="col-sm-auto">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-success" onclick="approveRequest({{ $leaveRequest->id }})">
                        <i class="tio-checkmark-circle me-1"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger" onclick="rejectRequest({{ $leaveRequest->id }})">
                        <i class="tio-clear-circle me-1"></i> Reject
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row">
        <div class="col-lg-8">
            <!-- Request Details -->
            <div class="card detail-card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title text-white mb-0">
                        <i class="tio-calendar me-2"></i>Leave Request Information
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="tio-user-outlined text-primary me-2"></i>
                                <h6 class="mb-0">Employee</h6>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm avatar-circle me-2">
                                    <img class="avatar-img" src="{{ $leaveRequest->admin->image ? asset('storage/admin/' . $leaveRequest->admin->image) : asset('assets/admin/img/160x160/img1.jpg') }}" alt="Employee">
                                </div>
                                <div>
                                    <p class="text-dark mb-0">{{ $leaveRequest->admin->f_name }} {{ $leaveRequest->admin->l_name }}</p>
                                    <small class="text-muted">{{ $leaveRequest->admin->email }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="tio-label-outlined text-primary me-2"></i>
                                <h6 class="mb-0">Leave Type</h6>
                            </div>
                            <span class="badge badge-soft-primary badge-pill">{{ ucfirst($leaveRequest->leave_type) }}</span>
                        </div>

                        <div class="col-sm-6 mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="tio-date-range text-primary me-2"></i>
                                <h6 class="mb-0">Duration</h6>
                            </div>
                            <p class="mb-0 text-dark">{{ $leaveRequest->start_date->format('F d, Y') }}</p>
                            <small class="text-muted">to {{ $leaveRequest->end_date->format('F d, Y') }}</small>
                        </div>

                        <div class="col-sm-6 mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="tio-time text-primary me-2"></i>
                                <h6 class="mb-0">Total Days</h6>
                            </div>
                            <span class="badge badge-soft-dark badge-pill">{{ $leaveRequest->total_days }} day(s)</span>
                        </div>

                        <div class="col-12 mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="tio-chat-outlined text-primary me-2"></i>
                                <h6 class="mb-0">Reason</h6>
                            </div>
                            <div class="bg-soft-light p-3 rounded">
                                <p class="mb-0">{{ $leaveRequest->reason }}</p>
                            </div>
                        </div>

                        @if($leaveRequest->admin_notes)
                        <div class="col-12">
                            <div class="d-flex align-items-center mb-2">
                                <i class="tio-notebook text-primary me-2"></i>
                                <h6 class="mb-0">Admin Notes</h6>
                            </div>
                            <div class="alert alert-soft-info">
                                <div class="media">
                                    <i class="tio-info-outlined media-object mt-1"></i>
                                    <div class="media-body ms-3">
                                        <p class="mb-0">{{ $leaveRequest->admin_notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- End Request Details -->

            @if($leaveRequest->start_date->isFuture())
            <!-- Calendar Preview -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="tio-calendar me-2"></i>Calendar Preview
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-soft-primary">
                        <div class="media">
                            <i class="tio-info-outlined media-object mt-1"></i>
                            <div class="media-body ms-3">
                                <p class="mb-0">
                                    Leave period: <strong>{{ $leaveRequest->start_date->format('F d') }}</strong> to 
                                    <strong>{{ $leaveRequest->end_date->format('F d, Y') }}</strong>
                                    ({{ $leaveRequest->total_days }} working day{{ $leaveRequest->total_days > 1 ? 's' : '' }})
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row text-center">
                        @php
                            $current = $leaveRequest->start_date->copy();
                            $dayCount = 0;
                        @endphp
                        @while($current->lte($leaveRequest->end_date) && $dayCount < 7)
                            <div class="col">
                                <div class="p-2 {{ $current->isWeekend() ? 'bg-soft-secondary' : 'bg-soft-primary' }} rounded mb-2">
                                    <small class="d-block text-muted">{{ $current->format('D') }}</small>
                                    <span class="font-weight-bold">{{ $current->format('d') }}</span>
                                    <small class="d-block">{{ $current->format('M') }}</small>
                                </div>
                            </div>
                            @php
                                $current->addDay();
                                $dayCount++;
                            @endphp
                        @endwhile
                        
                        @if($leaveRequest->total_days > 7)
                        <div class="col">
                            <div class="p-2 bg-soft-light rounded mb-2 d-flex align-items-center justify-content-center">
                                <small class="text-muted">+{{ $leaveRequest->total_days - 7 }} more days</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Timeline -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="tio-history me-2"></i>Request Timeline
                    </h4>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item active">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Request Submitted</h6>
                                    <small class="text-muted">{{ $leaveRequest->created_at->format('M d, Y h:i A') }}</small>
                                </div>
                                <i class="tio-checkmark-circle text-success"></i>
                            </div>
                        </div>

                        @if($leaveRequest->status != 'pending')
                        <div class="timeline-item {{ $leaveRequest->status == 'approved' ? 'active' : 'rejected' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Request {{ ucfirst($leaveRequest->status) }}</h6>
                                    <small class="text-muted">{{ $leaveRequest->approved_at->format('M d, Y h:i A') }}</small>
                                    @if($leaveRequest->approvedBy)
                                        <small class="d-block text-muted">by {{ $leaveRequest->approvedBy->f_name }} {{ $leaveRequest->approvedBy->l_name }}</small>
                                    @endif
                                </div>
                                <i class="tio-{{ $leaveRequest->status == 'approved' ? 'checkmark-circle text-success' : 'clear-circle text-danger' }}"></i>
                            </div>
                        </div>
                        @else
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 text-muted">Awaiting Review</h6>
                                    <small class="text-muted">Pending admin approval</small>
                                </div>
                                <i class="tio-time text-warning"></i>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="tio-settings-outlined me-2"></i>Quick Actions
                    </h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if(auth('admin')->user()->role_id == 1)
                            <a href="{{ route('admin.leave.index') }}" class="btn btn-soft-primary">
                                <i class="tio-arrow-backward me-1"></i> Back to All Requests
                            </a>
                        @else
                            <a href="{{ route('admin.leave.index') }}" class="btn btn-soft-primary">
                                <i class="tio-arrow-backward me-1"></i> Back to My Requests
                            </a>
                        @endif

                        @if($leaveRequest->admin_id == auth('admin')->user()->id && $leaveRequest->status == 'pending')
                            <button type="button" class="btn btn-soft-danger w-100" 
                                    onclick="confirmDelete({{ $leaveRequest->id }}, '{{ $leaveRequest->leave_type }}', '{{ $leaveRequest->start_date->format('M d, Y') }}', '{{ $leaveRequest->end_date->format('M d, Y') }}', {{ $leaveRequest->total_days }})">
                                <i class="tio-delete-outlined me-1"></i> Delete Request
                            </button>
                        @endif

                        @if(auth('admin')->user()->role_id != 1)
                            <a href="{{ route('admin.leave.create') }}" class="btn btn-soft-success">
                                <i class="tio-add me-1"></i> New Leave Request
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Request Summary -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="tio-pie-chart-outlined me-2"></i>Summary
                    </h4>
                </div>
                <div class="card-body">
                    <dl class="row font-size-sm">
                        <dt class="col-sm-4">Request ID:</dt>
                        <dd class="col-sm-8">#{{ $leaveRequest->id }}</dd>

                        <dt class="col-sm-4">Type:</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-soft-primary">{{ ucfirst($leaveRequest->leave_type) }}</span>
                        </dd>

                        <dt class="col-sm-4">Duration:</dt>
                        <dd class="col-sm-8">{{ $leaveRequest->total_days }} day(s)</dd>

                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            @if($leaveRequest->status == 'pending')
                                <span class="badge badge-soft-warning">Pending</span>
                            @elseif($leaveRequest->status == 'approved')
                                <span class="badge badge-soft-success">Approved</span>
                            @else
                                <span class="badge badge-soft-danger">Rejected</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Submitted:</dt>
                        <dd class="col-sm-8">{{ $leaveRequest->created_at->diffForHumans() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
@if(auth('admin')->user()->role_id == 1 && $leaveRequest->status == 'pending')
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
                        <small class="form-text text-muted">These notes will be visible to the employee.</small>
                    </div>
                    
                    <div class="alert alert-soft-info">
                        <div class="media">
                            <i class="tio-info-outlined media-object mt-1"></i>
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
@endif

<!-- Delete Confirmation Modal -->
@if($leaveRequest->admin_id == auth('admin')->user()->id && $leaveRequest->status == 'pending')
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title text-danger" id="deleteModalLabel">
                    <i class="tio-warning-outlined me-2"></i>Confirm Delete
                </h4>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="avatar avatar-xl avatar-circle bg-soft-danger text-danger mx-auto mb-3">
                        <i class="tio-delete-outlined"></i>
                    </div>
                    <h5 class="modal-title mb-3">Delete Leave Request?</h5>
                    <p class="text-body">
                        Are you sure you want to delete this leave request? 
                        <strong>This action cannot be undone.</strong>
                    </p>
                </div>

                <div class="alert alert-soft-danger">
                    <div class="media">
                        <i class="tio-info-outlined media-object mt-1"></i>
                        <div class="media-body ms-3">
                            <h6 class="mb-1">What happens when you delete:</h6>
                            <ul class="mb-0 font-size-sm">
                                <li>The leave request will be permanently removed</li>
                                <li>You won't be able to recover this request</li>
                                <li>You'll be redirected to your requests list</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Request Details Preview -->
                <div class="card bg-soft-light border-0 mb-3" id="deleteRequestDetails">
                    <div class="card-body p-3">
                        <div class="row font-size-sm">
                            <div class="col-6">
                                <strong>Leave Type:</strong><br>
                                <span id="deleteLeaveType" class="text-muted">{{ ucfirst($leaveRequest->leave_type) }}</span>
                            </div>
                            <div class="col-6">
                                <strong>Duration:</strong><br>
                                <span id="deleteDuration" class="text-muted">{{ $leaveRequest->total_days }} day(s)</span>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row font-size-sm">
                            <div class="col-12">
                                <strong>Dates:</strong><br>
                                <span id="deleteDateRange" class="text-muted">{{ $leaveRequest->start_date->format('M d, Y') }} to {{ $leaveRequest->end_date->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-white btn-lg" data-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.leave.destroy', $leaveRequest->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-lg" id="confirmDeleteBtn">
                        <i class="tio-delete-outlined me-1"></i> Yes, Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('script_2')
<script>
    function confirmDelete(requestId, leaveType, startDate, endDate, totalDays) {
        // Show modal
        $('#deleteModal').modal('show');
    }

    // Handle delete form submission with loading state
    document.addEventListener('DOMContentLoaded', function() {
        const deleteForm = document.querySelector('#deleteModal form');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function() {
                const deleteBtn = document.getElementById('confirmDeleteBtn');
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Deleting...';
            });
        }
    });

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
</script>
@endpush