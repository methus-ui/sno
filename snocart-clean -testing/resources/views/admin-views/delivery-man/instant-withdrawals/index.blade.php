@extends('layouts.admin.app')
@section('title', translate('messages.instant_withdrawals'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-money"></i> {{ translate('messages.instant_withdrawals') }}</h1>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="btn-group">
                <a href="?status=pending" class="btn btn-sm btn-{{ $status === 'pending' ? 'primary' : 'outline-primary' }}">Pending</a>
                <a href="?status=completed" class="btn btn-sm btn-{{ $status === 'completed' ? 'primary' : 'outline-primary' }}">Completed</a>
                <a href="?status=failed" class="btn btn-sm btn-{{ $status === 'failed' ? 'primary' : 'outline-primary' }}">Rejected</a>
                <a href="?status=all" class="btn btn-sm btn-{{ $status === 'all' ? 'primary' : 'outline-primary' }}">All</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-borderless table-hover">
                <thead class="thead-light"><tr>
                    <th>#</th>
                    <th>{{ translate('messages.delivery_man') }}</th>
                    <th>{{ translate('messages.amount') }}</th>
                    <th>{{ translate('messages.method') }}</th>
                    <th>{{ translate('messages.status') }}</th>
                    <th>{{ translate('messages.requested_at') }}</th>
                    <th>{{ translate('messages.action') }}</th>
                </tr></thead>
                <tbody>
                @foreach($withdrawals as $w)
                    <tr>
                        <td>{{ $w->id }}</td>
                        <td>{{ $w->deliveryMan ? $w->deliveryMan->f_name . ' ' . $w->deliveryMan->l_name : 'N/A' }}</td>
                        <td>{{ \App\CentralLogics\Helpers::format_currency($w->amount) }}</td>
                        <td>{{ ucfirst($w->method) }}</td>
                        <td><span class="badge badge-{{ $w->status === 'completed' ? 'success' : ($w->status === 'pending' ? 'warning' : 'danger') }}">{{ ucfirst($w->status) }}</span></td>
                        <td>{{ $w->requested_at ? $w->requested_at->format('d M Y H:i') : '-' }}</td>
                        <td>
                            @if($w->status === 'pending')
                                <form method="POST" action="{{ route('admin.transactions.dm-performance.instant-withdrawals.approve', $w->id) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">{{ translate('messages.approve') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.transactions.dm-performance.instant-withdrawals.reject', $w->id) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger">{{ translate('messages.reject') }}</button>
                                </form>
                            @else
                                {{ $w->transaction_ref ?? '-' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $withdrawals->links() }}
        </div>
    </div>
</div>
@endsection
