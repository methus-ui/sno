<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Admin;
use Carbon\Carbon;
use Brian2694\Toastr\Facades\Toastr;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $admin = auth('admin')->user();
        
        if ($admin->role_id == 1) {
            // Master admin sees all leave requests
            $leaveRequests = LeaveRequest::with('admin')->latest()->paginate(15);
            return view('admin-views.leave.index', compact('leaveRequests'));
        } else {
            // Employee sees only their leave requests
            $leaveRequests = LeaveRequest::where('admin_id', $admin->id)->latest()->paginate(15);
            return view('admin-views.leave.my-requests', compact('leaveRequests'));
        }
    }

    public function create()
    {
        $admin = auth('admin')->user();
        
        // Only employees can create leave requests
        if ($admin->role_id == 1) {
            abort(403, 'Master admin cannot create leave requests');
        }

        return view('admin-views.leave.create');
    }

    public function store(Request $request)
    {
        $admin = auth('admin')->user();
        
        // Only employees can create leave requests
        if ($admin->role_id == 1) {
            abort(403, 'Master admin cannot create leave requests');
        }

        $request->validate([
            'leave_type' => 'required|in:sick,personal,vacation,emergency,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $totalDays = $endDate->diffInDays($startDate) + 1;

        // Check for overlapping leave requests
        $overlapping = LeaveRequest::where('admin_id', $admin->id)
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        if ($overlapping) {
            Toastr::error('You already have a leave request for the selected dates');
            return redirect()->back()->withInput();
        }

        LeaveRequest::create([
            'admin_id' => $admin->id,
            'leave_type' => $request->leave_type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $request->reason,
            'status' => 'pending'
        ]);

        Toastr::success('Leave request submitted successfully');
        return redirect()->route('admin.leave.index');
    }

    public function show($id)
    {
        $leaveRequest = LeaveRequest::with('admin', 'approvedBy')->findOrFail($id);
        $admin = auth('admin')->user();

        // Check permission
        if ($admin->role_id != 1 && $leaveRequest->admin_id != $admin->id) {
            abort(403, 'Unauthorized');
        }

        return view('admin-views.leave.show', compact('leaveRequest'));
    }

    public function updateStatus(Request $request, $id)
    {
        $admin = auth('admin')->user();
        
        // Only master admin can approve/reject
        if ($admin->role_id != 1) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string|max:500'
        ]);

        $leaveRequest = LeaveRequest::findOrFail($id);
        
        $leaveRequest->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'approved_by' => $admin->id,
            'approved_at' => Carbon::now()
        ]);

        Toastr::success('Leave request ' . $request->status . ' successfully');
        return redirect()->back();
    }

    public function destroy($id)
{
    $admin = auth('admin')->user();
    $leaveRequest = LeaveRequest::findOrFail($id);

    // Only the employee who created the request can delete it (if pending)
    if ($admin->role_id != 1 && $leaveRequest->admin_id != $admin->id) {
        abort(403, 'Unauthorized');
    }

    if ($leaveRequest->status != 'pending') {
        Toastr::error('Cannot delete approved or rejected leave requests');
        return redirect()->route('admin.leave.index');
    }

    $leaveRequest->delete();
    Toastr::success('Leave request deleted successfully');
    
    // Always redirect to the leave requests index page
    return redirect()->route('admin.leave.index');
}
}