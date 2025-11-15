<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SimpleTestController extends Controller
{
    public function test1()
    {
        return response()->json([
            'test' => 'Test 1: Basic response',
            'status' => 'OK'
        ]);
    }
    
    public function test2()
    {
        try {
            Log::info('Test 2: Instantiating BrevoMailer');
            $brevo = app(\App\Services\BrevoMailer::class);
            Log::info('Test 2: BrevoMailer instantiated successfully');
            
            return response()->json([
                'test' => 'Test 2: BrevoMailer instantiation',
                'status' => 'OK',
                'brevo_class' => get_class($brevo)
            ]);
        } catch (\Exception $e) {
            Log::error('Test 2 FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'test' => 'Test 2: BrevoMailer instantiation',
                'status' => 'FAILED',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    public function test3()
    {
        try {
            Log::info('Test 3: Sending via BrevoMailer directly');
            $brevo = app(\App\Services\BrevoMailer::class);
            
            $result = $brevo->send(
                'charityhub25@gmail.com',
                'Test User',
                'Simple Test Email',
                '<h1>Test</h1><p>This is a test email.</p>',
                'Test - This is a test email.'
            );
            
            Log::info('Test 3: Email sent successfully', $result);
            
            return response()->json([
                'test' => 'Test 3: Direct Brevo send',
                'status' => 'OK',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Test 3 FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'test' => 'Test 3: Direct Brevo send',
                'status' => 'FAILED',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    public function test4(Request $request)
    {
        try {
            Log::info('Test 4: Full registration flow simulation');
            
            // Simulate what registerMinimal does
            $email = $request->input('email', 'test@example.com');
            $name = $request->input('name', 'Test User');
            
            Log::info('Step 1: Generate code');
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            Log::info('Step 1: Code generated', ['code' => $code]);
            
            Log::info('Step 2: Instantiate BrevoMailer');
            $brevo = app(\App\Services\BrevoMailer::class);
            Log::info('Step 2: BrevoMailer ready');
            
            Log::info('Step 3: Render views');
            $html = '<h1>Code: ' . $code . '</h1>';
            $text = 'Code: ' . $code;
            Log::info('Step 3: Views rendered');
            
            Log::info('Step 4: Send email');
            $result = $brevo->send($email, $name, 'Test Email', $html, $text);
            Log::info('Step 4: Email sent', $result);
            
            return response()->json([
                'test' => 'Test 4: Full flow',
                'status' => 'OK',
                'email' => $email,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Test 4 FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'test' => 'Test 4: Full flow',
                'status' => 'FAILED',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
