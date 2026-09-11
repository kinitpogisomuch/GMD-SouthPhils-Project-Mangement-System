<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationRequest extends Model
{
    protected $fillable = [
        'client_id',
        'batch_id',
        'tank_type',
        'capacity',
        'quantity',
        'target_timeline',
        'location',
        'notes',
        'reference_files',
        'quotation_files',
        'status',
        'quotation_sent_at',
        'approved_at',
        'related_project_id',
        'decline_reason',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'quotation_files'   => 'array',
        'reference_files'   => 'array',
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** Still awaiting an outcome — not yet converted into a project or declined */
    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['pending', 'quotation_sent', 'approved']);
    }

    /** Other tank requests submitted in the same batch (same client visit to the form) */
    public function scopeBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /** "Chemical Tank (2x)" — a short label for tables/notifications */
    public function getTankSummaryAttribute(): string
    {
        $label = $this->tank_type ?: 'Tank';
        return $this->quantity > 1 ? "{$label} ({$this->quantity}x)" : $label;
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
