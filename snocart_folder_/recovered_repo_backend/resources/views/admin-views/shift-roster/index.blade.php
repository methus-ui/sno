@extends('layouts.admin.app')

@section('title', translate('messages.shift_roster'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{translate('messages.weekly_shift_roster')}}</h1>
            </div>
        </div>
    </div>

    <!-- Week Navigation -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('admin.shift-roster.index', ['week_start' => $weekStart->copy()->subWeek()->format('Y-m-d')]) }}" class="btn btn-sm btn-white mr-2">
                            <i class="tio-chevron-left"></i>
                        </a>
                        <h5 class="mb-0">{{ $weekStart->format('M d') }} - {{ $weekStart->copy()->addDays(6)->format('M d, Y') }}</h5>
                        <a href="{{ route('admin.shift-roster.index', ['week_start' => $weekStart->copy()->addWeek()->format('Y-m-d')]) }}" class="btn btn-sm btn-white ml-2">
                            <i class="tio-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <form action="{{ route('admin.shift-roster.index') }}" method="GET" class="d-flex">
                        <input type="date" name="week_start" class="form-control form-control-sm" value="{{ $weekStart->format('Y-m-d') }}">
                        <button type="submit" class="btn btn-sm btn-primary ml-2">{{translate('messages.go')}}</button>
                    </form>
                </div>
                <div class="col-md-4 text-right">
                    <form action="{{ route('admin.shift-roster.auto-generate-next-week') }}" method="POST" class="d-inline mr-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('{{translate('messages.auto_generate_confirm')}}')">
                            <i class="tio-autorenew"></i> {{translate('messages.auto_generate_next_week')}}
                        </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#copyWeekModal">
                        <i class="tio-copy"></i> {{translate('messages.copy_previous_week')}}
                    </button>
                    <a href="{{ route('admin.roster-roles.index') }}" class="btn btn-sm btn-outline-info ml-2">
                        <i class="tio-category"></i> {{translate('messages.manage_roles')}}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Roster Grid -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width:180px">{{translate('messages.employee')}}</th>
                            @foreach($days as $i => $day)
                                <th class="text-center" style="min-width:130px">
                                    {{ $day }}<br>
                                    <small class="text-muted">{{ $weekStart->copy()->addDays($i)->format('d M') }}</small>
                                </th>
                            @endforeach
                            <th class="text-center" style="min-width:180px">{{translate('messages.bulk_assign')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm avatar-circle mr-2">
                                        <img class="avatar-img onerror-image" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}" src="{{ $employee->image_full_url }}" alt="">
                                    </div>
                                    <span>{{ $employee->f_name }} {{ $employee->l_name }}</span>
                                </div>
                            </td>
                            @for($day = 0; $day <= 6; $day++)
                                @php
                                    $roster = isset($rosters[$employee->id]) ? $rosters[$employee->id]->where('day_of_week', $day)->first() : null;
                                @endphp
                                <td class="text-center p-1">
                                    @if($roster && $roster->is_off_day)
                                        <button class="btn btn-sm btn-block btn-soft-danger shift-cell" data-toggle="modal" data-target="#assignModal"
                                            data-admin-id="{{ $employee->id }}" data-day="{{ $day }}"
                                            data-off="1" data-start="" data-end="">
                                            OFF
                                        </button>
                                    @elseif($roster)
                                        <button class="btn btn-sm btn-block btn-soft-success shift-cell" data-toggle="modal" data-target="#assignModal"
                                            data-admin-id="{{ $employee->id }}" data-day="{{ $day }}"
                                            data-off="0" data-start="{{ \Carbon\Carbon::parse($roster->shift_start)->format('H:i') }}" data-end="{{ \Carbon\Carbon::parse($roster->shift_end)->format('H:i') }}"
                                            data-role-id="{{ $roster->roster_role_id }}">
                                            {{ \Carbon\Carbon::parse($roster->shift_start)->format('h:iA') }}<br>
                                            {{ \Carbon\Carbon::parse($roster->shift_end)->format('h:iA') }}
                                            @if($roster->rosterRole)
                                                <br><span class="badge badge-sm" style="background-color:{{ $roster->rosterRole->color }};color:#fff;font-size:9px">{{ $roster->rosterRole->name }}</span>
                                            @endif
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-block btn-outline-secondary shift-cell" data-toggle="modal" data-target="#assignModal"
                                            data-admin-id="{{ $employee->id }}" data-day="{{ $day }}"
                                            data-off="0" data-start="" data-end="">
                                            <i class="tio-add"></i>
                                        </button>
                                    @endif
                                </td>
                            @endfor
                            <td class="p-1">
                                <form action="{{ route('admin.shift-roster.bulk-assign') }}" method="POST" class="d-flex align-items-center">
                                    @csrf
                                    <input type="hidden" name="admin_id" value="{{ $employee->id }}">
                                    <input type="hidden" name="week_start_date" value="{{ $weekStart->format('Y-m-d') }}">
                                    <select name="shift_template_id" class="form-control form-control-sm mr-1" required>
                                        <option value="">{{translate('messages.template')}}</option>
                                        @foreach($templates as $template)
                                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="roster_role_id" class="form-control form-control-sm mr-1">
                                        <option value="">{{translate('messages.role')}}</option>
                                        @if(isset($rosterRoles))
                                        @foreach($rosterRoles as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary" title="{{translate('messages.apply')}}">
                                        <i class="tio-checkmark-circle"></i>
                                    </button>
                                </form>
                                <div class="mt-1">
                                    <small class="text-muted">{{translate('messages.off_days')}}:</small>
                                    <div class="d-flex flex-wrap">
                                        @foreach($days as $di => $dn)
                                            <label class="mr-1 mb-0" style="font-size:11px">
                                                <input type="checkbox" name="off_days[]" value="{{ $di }}" form="bulk-{{ $employee->id }}"> {{ substr($dn,0,2) }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ count($days) + 2 }}" class="text-center py-4">
                                {{translate('messages.no_employees_found')}}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Assign Shift Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.shift-roster.assign') }}" method="POST">
                @csrf
                <input type="hidden" name="admin_id" id="modal-admin-id">
                <input type="hidden" name="day_of_week" id="modal-day">
                <input type="hidden" name="week_start_date" value="{{ $weekStart->format('Y-m-d') }}">
                <div class="modal-header">
                    <h5 class="modal-title">{{translate('messages.assign_shift')}}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{translate('messages.shift_template')}}</label>
                        <select name="shift_template_id" id="modal-template" class="form-control">
                            <option value="">{{translate('messages.manual')}}</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" data-start="{{ \Carbon\Carbon::parse($template->start_time)->format('H:i') }}" data-end="{{ \Carbon\Carbon::parse($template->end_time)->format('H:i') }}">
                                    {{ $template->name }} ({{ \Carbon\Carbon::parse($template->start_time)->format('h:iA') }} - {{ \Carbon\Carbon::parse($template->end_time)->format('h:iA') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row" id="time-fields">
                        <div class="col-6">
                            <div class="form-group">
                                <label>{{translate('messages.shift_start')}}</label>
                                <input type="time" name="shift_start" id="modal-start" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>{{translate('messages.shift_end')}}</label>
                                <input type="time" name="shift_end" id="modal-end" class="form-control">
                            </div>
                        </div>
                        <div class="col-12">
                            <div id="modal-hours-display" class="alert alert-soft-info py-2 px-3" style="display:none; font-size: 13px;">
                                <i class="tio-time mr-1"></i>
                                <strong>{{translate('messages.total_shift')}}:</strong> <span id="modal-total-hrs">0</span>h <span id="modal-total-mins">0</span>m
                                &nbsp;|&nbsp;
                                <strong>{{translate('messages.working_hours')}}:</strong> <span id="modal-work-hrs">0</span>h <span id="modal-work-mins">0</span>m
                                <small class="text-muted ml-1">({{translate('messages.excl_30min_break')}})</small>
                                <span id="modal-hours-warning" class="text-danger ml-2" style="display:none;"><i class="tio-warning"></i> {{translate('messages.must_be_9_hours')}}</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="role-field">
                        <label>{{translate('messages.roster_role')}}</label>
                        <select name="roster_role_id" id="modal-role" class="form-control">
                            <option value="">{{translate('messages.none')}}</option>
                            @if(isset($rosterRoles))
                            @foreach($rosterRoles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="modal-off-day" name="is_off_day" value="1">
                            <label class="custom-control-label" for="modal-off-day">{{translate('messages.mark_as_off_day')}}</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-dismiss="modal">{{translate('messages.close')}}</button>
                    <button type="submit" class="btn btn-primary">{{translate('messages.save')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Copy Week Modal -->
<div class="modal fade" id="copyWeekModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.shift-roster.copy-week') }}" method="POST">
                @csrf
                <input type="hidden" name="from_week" value="{{ $weekStart->copy()->subWeek()->format('Y-m-d') }}">
                <input type="hidden" name="to_week" value="{{ $weekStart->format('Y-m-d') }}">
                <div class="modal-header">
                    <h5 class="modal-title">{{translate('messages.copy_previous_week')}}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>{{translate('messages.copy_week_confirmation')}}</p>
                    <p><strong>{{translate('messages.from')}}:</strong> {{ $weekStart->copy()->subWeek()->format('M d') }} - {{ $weekStart->copy()->subDay()->format('M d, Y') }}</p>
                    <p><strong>{{translate('messages.to')}}:</strong> {{ $weekStart->format('M d') }} - {{ $weekStart->copy()->addDays(6)->format('M d, Y') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-dismiss="modal">{{translate('messages.cancel')}}</button>
                    <button type="submit" class="btn btn-primary">{{translate('messages.copy')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    // Populate modal when clicking a shift cell
    $(document).on('click', '.shift-cell', function() {
        $('#modal-admin-id').val($(this).data('admin-id'));
        $('#modal-day').val($(this).data('day'));
        $('#modal-start').val($(this).data('start'));
        $('#modal-end').val($(this).data('end'));
        $('#modal-off-day').prop('checked', $(this).data('off') == 1);
        $('#modal-role').val($(this).data('role-id') || '');
        $('#modal-template').val('');
        toggleTimeFields();
        calcModalHours();
    });

    // Template selection auto-fills times
    $('#modal-template').on('change', function() {
        var selected = $(this).find(':selected');
        if (selected.val()) {
            $('#modal-start').val(selected.data('start'));
            $('#modal-end').val(selected.data('end'));
        }
        calcModalHours();
    });

    // Live hours calculation on time change
    $('#modal-start, #modal-end').on('change input', calcModalHours);

    function calcModalHours() {
        var start = $('#modal-start').val();
        var end = $('#modal-end').val();
        if (!start || !end) {
            $('#modal-hours-display').hide();
            return;
        }
        var sp = start.split(':'), ep = end.split(':');
        var startMin = parseInt(sp[0]) * 60 + parseInt(sp[1]);
        var endMin = parseInt(ep[0]) * 60 + parseInt(ep[1]);
        var totalMin = endMin - startMin;
        if (totalMin <= 0) totalMin += 1440; // next day
        var workMin = totalMin - 30; // subtract 30min break
        if (workMin < 0) workMin = 0;

        $('#modal-total-hrs').text(Math.floor(totalMin / 60));
        $('#modal-total-mins').text(totalMin % 60);
        $('#modal-work-hrs').text(Math.floor(workMin / 60));
        $('#modal-work-mins').text(workMin % 60);
        $('#modal-hours-display').show();

        if (totalMin != 540) {
            $('#modal-hours-warning').show();
        } else {
            $('#modal-hours-warning').hide();
        }
    }

    // Off-day toggle hides time fields
    $('#modal-off-day').on('change', toggleTimeFields);

    function toggleTimeFields() {
        if ($('#modal-off-day').is(':checked')) {
            $('#time-fields').hide();
            $('#modal-template').closest('.form-group').hide();
            $('#role-field').hide();
            $('#modal-hours-display').hide();
        } else {
            $('#time-fields').show();
            $('#modal-template').closest('.form-group').show();
            $('#role-field').show();
            calcModalHours();
        }
    }
</script>
@endpush
