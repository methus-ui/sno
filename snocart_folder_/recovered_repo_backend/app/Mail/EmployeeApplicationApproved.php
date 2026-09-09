<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\CentralLogics\Helpers;

class EmployeeApplicationApproved extends Mailable
{
    use Queueable, SerializesModels;

    protected $type;
    protected $application;

    public function __construct($type, $application)
    {
        $this->type = $type;
        $this->application = $application;
    }

    public function build()
    {
        $companyName = Helpers::get_business_settings('business_name') ?? config('app.name');
        $applicantName = $this->application->f_name . ' ' . $this->application->l_name;
        $email = $this->application->email;

        $loginUrl = $this->type === 'admin'
            ? route('admin.auth.login')
            : route('vendor_employee.auth.login');

        return $this->subject(translate('messages.employee_application_approved') ?: 'Congratulations! Your Application is Approved')
            ->view('emails.employee.application-approved', [
                'company_name' => $companyName,
                'applicant_name' => $applicantName,
                'email' => $email,
                'type' => $this->type,
                'login_url' => $loginUrl,
                'role_name' => $this->application->role?->name ?? 'Employee',
            ]);
    }
}
