<?php

namespace App\Http\Controllers;

use App\Services\BrevoMailer;
use App\Mail\ForgotPasswordCodeMail;
use App\Mail\PasswordChangedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BrevoTestController extends Controller
{
    protected $brevoMailer;

    public function __construct(BrevoMailer $brevoMailer)
    {
        $this->brevoMailer = $brevoMailer;
    }

    /**
     * Test Brevo API connection
     */
    public function testConnection()
    {
        try {
            $result = $this->brevoMailer->testConnection();
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Brevo connection test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test sending a simple email via Brevo
     */
    public function testSimpleEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            $result = $this->brevoMailer->send(
                $request->email,
                'Test Recipient',
                'Test Email from ' . config('app.name'),
                '<h1>Test Email</h1><p>This is a test email sent via Brevo API.</p><p>If you received this, the migration is working!</p>',
                'Test Email - This is a test email sent via Brevo API. If you received this, the migration is working!'
            );

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully',
                'message_id' => $result['message_id'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Test email failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test sending via Laravel Mail facade (should use Brevo transport)
     */
    public function testLaravelMail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            // Get first user or create a dummy one for testing
            $user = User::first() ?? User::factory()->make([
                'name' => 'Test User',
                'email' => $request->email
            ]);

            // Test with an actual mailable
            Mail::to($request->email)->send(new PasswordChangedMail($user, $request->ip()));

            return response()->json([
                'success' => true,
                'message' => 'Laravel Mail facade test successful - Email sent via Brevo',
                'details' => 'Sent PasswordChangedMail mailable'
            ]);
        } catch (\Exception $e) {
            Log::error('Laravel Mail test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send via Laravel Mail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test queued email
     */
    public function testQueuedEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            $user = User::first() ?? User::factory()->make([
                'name' => 'Test User',
                'email' => $request->email
            ]);

            // Test queued mail
            Mail::to($request->email)->queue(new ForgotPasswordCodeMail($user, '123456', now()->addMinutes(15), config('app.frontend_url') . '/auth/reset-password'));

            return response()->json([
                'success' => true,
                'message' => 'Queued email test successful',
                'details' => 'Email queued and will be processed by queue worker',
                'note' => 'Run: php artisan queue:work to process the queue'
            ]);
        } catch (\Exception $e) {
            Log::error('Queued email test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current mail configuration
     */
    public function getMailConfig()
    {
        return response()->json([
            'current_driver' => config('mail.default'),
            'brevo_configured' => !empty(config('services.brevo.api_key')),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'brevo_sender' => config('services.brevo.sender_email'),
            'environment' => app()->environment()
        ]);
    }
}
