@extends('layouts.admin.app')

@section('title', translate('messages.employee_applications'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/employee.png')}}" class="w--20" alt="">
            </span>
            <span>{{ translate('messages.employee_applications') }}</span>
        </h1>
        <div class="d-flex">
            <a href="{{ route('admin.employee-application.invite') }}" class="btn btn-primary">
                <i class="tio-add"></i> {{ translate('messages.send_invitation') }}
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-2 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <h6 class="card-subtitle">{{ translate('messages.pending_admin_applications') }}</h6>
                <span class="card-title h2 text-warning">{{ $stats['pending_admin'] }}</span>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <h6 class="card-subtitle">{{ translate('messages.pending_vendor_applications') }}</h6>
                <span class="card-title h2 text-warning">{{ $stats['pending_vendor'] }}</span>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <h6 class="card-subtitle">{{ translate('messages.approved_today_admin') }}</h6>
                <span class="card-title h2 text-success">{{ $stats['approved_today_admin'] }}</span>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <h6 class="card-subtitle">{{ translate('messages.approved_today_vendor') }}</h6>
                <span class="card-title h2 text-success">{{ $stats['approved_today_vendor'] }}</span>
            </div>
        </div>
    </div>

    <!-- Filters and Tabs -->
    <div class="card">
        <div class="card-header border-0">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <ul class="nav nav-tabs page-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link {{ $type === 'admin' ? 'active' : '' }}"
                               href="{{ route('admin.employee-application.list', ['type' => 'admin']) }}">
                                {{ translate('messages.admin_employees') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type === 'vendor' ? 'active' : '' }}"
                               href="{{ route('admin.employee-application.list', ['type' => 'vendor']) }}">
                                {{ translate('messages.vendor_employees') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <form action="{{ route('admin.employee-application.list') }}" method="GET" class="d-flex">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="text" name="search" class="form-control mr-2"
                               placeholder="{{ translate('messages.search') }}" value="{{ $search }}">
                        <button type="submit" class="btn btn-primary">{{ translate('messages.search') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('messages.sl') }}</th>
                            <th>{{ translate('messages.application_id') }}</th>
                            <th>{{ translate('messages.name') }}</th>
                            <th>{{ translate('messages.email') }}</th>
                            <th>{{ translate('messages.phone') }}</th>
                            <th>{{ translate('messages.role') }}</th>
                            @if($type === 'admin')
                                <th>{{ translate('messages.zone') }}</th>
                            @else
                                <th>{{ translate('messages.store') }}</th>
                            @endif
                            <th>{{ translate('messages.applied_date') }}</th>
                            <th class="text-center">{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $key => $application)
                            <tr>
                                <td>{{ $applications->firstItem() + $key }}</td>
                                <td>
                                    <span class="badge badge-soft-info">
                                        {{ substr($application->application_id, 0, 8) }}...
                                    </span>
                                </td>
                                <td>{{ $application->f_name }} {{ $application->l_name }}</td>
                                <td>{{ $application->email }}</td>
                                <td>{{ $application->phone }}</td>
                                <td>
                                    <span class="badge badge-soft-primary">
                                        {{ $application->role?->name ?? 'N/A' }}
                                    </span>
                                </td>
                                @if($type === 'admin')
                                    <td>{{ $application->zones?->name ?? 'N/A' }}</td>
                                @else
                                    <td>{{ $application->store?->name ?? 'N/A' }}</td>
                                @endif
                                <td>{{ $application->applied_at?->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.employee-application.view', $application->application_id) }}"
                                           class="btn btn-sm btn-white" title="{{ translate('messages.view') }}">
                                            <i class="tio-visible"></i>
                                        </a>
                                        <a href="{{ route('admin.employee-application.edit', $application->application_id) }}"
                                           class="btn btn-sm btn-white" title="{{ translate('messages.edit') }}">
                                            <i class="tio-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-success"
                                                onclick="approveApplication('{{ $application->application_id }}')"
                                                title="{{ translate('messages.approve') }}">
                                            <i class="tio-checkmark-circle"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="denyApplication('{{ $application->application_id }}')"
                                                title="{{ translate('messages.deny') }}">
                                            <i class="tio-clear-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <img src="{{ asset('public/assets/admin/img/empty-box.png') }}" alt="" class="mb-3" style="width: 80px;">
                                    <p class="text-muted">{{ translate('messages.no_applications_found') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer">
            {!! $applications->links() !!}
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('messages.approve_application') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>{{ translate('messages.approve_application_confirmation') }}</p>
                    <div class="form-group">
                        <label>{{ translate('messages.role') }} *</label>
                        <select name="role_id" class="form-control" required id="approve_role_id">
                            <option value="">{{ translate('messages.select_role') }}</option>
                        </select>
                    </div>
                    @if($type === 'admin')
                        <div class="form-group">
                            <label>{{ translate('messages.zone') }}</label>
                            <select name="zone_id" class="form-control" id="approve_zone_id">
                                <option value="">{{ translate('messages.select_zone') }}</option>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        {{ translate('messages.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-success">
                        {{ translate('messages.approve') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Deny Modal -->
<div class="modal fade" id="denyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="denyForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('messages.deny_application') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ translate('messages.rejection_reason') }} *</label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required
                                  placeholder="{{ translate('messages.enter_reason_for_rejection') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        {{ translate('messages.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-danger">
                        {{ translate('messages.deny') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('script_2')
<script>
    function approveApplication(applicationId) {
        $('#approveForm').attr('action', '{{ route("admin.employee-application.approve", ":id") }}'.replace(':id', applicationId));
        $('#approveModal').modal('show');
    }

    function denyApplication(applicationId) {
        $('#denyForm').attr('action', '{{ route("admin.employee-application.deny", ":id") }}'.replace(':id', applicationId));
        $('#denyModal').modal('show');
    }
</script>
@endpush
