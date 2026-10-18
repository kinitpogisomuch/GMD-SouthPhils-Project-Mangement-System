<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A checkout the client started through the payment gateway. It is only money in the books once the
 * gateway confirms it server-side (App\Services\OnlinePayments) — then it is linked to exactly one
 * PaymentTransaction (payment_transaction_id is unique).
 */
class OnlinePayment extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED   = 'expired';

    protected $fillable = [
        'payment_id',
        'project_id',
        'client_id',
        'quotation_batch_id',
        'payment_stage',
        'amount',
        'currency',
        'provider',
        'checkout_session_id',
        'checkout_url',
        'provider_payment_id',
        'payment_method',
        'status',
        'reference',
        'payment_transaction_id',
        'paid_at',
        'failure_reason',
        'provider_payload',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'paid_at'          => 'datetime',
        'provider_payload' => 'array',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'Awaiting confirmation',
            self::STATUS_PAID      => 'Paid',
            self::STATUS_FAILED    => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXPIRED   => 'Expired',
            default                => ucfirst($this->status),
        };
    }

    public function badgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID    => 'completed',
            self::STATUS_PENDING => 'pending',
            self::STATUS_FAILED  => 'revision',
            default              => 'archived',
        };
    }

    /** "BPI Online", "QR Ph" … for the gateway's payment method codes */
    public function methodLabel(): string
    {
        return self::METHOD_LABELS[$this->payment_method] ?? ($this->payment_method ? strtoupper($this->payment_method) : '—');
    }

    public const METHOD_LABELS = [
        'qrph'              => 'QR Ph (InstaPay)',
        'dob'               => 'BPI Online',
        'dob_ubp'           => 'UnionBank Online',
        'brankas_bdo'       => 'BDO Online',
        'brankas_landbank'  => 'Landbank Online',
        'brankas_metrobank' => 'Metrobank Online',
        'gcash'             => 'GCash',
        'paymaya'           => 'Maya',
        'card'              => 'Card',
    ];
}
