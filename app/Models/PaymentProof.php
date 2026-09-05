<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProof extends Model
{
    protected $fillable = [
        'payment_id',
        'payment_stage',
        'file_url',
        'notes',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
