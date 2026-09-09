@extends('layouts.admin.app')

@section('title', translate('messages.employee_verification_dashboard'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/employee.png')}}" class="w--26" alt="">
                </span>
                <span>{{ translate('messages.employee_verification_dashboard') }}</span>
            </h1>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">{{ translate('messages.pending_police_verification') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-6">
                            <h2 class="card-title text-warning">{{ $pendingPoliceVerification }}</h2>
                        </div>
                        <div class="col-6">
                            <div class="chartjs-custom" style="height: 3rem;">
                                <i class="tio-shield-outlined" style="font-size: 3rem; color: #ff9800;"></i>
                            </div>
                        </div>
                    </div>
                    <span class="badge badge-soft-warning">
                        <i class="tio-trending-flat mr-1"></i> {{ translate('messages.awaiting_verification') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">{{ translate('messages.pending_cancelled_cheque') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-6">
                            <h2 class="card-title text-info">{{ $pendingCheque }}</h2>
                        </div>
                        <div class="col-6">
                            <div class="chartjs-custom" style="height: 3rem;">
                                <i class="tio-receipt-outlined" style="font-size: 3rem; color: #00bcd4;"></i>
                            </div>
                        </div>
                    </div>
                    <span class="badge badge-soft-info">
                        <i class="tio-trending-flat mr-1"></i> {{ translate('messages.awaiting_document') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">{{ translate('messages.fully_verified_employees') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-6">
                            <h2 class="card-title text-success">{{ $verifiedEmployees }}</h2>
                        </div>
                        <div class="col-6">
                            <div class="chartjs-custom" style="height: 3rem;">
                                <i class="tio-checkmark-circle-outlined" style="font-size: 3rem; color: #4caf50;"></i>
                            </div>
                        </div>
                    </div>
                    <span class="badge badge-soft-success">
                        <i class="tio-done mr-1"></i> {{ translate('messages.complete') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">{{ translate('messages.pending_joining_letter') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-6">
                            <h2 class="card-title text-danger">{{ $pendingJoiningLetter }}</h2>
                        </div>
                        <div class="col-6">
                            <div class="chartjs-custom" style="height: 3rem;">
                                <i class="tio-email-outlined" style="font-size: 3rem; color: #f44336;"></i>
                            </div>
                        </div>
                    </div>
                    <span class="badge badge-soft-danger">
                        <i class="tio-trending-flat mr-1"></i> {{ translate('messages.awaiting_action') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Police Verification Table -->
    @if($employeesNeedingPoliceVerification->count() > 0)
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-header-title">
                <i class="tio-shield-outlined mr-2"></i>
                {{ translate('messages.pending_police_verification') }}
            </h5>
        </div>
        <div class="table-responsive datatable-custom">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('messages.id') }}</th>
                        <th>{{ translate('messages.name') }}</th>
                        <th>{{ translate('messages.role') }}</th>
                        <th>{{ translate('messages.aadhar_number') }}</th>
                        <th>{{ translate('messages.phone') }}</th>
                        <th>{{ translate('messages.applied_date') }}</th>
                        <th class="text-center">{{ translate('messages.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employeesNeedingPoliceVerification as $employee)
                    <tr>
                        <td>{{ $employee->id }}</td>
                        <td>
                            <a class="table-rest-info" href="{{ route('admin.users.employee.update', $employee->id) }}">
                                <div class="d-flex align-items-center">
                                    <img class="avatar avatar-circle" src="{{ $employee->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="Image">
                                    <div class="ml-3">
                                        <span class="d-block h5 text-hover-primary mb-0">{{ $employee->f_name }} {{ $employee->l_name }}</span>
                                        <span class="d-block font-size-sm text-body">{{ $employee->email }}</span>
                                    </div>
                                </div>
                            </a>
                        </td>
                        <td>{{ $employee->role?->name ?? translate('messages.n_a') }}</td>
                        <td>{{ $employee->aadhar_number ?? translate('messages.n_a') }}</td>
                        <td>{{ $employee->phone }}</td>
                        <td>{{ $employee->applied_at?->format('d M Y') ?? translate('messages.n_a') }}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-success" onclick="markPoliceVerified({{ $employee->id }})">
                                <i class="tio-done"></i> {{ translate('messages.mark_verified') }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {!! $employeesNeedingPoliceVerification->links() !!}
        </div>
    </div>
    @endif

    <!-- Pending Cancelled Cheque Table -->
    @if($employeesNeedingCheque->count() > 0)
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-header-title">
                <i class="tio-receipt-outlined mr-2"></i>
                {{ translate('messages.pending_cancelled_cheque') }}
            </h5>
        </div>
        <div class="table-responsive datatable-custom">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('messages.id') }}</th>
                        <th>{{ translate('messages.name') }}</th>
                        <th>{{ translate('messages.role') }}</th>
                        <th>{{ translate('messages.phone') }}</th>
                        <th>{{ translate('messages.date_of_joining') }}</th>
                        <th class="text-center">{{ translate('messages.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employeesNeedingCheque as $employee)
                    <tr>
                        <td>{{ $employee->id }}</td>
                        <td>
                            <a class="table-rest-info" href="{{ route('admin.users.employee.update', $employee->id) }}">
                                <div class="d-flex align-items-center">
                                    <img class="avatar avatar-circle" src="{{ $employee->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="Image">
                                    <div class="ml-3">
                                        <span class="d-block h5 text-hover-primary mb-0">{{ $employee->f_name }} {{ $employee->l_name }}</span>
                                        <span class="d-block font-size-sm text-body">{{ $employee->email }}</span>
                                    </div>
                                </div>
                            </a>
                        </td>
                        <td>{{ $employee->role?->name ?? translate('messages.n_a') }}</td>
                        <td>{{ $employee->phone }}</td>
                        <td>{{ $employee->date_of_joining?->format('d M Y') ?? translate('messages.n_a') }}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-info" onclick="markChequeSubmitted({{ $employee->id }})">
                                <i class="tio-done"></i> {{ translate('messages.mark_submitted') }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {!! $employeesNeedingCheque->links() !!}
        </div>
    </div>
    @endif

    <!-- Pending Joining Letter Table -->
    @if($employeesNeedingJoiningLetter->count() > 0)
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-header-title">
                <i class="tio-email-outlined mr-2"></i>
                {{ translate('messages.pending_joining_letter') }}
            </h5>
        </div>
        <div class="table-responsive datatable-custom">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('messages.id') }}</th>
                        <th>{{ translate('messages.name') }}</th>
                        <th>{{ translate('messages.role') }}</th>
                        <th>{{ translate('messages.email') }}</th>
                        <th>{{ translate('messages.date_of_joining') }}</th>
                        <th class="text-center">{{ translate('messages.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employeesNeedingJoiningLetter as $employee)
                    <tr>
                        <td>{{ $employee->id }}</td>
                        <td>
                            <a class="table-rest-info" href="{{ route('admin.users.employee.update', $employee->id) }}">
                                <div class="d-flex align-items-center">
                                    <img class="avatar avatar-circle" src="{{ $employee->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="Image">
                                    <div class="ml-3">
                                        <span class="d-block h5 text-hover-primary mb-0">{{ $employee->f_name }} {{ $employee->l_name }}</span>
                                    </div>
                                </div>
                            </a>
                        </td>
                        <td>{{ $employee->role?->name ?? translate('messages.n_a') }}</td>
                        <td>{{ $employee->email }}</td>
                        <td>{{ $employee->date_of_joining?->format('d M Y') ?? translate('messages.n_a') }}</td>
                        <td class="text-center">
                            <form action="{{ route('admin.users.employee.send-joining-letter', $employee->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="tio-send"></i> {{ translate('messages.send_letter') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {!! $employeesNeedingJoiningLetter->links() !!}
        </div>
    </div>
    @endif

    @if($employeesNeedingPoliceVerification->count() == 0 && $employeesNeedingCheque->count() == 0 && $employeesNeedingJoiningLetter->count() == 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="tio-checkmark-circle-outlined" style="font-size: 5rem; color: #4caf50;"></i>
            <h3 class="mt-3">{{ translate('messages.all_verifications_complete') }}</h3>
            <p class="text-muted">{{ translate('messages.no_pending_verifications') }}</p>
        </div>
    </div>
    @endif
</div>
@endsection

@push('script_2')
<script>
function markPoliceVerified(employeeId) {
    if (!confirm('{{ translate("messages.are_you_sure_mark_police_verified") }}')) {
        return;
    }

    $.ajax({
        url: '{{ url("admin/users/employee/mark-police-verified") }}/' + employeeId,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success('{{ translate("messages.police_verification_marked_complete") }}');
                location.reload();
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('{{ translate("messages.something_went_wrong") }}');
        }
    });
}

function markChequeSubmitted(employeeId) {
    if (!confirm('{{ translate("messages.are_you_sure_mark_cheque_submitted") }}')) {
        return;
    }

    $.ajax({
        url: '{{ url("admin/users/employee/mark-cheque-submitted") }}/' + employeeId,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success('{{ translate("messages.cancelled_cheque_marked_submitted") }}');
                location.reload();
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('{{ translate("messages.something_went_wrong") }}');
        }
    });
}
</script>
@endpush
