@extends('layouts.admin.app')
@section('title', 'Shift Management')
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title"><i class="tio-calendar"></i> Shift Management</h1>
            <a href="{{ route('admin.transactions.dm-performance.shifts.swap-requests') }}" class="btn btn-warning">
                Swap Requests
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-sm-3">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                </div>
                <div class="col-sm-2">
                    <button type="submit" class="btn btn-primary">View</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5>Assign Shift</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.transactions.dm-performance.shifts.assign') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-sm-3">
                        <label>Delivery Man</label>
                        <select name="delivery_man_id" class="form-control js-select2-custom" required>
                            <option value="">Select</option>
                            @foreach($deliveryMen as $dm)
                                <option value="{{ $dm->id }}">{{ $dm->f_name }} {{ $dm->l_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label>Shift Template</label>
                        <select name="shift_template_id" class="form-control" required>
                            @foreach($templates as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->start_time }} - {{ $t->end_time }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" value="{{ $date }}" required>
                    </div>
                    <div class="col-sm-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Assign</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5>Roster for {{ $date }}</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Delivery Man</th><th>Shift</th><th>Time</th><th>Status</th><th>Assigned By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                        <tr>
                            <td>{{ $booking->deliveryMan->f_name ?? '' }} {{ $booking->deliveryMan->l_name ?? '' }}</td>
                            <td>{{ $booking->shiftTemplate->name ?? 'Custom' }}</td>
                            <td>{{ $booking->shiftTemplate ? $booking->shiftTemplate->start_time : '' }} - {{ $booking->shiftTemplate ? $booking->shiftTemplate->end_time : '' }}</td>
                            <td>
                                @if($booking->status == 'self_booked') <span class="badge badge-soft-info">Self Booked</span>
                                @elseif($booking->status == 'scheduled') <span class="badge badge-soft-primary">Scheduled</span>
                                @elseif($booking->status == 'active') <span class="badge badge-soft-success">Active</span>
                                @elseif($booking->status == 'completed') <span class="badge badge-soft-success">Completed</span>
                                @elseif($booking->status == 'missed') <span class="badge badge-soft-danger">Missed</span>
                                @elseif($booking->status == 'cancelled') <span class="badge badge-soft-warning">Cancelled</span>
                                @else <span class="badge badge-soft-secondary">{{ $booking->status }}</span>
                                @endif
                            </td>
                            <td>{{ $booking->assigned_by ? 'Admin' : 'Self' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($bookings->count() === 0)
                <div class="empty--data text-center py-5"><h5>No shifts for this date</h5></div>
            @endif
        </div>
        @if($bookings->count() > 0)
            <div class="card-footer">{{ $bookings->links() }}</div>
        @endif
    </div>
</div>
@endsection
