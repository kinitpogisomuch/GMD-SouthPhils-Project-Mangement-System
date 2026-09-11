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
        'on_time_target'           => 'integer',
        'on_time_target_m1'        => 'integer',
        'on_time_target_m2'        => 'integer',
        'on_time_target_m3'        => 'integer',
        'budget_adherence_target'  => 'float',
    ];

    public static function forPeriod(int $year, int $quarter): ?self
    {
        return static::where('year', $year)->where('quarter', $quarter)->first();
    }
}
