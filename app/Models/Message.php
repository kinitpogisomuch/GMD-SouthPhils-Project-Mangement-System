<?php

namespace App\Models;

use App\Casts\PostgresBoolean;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'sender_type',
        'recipient_id',
        'recipient_type',
        'subject',
        'body',
        'attachments',
        'is_read',
    ];

    protected $casts = [
        'is_read'     => PostgresBoolean::class,
        'attachments' => 'array',
    ];

    /** All messages exchanged between two actors, oldest first */
    public function scopeBetweenActors($query, string $aType, int $aId, string $bType, int $bId)
    {
        return $query->where(function ($q) use ($aType, $aId, $bType, $bId) {
            $q->where('sender_type', $aType)->where('sender_id', $aId)
              ->where('recipient_type', $bType)->where('recipient_id', $bId);
        })->orWhere(function ($q) use ($aType, $aId, $bType, $bId) {
            $q->where('sender_type', $bType)->where('sender_id', $bId)
              ->where('recipient_type', $aType)->where('recipient_id', $aId);
        });
    }

    public function scopeUnread($query)
    {
        return $query->whereRaw('is_read IS NOT TRUE');
    }
}
