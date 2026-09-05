<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'title',
        'message',
        'related_project_id',
        'related_progress_id',
        'notification_type',
        'priority',
        'action_url',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function setIsReadAttribute($value): void
    {
        $this->attributes['is_read'] = $value ? 'true' : 'false';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'related_project_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereRaw('is_read IS NOT TRUE');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /** notification_type => lucide icon name, kept in sync with NotificationService::TYPE_* */
    private const ICON_MAP = [
        'project_created'                 => 'folder-kanban',
        'progress_requested'              => 'bell',
        'progress_submitted'              => 'send',
        'revision_requested'              => 'alert-triangle',
        'revision_submitted'              => 'refresh-cw',
        'progress_approved'               => 'check-circle',
        'phase_advanced'                  => 'layers',
        'project_completed'               => 'award',
        'pending_review'                  => 'clock',
        'material_added'                  => 'package-plus',
        'material_updated'                => 'package',
        'material_removed'                => 'package-minus',
        'material_requested'              => 'package-x',
        'material_usage_logged'           => 'clipboard-check',
        'labor_updated'                   => 'users',
        'shop_drawing_submitted'          => 'file-text',
        'shop_drawing_approved'           => 'check-circle',
        'shop_drawing_revision_requested' => 'alert-triangle',
        'quotation_sent'                  => 'receipt',
        'fund_released'                   => 'credit-card',
        'fund_replenished'                => 'refresh-cw',
        'client_signup_pending'           => 'user-plus',
        'client_approved'                 => 'check-circle',
        'client_rejected'                 => 'user-x',
        'quotation_request_submitted'     => 'clipboard-list',
        'quotation_request_declined'      => 'x-circle',
        'quotation_request_quotation_sent' => 'file-text',
        'quotation_request_approved'       => 'thumbs-up',
    ];

    public function getIconAttribute(): string
    {
        return self::ICON_MAP[$this->notification_type] ?? 'bell';
    }

    public function getIconClassAttribute(): string
    {
        return match ($this->priority) {
            'warning' => 'notif-icon-warning',
            'success' => 'notif-icon-success',
            default   => 'notif-icon-info',
        };
    }
}
