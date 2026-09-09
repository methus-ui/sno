<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RosterRole;
use Brian2694\Toastr\Facades\Toastr;

class RosterRoleController extends Controller
{
    public function index()
    {
        $admin = auth('admin')->user();
        if ($admin->role_id != 1) {
            Toastr::error(translate('messages.access_denied'));
            return back();
        }

        $roles = RosterRole::latest()->paginate(15);
        return view('admin-views.roster-roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:7',
        ]);

        RosterRole::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'created_by' => auth('admin')->id(),
        ]);

        Toastr::success(translate('messages.roster_role_created'));
        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:7',
        ]);

        $role = RosterRole::findOrFail($id);
        $role->update($request->only('name', 'description', 'color'));

        Toastr::success(translate('messages.roster_role_updated'));
        return back();
    }

    public function toggleStatus($id)
    {
        $role = RosterRole::findOrFail($id);
        $role->update(['is_active' => !$role->is_active]);

        Toastr::success(translate('messages.status_updated'));
        return back();
    }

    public function destroy($id)
    {
        RosterRole::findOrFail($id)->delete();
        Toastr::success(translate('messages.roster_role_deleted'));
        return back();
    }
}
