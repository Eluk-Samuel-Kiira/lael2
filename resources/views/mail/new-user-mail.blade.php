<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .credentials {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Welcome to {{ env('APP_NAME') }}</h2>
    </div>

    <div class="content">
        <p>Hello {{ $name }},</p>

        <p>Your account has been successfully created in <strong>{{ env('APP_NAME') }}</strong>. You have been assigned the role of <strong>{{ $role }}</strong>.</p>
        <p>Department: <strong>{{ $department }}</strong>.</p>

        <div class="credentials">
            <p><strong>Your Login Credentials:</strong></p>
            <p>Email: {{ $email }}</p>
            <p>Password: {{ $password }}</p>
        </div>

        <p>To access your account, please click the button below:</p>

        <a href="{{ route('login') }}" class="button">Login to Your Account</a>

        <p><strong>Important:</strong> For security reasons, we strongly recommend changing your password after your first login.</p>
    </div>

    <div class="footer">
        <p>This is an automated message, please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} {{ env('APP_NAME') }}. All rights reserved.</p>
    </div>
</body>
</html>
