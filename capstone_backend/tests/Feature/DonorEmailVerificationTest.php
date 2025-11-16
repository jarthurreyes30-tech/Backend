<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\PendingRegistration;

class DonorEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function uniqueEmail(string $prefix = 'donor'): string
    {
        return $prefix . '_' . Str::random(8) . '@example.com';
    }

    public function test_fresh_registration_verifies_successfully()
    {
        Mail::fake();

        $email = $this->uniqueEmail('fresh');

        $reg = $this->postJson('/api/auth/register-minimal', [
            'name' => 'Test Donor',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ])->assertStatus(200);

        $this->assertDatabaseMissing('pending_registrations', ['email' => $email]);
        $this->assertDatabaseMissing('users', ['email' => $email]);

        $code = session('pending_donor_registration.verification_code');
        $this->assertNotEmpty($code);

        $verify = $this->postJson('/api/auth/verify-email-code', [
            'email' => $email,
            'code' => (string) $code,
        ]);
        $this->assertTrue(in_array($verify->status(), [200, 201]));

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => 'donor',
        ]);
        $this->assertNotNull(User::where('email', $email)->first()->email_verified_at);
        $this->assertNull(session('pending_donor_registration'));
    }

    public function test_wrong_code_then_correct_code_succeeds()
    {
        Mail::fake();

        $email = $this->uniqueEmail('wrongfirst');

        $this->postJson('/api/auth/register-minimal', [
            'name' => 'Test Donor',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ])->assertStatus(200);

        $code = (string) session('pending_donor_registration.verification_code');
        $bad = '000000';
        if ($bad === $code) { $bad = '111111'; }

        $this->postJson('/api/auth/verify-email-code', [
            'email' => $email,
            'code' => $bad,
        ])->assertStatus(422);

        $this->postJson('/api/auth/verify-email-code', [
            'email' => $email,
            'code' => $code,
        ])->assertStatus(201);
    }

    public function test_back_then_resubmit_overwrites_code_and_verifies()
    {
        Mail::fake();

        $email = $this->uniqueEmail('back');

        $this->postJson('/api/auth/register-minimal', [
            'name' => 'Test Donor',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ])->assertStatus(200);
        $firstCode = (string) session('pending_donor_registration.verification_code');

        $this->postJson('/api/auth/register-minimal', [
            'name' => 'Test Donor',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ])->assertStatus(200);
        $secondCode = (string) session('pending_donor_registration.verification_code');

        $this->assertNotEmpty($secondCode);

        $this->postJson('/api/auth/verify-email-code', [
            'email' => $email,
            'code' => $secondCode,
        ])->assertStatus(201);
    }

    public function test_multiple_register_requests_do_not_create_duplicates()
    {
        Mail::fake();

        $email = $this->uniqueEmail('spam');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/register-minimal', [
                'name' => 'Test Donor',
                'email' => $email,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!'
            ])->assertStatus(200);
        }

        $this->assertDatabaseMissing('pending_registrations', ['email' => $email]);
        $this->assertDatabaseMissing('users', ['email' => $email]);

        $code = (string) session('pending_donor_registration.verification_code');
        $this->postJson('/api/auth/verify-email-code', [
            'email' => $email,
            'code' => $code,
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => $email]);
    }

    public function test_resend_verification_code_throttling_and_limits()
    {
        Mail::fake();

        $email = $this->uniqueEmail('throttle');

        $this->postJson('/api/auth/register-minimal', [
            'name' => 'Test Donor',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ])->assertStatus(200);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/resend-verification-code', ['email' => $email])
                ->assertStatus(200)
                ->assertJsonStructure(['success','message','remaining_resends','expires_in']);
        }

        $this->postJson('/api/auth/resend-verification-code', ['email' => $email])
            ->assertStatus(429);
    }
}
