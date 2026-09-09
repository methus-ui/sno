@extends('layouts.admin.app')
@section('title', 'Shift Swap Requests')
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title"><i class="tio-swap-horizontal"></i> Shift Swap Requests</h1>
            <a href="{{ route('admin.transactions.dm-performance.shifts.index') }}" class="btn btn-secondary"><i class="tio-arrow-backward"></i> Back</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Requester</th><th>Target</th><th>Type</th><th>Shift Date</th><th>Reason</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $req)
                        <tr>
                            <td>{{ $req->requester->f_name ?? '' }} {{ $req->requester->l_name ?? '' }}</td>
                            <td>{{ $req->target ? $req->target->f_name.' '.$req->target->l_name : 'N/A' }}</td>
                            <td><span class="badge badge-soft-info">{{ $req->request_type }}</span></td>
                            <td>{{ $req->originalRoster->date ?? '' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($req->reason, 50) }}</td>
                            <td>
                                @if($req->status == 'pending') <span class="badge badge-warning">Pending</span>
                                @elseif($req->status == 'approved') <span class="badge badge-success">Approved</span>
                                @elseif($req->status == 'rejected') <span class="badge badge-danger">Rejected</span>
                                @else <span class="badge badge-secondary">{{ $req->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($req->status == 'pending')
                                    <form method="POST" action="{{ route('admin.transactions.dm-performance.shifts.approve-swap', $req->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.transactions.dm-performance.shifts.reject-swap', $req->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($requests->count() === 0)
                <div class="empty--data text-center py-5"><h5>No swap requests</h5></div>
            @endif
        </div>
        @if($requests->count() > 0)
            <div class="card-footer">{{ $requests->links() }}</div>
        @endif
    </div>
</div>
@endsection
