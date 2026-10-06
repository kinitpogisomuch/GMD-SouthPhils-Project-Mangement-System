<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiQuarterTarget extends Model
{
    protected $fillable = [
        'year',
        'quarter',
        'profit_target',
        'profit_target_m1',
        'profit_target_m2',
        'profit_target_m3',
        'on_time_target',
        'on_time_target_m1',
        'on_time_target_m2',
        'on_time_target_m3',
        'budget_adherence_target',
    ];

    protected $casts = [
        'year'                     => 'integer',
        'quarter'                  => 'integer',
        'profit_target'            => 'float',
        'profit_target_m1'         => 'float',
        'profit_target_m2'         => 'float',
        'profit_target_m3'         => 'float',
        // On-time delivery target RATE in percent (e.g. 90.00), one per quarter — the
        // monthly columns hold a copy of it. Null = no on-time target set.
        'on_time_target'           => 'float',
        'on_time_target_m1'        => 'float',
        'on_time_target_m2'        => 'float',
        'on_time_target_m3'        => 'float',
        'budget_adherence_target'  => 'float',
    ];

    public static function forPeriod(int $year, int $quarter): ?self
    {
        return static::where('year', $year)->where('quarter', $quarter)->first();
    }
}
