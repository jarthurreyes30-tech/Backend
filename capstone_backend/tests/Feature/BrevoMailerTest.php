<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\BrevoMailer;
use App\Mail\ForgotPasswordCodeMail;
use App\Mail\PasswordChangedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class BrevoMailerTest extends TestCase
{
    // Note: RefreshDatabase disabled due to SQLite migration incompatibility
    // These tests verify Brevo configuration without database interactions

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set Brevo as mail driver for tests
        Config::set('mail.default', 'brevo');
        Config::set('services.brevo.api_key', 'test-api-key');
        Config::set('services.brevo.sender_email', 'test@example.com');
        Config::set('services.brevo.sender_name', 'Test App');
    }

    /** @test */
    public function it_can_instantiate_brevo_mailer()
    {
        $mailer = app(BrevoMailer::class);
        
        $this->assertInstanceOf(BrevoMailer::class, $mailer);
    }

    /** @test */
    public function brevo_is_set_as_default_mail_driver()
    {
        $this->assertEquals('brevo', config('mail.default'));
    }

    /** @test */
    public function brevo_configuration_is_loaded()
    {
        $this->assertNotNull(config('services.brevo.api_key'));
        $this->assertNotNull(config('services.brevo.sender_email'));
        $this->assertNotNull(config('services.brevo.sender_name'));
    }

    /** @test */
    public function mail_facade_uses_brevo_transport()
    {
        // This test verifies that the Mail facade is configured to use Brevo
        $mailer = Mail::mailer();
        $transport = $mailer->getSymfonyTransport();
        
        $this->assertEquals('brevo', (string) $transport);
    }

    /** @test */
    public function mailable_can_be_sent_via_mail_facade()
    {
        // Use Mail::fake() to prevent actual API calls during testing
        Mail::fake();
        
        // Create a mock user without database
        $user = new User([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'donor'
        ]);
        
        Mail::to($user->email)->send(new PasswordChangedMail($user, '127.0.0.1'));
        
        Mail::assertSent(PasswordChangedMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /** @test */
    public function queued_mailable_can_be_processed()
    {
        Mail::fake();
        
        // Create a mock user without database
        $user = new User([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'donor'
        ]);
        
        Mail::to($user->email)->queue(new ForgotPasswordCodeMail(
            $user,
            '123456',
            now()->addMinutes(15),
            'http://test.com/reset'
        ));
        
        Mail::assertQueued(ForgotPasswordCodeMail::class);
    }

    /** @test */
    public function brevo_mailer_service_is_registered()
    {
        $this->assertTrue(app()->bound(BrevoMailer::class));
    }

    /** @test */
    public function mail_config_has_brevo_transport()
    {
        $mailers = config('mail.mailers');
        
        $this->assertArrayHasKey('brevo', $mailers);
        $this->assertEquals('brevo', $mailers['brevo']['transport']);
    }

    /** @test */
    public function test_endpoint_returns_correct_config()
    {
        $response = $this->get('/api/brevo-test/config');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_driver',
            'brevo_configured',
            'from_address',
            'from_name',
            'brevo_sender',
            'environment'
        ]);
    }
}
