@extends('layouts.admin.app')

@section('title', translate('messages.roster_roles'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{translate('messages.roster_roles')}}</h1>
                <p class="page-header-text">{{translate('messages.manage_roster_roles')}}</p>
            </div>
        </div>
    </div>

    <!-- Add Role -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title">{{translate('messages.add_new_role')}}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.roster-roles.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">{{translate('messages.name')}}</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Order Handler" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">{{translate('messages.description')}}</label>
                            <input type="text" name="description" class="form-control" placeholder="{{translate('messages.optional_description')}}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">{{translate('messages.color')}}</label>
                            <input type="color" name="color" class="form-control" value="#007bff" style="height:38px">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">{{translate('messages.add')}}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Roles List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">{{translate('messages.role_list')}}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{translate('messages.name')}}</th>
                            <th>{{translate('messages.description')}}</th>
                            <th>{{translate('messages.color')}}</th>
                            <th>{{translate('messages.status')}}</th>
                            <th>{{translate('messages.action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $key => $role)
                        <tr>
                            <td>{{ $roles->firstItem() + $key }}</td>
                            <td>
                                <span class="badge" style="background-color: {{ $role->color }}; color: #fff;">{{ $role->name }}</span>
                            </td>
                            <td>{{ $role->description ?? '-' }}</td>
                            <td>
                                <span class="d-inline-block rounded-circle" style="width:20px;height:20px;background-color:{{ $role->color }}"></span>
                                {{ $role->color }}
                            </td>
                            <td>
                                <span class="badge badge-soft-{{ $role->is_active ? 'success' : 'danger' }}">
                                    {{ $role->is_active ? translate('messages.active') : translate('messages.inactive') }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editRole{{ $role->id }}">
                                    <i class="tio-edit"></i>
                                </button>
                                <form action="{{ route('admin.roster-roles.toggle', $role->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $role->is_active ? 'warning' : 'success' }}" title="{{ $role->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="tio-{{ $role->is_active ? 'invisible' : 'visible' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.roster-roles.destroy', $role->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{translate('messages.are_you_sure')}}')">
                                        <i class="tio-delete"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editRole{{ $role->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.roster-roles.update', $role->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{translate('messages.edit_role')}}</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>{{translate('messages.name')}}</label>
                                                <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                                            </div>
                                            <div class="form-group">
                                                <label>{{translate('messages.description')}}</label>
                                                <input type="text" name="description" class="form-control" value="{{ $role->description }}">
                                            </div>
                                            <div class="form-group">
                                                <label>{{translate('messages.color')}}</label>
                                                <input type="color" name="color" class="form-control" value="{{ $role->color }}" style="height:38px">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-white" data-dismiss="modal">{{translate('messages.close')}}</button>
                                            <button type="submit" class="btn btn-primary">{{translate('messages.update')}}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">{{translate('messages.no_roles_found')}}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($roles->hasPages())
                <div class="card-footer">{{ $roles->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
