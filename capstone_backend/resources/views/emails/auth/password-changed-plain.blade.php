Password Changed Successfully - {{ config('app.name') }}

Hi {{ $user->name }},

This email confirms that your password was successfully changed.

Changed At: {{ $changedAt->format('F d, Y h:i A') }}
IP Address: {{ $ipAddress }}

DIDN'T MAKE THIS CHANGE?
If you did not change your password, your account may be compromised. Please take action immediately:
1. Reset your password immediately
2. Review recent account activity
3. Contact our support team

Security Tips:
✓ Use a strong, unique password
✓ Enable two-factor authentication
✓ Never share your password
✓ Be cautious of phishing emails

Your account security is our top priority. Thank you for keeping your account safe!

Best regards,
The {{ config('app.name') }} Security Team

---
This is an automated message from {{ config('app.name') }}.
Please do not reply to this email.

© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
