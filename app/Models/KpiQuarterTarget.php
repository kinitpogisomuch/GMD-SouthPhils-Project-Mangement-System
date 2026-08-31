<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiQuarterTarget extends Model
{
    protected $fillable = [
        'year',
        'quarter',
        'profit_target',
        'on_time_target',
        'budget_adherence_target',
    ];

    protected $casts = [
        'year'                     => 'integer',
        'quarter'                  => 'integer',
        'profit_target'            => 'float',
        'on_time_target'           => 'integer',
        'budget_adherence_target'  => 'float',
    ];

    public static function forPeriod(int $year, int $quarter): ?self
    {
        return static::where('year', $year)->where('quarter', $quarter)->first();
    }
}
