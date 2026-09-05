<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingStatement extends Model
{
    protected $fillable = [
        'payment_id',
        'attention',
        'bill_to',
        'statement_date',
        'reference_no',
        'tin_number',
        'project_title',
        'project_location',
        'po_number',
        'pr_number',
        'subject',
        'deposit_instructions',
        'prepared_by_name',
        'prepared_by_role',
        'approved_by_name',
        'approved_by_role',
    ];

    protected $casts = [
        'statement_date' => 'date',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
