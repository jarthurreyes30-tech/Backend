<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\VerificationCodeMail;
use App\Services\BrevoMailer;

class EmailTestController extends Controller
{
    /**
     * Test email sending directly - NO QUEUE
     */
    public function testDirectEmail(Request $request)
    {
        $email = $request->input('email', 'charityhub25@gmail.com');
        
        Log::info('=== STARTING DIRECT EMAIL TEST ===', ['email' => $email]);
        
        try {
            // Test 1: Direct BrevoMailer service
            Log::info('Test 1: Using BrevoMailer service directly');
            $brevoMailer = app(BrevoMailer::class);
            
            $result = $brevoMailer->send(
                $email,
                'Test User',
                'Test Email from GiveOra',
                '<h1>This is a test email</h1><p>If you receive this, Brevo is working!</p>',
                'This is a test email. If you receive this, Brevo is working!'
            );
            
            Log::info('BrevoMailer result', $result);
            
            // Test 2: Using Mail facade with VerificationCodeMail
            Log::info('Test 2: Using Mail facade with VerificationCodeMail');
            Mail::to($email)->send(
                new VerificationCodeMail('Test User', $email, '123456', now()->addMinutes(15))
            );
            
            Log::info('=== EMAIL TEST COMPLETED SUCCESSFULLY ===');
            
            return response()->json([
                'success' => true,
                'message' => 'Test emails sent! Check your inbox and logs.',
                'brevo_result' => $result,
                'email' => $email
            ]);
            
        } catch (\Exception $e) {
            Log::error('=== EMAIL TEST FAILED ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    
    /**
     * Check Brevo configuration
     */
    public function checkBrevoConfig()
    {
        $config = [
            'BREVO_API_KEY' => config('services.brevo.api_key') ? 'SET (length: ' . strlen(config('services.brevo.api_key')) . ')' : 'NOT SET',
            'BREVO_SENDER_EMAIL' => config('services.brevo.sender_email'),
            'BREVO_SENDER_NAME' => config('services.brevo.sender_name'),
            'MAIL_MAILER' => config('mail.default'),
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
            'MAIL_FROM_NAME' => config('mail.from.name'),
            'QUEUE_CONNECTION' => config('queue.default'),
        ];
        
        return response()->json([
            'config' => $config,
            'api_key_status' => config('services.brevo.api_key') ? '✅ SET' : '❌ NOT SET'
        ]);
    }
}
