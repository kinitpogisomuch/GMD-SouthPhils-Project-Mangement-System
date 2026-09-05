<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTankItem extends Model
{
    const TANK_TYPES = [
        'Underground Fuel Storage Tanks',
        'Cooking Oil Storage Tank',
        'Chemical Tank',
        'Polymer Tank',
        'Aboveground Water Storage Tanks',
        'Tetrapod',
        'Fuel Pipe Line Installation',
        'Re-piping of Fuel Pipe Line',
        'Aboveground Fuel Storage Tanks',
        'Fuel Day Tanks',
        'Others',
    ];

    protected $fillable = [
        'project_id',
        'tank_type',
        'shape',
        'capacity',
        'dimensions',
        'quantity',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'sort_order' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function getSummaryAttribute(): string
    {
        $qty = $this->quantity > 1 ? "({$this->quantity}x) " : '';
        return $qty . $this->tank_type . ($this->capacity ? ' — ' . $this->capacity : '');
    }
}
