@extends('layouts.admin.app')

@section('title', translate('messages.employee_attendance'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{translate('messages.employee_attendance')}}</h1>
                <p class="page-header-text">{{translate('messages.manage_employee_attendance')}}</p>
            </div>
            <div class="col-sm-auto">
                <a href="{{route('admin.attendance.report')}}" class="btn btn-primary">
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
                    <form action="{{route('admin.attendance.index')}}" method="GET">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="employee_id" class="form-label">{{translate('messages.employee')}}</label>
                                    <select name="employee_id" id="employee_id" class="form-control">
                                        <option value="">{{translate('messages.all_employees')}}</option>
                                        @foreach($employees as $employee)
                                        <option value="{{$employee->id}}" {{request('employee_id') == $employee->id ? 'selected' : ''}}>
                                            {{$employee->f_name}} {{$employee->l_name}}
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

    <!-- Punch In/Out Buttons (for non-admin employees) -->
    @if(auth('admin')->user()->role_id != 1)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    @if($canPunchIn)
                    <form action="{{route('admin.attendance.punch-in')}}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="tio-time"></i> {{translate('messages.punch_in')}}
                        </button>
                    </form>
                    @endif
                    
                    @if($canPunchOut)
                    <!-- Break Buttons -->
                    <button type="button" id="btn-start-break" class="btn btn-warning btn-lg ml-3" onclick="startBreak()" style="display:none">
                        <i class="tio-pause"></i> {{translate('messages.start_break')}}
                    </button>
                    <button type="button" id="btn-end-break" class="btn btn-info btn-lg ml-3" onclick="endBreak()" style="display:none">
                        <i class="tio-play"></i> {{translate('messages.end_break')}}
                        <span id="break-timer" class="ml-1"></span>
                    </button>

                    <form action="{{route('admin.attendance.punch-out')}}" method="POST" class="d-inline ml-3">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="tio-time"></i> {{translate('messages.punch_out')}}
                        </button>
                    </form>
                    @endif
                    
                    <!-- Break Info -->
                    <div class="mt-2" id="break-info" style="display:none">
                        <span class="badge badge-soft-info" id="break-total-display"></span>
                        <span class="badge badge-soft-warning" id="break-extra-display"></span>
                        <span class="badge badge-soft-primary" id="expected-end-display"></span>
                    </div>

                    @if($todayAttendance)
                    <div class="mt-3">
                        <p class="mb-1">
                            <strong>{{translate('messages.today_status')}}:</strong>
                            @if($todayAttendance->punch_in && !$todayAttendance->punch_out)
                                <span class="badge badge-warning">{{translate('messages.working')}}</span>
                            @elseif($todayAttendance->punch_in && $todayAttendance->punch_out)
                                <span class="badge badge-success">{{translate('messages.completed')}}</span>
                            @endif
                        </p>
                        @if($todayAttendance->punch_in)
                        <p class="mb-1">
                            <strong>{{translate('messages.punch_in')}}:</strong> 
                            {{Carbon\Carbon::parse($todayAttendance->punch_in)->format('h:i A')}}
                        </p>
                        @endif
                        @if($todayAttendance->punch_out)
                        <p class="mb-1">
                            <strong>{{translate('messages.punch_out')}}:</strong> 
                            {{Carbon\Carbon::parse($todayAttendance->punch_out)->format('h:i A')}}
                        </p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-primary">{{$totalEmployees}}</h4>
                    <small class="text-muted">{{translate('messages.total_employees')}}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-success">{{$presentEmployees}}</h4>
                    <small class="text-muted">{{translate('messages.present_today')}}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-danger">{{$absentEmployees}}</h4>
                    <small class="text-muted">{{translate('messages.absent_today')}}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-warning">{{$onLeaveEmployees}}</h4>
                    <small class="text-muted">{{translate('messages.on_leave_today')}}</small>
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
                                    <th>{{translate('messages.employee')}}</th>
                                    <th>{{translate('messages.date')}}</th>
                                    <th>{{translate('messages.punch_in')}}</th>
                                    <th>{{translate('messages.punch_out')}}</th>
                                    <th>{{translate('messages.working_hours')}}</th>
                                    <th>{{translate('messages.status')}}</th>
                                    <th>{{translate('messages.breaks')}}</th>
                                    <th>{{translate('messages.actual_work')}}</th>
                                    <th>{{translate('messages.shift_status')}}</th>
                                    <th>{{translate('messages.notes')}}</th>
                                    <th>{{translate('messages.action')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances as $key => $attendance)
                                <tr>
                                    <td>{{$key + $attendances->firstItem()}}</td>
                                    <td>
                                        <div class="media align-items-center">
                                            <img class="avatar avatar-sm mr-3" 
                                                 src="{{asset('storage/app/public/admin/'.$attendance->admin->image)}}" 
                                                 onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                                                 alt="{{$attendance->admin->f_name}}">
                                            <div class="media-body">
                                                <h6 class="mb-0">{{$attendance->admin->f_name}} {{$attendance->admin->l_name}}</h6>
                                                <small class="text-muted">{{$attendance->admin->phone}}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{Carbon\Carbon::parse($attendance->attendance_date)->format('d M Y')}}</td>
                                    <td>
                                        @if($attendance->punch_in)
                                            <span class="badge badge-soft-success">
                                                {{Carbon\Carbon::parse($attendance->punch_in)->format('h:i A')}}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->punch_out)
                                            <span class="badge badge-soft-danger">
                                                {{Carbon\Carbon::parse($attendance->punch_out)->format('h:i A')}}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->punch_in && $attendance->punch_out)
                                            <span class="font-weight-bold">
                                                {{Carbon\Carbon::parse($attendance->punch_in)->diffInHours(Carbon\Carbon::parse($attendance->punch_out))}} hours
                                            </span>
                                        @elseif($attendance->punch_in)
                                            <span class="font-weight-bold text-warning">
                                                {{translate('messages.still_working')}}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
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
                                    <td>
                                        <span class="badge badge-soft-info">{{ $attendance->total_break_minutes ?? 0 }} min</span>
                                        @if($attendance->extra_break_minutes > 0)
                                            <span class="badge badge-soft-warning">+{{ $attendance->extra_break_minutes }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $attendance->actual_work_hours ? $attendance->actual_work_hours . ' hrs' : '-' }}</td>
                                    <td>
                                        @if($attendance->shift_completed)
                                            <span class="badge badge-success">{{translate('messages.completed')}}</span>
                                        @elseif($attendance->early_departure)
                                            <span class="badge badge-danger">{{translate('messages.early_departure')}}</span>
                                        @elseif($attendance->punch_in && !$attendance->punch_out)
                                            <span class="badge badge-warning">{{translate('messages.working')}}</span>
                                        @else
                                            <span class="badge badge-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>{{$attendance->notes ?? '-'}}</td>
                                    <td>
                                        <a href="{{ route('admin.attendance.show', $attendance->id) }}" class="btn btn-sm btn-outline-info" title="{{translate('messages.view_details')}}">
                                            <i class="tio-visible"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="12" class="text-center">
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

@push('script_2')
<script>
    var breakTimerInterval = null;
    var breakStartTime = null;

    function pollBreakStatus() {
        $.get('{{ route("admin.breaks.status") }}', function(data) {
            if (!data.has_attendance) {
                $('#btn-start-break, #btn-end-break, #break-info').hide();
                return;
            }
            if (data.on_break) {
                $('#btn-start-break').hide();
                $('#btn-end-break').show();
                if (data.active_break) {
                    breakStartTime = data.active_break.break_start;
                    startBreakTimer();
                }
            } else {
                $('#btn-start-break').show();
                $('#btn-end-break').hide();
                stopBreakTimer();
            }
            // Update break info
            if (data.total_break_minutes > 0 || data.expected_shift_end) {
                $('#break-info').show();
                $('#break-total-display').text('Total Break: ' + data.total_break_minutes + '/' + data.allocated_break_minutes + ' min');
                if (data.extra_break_minutes > 0) {
                    $('#break-extra-display').text('Extra: +' + data.extra_break_minutes + ' min').show();
                } else {
                    $('#break-extra-display').hide();
                }
                if (data.expected_shift_end) {
                    $('#expected-end-display').text('Expected End: ' + data.expected_shift_end);
                }
            }
        });
    }

    function startBreak() {
        $.post('{{ route("admin.breaks.start") }}', {_token: '{{ csrf_token() }}'}, function(data) {
            if (data.success) {
                toastr.success(data.message);
                pollBreakStatus();
            }
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.error || 'Error');
        });
    }

    function endBreak() {
        $.post('{{ route("admin.breaks.end") }}', {_token: '{{ csrf_token() }}'}, function(data) {
            if (data.success) {
                toastr.success(data.message + ' (' + data.duration_minutes + ' min)');
                pollBreakStatus();
            }
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.error || 'Error');
        });
    }

    function startBreakTimer() {
        stopBreakTimer();
        breakTimerInterval = setInterval(function() {
            if (!breakStartTime) return;
            var now = new Date();
            var parts = breakStartTime.split(':');
            var start = new Date();
            start.setHours(parts[0], parts[1], parts[2]);
            var diff = Math.floor((now - start) / 1000);
            var m = Math.floor(diff / 60);
            var s = diff % 60;
            $('#break-timer').text('(' + m + ':' + (s < 10 ? '0' : '') + s + ')');
        }, 1000);
    }

    function stopBreakTimer() {
        if (breakTimerInterval) {
            clearInterval(breakTimerInterval);
            breakTimerInterval = null;
        }
        $('#break-timer').text('');
    }

    @if(auth('admin')->user()->role_id != 1 && isset($canPunchOut) && $canPunchOut)
    $(document).ready(function() {
        pollBreakStatus();
        setInterval(pollBreakStatus, 30000);
    });
    @endif
</script>
@endpush
