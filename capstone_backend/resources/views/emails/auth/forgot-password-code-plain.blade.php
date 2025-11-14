Password Reset Request - {{ config('app.name') }}

Hello {{ $user->name }},

We received a request to reset your password. Use the verification code below to complete the process:

YOUR VERIFICATION CODE: {{ $code }}

This code will expire at {{ $expiresAt->format('g:i A') }} ({{ $expiresAt->diffForHumans() }})

You can also reset your password by visiting:
{{ $resetUrl }}?email={{ urlencode($user->email) }}

SECURITY NOTICE:
If you did not request a password reset, please ignore this email or contact our support team if you have concerns. This code can only be used once and will expire in 15 minutes.

---
This is an automated message from {{ config('app.name') }}.
Please do not reply to this email.

© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
