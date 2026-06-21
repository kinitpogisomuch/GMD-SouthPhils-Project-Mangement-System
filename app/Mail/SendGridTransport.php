<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class SendGridTransport extends AbstractTransport
{
    public function __construct(private string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $from = $email->getFrom()[0];

        $to = array_map(fn($addr) => ['email' => $addr->getAddress(), 'name' => $addr->getName() ?: $addr->getAddress()],
            array_values($email->getTo())
        );

        $payload = [
            'personalizations' => [['to' => $to]],
            'from'             => ['email' => $from->getAddress(), 'name' => $from->getName() ?: $from->getAddress()],
            'subject'          => $email->getSubject(),
            'content'          => [],
        ];

        if ($html = $email->getHtmlBody()) {
            $payload['content'][] = ['type' => 'text/html', 'value' => $html];
        }
        if ($text = $email->getTextBody()) {
            $payload['content'][] = ['type' => 'text/plain', 'value' => $text];
        }
        if (empty($payload['content'])) {
            $payload['content'][] = ['type' => 'text/plain', 'value' => ' '];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.sendgrid.com/v3/mail/send', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('SendGrid API error: ' . $response->body());
        }
    }

    public function __toString(): string
    {
        return 'sendgrid-http';
    }
}
