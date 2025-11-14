<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Password Reset Code</title>
    <style>
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            background-color: #f5f5f5;
        }
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a1a1a;
            }
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        @media (prefers-color-scheme: dark) {
            .card {
                background: #2d2d2d;
                color: #ffffff;
            }
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #F2A024;
            font-size: 28px;
            margin: 0;
        }
        .title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }
        @media (prefers-color-scheme: dark) {
            .title {
                color: #ffffff;
            }
        }
        .message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            text-align: center;
        }
        @media (prefers-color-scheme: dark) {
            .message {
                color: #cccccc;
            }
        }
        .code-container {
            background: linear-gradient(135deg, #F2A024 0%, #E89015 100%);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .code {
            font-size: 48px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #ffffff;
            font-family: 'Courier New', monospace;
        }
        .code-label {
            font-size: 14px;
            color: #ffffff;
            opacity: 0.9;
            margin-top: 10px;
        }
        .expiry {
            text-align: center;
            font-size: 14px;
            color: #999;
            margin: 20px 0;
        }
        @media (prefers-color-scheme: dark) {
            .expiry {
                color: #999;
            }
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background: #F2A024;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
        }
        .button:hover {
            background: #E89015;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eeeeee;
            font-size: 14px;
            color: #999;
            text-align: center;
        }
        @media (prefers-color-scheme: dark) {
            .footer {
                border-top-color: #444;
                color: #999;
            }
        }
        .security-note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #856404;
        }
        @media (prefers-color-scheme: dark) {
            .security-note {
                background: #332701;
                border-left-color: #ffc107;
                color: #ffd54f;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <h1>{{ config('app.name') }}</h1>
            </div>
            
            <div class="title">
                Password Reset Request
            </div>
            
            <div class="message">
                Hello {{ $user->name }},<br><br>
                We received a request to reset your password. Use the verification code below to complete the process:
            </div>
            
            <div class="code-container">
                <div class="code">{{ $code }}</div>
                <div class="code-label">Your Verification Code</div>
            </div>
            
            <div class="expiry">
                ⏰ This code will expire at {{ $expiresAt->format('g:i A') }} ({{ $expiresAt->diffForHumans() }})
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}?email={{ urlencode($user->email) }}" class="button">
                    Reset Password
                </a>
            </div>
            
            <div class="security-note">
                <strong>🔒 Security Notice:</strong><br>
                If you did not request a password reset, please ignore this email or contact our support team if you have concerns. This code can only be used once and will expire in 15 minutes.
            </div>
            
            <div class="footer">
                <p>This is an automated message from {{ config('app.name') }}.<br>
                Please do not reply to this email.</p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
