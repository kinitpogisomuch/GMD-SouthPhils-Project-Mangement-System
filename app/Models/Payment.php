<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'project_id',
        'client',
        'client_type',
        'contract_amount',
        'project_budget',
        'markup',
        'down_payment',
        'balance',
        'status',
        'payment_terms',
        'payment_term_type',
        'date',
    ];

    protected $casts = [
        'contract_amount' => 'decimal:2',
        'project_budget'  => 'decimal:2',
        'markup'          => 'decimal:2',
        'down_payment'    => 'decimal:2',
        'balance'         => 'decimal:2',
        'date'            => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class)->orderBy('payment_date');
    }

    public function billingStatements()
    {
        return $this->hasMany(BillingStatement::class)->latest();
    }

    public function proofs()
    {
        return $this->hasMany(PaymentProof::class)->latest();
    }

    // -------------------------------------------------------------------------
    // Computed helpers
    // -------------------------------------------------------------------------

    public function totalPaid(): float
    {
        return (float) $this->transactions->sum('amount_paid');
    }

    public function currentBalance(): float
    {
        return max(0, (float) $this->contract_amount - $this->totalPaid());
    }

    /** Amounts per stage based on payment_term_type and contract_amount */
    public function stageAmounts(): array
    {
        $c = (float) $this->contract_amount;
        if ($this->payment_term_type === 'big_project') {
            return [
                'down_payment'     => round($c * 0.50, 2),
                'progress_payment' => round($c * 0.30, 2),
                'final_payment'    => round($c * 0.20, 2),
            ];
        }
        // small_project
        return [
            'down_payment'  => round($c * 0.50, 2),
            'final_payment' => round($c * 0.50, 2),
        ];
    }

    /** Stages available for this payment_term_type */
    public function stages(): array
    {
        if ($this->payment_term_type === 'big_project') {
            return ['down_payment', 'progress_payment', 'final_payment'];
        }
        return ['down_payment', 'final_payment'];
    }

    /** Stages the client already has an admin-confirmed proof for — no need to submit again,
     *  even if that confirmed payment was only a partial and the stage isn't fully settled yet. */
    public function confirmedProofStages(): array
    {
        return $this->proofs->where('status', 'confirmed')->pluck('payment_stage')->unique()->values()->all();
    }

    /**
     * The progress payment can be paid in as many instalments as the client likes, but only
     * until the project reaches its final-payment phase (delivery). From then on it is closed
     * and whatever is still unpaid on it is collected with the final payment instead.
     */
    public function progressPaymentClosed(): bool
    {
        $project = $this->project;

        return $project && ($project->current_phase === 'delivery' || $project->status === 'completed');
    }

    /**
     * Where each stage stands for the client's proof-of-payment form:
     *   paid    — settled, nothing more to submit
     *   carried — progress payment closed unpaid; its balance moved to the final payment
     *   open    — the one stage the client can submit proof for right now
     *   locked  — waits for the stage before it
     *
     * The admin's Record Payment form follows the same states; pass false there so a down
     * payment counts as paid only once its money is actually recorded.
     */
    public function proofStageStates(bool $forClient = true): array
    {
        $paid       = $this->paidStages();
        $downProven = $forClient && in_array('down_payment', $this->confirmedProofStages());
        $closed     = $this->progressPaymentClosed();
        $states     = [];
        $opened     = false;

        foreach ($this->stages() as $stage) {
            if (in_array($stage, $paid) || ($stage === 'down_payment' && $downProven)) {
                $states[$stage] = 'paid';
            } elseif ($stage === 'progress_payment' && $closed) {
                $states[$stage] = 'carried';
            } elseif (!$opened) {
                $states[$stage] = 'open';
                $opened = true;
            } else {
                $states[$stage] = 'locked';
            }
        }

        return $states;
    }

    /** Stages still open for a new proof-of-payment submission on the client Payments page — stages are paid in order, so at most one. */
    public function stagesOpenForProof(): array
    {
        return array_keys($this->proofStageStates(), 'open');
    }

    /** Amount already paid toward a given stage (partial payments accumulate) */
    public function stagePaidAmount(string $stage): float
    {
        return (float) $this->transactions
            ->where('payment_stage', $stage)
            ->sum('amount_paid');
    }

    /** Remaining balance owed on a given stage */
    public function stageRemaining(string $stage): float
    {
        $expected = $this->stageAmounts()[$stage] ?? 0;

        return max(0, round($expected - $this->stagePaidAmount($stage), 2));
    }

    /**
     * What the client owes when submitting proof for each stage, judged against the
     * cumulative total received (same basis as paidStages()). The down payment is a
     * fixed amount; whatever is left unpaid on the progress payment carries over into
     * the final payment, so the final amount due is always the rest of the contract.
     */
    public function stageAmountsDue(): array
    {
        $amounts = $this->stageAmounts();
        $paid    = round($this->totalPaid(), 2);
        $down    = (float) $amounts['down_payment'];

        $due = ['down_payment' => max(0, round($down - $paid, 2))];

        if (isset($amounts['progress_payment'])) {
            $progress = (float) $amounts['progress_payment'];
            $due['progress_payment'] = max(0, round(min($progress, $down + $progress - $paid), 2));
        }

        $due['final_payment'] = max(0, round((float) $this->contract_amount - max($paid, $down), 2));

        return $due;
    }

    /**
     * Which stages are "settled" — i.e. unlock the phase gate tied to them.
     *
     * Real clients rarely pay in the exact 50/30/20 split, so this is judged
     * against the CUMULATIVE total received so far, not the amount recorded
     * under that specific stage's label. E.g. for a big project, the Down
     * Payment gate unlocks once total payments reach 50% of the contract,
     * Progress Payment at 80%, Final at 100% — however that money was
     * actually tagged across transactions. Stages stay sequential (a later
     * stage can't be settled unless every earlier cumulative threshold is
     * also met), matching the phases' own sequential progression.
     */
    public function paidStages(): array
    {
        $amounts    = $this->stageAmounts();
        $totalPaid  = round($this->totalPaid(), 2);
        $cumulative = 0.0;
        $settled    = [];

        foreach ($this->stages() as $stage) {
            $cumulative += (float) ($amounts[$stage] ?? 0);

            if ($cumulative <= 0 || $totalPaid + 0.01 < round($cumulative, 2)) {
                break;
            }

            $settled[] = $stage;
        }

        return $settled;
    }

    /**
     * The one stage currently awaiting the client's payment — the earliest unpaid
     * stage in sequence — but only once GMD has actually billed it (a sent statement
     * targeting that stage, or a sent "Full Statement" covering everything). Null
     * when nothing is currently billed-and-unpaid (either fully paid, or GMD hasn't
     * sent the next billing statement yet). This is what unlocks the client's
     * "Upload Proof of Payment" box for a stage on the Payment Detail page.
     */
    public function currentBilledStage(): ?string
    {
        $paidStages   = $this->paidStages();
        // A progress payment that closed unpaid is no longer billed on its own — it rides on the final payment.
        $skipProgress = $this->progressPaymentClosed();
        $currentStage = collect($this->stages())->first(
            fn ($s) => !in_array($s, $paidStages) && !($s === 'progress_payment' && $skipProgress)
        );

        if (!$currentStage) {
            return null;
        }

        $isBilled = $this->billingStatements()
            ->whereNotNull('sent_at')
            ->get()
            ->contains(fn ($s) => is_null($s->billing_stage) || $s->billing_stage === $currentStage);

        return $isBilled ? $currentStage : null;
    }

    /**
     * Whether the client still has something to do — a stage is billed and unpaid
     * (see currentBilledStage()) AND they haven't already submitted proof for it.
     * Once they upload, the ball is in GMD's court, so this drops false even though
     * the stage itself stays "Unpaid" until GMD records the payment. Drives the
     * client header's Payments nav badge.
     */
    public function needsClientAction(): bool
    {
        $stage = $this->currentBilledStage();

        return $stage !== null && !$this->proofs()->where('payment_stage', $stage)->exists();
    }

    /** Compute status from recorded transactions */
    public function computeStatus(): string
    {
        $paid = $this->paidStages();

        if ($this->payment_term_type === 'big_project') {
            $allPaid = in_array('down_payment', $paid)
                && in_array('progress_payment', $paid)
                && in_array('final_payment', $paid);
            if ($allPaid)                                   return 'Fully Paid';
            if (in_array('progress_payment', $paid))        return 'Progress Payment Paid';
            if (in_array('down_payment', $paid)) {
                // Something has been paid beyond the down payment, but not the whole progress payment yet
                $paidBeyondDown = $this->totalPaid() > (float) $this->stageAmounts()['down_payment'] + 0.01;
                return $paidBeyondDown ? 'Progress Payment Partially Paid' : 'Down Payment Paid';
            }
            return 'Pending Down Payment';
        }

        // small_project
        $allPaid = in_array('down_payment', $paid) && in_array('final_payment', $paid);
        if ($allPaid)                            return 'Fully Paid';
        if (in_array('down_payment', $paid))     return 'Down Payment Paid';
        return 'Pending Down Payment';
    }

    /** Recalculate and persist balance + status */
    public function recalculate(): void
    {
        $this->balance = $this->currentBalance();
        $this->status  = $this->computeStatus();
        $this->save();
    }

    /** CSS class for a given status string */
    public static function statusBadgeClass(string $status): string
    {
        return match($status) {
            'Fully Paid'             => 'completed',
            'Progress Payment Paid'  => 'ongoing',
            'Progress Payment Partially Paid' => 'ongoing',
            'Down Payment Paid'      => 'ongoing',
            'Pending Down Payment'   => 'pending',
            default                  => 'pending',
        };
    }
}
