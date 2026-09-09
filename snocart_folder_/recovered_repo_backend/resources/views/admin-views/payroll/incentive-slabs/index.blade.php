@extends('layouts.admin.app')

@section('title', translate('messages.incentive_slabs'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title">
                    <i class="tio-star"></i> {{ translate('messages.incentive_slabs') }}
                    <span class="badge badge-soft-dark ml-2">{{ $slabs->total() }}</span>
                </h1>
                <a href="{{ route('admin.transactions.payroll.index') }}" class="btn btn-secondary">
                    <i class="tio-arrow-backward"></i> {{ translate('messages.back_to_payroll') }}
                </a>
            </div>
        </div>

        <!-- Add New Slab -->
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-header-title">{{ translate('messages.add_incentive_slab') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.transactions.payroll.incentive-slabs.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">{{ translate('messages.title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="{{ translate('messages.slab_title') }}" required>
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label">{{ translate('messages.type') }} <span class="text-danger">*</span></label>
                            <select name="type" class="form-control" required>
                                <option value="delivery_count">{{ translate('messages.delivery_count') }}</option>
                                <option value="avg_delivery_time">{{ translate('messages.avg_delivery_time') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label">{{ translate('messages.min_value') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="min_value" class="form-control" required min="0">
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label">{{ translate('messages.max_value') }}</label>
                            <input type="number" step="0.01" name="max_value" class="form-control" min="0">
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label">{{ translate('messages.bonus_amount') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="bonus_amount" class="form-control" required min="0">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="tio-add"></i> {{ translate('messages.add') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Slabs Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-nowrap card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('messages.SL') }}</th>
                                <th>{{ translate('messages.title') }}</th>
                                <th>{{ translate('messages.type') }}</th>
                                <th>{{ translate('messages.min_value') }}</th>
                                <th>{{ translate('messages.max_value') }}</th>
                                <th>{{ translate('messages.bonus_amount') }}</th>
                                <th>{{ translate('messages.status') }}</th>
                                <th>{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($slabs as $k => $slab)
                                <tr>
                                    <td>{{ $slabs->firstItem() + $k }}</td>
                                    <td>{{ $slab->title }}</td>
                                    <td>
                                        @if($slab->type == 'delivery_count')
                                            <span class="badge badge-soft-primary">{{ translate('messages.delivery_count') }}</span>
                                        @else
                                            <span class="badge badge-soft-info">{{ translate('messages.avg_delivery_time') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $slab->min_value }}</td>
                                    <td>{{ $slab->max_value ?? translate('messages.N/A') }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($slab->bonus_amount) }}</td>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" class="toggle-switch-input"
                                                onclick="location.href='{{ route('admin.transactions.payroll.incentive-slabs.status', [$slab->id, $slab->status ? 0 : 1]) }}'"
                                                {{ $slab->status ? 'checked' : '' }}>
                                            <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                                        </label>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editSlab{{ $slab->id }}">
                                            <i class="tio-edit"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.transactions.payroll.incentive-slabs.delete', $slab->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ translate('messages.are_you_sure') }}')">
                                                <i class="tio-delete"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editSlab{{ $slab->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('admin.transactions.payroll.incentive-slabs.update', $slab->id) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">{{ translate('messages.edit_incentive_slab') }}</h5>
                                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label>{{ translate('messages.title') }}</label>
                                                        <input type="text" name="title" class="form-control" value="{{ $slab->title }}" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>{{ translate('messages.type') }}</label>
                                                        <select name="type" class="form-control" required>
                                                            <option value="delivery_count" {{ $slab->type == 'delivery_count' ? 'selected' : '' }}>{{ translate('messages.delivery_count') }}</option>
                                                            <option value="avg_delivery_time" {{ $slab->type == 'avg_delivery_time' ? 'selected' : '' }}>{{ translate('messages.avg_delivery_time') }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>{{ translate('messages.min_value') }}</label>
                                                        <input type="number" step="0.01" name="min_value" class="form-control" value="{{ $slab->min_value }}" required min="0">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>{{ translate('messages.max_value') }}</label>
                                                        <input type="number" step="0.01" name="max_value" class="form-control" value="{{ $slab->max_value }}" min="0">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>{{ translate('messages.bonus_amount') }}</label>
                                                        <input type="number" step="0.01" name="bonus_amount" class="form-control" value="{{ $slab->bonus_amount }}" required min="0">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('messages.close') }}</button>
                                                    <button type="submit" class="btn btn-primary">{{ translate('messages.update') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($slabs->count() === 0)
                    <div class="empty--data text-center py-5">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public" width="100">
                        <h5 class="mt-3">{{ translate('messages.no_data_found') }}</h5>
                    </div>
                @endif
            </div>
            @if($slabs->count() > 0)
                <div class="card-footer">
                    {{ $slabs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
