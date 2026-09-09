<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\CentralLogics\Helpers;

class EmployeeApplicationReceived extends Mailable
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
        $applicationId = $this->application->application_id;

        return $this->subject(translate('messages.employee_application_received') ?: 'Employee Application Received')
            ->view('emails.employee.application-received', [
                'company_name' => $companyName,
                'applicant_name' => $applicantName,
                'application_id' => $applicationId,
                'type' => $this->type,
                'status_url' => route('employee.application.status', ['id' => $applicationId]),
            ]);
    }
}
