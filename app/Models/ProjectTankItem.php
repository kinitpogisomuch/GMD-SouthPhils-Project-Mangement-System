<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTankItem extends Model
{
    protected $fillable = [
        'project_id',
        'tank_type',
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
