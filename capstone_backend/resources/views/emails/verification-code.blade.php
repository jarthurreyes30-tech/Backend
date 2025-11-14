@extends('emails.layout')

@section('title', 'Verify Your Email')

@section('content')
    <h2>Welcome to {{ config('app.name', 'GiveOra') }}! 🎉</h2>
    
    <p>Dear {{ $name }},</p>
    
    <p>Thank you for registering with {{ config('app.name', 'GiveOra') }}! To complete your registration and activate your account, please verify your email address.</p>
    
    <div class="success-box" style="text-align: center; padding: 30px; background: #f0f9ff; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0; font-size: 14px; color: #64748b;">Your Verification Code:</p>
        <h1 style="margin: 0; font-size: 48px; font-weight: bold; letter-spacing: 8px; color: #0ea5e9; font-family: 'Courier New', monospace;">
            {{ $code }}
        </h1>
        <p style="margin: 10px 0 0 0; font-size: 12px; color: #94a3b8;">
            This code expires in <strong>15 minutes</strong>
        </p>
    </div>
    
    <div class="info-box" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0;"><strong>⚠️ Important:</strong></p>
        <ul style="margin: 0; padding-left: 20px;">
            <li>Enter this code on the registration page to activate your account</li>
            <li><strong>Your account will NOT be created</strong> until you verify this code</li>
            <li>You cannot login without verifying your email first</li>
            <li>This code will expire at: <strong>{{ $expiresAt->format('g:i A, F j, Y') }}</strong></li>
            <li>Maximum 5 attempts allowed</li>
        </ul>
    </div>
    
    <p><strong>Next Steps:</strong></p>
    <ol style="padding-left: 20px;">
        <li>Go back to the registration page</li>
        <li>Enter the 6-digit code above</li>
        <li>Click "Verify Email"</li>
        <li>Your account will be created and you can login!</li>
    </ol>
    
    <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0;">
        <p style="margin: 0;"><strong>🔒 Security Notice:</strong></p>
        <ul style="margin: 5px 0 0 0; padding-left: 20px;">
            <li>Never share this code with anyone</li>
            <li>We will never ask for this code via phone or email</li>
            <li>If you didn't request this, please ignore this email</li>
        </ul>
    </div>
    
    <p><strong>Didn't receive the code or it expired?</strong><br>
    You can request a new verification code on the registration page (maximum 3 resends).</p>
    
    <p>Welcome to the {{ config('app.name', 'GiveOra') }} community!<br>
    <strong>The {{ config('app.name', 'GiveOra') }} Team</strong></p>
@endsection
