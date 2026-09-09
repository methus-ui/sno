<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Update</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #ef4444; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0;">{{ $company_name }}</h1>
    </div>

    <div style="background-color: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px;">
        <h2 style="color: #ef4444;">Application Status Update</h2>

        <p>Dear {{ $applicant_name }},</p>

        <p>Thank you for your interest in the <strong>{{ ucfirst($type) }} Employee</strong> position at {{ $company_name }}.</p>

        <p>After careful consideration, we regret to inform you that we are unable to proceed with your application at this time.</p>

        @if($reason)
        <div style="background-color: white; padding: 20px; border-left: 4px solid #ef4444; margin: 20px 0;">
            <p style="margin: 0;"><strong>Reason:</strong></p>
            <p style="margin: 10px 0 0 0;">{{ $reason }}</p>
        </div>
        @endif

        <p>We appreciate the time and effort you put into your application. This decision was difficult and does not reflect on your qualifications or potential.</p>

        <p><strong>What you can do:</strong></p>
        <ul>
            <li>Review and update your qualifications</li>
            <li>Apply again in the future when new positions open</li>
            <li>Consider other positions that may match your skills</li>
        </ul>

        <p>We encourage you to stay connected with us for future opportunities.</p>

        <p>We wish you all the best in your career endeavors.</p>

        <p>Best regards,<br>
        <strong>{{ $company_name }} HR Team</strong></p>
    </div>

    <div style="text-align: center; padding: 20px; color: #666; font-size: 12px;">
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
