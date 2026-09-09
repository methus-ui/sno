@extends('layouts.admin.app')

@section('title', translate('Security Deposits'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon">
                        <img src="{{asset('public/assets/admin/img/bank.png')}}" class="w--20" alt="">
                    </span>
                    <span>{{translate('Security Deposits')}} <span class="badge badge-soft-secondary">{{$deliveryMen->total()}}</span></span>
                </h1>
            </div>
            <div class="col-sm-auto">
                <a class="btn btn-primary" href="{{route('admin.deliveryman.security-deposit.settings')}}">
                    <i class="tio-settings"></i> {{translate('Settings')}}
                </a>
                <a class="btn btn-success" href="{{route('admin.deliveryman.security-deposit.export', ['status' => $status])}}">
                    <i class="tio-download-to"></i> {{translate('Export')}}
                </a>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <!-- Stats Cards -->
    <div class="row gx-2 gx-lg-3">
        <div class="col-sm-6 col-lg-3">
            <a class="card card-hover-shadow" href="{{route('admin.deliveryman.security-deposit.index', ['status' => 'all'])}}">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-circle mr-3">
                            <span class="avatar-initials bg-soft-secondary text-secondary"><i class="tio-user"></i></span>
                        </div>
                        <div class="media-body">
                            <span class="d-block font-size-sm">{{translate('Total')}}</span>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0">{{$stats['total']}}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a class="card card-hover-shadow" href="{{route('admin.deliveryman.security-deposit.index', ['status' => 'paid'])}}">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-circle mr-3">
                            <span class="avatar-initials bg-soft-success text-success"><i class="tio-checkmark-circle"></i></span>
                        </div>
                        <div class="media-body">
                            <span class="d-block font-size-sm">{{translate('Paid')}}</span>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0">{{$stats['paid']}}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a class="card card-hover-shadow" href="{{route('admin.deliveryman.security-deposit.index', ['status' => 'unpaid'])}}">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-circle mr-3">
                            <span class="avatar-initials bg-soft-warning text-warning"><i class="tio-clock"></i></span>
                        </div>
                        <div class="media-body">
                            <span class="d-block font-size-sm">{{translate('Unpaid')}}</span>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0">{{$stats['unpaid']}}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a class="card card-hover-shadow" href="{{route('admin.deliveryman.security-deposit.index', ['status' => 'refunded'])}}">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-circle mr-3">
                            <span class="avatar-initials bg-soft-info text-info"><i class="tio-money"></i></span>
                        </div>
                        <div class="media-body">
                            <span class="d-block font-size-sm">{{translate('Refunded')}}</span>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0">{{$stats['refunded']}}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <!-- End Stats Cards -->

    <!-- Card -->
    <div class="card mt-3">
        <!-- Header -->
        <div class="card-header">
            <form action="{{route('admin.deliveryman.security-deposit.index')}}" method="GET">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <!-- Nav Scroller -->
                        <div class="js-nav-scroller hs-nav-scroller-horizontal">
                            <ul class="nav nav-tabs border-0">
                                <li class="nav-item">
                                    <a class="nav-link {{$status == 'all' ? 'active' : ''}}" href="{{route('admin.deliveryman.security-deposit.index', ['status' => 'all'])}}">{{translate('All')}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{$status == 'unpaid' ? 'active' : ''}}" href="{{route('admin.deliveryman.security-deposit.index', ['status' => 'unpaid'])}}">{{translate('Unpaid')}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{$status == 'paid' ? 'active' : ''}}" href="{{route('admin.deliveryman.security-deposit.index', ['status' => 'paid'])}}">{{translate('Paid')}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{$status == 'refunded' ? 'active' : ''}}" href="{{route('admin.deliveryman.security-deposit.index', ['status' => 'refunded'])}}">{{translate('Refunded')}}</a>
                                </li>
                            </ul>
                        </div>
                        <!-- End Nav Scroller -->
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" value="{{$search}}" class="form-control" placeholder="{{translate('Search by name, phone, email')}}" aria-label="{{translate('Search')}}">
                            <input type="hidden" name="status" value="{{$status}}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">{{translate('Search')}}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- End Header -->

        <!-- Table -->
        <div class="table-responsive datatable-custom">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{translate('ID')}}</th>
                        <th>{{translate('Delivery Man')}}</th>
                        <th>{{translate('Contact')}}</th>
                        <th>{{translate('Amount')}}</th>
                        <th>{{translate('Status')}}</th>
                        <th>{{translate('Paid At')}}</th>
                        <th>{{translate('Transaction ID')}}</th>
                        <th>{{translate('Actions')}}</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($deliveryMen as $dm)
                    <tr>
                        <td>{{$dm->id}}</td>
                        <td>
                            <a class="media align-items-center" href="{{route('admin.deliveryman.security-deposit.show', [$dm->id])}}">
                                <div class="avatar avatar-circle mr-3">
                                    <img class="avatar-img" src="{{$dm->image_full_url}}" alt="{{$dm->f_name}}">
                                </div>
                                <div class="media-body">
                                    <span class="d-block font-weight-semibold">{{$dm->f_name}} {{$dm->l_name}}</span>
                                </div>
                            </a>
                        </td>
                        <td>
                            <div class="text-wrap" style="width: 18rem;">
                                <div>{{translate('Phone')}}: {{$dm->phone}}</div>
                                <div>{{translate('Email')}}: {{$dm->email}}</div>
                            </div>
                        </td>
                        <td>{{Helpers::currency_symbol()}}{{$dm->security_deposit_amount ?: 0}}</td>
                        <td>
                            @if($dm->security_deposit_status == 'paid')
                                <span class="badge badge-soft-success">
                                    <span class="legend-indicator bg-success"></span>{{translate('Paid')}}
                                </span>
                            @elseif($dm->security_deposit_status == 'unpaid')
                                <span class="badge badge-soft-warning">
                                    <span class="legend-indicator bg-warning"></span>{{translate('Unpaid')}}
                                </span>
                            @elseif($dm->security_deposit_status == 'refunded')
                                <span class="badge badge-soft-info">
                                    <span class="legend-indicator bg-info"></span>{{translate('Refunded')}}
                                </span>
                            @endif
                        </td>
                        <td>{{$dm->security_deposit_paid_at ? $dm->security_deposit_paid_at->format('Y-m-d H:i') : '-'}}</td>
                        <td><small>{{$dm->security_deposit_transaction_id ?: '-'}}</small></td>
                        <td>
                            <div class="btn--container justify-content-center">
                                <a class="btn btn-sm btn-white" href="{{route('admin.deliveryman.security-deposit.show', [$dm->id])}}">
                                    <i class="tio-visible-outlined"></i> {{translate('View')}}
                                </a>
                                @if($dm->security_deposit_status == 'paid')
                                    <button type="button" class="btn btn-sm btn-danger" onclick="refundModal({{$dm->id}}, '{{$dm->f_name}} {{$dm->l_name}}')">
                                        <i class="tio-money"></i> {{translate('Refund')}}
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <!-- End Table -->

        <!-- Footer -->
        <div class="card-footer">
            {!! $deliveryMen->links() !!}
        </div>
        <!-- End Footer -->
    </div>
    <!-- End Card -->
</div>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{translate('Refund Security Deposit')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="refundForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>{{translate('Are you sure you want to refund the security deposit for')}} <strong id="dmName"></strong>?</p>
                    <div class="form-group">
                        <label for="refund_reason">{{translate('Refund Reason')}} <span class="text-danger">*</span></label>
                        <textarea name="refund_reason" id="refund_reason" class="form-control" rows="3" required placeholder="{{translate('Enter reason for refund')}}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{translate('Cancel')}}</button>
                    <button type="submit" class="btn btn-primary">{{translate('Confirm Refund')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    function refundModal(dmId, dmName) {
        $('#dmName').text(dmName);
        $('#refundForm').attr('action', '{{route("admin.deliveryman.security-deposit.refund", ":id")}}'.replace(':id', dmId));
        $('#refundModal').modal('show');
    }
</script>
@endpush
