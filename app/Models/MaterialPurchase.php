<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialPurchase extends Model
{
    protected $fillable = [
        'project_id',
        'project_material_id',
        'material_name',
        'unit',
        'qty_bought',
        'actual_unit_cost',
        'total_paid',
        'supplier',
        'purchase_date',
        'notes',
    ];

    protected $casts = [
        'qty_bought'       => 'float',
        'actual_unit_cost' => 'float',
        'total_paid'       => 'float',
        'purchase_date'    => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function projectMaterial()
    {
        return $this->belongsTo(ProjectMaterial::class);
    }
}
