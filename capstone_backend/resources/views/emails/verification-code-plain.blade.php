Welcome to {{ config('app.name', 'GiveOra') }}!

Dear {{ $name }},

Thank you for registering with {{ config('app.name', 'GiveOra') }}! To complete your registration and activate your account, please verify your email address.

YOUR VERIFICATION CODE: {{ $code }}

This code expires in 15 minutes ({{ $expiresAt->format('g:i A, F j, Y') }})

IMPORTANT:
- Enter this code on the registration page to activate your account
- Your account will NOT be created until you verify this code
- You cannot login without verifying your email first
- Maximum 5 attempts allowed

NEXT STEPS:
1. Go back to the registration page
2. Enter the 6-digit code above
3. Click "Verify Email"
4. Your account will be created and you can login!

SECURITY NOTICE:
- Never share this code with anyone
- We will never ask for this code via phone or email
- If you didn't request this, please ignore this email

Didn't receive the code or it expired?
You can request a new verification code on the registration page (maximum 3 resends).

Welcome to the {{ config('app.name', 'GiveOra') }} community!
The {{ config('app.name', 'GiveOra') }} Team

---
This email was sent to {{ $email }} because you registered for an account.
If you didn't register, please ignore this email.
