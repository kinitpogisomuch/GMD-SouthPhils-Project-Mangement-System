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
        'notes',
        'mode_of_payment',
        'recorded_by',
    ];

    protected $casts = [
        'amount_paid'  => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
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
