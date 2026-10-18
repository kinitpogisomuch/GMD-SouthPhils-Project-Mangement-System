<?php

namespace App\Models;

use App\Casts\PostgresBoolean;
use Illuminate\Database\Eloquent\Model;

/** A webhook received from the payment gateway, kept for audit and reconciliation */
class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'signature_valid',
        'checkout_session_id',
        'payload',
        'result',
        'processed_at',
    ];

    protected $casts = [
        'signature_valid' => PostgresBoolean::class,
        'payload'         => 'array',
        'processed_at'    => 'datetime',
    ];
}
