@extends('layouts.admin.app')

@section('title', translate('messages.send_employee_invitation'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <i class="tio-email"></i>
            </span>
            <span>{{ translate('messages.send_employee_invitation') }}</span>
        </h1>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.employee-application.invite.send') }}">
                        @csrf

                        <div class="form-group">
                            <label>{{ translate('messages.employee_type') }} *</label>
                            <select name="employee_type" class="form-control" id="employee_type" required>
                                <option value="">{{ translate('messages.select_type') }}</option>
                                <option value="admin">{{ translate('messages.admin_employee') }}</option>
                                <option value="vendor">{{ translate('messages.vendor_employee') }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>{{ translate('messages.email') }} *</label>
                            <input type="email" name="email" class="form-control" required
                                   placeholder="employee@example.com">
                        </div>

                        <!-- Admin Employee Fields -->
                        <div id="admin_fields" style="display: none;">
                            <div class="form-group">
                                <label>{{ translate('messages.role') }} *</label>
                                <select name="role_id" class="form-control admin-role">
                                    <option value="">{{ translate('messages.select_role') }}</option>
                                    @foreach($adminRoles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label>{{ translate('messages.zone') }}</label>
                                <select name="zone_id" class="form-control">
                                    <option value="">{{ translate('messages.select_zone') }}</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Vendor Employee Fields -->
                        <div id="vendor_fields" style="display: none;">
                            <div class="form-group">
                                <label>{{ translate('messages.role') }} *</label>
                                <select name="role_id" class="form-control vendor-role">
                                    <option value="">{{ translate('messages.select_role') }}</option>
                                    @foreach($vendorRoles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label>{{ translate('messages.store') }} *</label>
                                <select name="store_id" class="form-control">
                                    <option value="">{{ translate('messages.select_store') }}</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{ translate('messages.expiry_days') }}</label>
                            <input type="number" name="expiry_days" class="form-control" value="7" min="1" max="30">
                            <small class="form-text text-muted">
                                {{ translate('messages.invitation_expiry_help') }}
                            </small>
                        </div>

                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{ route('admin.employee-application.list') }}" class="btn btn-secondary">
                                {{ translate('messages.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="tio-send"></i> {{ translate('messages.send_invitation') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ translate('messages.how_it_works') }}</h5>
                    <ol class="pl-3">
                        <li class="mb-2">{{ translate('messages.invitation_step_1') }}</li>
                        <li class="mb-2">{{ translate('messages.invitation_step_2') }}</li>
                        <li class="mb-2">{{ translate('messages.invitation_step_3') }}</li>
                        <li>{{ translate('messages.invitation_step_4') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    $('#employee_type').on('change', function() {
        const type = $(this).val();

        if (type === 'admin') {
            $('#admin_fields').show();
            $('#vendor_fields').hide();
            $('.admin-role').prop('required', true);
            $('.vendor-role').prop('required', false);
        } else if (type === 'vendor') {
            $('#vendor_fields').show();
            $('#admin_fields').hide();
            $('.vendor-role').prop('required', true);
            $('.admin-role').prop('required', false);
        } else {
            $('#admin_fields, #vendor_fields').hide();
        }
    });
</script>
@endpush
