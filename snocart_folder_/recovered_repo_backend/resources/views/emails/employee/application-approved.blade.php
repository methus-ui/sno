<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Approved</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #10b981; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0;">🎉 Congratulations!</h1>
    </div>

    <div style="background-color: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px;">
        <h2 style="color: #10b981;">Your Application Has Been Approved!</h2>

        <p>Dear {{ $applicant_name }},</p>

        <p>We are pleased to inform you that your application for the <strong>{{ $role_name }}</strong> position has been <strong style="color: #10b981;">APPROVED</strong>!</p>

        <p>Welcome to the {{ $company_name }} team! We're excited to have you on board.</p>

        <div style="background-color: white; padding: 20px; border-left: 4px solid #10b981; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #10b981;">Your Login Credentials</h3>
            <p><strong>Email:</strong> {{ $email }}</p>
            <p><strong>Password:</strong> The password you set during registration</p>
            <p style="margin-bottom: 0;"><strong>Portal:</strong> {{ ucfirst($type) }} Panel</p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $login_url }}" style="background-color: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 16px;">Login to Your Account</a>
        </div>

        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Click the button above to access your account</li>
            <li>Login with your email and password</li>
            <li>Complete your profile if needed</li>
            <li>Start working!</li>
        </ol>

        <p>If you encounter any issues logging in or have questions, please contact your supervisor or our support team.</p>

        <p>We look forward to working with you!</p>

        <p>Best regards,<br>
        <strong>{{ $company_name }} Team</strong></p>
    </div>

    <div style="text-align: center; padding: 20px; color: #666; font-size: 12px;">
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
