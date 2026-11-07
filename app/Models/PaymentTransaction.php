<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'payment_id',
        'payment_stage',
        'amount_paid',
        'payment_date',
        'reference_number',
        'receipt_url',
        'receipt_urls',
        'notes',
        'mode_of_payment',
        'recorded_by',
    ];

    protected $casts = [
        'amount_paid'  => 'decimal:2',
        'payment_date' => 'date',
        'receipt_urls' => 'array',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function onlinePayment()
    {
        return $this->hasOne(OnlinePayment::class);
    }

    /** Paid online through the gateway */
    public function isOnline(): bool
    {
        return $this->mode_of_payment === 'online_bank';
    }

    /**
     * Gets the system-generated Acknowledgement Receipt: online payments, and cash / cheque payments GMD records
     * (their OR number is on it). Older payments keep the receipts uploaded for them; imported ones have no OR.
     */
    public function hasGeneratedReceipt(): bool
    {
        if ($this->isOnline()) {
            return true;
        }

        return empty($this->receipt_urls) && empty($this->receipt_url) && filled($this->reference_number);
    }

    /** Number printed on the generated receipt — digits for online payments, like the booklet (e.g. 00012); otherwise the OR number */
    public function receiptNumber(): string
    {
        return $this->isOnline() ? str_pad((string) $this->id, 5, '0', STR_PAD_LEFT) : (string) $this->reference_number;
    }

    /**
     * A fresh OR / serial number for a payment GMD records, e.g. OR-2026-K7QX4M — random (no I/O/0/1, so it
     * reads cleanly off a printout) and not used on any other payment yet. The column is unique as well.
     */
    public static function generateOrNumber(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $number = 'OR-' . now()->format('Y') . '-' . $code;
        } while (static::whereRaw('LOWER(reference_number) = ?', [strtolower($number)])->exists());

        return $number;
    }

    public static function stageLabel(string $stage): string
    {
        return match($stage) {
            'down_payment'      => 'Down Payment',
            'progress_payment'  => 'Progress Payment',
            'final_payment'     => 'Final Payment',
            'full_payment'      => 'Full Payment',
            default            => ucfirst(str_replace('_', ' ', $stage)),
        };
    }
}
