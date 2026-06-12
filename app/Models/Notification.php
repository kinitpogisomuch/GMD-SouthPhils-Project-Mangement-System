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
}
