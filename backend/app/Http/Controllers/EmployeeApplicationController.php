<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeApplicationRequest;
use App\Models\Admin;
use App\Models\VendorEmployee;
use App\Models\AdminRole;
use App\Models\EmployeeRole;
use App\Models\Zone;
use App\Models\Store;
use App\Models\BusinessSetting;
use App\Models\EmployeeInvitation;
use App\Services\EmployeeApplicationService;
use App\Services\EmployeeInvitationService;
use App\Mail\EmployeeApplicationReceived;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmployeeApplicationController extends Controller
{
    public function __construct(
        protected EmployeeApplicationService $applicationService,
        protected EmployeeInvitationService $invitationService
    ) {}

    /**
     * Show registration type selection
     */
    public function selectType(Request $request)
    {
        // Check if public registration is enabled
        $publicRegistrationEnabled = BusinessSetting::where('key', 'employee_public_registration_enabled')
            ->first()?->value ?? '0';

        // If coming from invitation, skip this page
        if ($request->has('token')) {
            $invitation = $this->invitationService->validateInvitation($request->token);

            if (!$invitation) {
                Toastr::error(translate('messages.invalid_or_expired_invitation'));
                return redirect()->route('home');
            }

            // Redirect directly to appropriate registration
            if ($invitation->employee_type === 'admin') {
                return redirect()->route('employee.register.admin', ['token' => $request->token]);
            } else {
                return redirect()->route('employee.register.vendor', ['token' => $request->token]);
            }
        }

        // For public registration
        if ($publicRegistrationEnabled !== '1') {
            Toastr::error(translate('messages.employee_registration_is_currently_disabled'));
            return redirect()->route('home');
        }

        return view('employee-application.select-type');
    }

    /**
     * Show admin employee registration form
     */
    public function showAdminRegistration(Request $request)
    {
        $invitation = null;
        $invitationData = [];

        // Check for invitation token
        if ($request->has('token')) {
            $invitation = $this->invitationService->validateInvitation($request->token);

            if (!$invitation || $invitation->employee_type !== 'admin') {
                Toastr::error(translate('messages.invalid_or_expired_invitation'));
                return redirect()->route('employee.register');
            }

            $invitationData = $this->invitationService->getInvitationData($invitation);
        } else {
            // Check if public registration is enabled
            $publicRegistrationEnabled = BusinessSetting::where('key', 'employee_public_registration_enabled')
                ->first()?->value ?? '0';

            if ($publicRegistrationEnabled !== '1') {
                Toastr::error(translate('messages.employee_registration_is_currently_disabled'));
                return redirect()->route('home');
            }
        }

        $roles = AdminRole::where('id', '!=', 1)->where('status', 1)->get();
        $zones = Zone::where('status', 1)->get();

        // Use v2 form with verification fields
        return view('employee-application.admin-register-v2', compact('roles', 'zones', 'invitation', 'invitationData'));
    }

    /**
     * Show vendor employee registration form
     */
    public function showVendorRegistration(Request $request)
    {
        $invitation = null;
        $invitationData = [];

        // Check for invitation token
        if ($request->has('token')) {
            $invitation = $this->invitationService->validateInvitation($request->token);

            if (!$invitation || $invitation->employee_type !== 'vendor') {
                Toastr::error(translate('messages.invalid_or_expired_invitation'));
                return redirect()->route('employee.register');
            }

            $invitationData = $this->invitationService->getInvitationData($invitation);
        } else {
            // Check if public registration is enabled
            $publicRegistrationEnabled = BusinessSetting::where('key', 'employee_public_registration_enabled')
                ->first()?->value ?? '0';

            if ($publicRegistrationEnabled !== '1') {
                Toastr::error(translate('messages.employee_registration_is_currently_disabled'));
                return redirect()->route('home');
            }
        }

        $roles = EmployeeRole::where('status', 1)->get();
        $stores = Store::where('status', 1)->with('vendor')->get();

        return view('employee-application.vendor-register', compact('roles', 'stores', 'invitation', 'invitationData'));
    }

    /**
     * Submit admin employee application
     */
    public function submitAdminApplication(Request $request)
    {
        $request->validate([
            'f_name' => 'required|string|max:100',
            'l_name' => 'required|string|max:100',
            'email' => 'required|email|unique:admins,email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'required|exists:admin_roles,id',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'resume' => 'nullable|mimes:pdf|max:5120',
            'id_proof' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'certificates.*' => 'nullable|mimes:pdf|max:5120',

            // Verification fields
            'alternate_phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20|different:phone',
            'father_phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20',
            'family_contact_phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20',
            'family_contact_name' => 'nullable|string|max:100',
            'aadhar_number' => ['required', 'regex:/^[0-9]{12}$/', new \App\Rules\ValidAadhar],
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|regex:/^[0-9]{6}$/',
            'date_of_joining' => 'nullable|date|after_or_equal:today',
            'past_experience' => 'nullable|string|max:1000',
            'cancelled_cheque' => 'nullable|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $invitation = null;

        // Validate invitation if provided
        if ($request->has('invitation_token')) {
            $invitation = $this->invitationService->validateInvitation($request->invitation_token);

            if (!$invitation) {
                Toastr::error(translate('messages.invalid_or_expired_invitation'));
                return back()->withInput();
            }
        }

        // Prepare and create application
        $data = $this->applicationService->prepareAdminApplicationData($request, $invitation?->token);

        // Sanitize names to prevent XSS (defense in depth)
        $data['f_name'] = strip_tags($data['f_name'] ?? '');
        $data['l_name'] = strip_tags($data['l_name'] ?? '');
        $data['family_contact_name'] = isset($data['family_contact_name']) ? strip_tags($data['family_contact_name']) : null;

        $application = Admin::create($data);

        // Mark invitation as used
        if ($invitation) {
            $invitation->markAsUsed();
        }

        // Send confirmation email
        try {
            Mail::to($application->email)->send(
                new EmployeeApplicationReceived('admin', $application)
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send application confirmation email: ' . $e->getMessage());
        }

        Toastr::success(translate('messages.application_submitted_successfully'));
        return redirect()->route('employee.application.status', ['id' => $application->application_id]);
    }

    /**
     * Submit vendor employee application
     */
    public function submitVendorApplication(Request $request)
    {
        $request->validate([
            'f_name' => 'required|string|max:100',
            'l_name' => 'required|string|max:100',
            'email' => 'required|email|unique:vendor_employees,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'required|exists:employee_roles,id',
            'store_id' => 'required|exists:stores,id',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'resume' => 'nullable|mimes:pdf|max:5120',
            'id_proof' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'certificates.*' => 'nullable|mimes:pdf|max:5120',
        ]);

        $invitation = null;

        // Validate invitation if provided
        if ($request->has('invitation_token')) {
            $invitation = $this->invitationService->validateInvitation($request->invitation_token);

            if (!$invitation) {
                Toastr::error(translate('messages.invalid_or_expired_invitation'));
                return back()->withInput();
            }
        }

        // Prepare and create application
        $data = $this->applicationService->prepareVendorApplicationData($request, $invitation?->token);

        // Sanitize names to prevent XSS (defense in depth)
        $data['f_name'] = strip_tags($data['f_name'] ?? '');
        $data['l_name'] = strip_tags($data['l_name'] ?? '');
        $data['family_contact_name'] = isset($data['family_contact_name']) ? strip_tags($data['family_contact_name']) : null;

        $application = VendorEmployee::create($data);

        // Mark invitation as used
        if ($invitation) {
            $invitation->markAsUsed();
        }

        // Send confirmation email
        try {
            Mail::to($application->email)->send(
                new EmployeeApplicationReceived('vendor', $application)
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send application confirmation email: ' . $e->getMessage());
        }

        Toastr::success(translate('messages.application_submitted_successfully'));
        return redirect()->route('employee.application.status', ['id' => $application->application_id]);
    }

    /**
     * Check application status
     */
    public function checkStatus(Request $request)
    {
        if ($request->has('application_id')) {
            // Try to find in both tables
            $application = Admin::where('application_id', $request->application_id)->first();
            $type = 'admin';

            if (!$application) {
                $application = VendorEmployee::where('application_id', $request->application_id)->first();
                $type = 'vendor';
            }

            if (!$application) {
                Toastr::error(translate('messages.application_not_found'));
                return back();
            }

            return view('employee-application.status-result', compact('application', 'type'));
        }

        return view('employee-application.status-check');
    }

    /**
     * Show application status by ID
     */
    public function showStatus(string $applicationId)
    {
        // Try to find in both tables
        $application = Admin::where('application_id', $applicationId)->first();
        $type = 'admin';

        if (!$application) {
            $application = VendorEmployee::where('application_id', $applicationId)->first();
            $type = 'vendor';
        }

        if (!$application) {
            Toastr::error(translate('messages.application_not_found'));
            return redirect()->route('employee.application.check');
        }

        return view('employee-application.status-result', compact('application', 'type'));
    }
}
