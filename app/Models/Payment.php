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
        'down_payment',
        'balance',
        'status',
        'payment_terms',
        'date',
    ];

    protected $casts = [
        'contract_amount' => 'decimal:2',
        'down_payment'    => 'decimal:2',
        'balance'         => 'decimal:2',
        'date'            => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}