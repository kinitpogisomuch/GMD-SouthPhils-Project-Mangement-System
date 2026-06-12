<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMaterial extends Model
{
    protected $fillable = [
        'project_id',
        'material_name',
        'category',
        'quantity',
        'unit',
        'price_per_unit',
        'total_cost',
        'factor',
        'notes',
        'status',
    ];

    protected $casts = [
        'quantity'       => 'float',
        'price_per_unit' => 'float',
        'total_cost'     => 'float',
        'factor'         => 'float',
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
