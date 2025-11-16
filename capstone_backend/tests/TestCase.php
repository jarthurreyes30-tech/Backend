<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        // Stub BrevoMailer to prevent real email sending during tests
        $this->app->instance(\App\Services\BrevoMailer::class, new class {
            public function send($toEmail, $toName, $subject, $html, $text)
            {
                return true;
            }
        });
    }
}
