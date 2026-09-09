@extends('layouts.admin.app')

@section('title', translate('messages.generate_payroll'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <i class="tio-wallet"></i> {{ translate('messages.generate_payroll') }}
            </h1>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-header-title">{{ translate('messages.payroll_generation') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="tio-info"></i>
                            {{ translate('messages.salaried_delivery_men_count') }}: <strong>{{ $salariedCount }}</strong>
                            <br>
                            <small>{{ translate('messages.payroll_will_be_generated_for_all_salaried_dm') }}</small>
                        </div>

                        @if($salariedCount > 0)
                        <form method="POST" action="{{ route('admin.transactions.payroll.store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label">{{ translate('messages.period_from') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="period_from" class="form-control" required max="{{ now()->toDateString() }}">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">{{ translate('messages.period_to') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="period_to" class="form-control" required max="{{ now()->toDateString() }}">
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="tio-checkmark-circle"></i> {{ translate('messages.generate_payroll') }}
                                </button>
                                <a href="{{ route('admin.transactions.payroll.index') }}" class="btn btn-secondary btn-lg ml-2">{{ translate('messages.back') }}</a>
                            </div>
                        </form>
                        @else
                            <div class="alert alert-warning">
                                {{ translate('messages.no_salaried_delivery_men_found') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
