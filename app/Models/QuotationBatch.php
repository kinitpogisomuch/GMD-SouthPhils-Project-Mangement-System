<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationBatch extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    // Markup (profit) is a percentage of the Project Budget, kept within this range.
    const MARKUP_MIN = 5;
    const MARKUP_MAX = 10;

    protected $fillable = [
        'id',
        'client_id',
        'tank_type',
        'capacity',
        'tank_quantity',
        'estimated_working_days',
        'project_budget',
        'markup',
        'markup_percent',
        'contract_value',
        'quotation_files',
        'payment_term_type',
    ];

    protected $casts = [
        'estimated_working_days' => 'integer',
        'tank_quantity'          => 'integer',
        'project_budget'         => 'float',
        'markup'                 => 'float',
        'markup_percent'         => 'float',
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

    /** Number of identical tanks the builder's per-tank costs are multiplied by (never below 1). */
    public function tankQuantity(): int
    {
        return max(1, (int) ($this->tank_quantity ?? 1));
    }

    /**
     * Same shape as Project::estimatedBudget() — materials with their per-material factor + raw labor —
     * for the whole quotation. The builder's rows describe ONE tank, so the unit figures are multiplied
     * by the tank quantity here, once; the rows themselves are never multiplied, so saving or reopening
     * a quotation can't apply the quantity twice.
     */
    public function estimatedBudget(): array
    {
        $unitMaterials = round($this->activeMaterials()->get()->sum(function ($material) {
            $factor = $material->factor ?? ProjectMaterial::DEFAULT_FACTOR;
            return (float) $material->total_cost * (1 + $factor / 100);
        }), 2);

        $unitLabor = round((float) $this->activeLabor()->sum('total_cost'), 2);
        $quantity  = $this->tankQuantity();

        $materials = round($unitMaterials * $quantity, 2);
        $labor     = round($unitLabor * $quantity, 2);

        return [
            'unit_materials' => $unitMaterials,
            'unit_labor'     => $unitLabor,
            'unit_total'     => round($unitMaterials + $unitLabor, 2),
            'quantity'       => $quantity,
            'materials'      => $materials,
            'labor'          => $labor,
            'total'          => round($materials + $labor, 2),
        ];
    }
}
