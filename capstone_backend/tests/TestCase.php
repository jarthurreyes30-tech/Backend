<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Services\BrevoMailer;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        // Bind a BrevoMailer stub that satisfies type-hint and avoids real API calls
        $this->app->bind(BrevoMailer::class, function () {
            return new class extends BrevoMailer {
                public function __construct() {}
                public function send(
                    string $to,
                    string $toName,
                    string $subject,
                    string $htmlContent,
                    ?string $textContent = null,
                    array $attachments = [],
                    array $cc = [],
                    array $bcc = []
                ): array {
                    return ['success' => true, 'message_id' => 'test'];
                }
                public function sendMailable($mailable, string $to, ?string $toName = null): array
                {
                    return ['success' => true, 'message_id' => 'test'];
                }
                public function testConnection(): array
                {
                    return [
                        'success' => true,
                        'message' => 'Brevo API connection successful',
                        'account' => [
                            'email' => 'test@example.com',
                            'company_name' => 'Test Co'
                        ]
                    ];
                }
            };
        });
    }
}
