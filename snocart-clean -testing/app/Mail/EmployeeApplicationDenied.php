<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\CentralLogics\Helpers;

class EmployeeApplicationDenied extends Mailable
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
        $reason = $this->application->rejection_reason;

        return $this->subject(translate('messages.employee_application_denied') ?: 'Employee Application Update')
            ->view('emails.employee.application-denied', [
                'company_name' => $companyName,
                'applicant_name' => $applicantName,
                'reason' => $reason,
                'type' => $this->type,
            ]);
    }
}
