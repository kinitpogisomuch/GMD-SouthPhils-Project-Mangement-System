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
        'reference_no',
        'specifications',
        'document',
        'current_version',
        'project_id',
        'converted_at',
    ];

    protected $casts = [
        'estimated_working_days' => 'integer',
        'tank_quantity'          => 'integer',
        'project_budget'         => 'float',
        'markup'                 => 'float',
        'markup_percent'         => 'float',
        'contract_value'         => 'float',
        'quotation_files'        => 'array',
        'specifications'         => 'array',
        'document'               => 'array',
        'current_version'        => 'integer',
        'converted_at'           => 'datetime',
    ];

    protected static function booted(): void
    {
        // Every quotation gets its reference number the moment it exists
        static::creating(function (QuotationBatch $batch) {
            if (empty($batch->reference_no)) {
                $batch->reference_no = self::nextReferenceNumber();
            }
        });
    }

    /**
     * GMDSP-<year>-<sequence>, e.g. GMDSP-2026-061 — the same format as GMD's printed quotations. The
     * sequence restarts every year. A transaction-scoped advisory lock serialises concurrent submissions,
     * and the unique index on reference_no is the final guarantee.
     */
    public static function nextReferenceNumber(): string
    {
        $year   = now()->format('Y');
        $prefix = "GMDSP-{$year}-";

        return \DB::transaction(function () use ($prefix) {
            \DB::select('SELECT pg_advisory_xact_lock(?)', [crc32('quotation_reference_no')]);

            $last = self::where('reference_no', 'like', $prefix . '%')
                ->pluck('reference_no')
                ->map(fn ($ref) => (int) substr($ref, strlen($prefix)))
                ->max() ?? 0;

            return $prefix . str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function versions()
    {
        return $this->hasMany(QuotationVersion::class, 'quotation_batch_id')->orderByDesc('version');
    }

    /** The quotation most recently sent to the client, if any */
    public function latestVersion(): ?QuotationVersion
    {
        return $this->versions()->first();
    }

    /** "GMDSP-2026-061" for the first quotation, "GMDSP-2026-061-R1" for its first revision, … */
    public function quotationNumberFor(int $version): string
    {
        $base = $this->reference_no ?: ('Q-' . substr($this->id, 0, 8));

        return $version <= 1 ? $base : $base . '-R' . ($version - 1);
    }

    /** One document setting (project title, validity days, notes …), or its default */
    public function documentSetting(string $key, $default = null)
    {
        return data_get($this->document ?? [], $key, $default);
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
