@extends('layouts.admin.app')

@section('title', translate('messages.deliveryman_attendance_report'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{translate('messages.deliveryman_attendance_report')}}</h1>
                <p class="page-header-text">{{translate('messages.export_deliveryman_attendance_data')}}</p>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{translate('messages.export_attendance_report')}}</h5>
                </div>
                <div class="card-body">
                    <form action="{{route('admin.deliveryman.attendance.export')}}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="delivery_man_id" class="form-label">{{translate('messages.delivery_man')}}</label>
                                    <select name="delivery_man_id" id="delivery_man_id" class="form-control">
                                        <option value="">{{translate('messages.all_delivery_men')}}</option>
                                        @foreach($deliveryMen as $dm)
                                        <option value="{{$dm->id}}">
                                            {{$dm->f_name}} {{$dm->l_name}} (ID: {{$dm->id}})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="report_type" class="form-label">{{translate('messages.report_type')}}</label>
                                    <select name="report_type" id="report_type" class="form-control" required>
                                        <option value="weekly">{{translate('messages.weekly')}}</option>
                                        <option value="monthly" selected>{{translate('messages.monthly')}}</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="export_type" class="form-label">{{translate('messages.export_format')}}</label>
                                    <select name="export_type" id="export_type" class="form-control" required>
                                        <option value="excel">{{translate('messages.excel')}}</option>
                                        <option value="csv">{{translate('messages.csv')}}</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="tio-download"></i> {{translate('messages.export_report')}}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Period Overview -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{translate('messages.current_month_overview')}}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="media align-items-center">
                                <div class="media-body ml-3">
                                    <h4 class="mb-1">{{$deliveryMen->count()}}</h4>
                                    <small class="d-block text-muted">{{translate('messages.total_delivery_men')}}</small>
                                </div>
                                <div class="avatar avatar-lg avatar-4by3 avatar-circle">
                                    <span class="avatar-initials bg-primary">
                                        <i class="tio-motorcycle"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="media align-items-center">
                                <div class="media-body ml-3">
                                    <h4 class="mb-1">{{date('t')}}</h4>
                                    <small class="d-block text-muted">{{translate('messages.working_days_this_month')}}</small>
                                </div>
                                <div class="avatar avatar-lg avatar-4by3 avatar-circle">
                                    <span class="avatar-initials bg-info">
                                        <i class="tio-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="media align-items-center">
                                <div class="media-body ml-3">
                                    <h4 class="mb-1">{{date('j')}}</h4>
                                    <small class="d-block text-muted">{{translate('messages.days_completed')}}</small>
                                </div>
                                <div class="avatar avatar-lg avatar-4by3 avatar-circle">
                                    <span class="avatar-initials bg-success">
                                        <i class="tio-checkmark-circle"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="media align-items-center">
                                <div class="media-body ml-3">
                                    <h4 class="mb-1">{{date('t') - date('j')}}</h4>
                                    <small class="d-block text-muted">{{translate('messages.days_remaining')}}</small>
                                </div>
                                <div class="avatar avatar-lg avatar-4by3 avatar-circle">
                                    <span class="avatar-initials bg-warning">
                                        <i class="tio-clock"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script_2')
<script>
    // Add any additional JavaScript if needed
</script>
@endsection