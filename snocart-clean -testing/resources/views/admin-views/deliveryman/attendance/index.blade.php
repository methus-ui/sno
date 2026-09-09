@extends('layouts.admin.app')

@section('title', translate('messages.deliveryman_attendance'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{translate('messages.deliveryman_attendance')}}</h1>
                <p class="page-header-text">{{translate('messages.manage_deliveryman_attendance')}}</p>
            </div>
            <div class="col-sm-auto">
                <a href="{{route('admin.deliveryman.attendance.report')}}" class="btn btn-primary">
                    <i class="tio-download"></i> {{translate('messages.export_report')}}
                </a>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <!-- Filter Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('admin.deliveryman.attendance.index')}}" method="GET">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="delivery_man_id" class="form-label">{{translate('messages.delivery_man')}}</label>
                                    <select name="delivery_man_id" id="delivery_man_id" class="form-control">
                                        <option value="">{{translate('messages.all_delivery_men')}}</option>
                                        @foreach($deliveryMen as $dm)
                                        <option value="{{$dm->id}}" {{request('delivery_man_id') == $dm->id ? 'selected' : ''}}>
                                            {{$dm->f_name}} {{$dm->l_name}}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date" class="form-label">{{translate('messages.start_date')}}</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{$startDate}}">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date" class="form-label">{{translate('messages.end_date')}}</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{$endDate}}">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="tio-search"></i> {{translate('messages.filter')}}
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

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-success">{{$totalPresent}}</h4>
                    <small class="text-muted">{{translate('messages.present_days')}}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-warning">{{$totalPartial}}</h4>
                    <small class="text-muted">{{translate('messages.partial_days')}}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-danger">{{$totalAbsent}}</h4>
                    <small class="text-muted">{{translate('messages.absent_days')}}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-info">{{$totalWorkingDays}}</h4>
                    <small class="text-muted">{{translate('messages.total_days')}}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{translate('messages.attendance_records')}}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{translate('messages.sl')}}</th>
                                    <th>{{translate('messages.delivery_man')}}</th>
                                    <th>{{translate('messages.date')}}</th>
                                    <th>{{translate('messages.punch_in')}}</th>
                                    <th>{{translate('messages.punch_out')}}</th>
                                    <th>{{translate('messages.working_hours')}}</th>
                                    <th>{{translate('messages.status')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances as $key => $attendance)
                                <tr>
                                    <td>{{$key + $attendances->firstItem()}}</td>
                                    <td>
                                        <div class="media align-items-center">
                                            <img class="avatar avatar-sm mr-3" 
                                                 src="{{asset('storage/app/public/delivery-man/'.$attendance->deliveryMan->image)}}" 
                                                 onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                                                 alt="{{$attendance->deliveryMan->f_name}}">
                                            <div class="media-body">
                                                <h6 class="mb-0">{{$attendance->deliveryMan->f_name}} {{$attendance->deliveryMan->l_name}}</h6>
                                                <small class="text-muted">{{$attendance->deliveryMan->phone}}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{$attendance->date->format('d M Y')}}</td>
                                    <td>
                                        @if($attendance->punch_in_time)
                                            <span class="badge badge-soft-success">
                                                {{Carbon\Carbon::parse($attendance->punch_in_time)->format('H:i A')}}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->punch_out_time)
                                            <span class="badge badge-soft-danger">
                                                {{Carbon\Carbon::parse($attendance->punch_out_time)->format('H:i A')}}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-weight-bold">{{$attendance->formatted_working_hours}}</span>
                                    </td>
                                    <td>
                                        @if($attendance->status == 'present')
                                            <span class="badge badge-success">{{translate('messages.present')}}</span>
                                        @elseif($attendance->status == 'partial')
                                            <span class="badge badge-warning">{{translate('messages.partial')}}</span>
                                        @else
                                            <span class="badge badge-danger">{{translate('messages.absent')}}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="py-5">
                                            <img src="{{asset('public/assets/admin/img/no-data.png')}}" alt="" class="w-75px">
                                            <p class="mt-3">{{translate('messages.no_data_found')}}</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="page-area px-4 pb-3">
                        {{$attendances->appends(request()->query())->links()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection