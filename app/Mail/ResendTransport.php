<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class ResendTransport extends AbstractTransport
{
    public function __construct(private string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $to = array_map(
            fn($addr) => $addr->getAddress(),
            array_values($email->getTo())
        );

        $payload = [
            'from'    => $email->getFrom()[0]->toString(),
            'to'      => $to,
            'subject' => $email->getSubject(),
        ];

        // HTML body
        if ($html = $email->getHtmlBody()) {
            $payload['html'] = $html;
        } elseif ($text = $email->getTextBody()) {
            $payload['text'] = $text;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.resend.com/emails', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Resend API error: ' . $response->body()
            );
        }
    }

    public function __toString(): string
    {
        return 'resend';
    }
}
