@extends('layouts.admin.app')
@section('title', translate('messages.daily_incentive_rules'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title">
                <i class="tio-star"></i> {{ translate('messages.daily_incentive_rules') }}
                <span class="badge badge-soft-dark ml-2">{{ $rules->total() }}</span>
            </h1>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="card-header-title">{{ translate('messages.add_daily_incentive_rule') }}</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.transactions.payroll.daily-incentives.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label">{{ translate('messages.title') }} <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. 5 Delivery Milestone" required>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">{{ translate('messages.delivery_count') }} <span class="text-danger">*</span></label>
                        <input type="number" name="delivery_count" class="form-control" placeholder="e.g. 5" required min="1">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">{{ translate('messages.bonus_amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="bonus_amount" class="form-control" placeholder="e.g. 30" required min="0">
                    </div>
                    <div class="col-sm-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="tio-add"></i> {{ translate('messages.add') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('messages.SL') }}</th>
                            <th>{{ translate('messages.title') }}</th>
                            <th>{{ translate('messages.delivery_count') }}</th>
                            <th>{{ translate('messages.bonus_amount') }}</th>
                            <th>{{ translate('messages.status') }}</th>
                            <th>{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rules as $k => $rule)
                        <tr>
                            <td>{{ $rules->firstItem() + $k }}</td>
                            <td>{{ $rule->title }}</td>
                            <td><span class="badge badge-soft-primary">{{ $rule->delivery_count }} deliveries</span></td>
                            <td>{{ \App\CentralLogics\Helpers::format_currency($rule->bonus_amount) }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.transactions.payroll.daily-incentives.toggle', $rule->id) }}" class="d-inline">
                                    @csrf
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="toggle-switch-input" onchange="this.form.submit()" {{ $rule->status ? 'checked' : '' }}>
                                        <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                                    </label>
                                </form>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editRule{{ $rule->id }}"><i class="tio-edit"></i></button>
                                <form method="POST" action="{{ route('admin.transactions.payroll.daily-incentives.delete', $rule->id) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="tio-delete"></i></button>
                                </form>
                            </td>
                        </tr>
                        <div class="modal fade" id="editRule{{ $rule->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.transactions.payroll.daily-incentives.update', $rule->id) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header"><h5 class="modal-title">Edit Rule</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                                        <div class="modal-body">
                                            <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" value="{{ $rule->title }}" required></div>
                                            <div class="form-group"><label>Delivery Count</label><input type="number" name="delivery_count" class="form-control" value="{{ $rule->delivery_count }}" required min="1"></div>
                                            <div class="form-group"><label>Bonus Amount</label><input type="number" step="0.01" name="bonus_amount" class="form-control" value="{{ $rule->bonus_amount }}" required min="0"></div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Update</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($rules->count() === 0)
                <div class="empty--data text-center py-5">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="" width="100">
                    <h5 class="mt-3">{{ translate('messages.no_data_found') }}</h5>
                </div>
            @endif
        </div>
        @if($rules->count() > 0)
            <div class="card-footer">{{ $rules->links() }}</div>
        @endif
    </div>
</div>
@endsection
