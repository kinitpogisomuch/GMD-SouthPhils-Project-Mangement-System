<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationRequest extends Model
{
    /*
     | Workflow: pending → in_preparation → quotation_sent → approved → (client sends PO) → converted
     |   quotation_sent → pending (+ decline_reason)  client asked for a revision
     |   quotation_sent → rejected                    client turned the quotation down for good
     |   pending / in_preparation / quotation_sent → declined   GMD declined the request
     */
    public const STATUS_PENDING        = 'pending';
    public const STATUS_IN_PREPARATION = 'in_preparation';
    public const STATUS_SENT           = 'quotation_sent';
    public const STATUS_APPROVED       = 'approved';
    public const STATUS_REJECTED       = 'rejected';
    public const STATUS_CONVERTED      = 'converted';
    public const STATUS_DECLINED       = 'declined';

    /** Statuses in which GMD can still work on (and send) the quotation */
    public const EDITABLE_STATUSES = [self::STATUS_PENDING, self::STATUS_IN_PREPARATION];

    /** label + badge class for the admin portal (status-badge classes from admin.css) */
    public const STATUS_META = [
        'pending'            => ['label' => 'Pending',            'class' => 'pending'],
        'revision_requested' => ['label' => 'Revision Requested', 'class' => 'revision'],
        'in_preparation'     => ['label' => 'In Preparation',     'class' => 'planning'],
        'quotation_sent'     => ['label' => 'Sent to Client',     'class' => 'ongoing'],
        'approved'           => ['label' => 'Approved',           'class' => 'approved'],
        'awaiting_po'        => ['label' => 'Awaiting PO',        'class' => 'ongoing'],
        'po_received'        => ['label' => 'PO Received',        'class' => 'approved'],
        'rejected'           => ['label' => 'Rejected by Client', 'class' => 'revision'],
        'converted'          => ['label' => 'Converted to Project', 'class' => 'active'],
        'declined'           => ['label' => 'Declined',           'class' => 'archived'],
    ];

    protected $fillable = [
        'client_id',
        'batch_id',
        'tank_type',
        'capacity',
        'quantity',
        'target_timeline',
        'fulfillment',
        'location',
        'notes',
        'reference_files',
        'quotation_files',
        'status',
        'quotation_sent_at',
        'approved_at',
        'related_project_id',
        'decline_reason',
        'specifications',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'quotation_files'   => 'array',
        'reference_files'   => 'array',
        'specifications'    => 'array',
        'quotation_sent_at' => 'datetime',
        'approved_at'       => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'related_project_id');
    }

    public function quotationBatch()
    {
        return $this->belongsTo(QuotationBatch::class, 'batch_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** Still awaiting an outcome — not yet converted into a project, declined or rejected */
    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['pending', 'in_preparation', 'quotation_sent', 'approved']);
    }

    /** Other tank requests submitted in the same batch (same client visit to the form) */
    public function scopeBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * pending + a client's revision reason reads "Revision Requested" everywhere; an approved quotation reads
     * "Awaiting PO" until the client sends their Purchase Order, then "PO Received"
     */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === self::STATUS_PENDING && !empty($this->decline_reason)) {
            return 'revision_requested';
        }
        if ($this->status === self::STATUS_APPROVED) {
            return $this->hasPurchaseOrder() ? 'po_received' : 'awaiting_po';
        }

        return $this->status;
    }

    /** The client sent the Purchase Order for this request's quotation */
    public function hasPurchaseOrder(): bool
    {
        return (bool) $this->quotationBatch?->hasPurchaseOrder();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_META[$this->display_status]['label'] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusClassAttribute(): string
    {
        return self::STATUS_META[$this->display_status]['class'] ?? 'pending';
    }

    /** "Chemical Tank (2x)" — a short label for tables/notifications */
    public function getTankSummaryAttribute(): string
    {
        $label = $this->tank_type ?: 'Tank';
        return $this->quantity > 1 ? "{$label} ({$this->quantity}x)" : $label;
    }

    /**
     * The client's type-specific answers as label → value pairs, in the order the form asks them,
     * e.g. ['Product Stored' => 'Diesel', 'Wall Construction' => 'Double wall'].
     */
    public function specificationLines(): array
    {
        return \App\Support\TankCatalog::describeAnswers($this->tank_type, $this->specifications ?? []);
    }

    /** How the tank reaches the client: picked up at the shop, or assembled on site by GMD (older requests: delivery) */
    public const FULFILLMENT_PICKUP   = 'pickup';
    public const FULFILLMENT_ONSITE   = 'onsite';
    public const FULFILLMENT_DELIVERY = 'delivery';

    public function isOnsite(): bool
    {
        return $this->fulfillment === self::FULFILLMENT_ONSITE;
    }

    /** Label, icon and address label for the request's fulfillment, for the request cards and lists */
    public function fulfillmentInfo(): array
    {
        return match ($this->fulfillment ?? self::FULFILLMENT_DELIVERY) {
            self::FULFILLMENT_PICKUP => ['key' => 'pickup',   'label' => 'For pick-up',      'icon' => 'package-check', 'address_label' => null],
            self::FULFILLMENT_ONSITE => ['key' => 'onsite',   'label' => 'Assembled on site', 'icon' => 'hard-hat',      'address_label' => 'Site Location'],
            default                  => ['key' => 'delivery', 'label' => 'For delivery',     'icon' => 'truck',         'address_label' => 'Delivery Address'],
        };
    }

    /**
     * "Nov 15, 2026" for a proper target date (current form), or the raw value
     * unchanged for older free-text entries ("2 months", "june") submitted before
     * the field became a date picker — target_timeline itself stays a plain string
     * column so old rows never fail to load.
     */
    public function getTargetTimelineDisplayAttribute(): ?string
    {
        if (empty($this->target_timeline)) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->target_timeline)) {
            try {
                return \Carbon\Carbon::parse($this->target_timeline)->format('M j, Y');
            } catch (\Exception $e) {
                // fall through to raw value below
            }
        }

        return $this->target_timeline;
    }
}
