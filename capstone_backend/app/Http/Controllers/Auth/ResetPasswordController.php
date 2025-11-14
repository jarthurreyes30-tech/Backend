<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordResetCode;
use App\Mail\PasswordChangedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class ResetPasswordController extends Controller
{
    /**
     * Reset user password with verification code
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = $request->email;
        $code = $request->code;
        $newPassword = $request->password;

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
            
            $remainingAttempts = 5 - $resetCode->attempts;
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.',
                'remaining_attempts' => $remainingAttempts,
            ], 400);
        }

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Update password
            $user->password = Hash::make($newPassword);
            $user->save();

            // Mark reset code as used
            $resetCode->markAsUsed();

            // Log the password change
            Log::info('Password reset successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            // Send confirmation email with IP address
            Mail::to($user->email)->queue(new PasswordChangedMail($user, $request->ip()));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Your password has been reset successfully. You can now log in with your new password.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Password reset failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resetting your password. Please try again.',
            ], 500);
        }
    }
}
