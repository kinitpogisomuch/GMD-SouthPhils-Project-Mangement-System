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
        'billing_stage',
        'deposit_instructions',
        'prepared_by_name',
        'prepared_by_role',
        'prepared_by_signature',
        'approved_by_name',
        'approved_by_role',
        'approved_by_signature',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'sent_at'        => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * The most recently saved signatory (name, role, e-signature) for 'prepared_by' or 'approved_by', from any
     * project's statements — whatever was last uploaded or changed carries into every new billing statement.
     */
    public static function latestSignatory(string $who): ?self
    {
        return static::whereNotNull("{$who}_signature")->latest('updated_at')->latest('id')->first();
    }

    /** The default e-signature: the most recently uploaded one, else the latest saved on any statement */
    public static function latestSignature(string $who): ?string
    {
        $context = $who === 'prepared_by' ? ESignature::BILLING_PREPARED_BY : ESignature::BILLING_APPROVED_BY;

        return ESignature::latestUrl($context) ?: static::latestSignatory($who)?->{"{$who}_signature"};
    }
}
