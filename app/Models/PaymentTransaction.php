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

    /** Paid online through the gateway — these get a system-generated Acknowledgement Receipt */
    public function isOnline(): bool
    {
        return $this->mode_of_payment === 'online_bank';
    }

    /** Acknowledgement Receipt number of a system-generated receipt — digits only, like the printed booklet (e.g. 00012) */
    public function receiptNumber(): string
    {
        return str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
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
            default             => ucfirst(str_replace('_', ' ', $stage)),
        };
    }
}
