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

    /**
     * The tank type as it appears in TANK_TYPES, so older free-text spellings ("Fuel Day Tank",
     * "Aboveground Fuel Storage Tank") are grouped with the current option ("Fuel Day Tanks").
     * A type that isn't in the list comes back unchanged.
     */
    public static function canonicalType(?string $type): ?string
    {
        $type = trim((string) $type);
        if ($type === '') {
            return null;
        }

        $key = fn (string $t) => rtrim(mb_strtolower(preg_replace('/\s+/', ' ', trim($t))), 's');
        foreach (self::TANK_TYPES as $known) {
            if ($key($known) === $key($type)) {
                return $known;
            }
        }

        return $type;
    }

    /** "18,000", "18000 L" and "18,000 liters" all read "18,000 liters"; anything else is kept as typed. */
    public static function normalizeCapacity(?string $capacity): ?string
    {
        $capacity = trim((string) $capacity);
        if ($capacity === '') {
            return null;
        }

        if (preg_match('/^([\d,]+(?:\.\d+)?)\s*(l|liters?|litres?)?$/i', $capacity, $m)) {
            $liters = (float) str_replace(',', '', $m[1]);
            if ($liters > 0) {
                return number_format($liters, fmod($liters, 1) ? 2 : 0) . ' liters';
            }
        }

        return $capacity;
    }

    public function getSummaryAttribute(): string
    {
        $qty = $this->quantity > 1 ? "({$this->quantity}x) " : '';
        return $qty . $this->tank_type . ($this->capacity ? ' — ' . $this->capacity : '');
    }
}
