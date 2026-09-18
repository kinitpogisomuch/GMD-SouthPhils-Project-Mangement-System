<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplate extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'project_name',
        'tank_items',
    ];

    protected $casts = [
        'tank_items' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Save (or refresh) this project's template from its own tank/project specs —
     * templates exist purely to reuse those on a new project; materials, labor,
     * markup, and payment terms all belong to that specific project's own Build
     * Quotation, and don't carry over.
     */
    public static function snapshotFromCompletedProject(Project $project): self
    {
        $tankItems = $project->tankItems()->orderBy('sort_order')->get()
            ->map(fn ($t) => [
                'tank_type'  => $t->tank_type,
                'shape'      => $t->shape,
                'capacity'   => $t->capacity,
                'dimensions' => $t->dimensions,
                'quantity'   => $t->quantity,
                'notes'      => $t->notes,
            ])->values()->toArray();

        return static::updateOrCreate(
            ['project_id' => $project->id],
            [
                'name'         => $project->name,
                'project_name' => $project->name,
                'tank_items'   => $tankItems,
            ]
        );
    }
}
