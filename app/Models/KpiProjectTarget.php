<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiProjectTarget extends Model
{
    protected $fillable = [
        'min_profit_per_project',
        'max_duration_days',
        'budget_adherence_target',
    ];

    protected $casts = [
        'min_profit_per_project'  => 'float',
        'max_duration_days'       => 'integer',
        'budget_adherence_target' => 'float',
    ];

    public static function instance(): self
    {
        return static::firstOrCreate([], []);
    }
}
