<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\OnlinePayment;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Services\AuditTrail;
use App\Services\OnlinePayments;
use App\Services\PayMongoGateway;
use Illuminate\Http\Request;

/**
 * Online bank payments. "Pay Now" sends the client to the gateway's hosted checkout; the result comes
 * back by signed webhook (and is double-checked with the gateway when the client returns). A browser
 * redirect alone never marks anything as paid.
 */
class OnlinePaymentController extends Controller
{
    public function __construct(private OnlinePayments $payments, private PayMongoGateway $gateway)
    {
    }

    /** Same ownership rule as the rest of the client's payment pages */
    private function clientPayment(int $id): Payment
    {
        $payment = Payment::with('project')->findOrFail($id);
        abort_unless($payment->project && (int) $payment->project->client_id === (int) session('user_id'), 403);

        return $payment;
    }

    private function clientOnline(int $onlineId): OnlinePayment
    {
        $online = OnlinePayment::findOrFail($onlineId);
        $this->clientPayment($online->payment_id);

        return $online;
    }

    /** Client: Pay Now */
    public function pay(Request $request, $id)
    {
        $payment = $this->clientPayment((int) $id);
        $client  = Client::find(session('user_id'));

        try {
            $online = $this->payments->startCheckout($payment, [
                'id'    => $client?->id,
                'name'  => $client?->name,
                'email' => $client?->email,
            ]);
        } catch (\DomainException $e) {
            return redirect()->route('client.payments.show', $payment->id)->with('error', $e->getMessage());
        }

        if (!$online->checkout_url) {
            return redirect()->route('client.payments.show', $payment->id)
                ->with('error', 'The payment page could not be opened. Please try again.');
        }

        // The gateway's own page — the client's bank login happens there, never here
        return redirect()->away($online->checkout_url);
    }

    /** Client is back from the checkout page — ask the gateway what actually happened */
    public function returned($onlineId)
    {
        $online = $this->clientOnline((int) $onlineId);
        $status = $this->payments->refresh($online);

        $message = match ($status) {
            OnlinePayment::STATUS_PAID    => ['success', 'Payment confirmed by your bank and recorded. Thank you! A receipt was emailed to you by PayMongo.'],
            OnlinePayment::STATUS_PENDING => ['success', 'Thank you! We are waiting for the bank\'s confirmation — this usually takes a few moments. The payment will appear in your history once it is confirmed.'],
            OnlinePayment::STATUS_FAILED  => ['error', 'The payment did not go through' . ($online->fresh()->failure_reason ? ': ' . $online->fresh()->failure_reason : '.') . ' You were not charged. You can try again.'],
            default                       => ['error', 'The payment was not completed. You can try again with Pay Now.'],
        };

        return redirect()->route('client.payments.show', $online->payment_id)->with($message[0], $message[1]);
    }

    /** Client pressed "back / cancel" on the checkout page */
    public function cancelled($onlineId)
    {
        $online = $this->clientOnline((int) $onlineId);
        $status = $this->payments->cancel($online);

        return redirect()->route('client.payments.show', $online->payment_id)->with(
            $status === OnlinePayment::STATUS_PAID ? 'success' : 'error',
            $status === OnlinePayment::STATUS_PAID
                ? 'This payment had already gone through and is now recorded. Thank you!'
                : 'Payment cancelled — nothing was charged. You can pay again any time with Pay Now.'
        );
    }

    /** Client: "Check status" on a payment still awaiting confirmation */
    public function check($onlineId)
    {
        $online = $this->clientOnline((int) $onlineId);
        $status = $this->payments->refresh($online);

        return redirect()->route('client.payments.show', $online->payment_id)->with(
            $status === OnlinePayment::STATUS_PAID ? 'success' : 'error',
            $status === OnlinePayment::STATUS_PAID ? 'Payment confirmed and recorded.' : 'Still ' . strtolower($online->fresh()->statusLabel()) . '.'
        );
    }

    /** Admin: verify a pending online payment with the gateway now */
    public function adminVerify($onlineId)
    {
        $online = OnlinePayment::findOrFail((int) $onlineId);
        $status = $this->payments->refresh($online, null);

        return redirect()->route('admin.payments.show', $online->payment_id)->with(
            'success',
            "Checked with PayMongo — {$online->reference} is " . strtolower($online->fresh()->statusLabel()) . '.'
        );
    }

    /**
     * POST /webhooks/paymongo — signed notifications from PayMongo. Every delivery is stored; only one
     * with a valid signature is acted on, and a repeated event id is acknowledged without re-processing.
     */
    public function webhook(Request $request)
    {
        $raw      = $request->getContent();
        $payload  = json_decode($raw, true) ?: [];
        $eventId  = $payload['data']['id'] ?? null;
        $type     = $payload['data']['attributes']['type'] ?? null;
        $livemode = (bool) ($payload['data']['attributes']['livemode'] ?? false);
        $resource = $payload['data']['attributes']['data'] ?? [];
        $valid    = $this->gateway->verifyWebhookSignature($request->header('Paymongo-Signature'), $raw, $livemode);

        $sessionId = ($resource['type'] ?? null) === 'checkout_session' ? ($resource['id'] ?? null) : null;

        if (!$valid) {
            // kept for the audit trail, but never acted on
            PaymentWebhookEvent::create([
                'provider' => 'paymongo', 'event_id' => null, 'event_type' => $type, 'signature_valid' => false,
                'checkout_session_id' => $sessionId, 'payload' => $payload, 'result' => 'rejected: invalid signature',
            ]);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // A repeated delivery of the same event is acknowledged without processing it again. The insert runs in
        // its own (sub)transaction so a race on the unique event id can't poison any surrounding transaction.
        if ($eventId && PaymentWebhookEvent::where('event_id', $eventId)->exists()) {
            return response()->json(['message' => 'Already processed']);
        }
        try {
            $event = \Illuminate\Support\Facades\DB::transaction(fn () => PaymentWebhookEvent::create([
                'provider' => 'paymongo', 'event_id' => $eventId, 'event_type' => $type, 'signature_valid' => true,
                'checkout_session_id' => $sessionId, 'payload' => $payload,
            ]));
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json(['message' => 'Already processed']);
        }

        $result = 'ignored';
        $online = null;

        if ($sessionId) {
            $online = OnlinePayment::where('checkout_session_id', $sessionId)->first();
        }
        if (!$online && !empty($resource['attributes']['metadata']['online_payment_id'])) {
            $online = OnlinePayment::find((int) $resource['attributes']['metadata']['online_payment_id']);
        }

        if ($online && $type === 'checkout_session.payment.paid' && $sessionId) {
            $result = 'status: ' . $this->payments->applySession($online, $resource, AuditTrail::provider());
        } elseif ($online && in_array($type, ['payment.paid', 'payment.failed'], true)) {
            // confirm against the checkout session itself rather than trusting the event alone
            $result = 'status: ' . $this->payments->refresh($online, AuditTrail::provider());
        } elseif (!$online) {
            $result = 'no matching online payment';
        }

        $event->update(['result' => $result, 'processed_at' => now()]);

        return response()->json(['message' => 'ok']);
    }
}
