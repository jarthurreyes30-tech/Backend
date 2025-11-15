<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\BrevoMailer;

class DebugEmailController extends Controller
{
    public function testBrevo(Request $request)
    {
        try {
            $email = $request->input('email', 'charityhub25@gmail.com');
            
            Log::info('=== DEBUG EMAIL TEST START ===');
            
            // Test 1: Check if BrevoMailer can be instantiated
            Log::info('Step 1: Instantiating BrevoMailer...');
            $brevoMailer = app(BrevoMailer::class);
            Log::info('Step 1: SUCCESS - BrevoMailer instantiated');
            
            // Test 2: Render views
            Log::info('Step 2: Rendering email views...');
            $htmlView = view('emails.verification-code', [
                'name' => 'Test User',
                'code' => '123456',
                'expiresAt' => now()->addMinutes(15),
                'email' => $email
            ])->render();
            Log::info('Step 2: SUCCESS - HTML view rendered', ['length' => strlen($htmlView)]);
            
            $textView = view('emails.verification-code-plain', [
                'name' => 'Test User',
                'code' => '123456',
                'expiresAt' => now()->addMinutes(15),
                'email' => $email
            ])->render();
            Log::info('Step 2: SUCCESS - Text view rendered', ['length' => strlen($textView)]);
            
            // Test 3: Send email
            Log::info('Step 3: Sending email via Brevo...');
            $result = $brevoMailer->send(
                $email,
                'Test User',
                'Test Email - GiveOra',
                $htmlView,
                $textView
            );
            Log::info('Step 3: SUCCESS - Email sent', $result);
            
            Log::info('=== DEBUG EMAIL TEST COMPLETE - SUCCESS ===');
            
            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully!',
                'result' => $result,
                'email' => $email
            ]);
            
        } catch (\Exception $e) {
            Log::error('=== DEBUG EMAIL TEST FAILED ===', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
