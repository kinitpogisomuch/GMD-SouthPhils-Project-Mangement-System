<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    protected $fillable = [
        'project_id',
        'project_material_id',
        'requested_by',
        'material',
        'quantity',
        'unit',
        'project',
        'supplier',
        'requested_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'requested_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function projectMaterial()
    {
        return $this->belongsTo(ProjectMaterial::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }
}
