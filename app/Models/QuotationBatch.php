<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationBatch extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'client_id',
        'estimated_working_days',
        'project_budget',
        'markup',
        'contract_value',
        'quotation_files',
        'payment_term_type',
    ];

    protected $casts = [
        'estimated_working_days' => 'integer',
        'project_budget'         => 'float',
        'markup'                 => 'float',
        'contract_value'         => 'float',
        'quotation_files'        => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function quotationRequests()
    {
        return $this->hasMany(QuotationRequest::class, 'batch_id', 'id');
    }

    public function materials()
    {
        return $this->hasMany(ProjectMaterial::class, 'quotation_batch_id');
    }

    public function activeMaterials()
    {
        return $this->materials()->where('status', 'active');
    }

    public function labor()
    {
        return $this->hasMany(ProjectLabor::class, 'quotation_batch_id');
    }

    public function activeLabor()
    {
        return $this->labor()->where('status', 'active');
    }

    /** Same shape as Project::estimatedBudget() — materials with their per-material factor + raw labor. */
    public function estimatedBudget(): array
    {
        $materials = round($this->activeMaterials()->get()->sum(function ($material) {
            $factor = $material->factor ?? 7;
            return (float) $material->total_cost * (1 + $factor / 100);
        }), 2);

        $labor = round((float) $this->activeLabor()->sum('total_cost'), 2);

        return [
            'materials' => $materials,
            'labor'     => $labor,
            'total'     => round($materials + $labor, 2),
        ];
    }
}
