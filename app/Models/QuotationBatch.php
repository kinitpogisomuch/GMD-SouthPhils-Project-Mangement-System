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

    // Contingency: an allowance for the unforeseen, as a % of the direct cost (materials + labor + other costs)
    const CONTINGENCY_MAX = 20;

    // What a new costing starts with (the admin can change them before the first save)
    const DEFAULT_MARKUP      = 10;
    const DEFAULT_CONTINGENCY = 10;

    // Payment terms follow the size of the job: at or above this contract value it is a Big Project (3 phases),
    // below it a Small Project (2 phases)
    const BIG_PROJECT_THRESHOLD = 500000;

    // Other costs a job has when GMD assembles the tank on site — the rows a new list starts with
    const DEFAULT_OTHER_COSTS = ['Mobilization', 'Boarding / lodging', 'PPE / misc.', 'Shop drawing'];

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
        'contingency_percent',
        'onsite_assembly',
        'other_costs',
        'po_number',
        'po_files',
        'po_notes',
        'po_submitted_at',
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
        'contingency_percent'    => 'float',
        'onsite_assembly'        => \App\Casts\PostgresBoolean::class,
        'other_costs'            => 'array',
        'po_files'               => 'array',
        'po_submitted_at'        => 'datetime',
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

    /** The client sent their Purchase Order (required before the quotation can become a project) */
    public function hasPurchaseOrder(): bool
    {
        return $this->po_submitted_at !== null;
    }

    /** Number of identical tanks the builder's per-tank costs are multiplied by (never below 1). */
    public function tankQuantity(): int
    {
        return max(1, (int) ($this->tank_quantity ?? 1));
    }

    /**
     * The markup amount (₱): the markup % of the Project Budget — except when the costing is exactly the standard
     * costing of a standard size, where the contract value is GMD's estimated price exactly (× the tanks), and the
     * markup takes up the few pesos of rounding (other costs, if any, are added with the usual contingency and markup).
     */
    public function markupAmount(): float
    {
        $budget = $this->estimatedBudget();
        $pct    = (float) ($this->markup_percent ?? 0);

        if ($std = \App\Support\StandardCosting::matchedBy($this)) {
            $target = $std['estimated_price'] * $budget['quantity']
                    + $budget['others'] * (1 + $budget['contingency_percent'] / 100) * (1 + $pct / 100);

            return round($target - $budget['total'], 2);
        }

        return round($budget['total'] * $pct / 100, 2);
    }

    /** The job's other costs (on-site assembly) that count: [['label', 'amount'], …] — none unless assembled on site */
    public function otherCostItems(): array
    {
        if (!$this->onsite_assembly) {
            return [];
        }

        return array_values(array_filter(array_map(fn ($row) => [
            'label'  => trim((string) ($row['label'] ?? '')),
            'amount' => round((float) ($row['amount'] ?? 0), 2),
        ], (array) ($this->other_costs ?? [])), fn ($row) => $row['label'] !== '' || $row['amount'] > 0));
    }

    /**
     * The Project Budget, built up from the costing — same base shape as Project::estimatedBudget():
     *   materials (with their per-material factor) + labor          ← the builder's rows are for ONE tank,
     *                                                                  multiplied by the tank quantity here, once
     *   + other costs (on-site assembly)                             ← for the whole job, not per tank
     *   = direct cost
     *   + contingency (% of the direct cost)
     *   = total (the Project Budget; markup is a % of this)
     * The rows themselves are never multiplied, so saving or reopening a quotation can't apply the quantity twice.
     */
    public function estimatedBudget(): array
    {
        $unitMaterials = round($this->activeMaterials()->get()->sum(function ($material) {
            $factor = $material->factor ?? ProjectMaterial::DEFAULT_FACTOR;
            return (float) $material->total_cost * (1 + $factor / 100);
        }), 2);

        $unitLabor = round((float) $this->activeLabor()->sum('total_cost'), 2);
        $quantity  = $this->tankQuantity();

        $materials      = round($unitMaterials * $quantity, 2);
        $labor          = round($unitLabor * $quantity, 2);
        $others         = round(array_sum(array_column($this->otherCostItems(), 'amount')), 2);
        $direct         = round($materials + $labor + $others, 2);
        $contingencyPct = (float) ($this->contingency_percent ?? 0);
        $contingency    = round($direct * $contingencyPct / 100, 2);

        return [
            'unit_materials'      => $unitMaterials,
            'unit_labor'          => $unitLabor,
            'unit_total'          => round($unitMaterials + $unitLabor, 2),
            'quantity'            => $quantity,
            'materials'           => $materials,
            'labor'               => $labor,
            'others'              => $others,
            'direct'              => $direct,
            'contingency_percent' => $contingencyPct,
            'contingency'         => $contingency,
            'total'               => round($direct + $contingency, 2),
        ];
    }
}
