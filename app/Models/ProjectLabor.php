<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectLabor extends Model
{
    protected $table = 'project_labor';

    protected $fillable = [
        'project_id',
        'description',
        'daily_rate',
        'total_cost',
        'notes',
        'status',
    ];

    protected $casts = [
        'daily_rate' => 'float',
        'total_cost' => 'float',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }
}
