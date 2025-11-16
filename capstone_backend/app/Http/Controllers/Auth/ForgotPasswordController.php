<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordResetCode;
use App\Mail\ForgotPasswordCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    /**
     * Send password reset code to user's email
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = $request->email;
        $ip = $request->ip();

        // Rate limiting: 5 requests per hour per email
        $emailKey = 'forgot-password:email:' . $email;
        $ipKey = 'forgot-password:ip:' . $ip;

        if (RateLimiter::tooManyAttempts($emailKey, 5)) {
            $seconds = RateLimiter::availableIn($emailKey);
            return response()->json([
                'success' => false,
                'message' => 'Too many password reset requests. Please try again later.',
                'retry_after' => $seconds,
            ], 429);
        }

        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $seconds = RateLimiter::availableIn($ipKey);
            return response()->json([
                'success' => false,
                'message' => 'Too many requests from this IP. Please try again later.',
                'retry_after' => $seconds,
            ], 429);
        }

        // Hit the rate limiter
        RateLimiter::hit($emailKey, 3600); // 1 hour
        RateLimiter::hit($ipKey, 3600);

        // Check if user exists (but don't reveal this in response)
        $user = User::where('email', $email)->first();

        if ($user) {
            // Generate secure 6-digit code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Hash the code for storage
            $tokenHash = Hash::make($code);

            // Invalidate any existing codes for this email
            PasswordResetCode::where('email', $email)
                ->where('used', false)
                ->update(['used' => true]);

            // Create new reset code record
            $resetCode = PasswordResetCode::create([
                'email' => $email,
                'token_hash' => $tokenHash,
                'ip' => $ip,
                'expires_at' => Carbon::now()->addMinutes(15),
                'attempts' => 0,
                'used' => false,
            ]);

            // Queue email
            Mail::to($user->email)->queue(new ForgotPasswordCodeMail($user, $code, $resetCode->expires_at));

            Log::info('Password reset code sent', [
                'email' => $email,
                'ip' => $ip,
                'expires_at' => $resetCode->expires_at,
            ]);
        }

        // Always return success to prevent user enumeration
        return response()->json([
            'success' => true,
            'message' => 'If that email exists in our system, we sent a verification code. Please check your inbox.',
        ], 200);
    }

    /**
     * Resend reset code
     */
    public function resendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        // Use same logic as sendResetCode
        return $this->sendResetCode($request);
    }

    /**
     * Verify reset code (optional two-step verification)
     */
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $email = $request->email;
        $code = $request->code;

        // Find the most recent active reset code
        $resetCode = PasswordResetCode::forEmail($email)
            ->where('used', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$resetCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ], 400);
        }

        // Check if expired
        if ($resetCode->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired. Please request a new one.',
                'expired' => true,
            ], 400);
        }

        // Check max attempts
        if ($resetCode->hasMaxAttempts()) {
            $resetCode->markAsUsed();
            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new code.',
                'max_attempts' => true,
            ], 400);
        }

        // Verify code using constant-time comparison
        if (!Hash::check($code, $resetCode->token_hash)) {
            $resetCode->incrementAttempts();
            
            // If this wrong attempt reached the max, lock and signal `max_attempts`
            if ($resetCode->hasMaxAttempts()) {
                $resetCode->markAsUsed();
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed attempts. Please request a new code.',
                    'max_attempts' => true,
                ], 400);
            }
            
            $remainingAttempts = 5 - $resetCode->attempts;
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.',
                'remaining_attempts' => $remainingAttempts,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code verified successfully.',
        ], 200);
    }
}
