<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One entry in the audit trail — written through App\Services\AuditTrail, never edited afterwards. */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'action',
        'actor_type',
        'actor_id',
        'actor_name',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'details'    => 'array',
        'created_at' => 'datetime',
    ];

    public function scopeFor($query, string $type, $id)
    {
        return $query->where('auditable_type', $type)->where('auditable_id', (string) $id);
    }

    /** "Quotation sent (v2)" — a readable label for the activity list */
    public function getLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? ucfirst(str_replace(['.', '_'], ' ', $this->action));
    }

    public const ACTION_LABELS = [
        'quotation.requested'          => 'Quotation requested',
        'quotation.in_preparation'     => 'Quotation being prepared',
        'quotation.saved'              => 'Quotation builder saved',
        'quotation.sent'               => 'Quotation sent to client',
        'quotation.approved'           => 'Client approved the quotation',
        'quotation.revision_requested' => 'Client requested a revision',
        'quotation.rejected'           => 'Client rejected the quotation',
        'quotation.declined'           => 'Request declined by GMD',
        'quotation.converted'          => 'Converted into a project',
        'online_payment.started'       => 'Online payment started',
        'online_payment.paid'          => 'Online payment confirmed',
        'online_payment.failed'        => 'Online payment failed',
        'online_payment.cancelled'     => 'Online payment cancelled',
        'online_payment.expired'       => 'Online payment expired',
        'project.delivered_unpaid'     => 'Delivery authorized with an unpaid balance',
    ];
}
