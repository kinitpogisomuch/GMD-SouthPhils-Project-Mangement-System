<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProof extends Model
{
    protected $fillable = [
        'payment_id',
        'payment_stage',
        'amount',
        'mode_of_payment',
        'status',
        'file_url',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function modeOfPaymentLabel(): string
    {
        return match ($this->mode_of_payment) {
            'bank_transfer' => 'Bank Transfer',
            'cheque'        => 'Cheque',
            'cash'          => 'Cash',
            default         => '—',
        };
    }
}
