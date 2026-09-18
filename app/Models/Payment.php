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

    /** Which stages have at least one recorded transaction */
    public function paidStages(): array
    {
        return $this->transactions
            ->pluck('payment_stage')
            ->unique()
            ->values()
            ->all();
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
        $currentStage = collect($this->stages())->first(fn ($s) => !in_array($s, $paidStages));

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
            if (in_array('down_payment', $paid))            return 'Down Payment Paid';
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
            'Down Payment Paid'      => 'ongoing',
            'Pending Down Payment'   => 'pending',
            default                  => 'pending',
        };
    }
}
