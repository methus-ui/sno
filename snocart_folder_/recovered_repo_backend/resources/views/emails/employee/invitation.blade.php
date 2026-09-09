<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>You're Invited!</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #3b82f6; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0;">✉️ You're Invited!</h1>
    </div>

    <div style="background-color: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px;">
        <h2 style="color: #3b82f6;">Join Our Team at {{ $company_name }}</h2>

        <p>Hello!</p>

        <p>We're excited to invite you to join our team as a <strong>{{ $role_name }}</strong> ({{ ucfirst($employee_type) }} Employee).</p>

        <p>We believe you would be a great fit for our organization, and we've created a personalized invitation just for you!</p>

        <div style="background-color: white; padding: 20px; border-left: 4px solid #3b82f6; margin: 20px 0;">
            <p style="margin: 0;"><strong>Position:</strong> {{ $role_name }}</p>
            <p style="margin: 10px 0;"><strong>Type:</strong> {{ ucfirst($employee_type) }} Employee</p>
            <p style="margin: 10px 0 0 0;"><strong>Expires:</strong> {{ $expires_at }}</p>
        </div>

        <p><strong>How to get started:</strong></p>
        <ol>
            <li>Click the registration button below</li>
            <li>Complete the registration form (some fields will be pre-filled)</li>
            <li>Upload any required documents</li>
            <li>Submit your application for final approval</li>
        </ol>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $registration_url }}" style="background-color: #3b82f6; color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 16px; font-weight: bold;">Complete Registration</a>
        </div>

        <div style="background-color: #fef3c7; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px;">⚠️ <strong>Important:</strong> This invitation link expires on <strong>{{ $expires_at }}</strong>. Please complete your registration before this date.</p>
        </div>

        <p>If you have any questions about this invitation or the position, please don't hesitate to reach out to us.</p>

        <p>We look forward to welcoming you to our team!</p>

        <p>Best regards,<br>
        <strong>{{ $company_name }} Team</strong></p>
    </div>

    <div style="text-align: center; padding: 20px; color: #666; font-size: 12px;">
        <p>This invitation was sent to you personally. Please do not share this link with others.</p>
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
