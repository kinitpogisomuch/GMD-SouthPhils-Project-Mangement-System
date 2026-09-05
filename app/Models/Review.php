<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'project_id',
        'client_name',
        'rating',
        'comment',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /** "Kenneth Nadera" -> "K*****h N****a" — keeps first/last letter of each word, masks the rest */
    public function getMaskedClientNameAttribute(): string
    {
        return collect(preg_split('/\s+/', trim((string) $this->client_name)))
            ->filter()
            ->map(function ($word) {
                $len = mb_strlen($word);
                if ($len <= 2) {
                    return $word;
                }
                return mb_substr($word, 0, 1) . str_repeat('*', $len - 2) . mb_substr($word, -1);
            })
            ->implode(' ');
    }
}
