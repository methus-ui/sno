<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\EmployeeApplicationRepository;
use App\Services\EmployeeApplicationService;
use App\Services\EmployeeInvitationService;
use App\Models\Admin;
use App\Models\VendorEmployee;
use App\Models\AdminRole;
use App\Models\EmployeeRole;
use App\Models\Zone;
use App\Models\Store;
use App\Mail\EmployeeApplicationApproved;
use App\Mail\EmployeeApplicationDenied;
use App\Mail\EmployeeInvitationMail;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmployeeApplicationController extends Controller
{
    public function __construct(
        protected EmployeeApplicationRepository $applicationRepo,
        protected EmployeeApplicationService $applicationService,
        protected EmployeeInvitationService $invitationService
    ) {}

    /**
     * List all pending applications
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'admin'); // admin or vendor
        $zoneId = $request->query('zone_id');
        $storeId = $request->query('store_id');
        $search = $request->query('search');

        if ($type === 'admin') {
            $applications = $this->applicationRepo->getPendingAdminApplications(
                $search,
                $zoneId
            );
        } else {
            $applications = $this->applicationRepo->getPendingVendorApplications(
                $search,
                $storeId
            );
        }

        $stats = $this->applicationRepo->getApplicationStats();
        $zones = Zone::where('status', 1)->get();
        $stores = Store::where('status', 1)->get();

        return view('admin-views.employee-application.list', compact(
            'applications',
            'type',
            'stats',
            'zones',
            'stores',
            'search'
        ));
    }

    /**
     * View application details
     */
    public function view(string $applicationId)
    {
        $application = $this->applicationRepo->getApplicationByToken($applicationId, 'admin');
        $type = 'admin';

        if (!$application) {
            $application = $this->applicationRepo->getApplicationByToken($applicationId, 'vendor');
            $type = 'vendor';
        }

        if (!$application) {
            Toastr::error(translate('messages.application_not_found'));
            return back();
        }

        return view('admin-views.employee-application.view', compact('application', 'type'));
    }

    /**
     * Edit application before approval
     */
    public function edit(string $applicationId)
    {
        $application = Admin::where('application_id', $applicationId)->first();
        $type = 'admin';

        if (!$application) {
            $application = VendorEmployee::where('application_id', $applicationId)->first();
            $type = 'vendor';
        }

        if (!$application || !$application->isPending()) {
            Toastr::error(translate('messages.application_not_found_or_already_processed'));
            return back();
        }

        if ($type === 'admin') {
            $roles = AdminRole::where('id', '!=', 1)->where('status', 1)->get();
            $zones = Zone::where('status', 1)->get();
            return view('admin-views.employee-application.edit-admin', compact('application', 'roles', 'zones'));
        } else {
            $roles = EmployeeRole::where('status', 1)->get();
            $stores = Store::where('status', 1)->get();
            return view('admin-views.employee-application.edit-vendor', compact('application', 'roles', 'stores'));
        }
    }

    /**
     * Update application details
     */
    public function update(Request $request, string $applicationId)
    {
        $application = Admin::where('application_id', $applicationId)->first();
        $type = 'admin';

        if (!$application) {
            $application = VendorEmployee::where('application_id', $applicationId)->first();
            $type = 'vendor';
        }

        if (!$application || !$application->isPending()) {
            Toastr::error(translate('messages.application_not_found_or_already_processed'));
            return back();
        }

        // Update allowed fields
        $updateData = [
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
        ];

        if ($type === 'admin') {
            $updateData['role_id'] = $request->role_id;
            $updateData['zone_id'] = $request->zone_id;
        } else {
            $updateData['employee_role_id'] = $request->role_id;
            $updateData['store_id'] = $request->store_id;
        }

        $application->update($updateData);

        Toastr::success(translate('messages.application_updated_successfully'));
        return back();
    }

    /**
     * Approve application
     */
    public function approve(Request $request, string $applicationId)
    {
        $request->validate([
            'role_id' => 'required',
        ]);

        $application = Admin::where('application_id', $applicationId)->first();
        $type = 'admin';

        if (!$application) {
            $application = VendorEmployee::where('application_id', $applicationId)->first();
            $type = 'vendor';
        }

        if (!$application || !$application->isPending()) {
            Toastr::error(translate('messages.application_not_found_or_already_processed'));
            return back();
        }

        $approvalData = $this->applicationService->getApprovalData($request, auth('admin')->id());
        $this->applicationRepo->approveApplication($application, $approvalData);

        // Send approval email
        try {
            Mail::to($application->email)->send(
                new EmployeeApplicationApproved($type, $application)
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send approval email: ' . $e->getMessage());
        }

        Toastr::success(translate('messages.application_approved_successfully'));
        return redirect()->route('admin.employee-application.list', ['type' => $type]);
    }

    /**
     * Deny application
     */
    public function deny(Request $request, string $applicationId)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $application = Admin::where('application_id', $applicationId)->first();
        $type = 'admin';

        if (!$application) {
            $application = VendorEmployee::where('application_id', $applicationId)->first();
            $type = 'vendor';
        }

        if (!$application || !$application->isPending()) {
            Toastr::error(translate('messages.application_not_found_or_already_processed'));
            return back();
        }

        $denialData = $this->applicationService->getDenialData($request, auth('admin')->id());
        $this->applicationRepo->denyApplication($application, $denialData);

        // Send denial email
        try {
            Mail::to($application->email)->send(
                new EmployeeApplicationDenied($type, $application)
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send denial email: ' . $e->getMessage());
        }

        Toastr::success(translate('messages.application_denied_successfully'));
        return redirect()->route('admin.employee-application.list', ['type' => $type]);
    }

    /**
     * Show invitation form
     */
    public function showInviteForm()
    {
        $adminRoles = AdminRole::where('id', '!=', 1)->where('status', 1)->get();
        $vendorRoles = EmployeeRole::where('status', 1)->get();
        $zones = Zone::where('status', 1)->get();
        $stores = Store::where('status', 1)->get();

        return view('admin-views.employee-application.invite', compact(
            'adminRoles',
            'vendorRoles',
            'zones',
            'stores'
        ));
    }

    /**
     * Send invitation
     */
    public function sendInvitation(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'employee_type' => 'required|in:admin,vendor',
            'role_id' => 'required',
            'zone_id' => 'required_if:employee_type,admin',
            'store_id' => 'required_if:employee_type,vendor',
            'expiry_days' => 'nullable|integer|min:1|max:30',
        ]);

        $invitation = $this->invitationService->createInvitation([
            'email' => $request->email,
            'employee_type' => $request->employee_type,
            'role_id' => $request->role_id,
            'zone_id' => $request->zone_id,
            'store_id' => $request->store_id,
            'created_by' => auth('admin')->id(),
            'expiry_days' => $request->expiry_days ?? 7,
        ]);

        // Send invitation email
        try {
            Mail::to($invitation->email)->send(
                new EmployeeInvitationMail($invitation)
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send invitation email: ' . $e->getMessage());
            Toastr::error(translate('messages.failed_to_send_invitation'));
            return back();
        }

        Toastr::success(translate('messages.invitation_sent_successfully'));
        return back();
    }
}
