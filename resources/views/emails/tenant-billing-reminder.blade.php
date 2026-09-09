<!DOCTYPE html>
<html>
<head>
    <title>Billing Reminder</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ffc107; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚠️ Billing Reminder</h2>
        </div>
        <div class="content">
            <p>Dear {{ $admin->name }},</p>
            <p>This is a reminder that your tenant <strong>{{ $tenant->name }}</strong> will expire in <strong>{{ $daysUntilExpiry }} days</strong>.</p>
            <p><strong>Expiry Date:</strong> {{ $expiryDate->format('d M Y, h:i A') }}</p>
            <p>To avoid service interruption, please renew your subscription immediately.</p>
            <p>
                <a href="{{ $billingUrl }}" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
                    Renew Now
                </a>
            </p>
            <p>If you have any questions, please contact our support team.</p>
            <p>Thank you,<br>Stardena Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Stardena. All rights reserved.</p>
        </div>
    </div>
</body>
</html>