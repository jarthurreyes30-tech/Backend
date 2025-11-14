<?php

namespace App\Mail\Transport;

use App\Services\BrevoMailer;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport implements TransportInterface
{
    protected $brevoMailer;

    public function __construct(BrevoMailer $brevoMailer)
    {
        $this->brevoMailer = $brevoMailer;
    }

    public function send(\Symfony\Component\Mime\RawMessage $message, ?\Symfony\Component\Mailer\Envelope $envelope = null): ?SentMessage
    {
        $email = MessageConverter::toEmail($message);
        
        // Extract email details
        $to = $email->getTo()[0];
        $toAddress = $to->getAddress();
        $toName = $to->getName() ?: $toAddress;
        
        $subject = $email->getSubject();
        $htmlBody = $email->getHtmlBody();
        $textBody = $email->getTextBody();
        
        // Extract CC and BCC
        $cc = array_map(fn($addr) => ['email' => $addr->getAddress(), 'name' => $addr->getName()], $email->getCc());
        $bcc = array_map(fn($addr) => ['email' => $addr->getAddress(), 'name' => $addr->getName()], $email->getBcc());
        
        // Handle attachments
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                'content' => base64_encode($attachment->getBody()),
                'name' => $attachment->getName() ?: $attachment->getFilename()
            ];
        }
        
        // Send via Brevo
        $result = $this->brevoMailer->send(
            $toAddress,
            $toName,
            $subject,
            $htmlBody,
            $textBody,
            $attachments,
            $cc,
            $bcc
        );
        
        // Return SentMessage
        return new SentMessage($message, $envelope ?? \Symfony\Component\Mailer\Envelope::create($message));
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
