<?php

namespace App\Services;

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class BrevoMailer
{
    protected $apiInstance;
    protected $config;

    public function __construct()
    {
        $this->config = Configuration::getDefaultConfiguration()->setApiKey(
            'api-key',
            config('services.brevo.api_key')
        );
        
        $this->apiInstance = new TransactionalEmailsApi(
            new Client(),
            $this->config
        );
    }

    /**
     * Send email via Brevo API
     *
     * @param string $to Email recipient
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $htmlContent HTML content
     * @param string|null $textContent Plain text content
     * @param array $attachments Array of attachments
     * @param array $cc CC recipients
     * @param array $bcc BCC recipients
     * @return array Response from Brevo
     */
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
        try {
            $sendSmtpEmail = new SendSmtpEmail();
            
            // Set sender
            $sendSmtpEmail['sender'] = [
                'name' => config('services.brevo.sender_name', config('mail.from.name')),
                'email' => config('services.brevo.sender_email', config('mail.from.address'))
            ];
            
            // Set recipient(s)
            $sendSmtpEmail['to'] = [['email' => $to, 'name' => $toName]];
            
            // Set CC if provided
            if (!empty($cc)) {
                $sendSmtpEmail['cc'] = array_map(function($email) {
                    return is_array($email) ? $email : ['email' => $email];
                }, $cc);
            }
            
            // Set BCC if provided
            if (!empty($bcc)) {
                $sendSmtpEmail['bcc'] = array_map(function($email) {
                    return is_array($email) ? $email : ['email' => $email];
                }, $bcc);
            }
            
            // Set subject and content
            $sendSmtpEmail['subject'] = $subject;
            $sendSmtpEmail['htmlContent'] = $htmlContent;
            
            if ($textContent) {
                $sendSmtpEmail['textContent'] = $textContent;
            }
            
            // Handle attachments
            if (!empty($attachments)) {
                $sendSmtpEmail['attachment'] = $this->formatAttachments($attachments);
            }
            
            // Send email
            $result = $this->apiInstance->sendTransacEmail($sendSmtpEmail);
            
            Log::info('Brevo email sent successfully', [
                'message_id' => $result['messageId'] ?? null,
                'to' => $to,
                'subject' => $subject
            ]);
            
            return [
                'success' => true,
                'message_id' => $result['messageId'] ?? null,
                'response' => $result
            ];
            
        } catch (Exception $e) {
            Log::error('Brevo email sending failed', [
                'error' => $e->getMessage(),
                'to' => $to,
                'subject' => $subject,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new Exception("Failed to send email via Brevo: " . $e->getMessage());
        }
    }

    /**
     * Send email from Laravel Mailable
     *
     * @param object $mailable Laravel Mailable instance
     * @param string $to Recipient email
     * @param string|null $toName Recipient name
     * @return array
     */
    public function sendMailable($mailable, string $to, ?string $toName = null): array
    {
        // Render the mailable to get HTML content
        $html = $mailable->render();
        
        // Get subject from mailable
        $subject = $this->getMailableSubject($mailable);
        
        // Get plain text if available
        $textContent = null;
        if (method_exists($mailable, 'textView') && $mailable->textView) {
            $textContent = view($mailable->textView, $mailable->viewData)->render();
        }
        
        // Get attachments if any
        $attachments = $this->getMailableAttachments($mailable);
        
        return $this->send(
            $to,
            $toName ?? $to,
            $subject,
            $html,
            $textContent,
            $attachments
        );
    }

    /**
     * Format attachments for Brevo API
     *
     * @param array $attachments
     * @return array
     */
    protected function formatAttachments(array $attachments): array
    {
        $formatted = [];
        
        foreach ($attachments as $attachment) {
            if (is_string($attachment)) {
                // File path provided
                if (file_exists($attachment)) {
                    $formatted[] = [
                        'content' => base64_encode(file_get_contents($attachment)),
                        'name' => basename($attachment)
                    ];
                }
            } elseif (is_array($attachment)) {
                // Already formatted or has specific structure
                $formatted[] = $attachment;
            }
        }
        
        return $formatted;
    }

    /**
     * Get subject from mailable
     *
     * @param object $mailable
     * @return string
     */
    protected function getMailableSubject($mailable): string
    {
        if (property_exists($mailable, 'subject') && $mailable->subject) {
            return $mailable->subject;
        }
        
        if (method_exists($mailable, 'envelope')) {
            $envelope = $mailable->envelope();
            if ($envelope && isset($envelope->subject)) {
                return $envelope->subject;
            }
        }
        
        return 'No Subject';
    }

    /**
     * Get attachments from mailable
     *
     * @param object $mailable
     * @return array
     */
    protected function getMailableAttachments($mailable): array
    {
        $attachments = [];
        
        if (property_exists($mailable, 'attachments') && is_array($mailable->attachments)) {
            $attachments = $mailable->attachments;
        }
        
        if (method_exists($mailable, 'attachments')) {
            $mailableAttachments = $mailable->attachments();
            if (is_array($mailableAttachments)) {
                $attachments = array_merge($attachments, $mailableAttachments);
            }
        }
        
        return $attachments;
    }

    /**
     * Test Brevo API connection
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $account = $this->apiInstance->getAccount();
            
            return [
                'success' => true,
                'message' => 'Brevo API connection successful',
                'account' => [
                    'email' => $account['email'] ?? null,
                    'company_name' => $account['companyName'] ?? null,
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Brevo API connection failed: ' . $e->getMessage()
            ];
        }
    }
}
