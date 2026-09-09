<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Received</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #D82E5E; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0;">{{ $company_name }}</h1>
    </div>

    <div style="background-color: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px;">
        <h2 style="color: #D82E5E;">Application Received Successfully!</h2>

        <p>Dear {{ $applicant_name }},</p>

        <p>Thank you for applying for the <strong>{{ ucfirst($type) }} Employee</strong> position at {{ $company_name }}.</p>

        <p>We have successfully received your application and it is currently under review by our team.</p>

        <div style="background-color: white; padding: 20px; border-left: 4px solid #D82E5E; margin: 20px 0;">
            <p style="margin: 0;"><strong>Your Application ID:</strong></p>
            <p style="font-size: 24px; color: #D82E5E; font-weight: bold; margin: 10px 0;">{{ $application_id }}</p>
            <p style="margin: 0; font-size: 12px; color: #666;">Please save this ID to check your application status.</p>
        </div>

        <p>You can track the status of your application using the link below:</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $status_url }}" style="background-color: #D82E5E; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Check Application Status</a>
        </div>

        <p><strong>What happens next?</strong></p>
        <ul>
            <li>Our team will review your application</li>
            <li>You will receive an email notification once a decision is made</li>
            <li>This process typically takes 2-5 business days</li>
        </ul>

        <p>If you have any questions, please feel free to contact us.</p>

        <p>Best regards,<br>
        <strong>{{ $company_name }}</strong></p>
    </div>

    <div style="text-align: center; padding: 20px; color: #666; font-size: 12px;">
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
