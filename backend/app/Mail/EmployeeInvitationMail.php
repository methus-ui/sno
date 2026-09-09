<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\EmployeeInvitation;
use App\CentralLogics\Helpers;

class EmployeeInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $invitation;

    public function __construct(EmployeeInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function build()
    {
        $companyName = Helpers::get_business_settings('business_name') ?? config('app.name');
        $registrationUrl = route('employee.register', ['token' => $this->invitation->token]);
        $expiresAt = $this->invitation->expires_at->format('M d, Y');

        return $this->subject(translate('messages.employee_invitation') ?: 'You\'re Invited to Join Our Team')
            ->view('emails.employee.invitation', [
                'company_name' => $companyName,
                'registration_url' => $registrationUrl,
                'expires_at' => $expiresAt,
                'employee_type' => $this->invitation->employee_type,
                'role_name' => $this->invitation->role?->name ?? 'Employee',
            ]);
    }
}
