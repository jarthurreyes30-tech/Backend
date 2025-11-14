<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear rate limiters before each test
        RateLimiter::clear('forgot-password:email:test@example.com');
        RateLimiter::clear('forgot-password:ip:127.0.0.1');
    }

    /** @test */
    public function it_sends_reset_code_to_existing_email()
    {
        Mail::fake();
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Check that reset code was created
        $this->assertDatabaseHas('password_reset_codes', [
            'email' => 'test@example.com',
            'used' => false,
        ]);

        // Check email was queued
        Mail::assertQueued(\App\Mail\ForgotPasswordCodeMail::class, function ($mail) use ($user) {
            return $mail->hasTo('test@example.com');
        });
    }

    /** @test */
    public function it_returns_generic_message_for_non_existent_email()
    {
        Mail::fake();
        
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // No reset code should be created
        $this->assertDatabaseMissing('password_reset_codes', [
            'email' => 'nonexistent@example.com',
        ]);

        // No email should be sent
        Mail::assertNothingQueued();
    }

    /** @test */
    public function it_enforces_rate_limiting_per_email()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Make 5 requests (should succeed)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/forgot-password', [
                'email' => 'test@example.com',
            ]);
            $response->assertStatus(200);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /** @test */
    public function it_invalidates_previous_codes_when_new_one_is_requested()
    {
        Mail::fake();
        
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Request first code
        $this->postJson('/api/auth/forgot-password', ['email' => 'test@example.com']);
        
        $firstCode = PasswordResetCode::where('email', 'test@example.com')->first();
        
        // Request second code
        $this->postJson('/api/auth/forgot-password', ['email' => 'test@example.com']);
        
        // First code should be marked as used
        $this->assertDatabaseHas('password_reset_codes', [
            'id' => $firstCode->id,
            'used' => true,
        ]);

        // New code should exist
        $newCode = PasswordResetCode::where('email', 'test@example.com')
            ->where('used', false)
            ->latest()
            ->first();
        
        $this->assertNotNull($newCode);
        $this->assertNotEquals($firstCode->id, $newCode->id);
    }

    /** @test */
    public function it_verifies_correct_code()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        $code = '123456';
        $hashedCode = Hash::make($code);
        
        PasswordResetCode::create([
            'email' => 'test@example.com',
            'token_hash' => $hashedCode,
            'ip' => '127.0.0.1',
            'expires_at' => Carbon::now()->addMinutes(15),
            'attempts' => 0,
            'used' => false,
        ]);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'test@example.com',
            'code' => $code,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /** @test */
    public function it_rejects_invalid_code()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        $code = '123456';
        $hashedCode = Hash::make($code);
        
        PasswordResetCode::create([
            'email' => 'test@example.com',
            'token_hash' => $hashedCode,
            'ip' => '127.0.0.1',
            'expires_at' => Carbon::now()->addMinutes(15),
            'attempts' => 0,
            'used' => false,
        ]);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'test@example.com',
            'code' => '999999', // Wrong code
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);

        // Check attempts incremented
        $resetCode = PasswordResetCode::where('email', 'test@example.com')->first();
        $this->assertEquals(1, $resetCode->attempts);
    }

    /** @test */
    public function it_rejects_expired_code()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        $code = '123456';
        $hashedCode = Hash::make($code);
        
        PasswordResetCode::create([
            'email' => 'test@example.com',
            'token_hash' => $hashedCode,
            'ip' => '127.0.0.1',
            'expires_at' => Carbon::now()->subMinutes(1), // Expired
            'attempts' => 0,
            'used' => false,
        ]);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'test@example.com',
            'code' => $code,
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('expired', true);
    }

    /** @test */
    public function it_locks_after_max_attempts()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        $code = '123456';
        $hashedCode = Hash::make($code);
        
        $resetCode = PasswordResetCode::create([
            'email' => 'test@example.com',
            'token_hash' => $hashedCode,
            'ip' => '127.0.0.1',
            'expires_at' => Carbon::now()->addMinutes(15),
            'attempts' => 4, // Already 4 attempts
            'used' => false,
        ]);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'test@example.com',
            'code' => '999999', // Wrong code
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('max_attempts', true);

        // Code should be marked as used
        $resetCode->refresh();
        $this->assertTrue($resetCode->used);
    }

    /** @test */
    public function it_resets_password_with_valid_code()
    {
        Mail::fake();
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword123'),
        ]);
        
        $code = '123456';
        $hashedCode = Hash::make($code);
        
        PasswordResetCode::create([
            'email' => 'test@example.com',
            'token_hash' => $hashedCode,
            'ip' => '127.0.0.1',
            'expires_at' => Carbon::now()->addMinutes(15),
            'attempts' => 0,
            'used' => false,
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'code' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Password should be updated
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));

        // Code should be marked as used
        $resetCode = PasswordResetCode::where('email', 'test@example.com')->first();
        $this->assertTrue($resetCode->used);
        $this->assertNotNull($resetCode->used_at);

        // Confirmation email should be queued
        Mail::assertQueued(\App\Mail\PasswordChangedMail::class);
    }

    /** @test */
    public function it_validates_password_requirements_on_reset()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        $code = '123456';
        $hashedCode = Hash::make($code);
        
        PasswordResetCode::create([
            'email' => 'test@example.com',
            'token_hash' => $hashedCode,
            'ip' => '127.0.0.1',
            'expires_at' => Carbon::now()->addMinutes(15),
            'attempts' => 0,
            'used' => false,
        ]);

        // Too short password
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'code' => $code,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    /** @test */
    public function it_requires_password_confirmation_match()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        $code = '123456';
        $hashedCode = Hash::make($code);
        
        PasswordResetCode::create([
            'email' => 'test@example.com',
            'token_hash' => $hashedCode,
            'ip' => '127.0.0.1',
            'expires_at' => Carbon::now()->addMinutes(15),
            'attempts' => 0,
            'used' => false,
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'code' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }
}
