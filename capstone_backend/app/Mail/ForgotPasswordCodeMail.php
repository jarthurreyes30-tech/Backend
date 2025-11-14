<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $code;
    public $expiresAt;
    public $resetUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $code, Carbon $expiresAt)
    {
        $this->user = $user;
        $this->code = $code;
        $this->expiresAt = $expiresAt;
        $this->resetUrl = config('app.frontend_url') . '/auth/reset-password';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset Verification Code - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.forgot-password-code',
            text: 'emails.auth.forgot-password-code-plain',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
