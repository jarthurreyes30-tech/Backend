Welcome to {{ config('app.name', 'GiveOra') }}!

Dear {{ $name }},

Account Created Successfully!

You're now part of a community dedicated to making positive change.

We're thrilled to have you join {{ config('app.name', 'GiveOra') }}, where compassion meets action!

@if($role === 'donor')
As a donor, you can:
- Browse and support verified charity campaigns
- Track your donation history and impact
- Receive updates from the charities you support
- View transparent fund usage reports
- Engage with the charitable community
@elseif($role === 'charity')
As a charity organization, you can:
- Create fundraising campaigns
- Manage donations and donor relationships
- Post updates and engage with supporters
- Generate transparency reports
- Build trust through verified status
@endif

Go to Dashboard: {{ $dashboardUrl }}

Getting Started:
- Complete your profile for a personalized experience
- Explore active campaigns in your area
- Connect with causes that matter to you
- Learn about our transparency features

Need help? Our support team is here to assist you with any questions.

Together, we're building a platform that makes giving transparent, efficient, and impactful.

Welcome aboard!
The {{ config('app.name', 'GiveOra') }} Team

---
This email was sent to {{ $user->email }} because you registered for an account.
If you didn't register, please ignore this email.
