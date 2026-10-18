<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Thin client for the PayMongo API (https://developers.paymongo.com) — the Philippine payment gateway
 * used for online bank payments (BPI / UnionBank online banking, BDO / Landbank / Metrobank via Brankas,
 * and QR Ph / InstaPay from any bank app).
 *
 * The client is sent to PayMongo's hosted checkout page; banking credentials are entered there, never in
 * this system. Whether a checkout was paid is only ever learned from PayMongo itself: a webhook whose
 * signature checks out, or the checkout session fetched with the secret key.
 */
class PayMongoGateway
{
    public function isConfigured(): bool
    {
        return !empty(config('services.paymongo.secret_key'));
    }

    public function isTestMode(): bool
    {
        return str_starts_with((string) config('services.paymongo.secret_key'), 'sk_test_');
    }

    /** Only reads are retried — retrying a create could open a second checkout if the first one did arrive */
    private function http(bool $retry = false)
    {
        $client = Http::withBasicAuth((string) config('services.paymongo.secret_key'), '')
            ->baseUrl(rtrim(config('services.paymongo.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(20);

        return $retry
            ? $client->retry(2, 300, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException, false)
            : $client;
    }

    /**
     * Create a hosted checkout session. Amounts are in pesos here and sent to PayMongo in centavos.
     *
     * @return array the checkout_session resource (id, attributes.checkout_url, …)
     * @throws \RuntimeException with a readable message when PayMongo refuses or can't be reached
     */
    public function createCheckoutSession(array $params): array
    {
        $response = $this->http()->post('/checkout_sessions', [
            'data' => ['attributes' => array_filter([
                'billing'              => $params['billing'] ?? null,
                'line_items'           => [[
                    'currency'    => 'PHP',
                    'amount'      => (int) round($params['amount'] * 100),
                    'name'        => mb_substr($params['name'], 0, 255),
                    'description' => mb_substr($params['description'] ?? '', 0, 255) ?: null,
                    'quantity'    => 1,
                ]],
                'payment_method_types' => array_values(config('services.paymongo.payment_methods')),
                'description'          => mb_substr($params['description'] ?? $params['name'], 0, 255),
                'reference_number'     => $params['reference'],
                'success_url'          => $params['success_url'],
                'cancel_url'           => $params['cancel_url'],
                'send_email_receipt'   => true,
                'show_description'     => true,
                'show_line_items'      => true,
                'metadata'             => $params['metadata'] ?? null,
            ], fn ($v) => $v !== null)],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException($this->errorMessage($response));
        }

        return $response->json('data');
    }

    /** Fetch a checkout session (with its payments) using the secret key */
    public function retrieveCheckoutSession(string $id): array
    {
        $response = $this->http(true)->get('/checkout_sessions/' . rawurlencode($id));
        if (!$response->successful()) {
            throw new \RuntimeException($this->errorMessage($response));
        }

        return $response->json('data');
    }

    /** Close a checkout so it can no longer be paid (used when the client cancels) */
    public function expireCheckoutSession(string $id): void
    {
        try {
            $this->http()->post('/checkout_sessions/' . rawurlencode($id) . '/expire');
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Verify the Paymongo-Signature header: "t=<timestamp>,te=<test sig>,li=<live sig>", where each
     * signature is HMAC-SHA256 of "<timestamp>.<raw body>" with the webhook's secret. The signature for
     * the mode the event was sent in must match, and an old timestamp is rejected as a replay.
     */
    public function verifyWebhookSignature(?string $header, string $rawBody, bool $livemode): bool
    {
        $secret = (string) config('services.paymongo.webhook_secret');
        if ($secret === '' || !$header) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $piece) {
            [$k, $v] = array_pad(explode('=', trim($piece), 2), 2, '');
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? '';
        $given     = $livemode ? ($parts['li'] ?? '') : ($parts['te'] ?? '');
        if ($timestamp === '' || $given === '' || !ctype_digit($timestamp)) {
            return false;
        }

        $tolerance = (int) config('services.paymongo.webhook_tolerance', 600);
        if ($tolerance > 0 && abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);

        return hash_equals($expected, $given);
    }

    private function errorMessage($response): string
    {
        $detail = $response->json('errors.0.detail');

        return $detail
            ? 'The payment provider refused the request: ' . $detail
            : 'The payment provider could not be reached (HTTP ' . $response->status() . '). Please try again.';
    }
}
