<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProof extends Model
{
    // pending — waiting for GMD to verify · needs_correction — returned to the client with a reason ·
    // confirmed — recorded as a payment
    const STATUS_PENDING          = 'pending';
    const STATUS_NEEDS_CORRECTION = 'needs_correction';
    const STATUS_CONFIRMED        = 'confirmed';

    protected $fillable = [
        'payment_id',
        'payment_stage',
        'amount',
        'mode_of_payment',
        'status',
        'correction_reason',
        'returned_at',
        'resubmitted_at',
        'revisions',
        'file_url',
        'notes',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'returned_at'    => 'datetime',
        'resubmitted_at' => 'datetime',
        'revisions'      => 'array',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function modeOfPaymentLabel(): string
    {
        return self::modeLabel($this->mode_of_payment);
    }

    public static function modeLabel(?string $mode): string
    {
        return match ($mode) {
            'bank_transfer' => 'Bank Transfer',
            'cheque'        => 'Cheque',
            'cash'          => 'Cash',
            'online_bank'   => 'Online Payment',
            default         => '—',
        };
    }

    public function needsCorrection(): bool
    {
        return $this->status === self::STATUS_NEEDS_CORRECTION;
    }

    /**
     * The client can still correct this submission: it hasn't been recorded as a payment and its
     * stage isn't settled. Covers both a returned submission and one the client spotted a mistake in
     * before GMD reviewed it.
     */
    public function canBeResubmitted(): bool
    {
        if ($this->status === self::STATUS_CONFIRMED) {
            return false;
        }
        $payment = $this->payment;

        return !$payment || !in_array($this->payment_stage, $payment->paidStages(), true);
    }

    /** When the current version was sent: the latest resubmission, or the original upload. */
    public function submittedAt()
    {
        return $this->resubmitted_at ?? $this->created_at;
    }
}
