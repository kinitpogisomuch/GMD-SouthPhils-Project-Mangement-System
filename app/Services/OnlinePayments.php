<?php

namespace App\Services;

use App\Models\FundTransaction;
use App\Models\OnlinePayment;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\QuotationBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The online bank payment workflow: start a gateway checkout for the stage that is due, and turn a
 * confirmed gateway payment into exactly one PaymentTransaction.
 *
 * Confirmation is idempotent. The webhook, the client's return from the checkout page, the admin's
 * "Check status" button and the reconciliation command may all report the same payment, in any order
 * and any number of times; the first one records it, the rest find it already recorded. Guarantees:
 * a row lock on the online payment, its unique payment_transaction_id / provider_payment_id, and the
 * unique (case-insensitive) reference number on payment_transactions.
 */
class OnlinePayments
{
    /** PayMongo's smallest accepted amount for bank / QR payments */
    public const MIN_AMOUNT = 20.00;

    /** A checkout page stays usable this long; after that a new one is opened */
    private const REUSE_HOURS = 20;

    public function __construct(private PayMongoGateway $gateway)
    {
    }

    public function enabled(): bool
    {
        return $this->gateway->isConfigured();
    }

    /**
     * Open (or reuse) a checkout for the stage currently due on this payment.
     *
     * @return OnlinePayment with a checkout_url to send the client to
     * @throws \DomainException  a readable reason when there is nothing to pay or the gateway refused
     */
    public function startCheckout(Payment $payment, array $client): OnlinePayment
    {
        if (!$this->enabled()) {
            throw new \DomainException('Online payment is not available yet. Please use the proof-of-payment upload below.');
        }

        // Under a lock on the payment: two clicks / two tabs can't open two checkouts for the same stage
        $online = DB::transaction(function () use ($payment, $client) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();
            $locked->load('project', 'transactions', 'proofs');

            $due = $locked->onlinePayableStage();
            if (!$due) {
                throw new \DomainException('There is no payment due on this project right now.');
            }
            if ($due['amount'] < self::MIN_AMOUNT) {
                throw new \DomainException('The amount due (₱' . number_format($due['amount'], 2) . ') is below the online payment minimum of ₱' . number_format(self::MIN_AMOUNT, 2) . '. Please pay it by bank transfer and upload your proof.');
            }

            $existing = OnlinePayment::where('payment_id', $locked->id)
                ->where('status', OnlinePayment::STATUS_PENDING)
                ->where('payment_stage', $due['stage'])
                ->where('created_at', '>=', now()->subHours(self::REUSE_HOURS))
                ->latest('id')
                ->first();

            if ($existing && abs((float) $existing->amount - $due['amount']) < 0.005) {
                return $existing;   // same stage, same amount: reuse the checkout already opened
            }
            if ($existing) {
                // the amount due changed since (e.g. GMD recorded a partial payment) — that checkout is stale
                $existing->update(['status' => OnlinePayment::STATUS_EXPIRED, 'failure_reason' => 'Replaced by a new checkout for the updated amount.']);
                if ($existing->checkout_session_id) {
                    $this->gateway->expireCheckoutSession($existing->checkout_session_id);
                }
            }

            return OnlinePayment::create([
                'payment_id'         => $locked->id,
                'project_id'         => $locked->project_id,
                'client_id'          => $client['id'] ?? null,
                'quotation_batch_id' => QuotationBatch::where('project_id', $locked->project_id)->value('id'),
                'payment_stage'      => $due['stage'],
                'amount'             => $due['amount'],
                'currency'           => 'PHP',
                'provider'           => 'paymongo',
                'status'             => OnlinePayment::STATUS_PENDING,
                'reference'          => 'GMDPAY-' . $locked->id . '-' . strtoupper(Str::random(8)),
            ]);
        });

        if ($online->checkout_url) {
            return $online;
        }
        if ($online->created_at && $online->created_at->lt(now()->subMinute()) && !$online->checkout_session_id) {
            // an earlier attempt never reached the gateway — start that checkout again below
        } elseif ($online->wasRecentlyCreated === false && !$online->checkout_url) {
            throw new \DomainException('Your payment page is being prepared — please wait a moment and press Pay Now again.');
        }

        // Talk to the gateway outside the transaction so a slow response never holds the lock
        $project = $payment->project;
        $stage   = PaymentTransaction::stageLabel($online->payment_stage);
        try {
            $session = $this->gateway->createCheckoutSession([
                'amount'      => (float) $online->amount,
                'name'        => "{$stage} — " . ($project->name ?? 'GMD project'),
                'description' => 'GMD South Phils · ' . ($project->name ?? 'Project') . " · {$stage}",
                'reference'   => $online->reference,
                'billing'     => array_filter([
                    'name'  => $client['name'] ?? null,
                    'email' => $client['email'] ?? null,
                ]) ?: null,
                'success_url' => route('client.payments.online.return', $online->id),
                'cancel_url'  => route('client.payments.online.cancel', $online->id),
                'metadata'    => [
                    'online_payment_id' => (string) $online->id,
                    'payment_id'        => (string) $payment->id,
                    'project_id'        => (string) $payment->project_id,
                    'payment_stage'     => $online->payment_stage,
                ],
            ]);
        } catch (\RuntimeException $e) {
            $online->update(['status' => OnlinePayment::STATUS_FAILED, 'failure_reason' => $e->getMessage()]);
            AuditTrail::record('payment', $payment->id, 'online_payment.failed', ['reference' => $online->reference, 'reason' => $e->getMessage()]);
            throw new \DomainException($e->getMessage());
        }

        $online->update([
            'checkout_session_id' => $session['id'] ?? null,
            'checkout_url'        => $session['attributes']['checkout_url'] ?? null,
        ]);

        AuditTrail::record('payment', $payment->id, 'online_payment.started', [
            'reference' => $online->reference, 'stage' => $online->payment_stage, 'amount' => (float) $online->amount,
        ]);

        return $online;
    }

    /**
     * Ask the gateway where a checkout stands and apply it. Safe to call any number of times.
     *
     * @return string the online payment's status afterwards
     */
    public function refresh(OnlinePayment $online, ?array $actor = null): string
    {
        if (!$online->checkout_session_id || !$this->enabled()) {
            return $online->status;
        }

        try {
            $session = $this->gateway->retrieveCheckoutSession($online->checkout_session_id);
        } catch (\RuntimeException $e) {
            report($e);
            return $online->status;
        }

        return $this->applySession($online, $session, $actor ?? AuditTrail::provider());
    }

    /** Apply a checkout_session resource (from the API or a verified webhook) to our record */
    public function applySession(OnlinePayment $online, array $session, array $actor): string
    {
        $attrs = $session['attributes'] ?? [];
        $paid  = collect($attrs['payments'] ?? [])->first(fn ($p) => ($p['attributes']['status'] ?? null) === 'paid');

        if ($paid) {
            return $this->markPaid($online, $paid, $attrs['payment_method_used'] ?? null, $actor);
        }

        $failed = collect($attrs['payments'] ?? [])->first(fn ($p) => ($p['attributes']['status'] ?? null) === 'failed');
        if ($failed && $online->isPending()) {
            return $this->markClosed($online, OnlinePayment::STATUS_FAILED, $failed['attributes']['failed_message'] ?? 'The bank did not complete the payment.', $actor);
        }

        if (($attrs['status'] ?? null) === 'expired' && $online->isPending()) {
            return $this->markClosed($online, OnlinePayment::STATUS_EXPIRED, 'The checkout expired before it was paid.', $actor);
        }

        return $online->fresh()->status;
    }

    /** Client left the checkout without paying */
    public function cancel(OnlinePayment $online): string
    {
        // Make sure it really wasn't paid in the meantime before closing it
        $status = $this->refresh($online, AuditTrail::provider());
        if ($status !== OnlinePayment::STATUS_PENDING) {
            return $status;
        }

        if ($online->checkout_session_id) {
            $this->gateway->expireCheckoutSession($online->checkout_session_id);
        }

        return $this->markClosed($online, OnlinePayment::STATUS_CANCELLED, 'Cancelled by the client on the checkout page.', null);
    }

    private function markClosed(OnlinePayment $online, string $status, string $reason, ?array $actor): string
    {
        $updated = OnlinePayment::whereKey($online->id)->where('status', OnlinePayment::STATUS_PENDING)
            ->update(['status' => $status, 'failure_reason' => mb_substr($reason, 0, 1000), 'updated_at' => now()]);

        if ($updated) {
            AuditTrail::record('payment', $online->payment_id, 'online_payment.' . $status, ['reference' => $online->reference, 'reason' => $reason], $actor);
            if ($status === OnlinePayment::STATUS_FAILED) {
                NotificationService::onlinePaymentFailed($online->fresh());
            }
        }

        return OnlinePayment::whereKey($online->id)->value('status');
    }

    /**
     * Record a payment the gateway confirmed. Everything happens under row locks in one transaction;
     * a second confirmation of the same payment finds it already recorded and changes nothing.
     */
    private function markPaid(OnlinePayment $online, array $paid, ?string $methodUsed, array $actor): string
    {
        $providerPaymentId = (string) ($paid['id'] ?? '');
        $attrs             = $paid['attributes'] ?? [];
        $amount            = round(((int) ($attrs['amount'] ?? 0)) / 100, 2);
        $currency          = strtoupper((string) ($attrs['currency'] ?? 'PHP'));
        $paidAt            = !empty($attrs['paid_at']) ? \Carbon\Carbon::createFromTimestamp((int) $attrs['paid_at']) : now();
        $method            = $methodUsed ?: ($attrs['source']['type'] ?? null);

        try {
            $result = $this->recordPaid($online, $providerPaymentId, $amount, $currency, $paidAt, $method, $paid);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // the same gateway payment was recorded by a concurrent confirmation — nothing more to do
            return OnlinePayment::whereKey($online->id)->value('status');
        }

        [$outcome, $locked, $transaction] = $result;

        if ($outcome === 'recorded') {
            $payment = $locked->payment()->first();
            AuditTrail::record('payment', $payment->id, 'online_payment.paid', [
                'reference' => $locked->reference, 'provider_payment_id' => $providerPaymentId,
                'stage' => $locked->payment_stage, 'amount' => $amount, 'method' => $method,
            ], $actor);
            NotificationService::paymentRecorded($payment, $transaction);
            NotificationService::onlinePaymentConfirmed($locked, $transaction);
            NotificationService::syncPaymentDue($payment);
        } elseif ($outcome === 'mismatch') {
            AuditTrail::record('payment', $locked->payment_id, 'online_payment.failed', ['reference' => $locked->reference, 'reason' => $locked->failure_reason], $actor);
            NotificationService::onlinePaymentNeedsReview($locked);
        }

        return $locked->status;
    }

    /** @return array{0: string, 1: OnlinePayment, 2: ?PaymentTransaction} */
    private function recordPaid(OnlinePayment $online, string $providerPaymentId, float $amount, string $currency, $paidAt, ?string $method, array $paid): array
    {
        return DB::transaction(function () use ($online, $providerPaymentId, $amount, $currency, $paidAt, $method, $paid) {
            $locked = OnlinePayment::whereKey($online->id)->lockForUpdate()->first();
            if ($locked->status === OnlinePayment::STATUS_PAID) {
                return ['already', $locked, null];
            }
            if ($providerPaymentId === '' || $currency !== 'PHP' || abs($amount - (float) $locked->amount) > 0.009) {
                // Never book money that doesn't match what was asked for — leave it to GMD to reconcile
                $locked->update([
                    'status'           => OnlinePayment::STATUS_FAILED,
                    'failure_reason'   => "Gateway reported {$currency} " . number_format($amount, 2) . ' for a ₱' . number_format((float) $locked->amount, 2) . ' checkout — needs manual review.',
                    'provider_payload' => ['payment' => $paid],
                ]);
                return ['mismatch', $locked, null];
            }

            $payment = Payment::whereKey($locked->payment_id)->lockForUpdate()->first();
            $payment->load('project', 'transactions');
            $overpaid = $amount > $payment->currentBalance() + 0.01;

            $transaction = PaymentTransaction::create([
                'payment_id'       => $payment->id,
                'payment_stage'    => $locked->payment_stage,
                'amount_paid'      => $amount,
                'payment_date'     => $paidAt->toDateString(),
                'mode_of_payment'  => 'online_bank',
                'reference_number' => $providerPaymentId,
                'notes'            => 'Paid online via PayMongo (' . (OnlinePayment::METHOD_LABELS[$method] ?? ($method ?: 'bank')) . '). Ref ' . $locked->reference . '.'
                    . ($overpaid ? ' NOTE: this exceeds the balance that was due when it was confirmed — please review.' : ''),
                'recorded_by'      => 'PayMongo (verified automatically)',
            ]);

            $locked->update([
                'status'                 => OnlinePayment::STATUS_PAID,
                'provider_payment_id'    => $providerPaymentId,
                'payment_method'         => $method,
                'payment_transaction_id' => $transaction->id,
                'paid_at'                => $paidAt,
                'failure_reason'         => null,
                'provider_payload'       => ['payment' => $paid],
            ]);

            $payment->load('transactions');
            $payment->recalculate();

            if ($payment->project) {
                FundTransaction::autoReplenish($payment->project, $amount, PaymentTransaction::stageLabel($locked->payment_stage), $paidAt->toDateString());
            }

            return ['recorded', $locked, $transaction];
        });
    }

    /** Pending checkouts older than a few minutes, re-checked with the gateway (scheduled) */
    public function reconcilePending(): array
    {
        $checked = 0;
        $changed = 0;
        OnlinePayment::where('status', OnlinePayment::STATUS_PENDING)
            ->whereNotNull('checkout_session_id')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->each(function (OnlinePayment $online) use (&$checked, &$changed) {
                $checked++;
                if ($this->refresh($online, AuditTrail::system('Reconciliation')) !== OnlinePayment::STATUS_PENDING) {
                    $changed++;
                }
            });

        return ['checked' => $checked, 'changed' => $changed];
    }
}
