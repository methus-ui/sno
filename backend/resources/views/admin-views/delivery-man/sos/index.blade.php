@extends('layouts.admin.app')
@section('title', translate('messages.sos_dashboard'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-warning"></i> {{ translate('messages.sos_dashboard') }}</h1>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <div class="btn-group">
                <a href="?status=active" class="btn btn-sm btn-{{ $status === 'active' ? 'primary' : 'outline-primary' }}">Active</a>
                <a href="?status=resolved" class="btn btn-sm btn-{{ $status === 'resolved' ? 'primary' : 'outline-primary' }}">Resolved</a>
                <a href="?status=all" class="btn btn-sm btn-{{ $status === 'all' ? 'primary' : 'outline-primary' }}">All</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-borderless table-hover">
                <thead class="thead-light"><tr>
                    <th>#</th>
                    <th>{{ translate('messages.delivery_man') }}</th>
                    <th>{{ translate('messages.type') }}</th>
                    <th>{{ translate('messages.order') }}</th>
                    <th>{{ translate('messages.location') }}</th>
                    <th>{{ translate('messages.status') }}</th>
                    <th>{{ translate('messages.time') }}</th>
                    <th>{{ translate('messages.action') }}</th>
                </tr></thead>
                <tbody>
                @foreach($sosRequests as $sos)
                    <tr class="{{ $sos->status === 'active' ? 'table-danger' : '' }}">
                        <td>{{ $sos->id }}</td>
                        <td>{{ $sos->deliveryMan ? $sos->deliveryMan->f_name . ' ' . $sos->deliveryMan->l_name : 'N/A' }}</td>
                        <td><span class="badge badge-warning">{{ ucfirst($sos->type) }}</span></td>
                        <td>{{ $sos->order_id ? '#' . $sos->order_id : '-' }}</td>
                        <td>{{ $sos->latitude }}, {{ $sos->longitude }}</td>
                        <td><span class="badge badge-{{ $sos->status === 'active' ? 'danger' : 'success' }}">{{ ucfirst($sos->status) }}</span></td>
                        <td>{{ $sos->created_at->diffForHumans() }}</td>
                        <td>
                            @if($sos->status === 'active')
                                <form method="POST" action="{{ route('admin.transactions.dm-performance.sos.resolve', $sos->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">{{ translate('messages.resolve') }}</button>
                                </form>
                            @else
                                <span class="text-muted">{{ translate('messages.resolved') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $sosRequests->links() }}
        </div>
    </div>
</div>
@endsection
