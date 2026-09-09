@extends('layouts.admin.app')

@section('title', translate('messages.request_leave'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-no-gutter">
                        <li class="breadcrumb-item">
                            <a class="breadcrumb-link" href="{{route('admin.dashboard')}}">{{translate('messages.dashboard')}}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="breadcrumb-link" href="{{route('admin.leave.index')}}">{{translate('messages.leave_requests')}}</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{translate('messages.create_new')}}</li>
                    </ol>
                </nav>
                <h1 class="page-header-title">{{translate('messages.request_leave')}}</h1>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{translate('messages.leave_request_form')}}</h4>
                </div>
                <div class="card-body">
                    <form action="{{route('admin.leave.store')}}" method="POST">
                        @csrf
                        
                        <div class="form-group">
                            <label for="leave_type" class="form-label">{{translate('messages.leave_type')}} <span class="text-danger">*</span></label>
                            <select name="leave_type" id="leave_type" class="form-control @error('leave_type') is-invalid @enderror" required>
                                <option value="">{{translate('messages.select_leave_type')}}</option>
                                <option value="sick" {{old('leave_type') == 'sick' ? 'selected' : ''}}>{{translate('messages.sick_leave')}}</option>
                                <option value="personal" {{old('leave_type') == 'personal' ? 'selected' : ''}}>{{translate('messages.personal_leave')}}</option>
                                <option value="vacation" {{old('leave_type') == 'vacation' ? 'selected' : ''}}>{{translate('messages.vacation')}}</option>
                                <option value="emergency" {{old('leave_type') == 'emergency' ? 'selected' : ''}}>{{translate('messages.emergency')}}</option>
                                <option value="other" {{old('leave_type') == 'other' ? 'selected' : ''}}>{{translate('messages.other')}}</option>
                            </select>
                            @error('leave_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date" class="form-label">{{translate('messages.start_date')}} <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           name="start_date" 
                                           id="start_date" 
                                           class="form-control @error('start_date') is-invalid @enderror"
                                           value="{{old('start_date')}}"
                                           min="{{date('Y-m-d')}}"
                                           required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date" class="form-label">{{translate('messages.end_date')}} <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           name="end_date" 
                                           id="end_date" 
                                           class="form-control @error('end_date') is-invalid @enderror"
                                           value="{{old('end_date')}}"
                                           min="{{date('Y-m-d')}}"
                                           required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{translate('messages.total_days')}}</label>
                            <input type="text" id="total_days" class="form-control" readonly placeholder="{{translate('messages.will_calculate_automatically')}}">
                        </div>

                        <div class="form-group">
                            <label for="reason" class="form-label">{{translate('messages.reason')}} <span class="text-danger">*</span></label>
                            <textarea name="reason" 
                                      id="reason" 
                                      rows="4" 
                                      class="form-control @error('reason') is-invalid @enderror"
                                      placeholder="{{translate('messages.please_provide_reason_for_leave')}}"
                                      maxlength="500"
                                      required>{{old('reason')}}</textarea>
                            <small class="form-text text-muted">{{translate('messages.maximum_500_characters')}}</small>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{route('admin.leave.index')}}" class="btn btn-secondary">
                                {{translate('messages.cancel')}}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="tio-send"></i> {{translate('messages.submit_request')}}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script_2')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const totalDaysInput = document.getElementById('total_days');

        function calculateTotalDays() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (end >= start) {
                    const timeDiff = end.getTime() - start.getTime();
                    const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                    totalDaysInput.value = dayDiff + ' {{translate("messages.days")}}';
                    endDateInput.setCustomValidity('');
                } else {
                    totalDaysInput.value = '';
                    endDateInput.setCustomValidity('{{translate("messages.end_date_must_be_after_start_date")}}');
                }
            } else {
                totalDaysInput.value = '';
            }
        }

        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            calculateTotalDays();
        });

        endDateInput.addEventListener('change', calculateTotalDays);
    });
</script>
@endsection