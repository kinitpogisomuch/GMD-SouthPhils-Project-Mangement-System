<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One quotation as it was sent to the client — a frozen snapshot of the generated document (client
 * details, tank, specifications, itemized costs, totals and terms). Revisions create a new version;
 * earlier ones are kept as "superseded" so both sides can tell the latest quotation from older ones.
 */
class QuotationVersion extends Model
{
    public const STATUS_SENT               = 'sent';
    public const STATUS_SUPERSEDED         = 'superseded';
    public const STATUS_APPROVED           = 'approved';
    public const STATUS_REVISION_REQUESTED = 'revision_requested';
    public const STATUS_REJECTED           = 'rejected';

    protected $fillable = [
        'quotation_batch_id',
        'version',
        'quotation_number',
        'status',
        'snapshot',
        'contract_value',
        'pdf_path',
        'sent_at',
        'sent_by',
        'responded_at',
        'response_reason',
    ];

    protected $casts = [
        'version'        => 'integer',
        'snapshot'       => 'array',
        'contract_value' => 'float',
        'sent_at'        => 'datetime',
        'responded_at'   => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(QuotationBatch::class, 'quotation_batch_id');
    }

    public function isAwaitingDecision(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SENT               => 'Awaiting decision',
            self::STATUS_SUPERSEDED         => 'Superseded',
            self::STATUS_APPROVED           => 'Approved',
            self::STATUS_REVISION_REQUESTED => 'Revision requested',
            self::STATUS_REJECTED           => 'Rejected',
            default                         => ucfirst($this->status),
        };
    }

    /** Status badge class shared by the admin and client portals */
    public function badgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_SENT               => 'ongoing',
            self::STATUS_APPROVED           => 'completed',
            self::STATUS_REVISION_REQUESTED => 'pending',
            self::STATUS_REJECTED           => 'revision',
            default                         => 'archived',
        };
    }

    /** "quotation-GMDSP-2026-061-R1.pdf" */
    public function pdfFilename(): string
    {
        return 'quotation-' . preg_replace('/[^A-Za-z0-9\-]/', '', $this->quotation_number) . '.pdf';
    }
}
