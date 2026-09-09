@extends('layouts.admin.app')

@section('title', translate('messages.shift_templates'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{translate('messages.shift_templates')}}</h1>
            </div>
        </div>
    </div>

    <!-- Add Template -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title">{{translate('messages.add_new_template')}}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.shift-roster.templates.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">{{translate('messages.name')}}</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Morning Shift 9AM-6PM" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">{{translate('messages.start_time')}}</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">{{translate('messages.end_time')}}</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">{{translate('messages.add')}}</button>
                        </div>
                    </div>
                </div>
                <div id="tpl-hours-display" class="alert alert-soft-info py-2 px-3 mb-2" style="display:none; font-size: 13px;">
                    <i class="tio-time mr-1"></i>
                    <strong>{{translate('messages.total_shift')}}:</strong> <span id="tpl-total-hrs">0</span>h <span id="tpl-total-mins">0</span>m
                    &nbsp;|&nbsp;
                    <strong>{{translate('messages.working_hours')}}:</strong> <span id="tpl-work-hrs">0</span>h <span id="tpl-work-mins">0</span>m
                    <small class="text-muted ml-1">({{translate('messages.excl_30min_break')}})</small>
                    <span id="tpl-hours-warning" class="text-danger ml-2" style="display:none;"><i class="tio-warning"></i> {{translate('messages.must_be_9_hours')}}</span>
                </div>
                <small class="text-muted">{{translate('messages.shift_must_be_9_hours_including_break')}}</small>
            </form>
        </div>
    </div>

    <!-- Templates List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">{{translate('messages.template_list')}}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{translate('messages.name')}}</th>
                            <th>{{translate('messages.start_time')}}</th>
                            <th>{{translate('messages.end_time')}}</th>
                            <th>{{translate('messages.duration')}}</th>
                            <th>{{translate('messages.status')}}</th>
                            <th>{{translate('messages.action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $key => $template)
                        <tr>
                            <td>{{ $templates->firstItem() + $key }}</td>
                            <td>{{ $template->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($template->start_time)->format('h:i A') }}</td>
                            <td>{{ \Carbon\Carbon::parse($template->end_time)->format('h:i A') }}</td>
                            <td>9 {{translate('messages.hours')}} <small class="text-muted">({{translate('messages.incl_30min_break')}})</small></td>
                            <td>
                                <span class="badge badge-soft-{{ $template->is_active ? 'success' : 'danger' }}">
                                    {{ $template->is_active ? translate('messages.active') : translate('messages.inactive') }}
                                </span>
                            </td>
                            <td>
                                <form action="{{ route('admin.shift-roster.templates.delete', $template->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{translate('messages.are_you_sure')}}')">
                                        <i class="tio-delete"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">{{translate('messages.no_templates_found')}}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($templates->hasPages())
                <div class="card-footer">
                    {{ $templates->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    $('input[name="start_time"], input[name="end_time"]').on('change input', function() {
        var start = $('input[name="start_time"]').val();
        var end = $('input[name="end_time"]').val();
        if (!start || !end) { $('#tpl-hours-display').hide(); return; }
        var sp = start.split(':'), ep = end.split(':');
        var startMin = parseInt(sp[0]) * 60 + parseInt(sp[1]);
        var endMin = parseInt(ep[0]) * 60 + parseInt(ep[1]);
        var totalMin = endMin - startMin;
        if (totalMin <= 0) totalMin += 1440;
        var workMin = Math.max(0, totalMin - 30);

        $('#tpl-total-hrs').text(Math.floor(totalMin / 60));
        $('#tpl-total-mins').text(totalMin % 60);
        $('#tpl-work-hrs').text(Math.floor(workMin / 60));
        $('#tpl-work-mins').text(workMin % 60);
        $('#tpl-hours-display').show();
        $('#tpl-hours-warning').toggle(totalMin != 540);
    });
</script>
@endpush
