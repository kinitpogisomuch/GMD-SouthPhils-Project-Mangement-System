<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialUsage extends Model
{
    protected $fillable = [
        'project_id',
        'project_material_id',
        'material_name',
        'quantity_used',
        'unit',
        'used_date',
        'used_for',
        'notes',
        'recorded_by',
        'status',
    ];

    protected $casts = [
        'quantity_used' => 'float',
        'used_date'     => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function projectMaterial()
    {
        return $this->belongsTo(ProjectMaterial::class);
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
