<!DOCTYPE html>
<html>
<head>
    <title>Billing Expired</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; padding: 20px; text-align: center; color: white; }
        .content { padding: 20px; background: #f8f9fa; }
        .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🚫 Billing Expired</h2>
        </div>
        <div class="content">
            <p>Dear {{ $admin->name }},</p>
            <p>Your tenant <strong>{{ $tenant->name }}</strong> has expired and has been deactivated.</p>
            <p><strong>Reason:</strong> {{ $reason }}</p>
            <p>Your account is now inactive. To reactivate your account, please renew your subscription.</p>
            <p>
                <a href="{{ $billingUrl }}" style="display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;">
                    Renew Now
                </a>
            </p>
            <p>If you have any questions or believe this is an error, please contact our support team immediately.</p>
            <p>Thank you,<br>Stardena Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Stardena. All rights reserved.</p>
        </div>
    </div>
</body>
</html>