@extends('layouts.admin.app')

@section('title', translate('Security Deposit Settings'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/bank.png')}}" class="w--20" alt="">
            </span>
            <span>{{translate('Security Deposit Settings')}}</span>
        </h1>
    </div>
    <!-- End Page Header -->

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-settings"></i>
                        {{translate('Configure Security Deposit for Delivery Men')}}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{route('admin.deliveryman.security-deposit.update-settings')}}" method="POST">
                        @csrf

                        <!-- Enable/Disable Toggle -->
                        <div class="form-group">
                            <label class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 px-xl-4" for="security_deposit_enabled">
                                <span class="pr-1 d-flex align-items-center switch--label">
                                    <span class="line--limit-1">
                                        <strong>{{translate('Enable Security Deposit')}}</strong>
                                    </span>
                                    <span class="form-label-secondary text-danger d-flex" data-toggle="tooltip" data-placement="right"
                                        data-original-title="{{translate('If enabled, delivery men must pay a security deposit before starting work')}}">
                                        <img src="{{asset('/public/assets/admin/img/info-circle.svg')}}" alt="{{translate('info')}}">
                                    </span>
                                </span>
                                <input type="checkbox" name="security_deposit_enabled" value="1" class="toggle-switch-input" id="security_deposit_enabled" {{$enabled ? 'checked' : ''}}>
                                <span class="toggle-switch-label text">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>
                        </div>

                        <!-- Deposit Amount -->
                        <div class="form-group">
                            <label for="security_deposit_amount" class="form-label">
                                {{translate('Security Deposit Amount')}}
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{Helpers::currency_symbol()}}</span>
                                </div>
                                <input type="number" step="0.01" min="0" name="security_deposit_amount"
                                       id="security_deposit_amount" class="form-control"
                                       value="{{old('security_deposit_amount', $amount)}}"
                                       placeholder="500" required>
                            </div>
                            <small class="form-text text-muted">
                                {{translate('This amount will be collected from all new delivery men before they can start accepting orders.')}}
                            </small>
                        </div>

                        <!-- Information Box -->
                        <div class="alert alert-soft-info mb-4">
                            <div class="media">
                                <div class="media-body">
                                    <h5 class="alert-heading">{{translate('How Security Deposit Works')}}</h5>
                                    <p class="mb-1">• {{translate('New delivery men must pay the security deposit before starting work')}}</p>
                                    <p class="mb-1">• {{translate('Payment is processed through Razorpay payment gateway')}}</p>
                                    <p class="mb-1">• {{translate('Deposits can be refunded from the delivery men list')}}</p>
                                    <p class="mb-0">• {{translate('All transactions are logged and can be audited')}}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="btn--container justify-content-end">
                            <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                            <button type="submit" class="btn btn-primary">{{translate('Save Settings')}}</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Stats Card -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-chart-bar-4"></i>
                        {{translate('Quick Statistics')}}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center">
                                <h3 class="mb-1">{{\App\Models\DeliveryMan::count()}}</h3>
                                <p class="text-muted mb-0">{{translate('Total Delivery Men')}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center">
                                <h3 class="mb-1 text-success">{{\App\Models\DeliveryMan::where('security_deposit_status', 'paid')->count()}}</h3>
                                <p class="text-muted mb-0">{{translate('Deposits Paid')}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center">
                                <h3 class="mb-1 text-warning">{{\App\Models\DeliveryMan::where('security_deposit_status', 'unpaid')->count()}}</h3>
                                <p class="text-muted mb-0">{{translate('Deposits Pending')}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center">
                                <h3 class="mb-1 text-info">{{\App\Models\DeliveryMan::where('security_deposit_status', 'refunded')->count()}}</h3>
                                <p class="text-muted mb-0">{{translate('Deposits Refunded')}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="{{route('admin.deliveryman.security-deposit.index')}}" class="btn btn-sm btn-primary">
                            {{translate('View All Deposits')}} <i class="tio-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    $(document).ready(function() {
        // Show/hide amount field based on toggle
        $('#security_deposit_enabled').on('change', function() {
            if ($(this).is(':checked')) {
                $('#security_deposit_amount').prop('disabled', false).focus();
            } else {
                $('#security_deposit_amount').prop('disabled', true);
            }
        }).trigger('change');
    });
</script>
@endpush
